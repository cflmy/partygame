<?php

declare(strict_types=1);

/**
 * Shared multiplayer room helpers for party games.
 */

function pg_room_data_dir(string $gameSlug): string
{
    return __DIR__ . '/../games/' . $gameSlug . '/data/rooms';
}

function pg_room_token(): string
{
    return bin2hex(random_bytes(16));
}

function pg_room_file(string $gameSlug, string $roomId): string
{
    $safe = preg_replace('/[^0-9]/', '', $roomId);

    return pg_room_data_dir($gameSlug) . '/' . $safe . '.json';
}

function pg_room_ensure_dir(string $gameSlug): void
{
    $dir = pg_room_data_dir($gameSlug);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function pg_room_generate_id(string $gameSlug): string
{
    pg_room_ensure_dir($gameSlug);

    for ($i = 0; $i < 30; $i++) {
        $id = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!is_file(pg_room_file($gameSlug, $id))) {
            return $id;
        }
    }

    return (string) random_int(100000, 999999);
}

function pg_room_read(string $gameSlug, string $roomId): ?array
{
    $path = pg_room_file($gameSlug, $roomId);
    if (!is_file($path)) {
        return null;
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? $decoded : null;
}

function pg_room_write(string $gameSlug, string $roomId, array $room): bool
{
    pg_room_ensure_dir($gameSlug);
    $room['updated_at'] = time();

    return file_put_contents(
        pg_room_file($gameSlug, $roomId),
        json_encode($room, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}

function pg_room_update(string $gameSlug, string $roomId, callable $mutator): array
{
    $path = pg_room_file($gameSlug, $roomId);
    if (!is_file($path)) {
        return ['error' => 'room not found'];
    }

    $fp = fopen($path, 'c+');
    if ($fp === false) {
        return ['error' => 'room lock failed'];
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);

        return ['error' => 'room lock failed'];
    }

    $raw = stream_get_contents($fp);
    $room = json_decode($raw !== false && $raw !== '' ? $raw : '{}', true);
    if (!is_array($room)) {
        flock($fp, LOCK_UN);
        fclose($fp);

        return ['error' => 'room corrupted'];
    }

    $result = $mutator($room);
    if (isset($result['error'])) {
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result;
    }

    $room['updated_at'] = time();
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($room, JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $result;
}

function pg_room_find_player(array $room, string $token): ?array
{
    foreach ($room['players'] as $index => $player) {
        if (($player['token'] ?? '') === $token) {
            return array_merge($player, ['index' => $index]);
        }
    }

    return null;
}

function pg_room_find_spectator(array $room, string $token): ?array
{
    foreach ($room['spectators'] ?? [] as $index => $spectator) {
        if (($spectator['token'] ?? '') === $token) {
            return array_merge($spectator, ['index' => $index]);
        }
    }

    return null;
}

function pg_room_find_member(array $room, string $token): ?array
{
    $player = pg_room_find_player($room, $token);
    if ($player !== null) {
        $player['is_spectator'] = false;

        return $player;
    }

    $spectator = pg_room_find_spectator($room, $token);
    if ($spectator !== null) {
        $spectator['is_spectator'] = true;

        return $spectator;
    }

    return null;
}

function pg_room_mark_kicked(array &$room, string $targetToken): void
{
    if ($targetToken === '') {
        return;
    }
    if (!isset($room['kicked']) || !is_array($room['kicked'])) {
        $room['kicked'] = [];
    }
    $room['kicked'][$targetToken] = time();
    foreach ($room['kicked'] as $tok => $at) {
        if ((time() - (int) $at) > 3600) {
            unset($room['kicked'][$tok]);
        }
    }
}

function pg_room_is_kicked(array $room, string $token): bool
{
    return $token !== '' && !empty($room['kicked'][$token]);
}

function pg_room_player_names(array $room): array
{
    return array_map(static function (array $player): string {
        return $player['name'] ?? '';
    }, $room['players'] ?? []);
}

function pg_room_create(string $gameSlug, string $hostName, array $settings = []): array
{
    pg_room_ensure_dir($gameSlug);
    $hostName = trim($hostName);
    if ($hostName === '') {
        return ['error' => 'empty name'];
    }

    $roomId = pg_room_generate_id($gameSlug);
    $token = pg_room_token();
    $playerId = substr(pg_room_token(), 0, 12);

    $room = array_merge([
        'room_id'     => $roomId,
        'game'        => $gameSlug,
        'phase'       => 'lobby',
        'level'       => $settings['level'] ?? 'normal',
        'players'     => [[
            'id'        => $playerId,
            'token'     => $token,
            'name'      => $hostName,
            'is_host'   => true,
        ]],
        'spectators'  => [],
        'kicked'      => [],
        'created_at'  => time(),
        'updated_at'  => time(),
    ], $settings);

    if (!pg_room_write($gameSlug, $roomId, $room)) {
        return ['error' => 'create failed'];
    }

    $me = pg_room_find_player($room, $token);

    return [
        'ok'      => true,
        'room_id' => $roomId,
        'token'   => $token,
        'is_host' => true,
        'state'   => null,
        'me'      => $me,
    ];
}

function pg_room_join(string $gameSlug, string $roomId, string $name, bool $asSpectator = false): array
{
    $roomId = preg_replace('/[^0-9]/', '', $roomId);
    if (strlen($roomId) !== 6) {
        return ['error' => 'invalid room'];
    }

    $name = trim($name);
    if ($name === '') {
        return ['error' => 'empty name'];
    }

    return pg_room_update($gameSlug, $roomId, static function (array &$room) use ($name, $asSpectator): array {
        if (!$asSpectator && ($room['phase'] ?? '') !== 'lobby') {
            return ['error' => 'game already started'];
        }

        foreach ($room['players'] as $player) {
            if (($player['name'] ?? '') === $name) {
                return ['error' => 'name taken'];
            }
        }
        foreach ($room['spectators'] ?? [] as $spectator) {
            if (($spectator['name'] ?? '') === $name) {
                return ['error' => 'name taken'];
            }
        }

        $token = pg_room_token();

        if ($asSpectator) {
            $memberId = substr(pg_room_token(), 0, 12);
            $room['spectators'][] = [
                'id'    => $memberId,
                'token' => $token,
                'name'  => $name,
            ];

            return [
                'ok'           => true,
                'room_id'      => $room['room_id'],
                'token'        => $token,
                'is_host'      => false,
                'is_spectator' => true,
            ];
        }

        if (count($room['players']) >= 12) {
            return ['error' => 'room full'];
        }

        $playerId = substr(pg_room_token(), 0, 12);
        $room['players'][] = [
            'id'      => $playerId,
            'token'   => $token,
            'name'    => $name,
            'is_host' => false,
        ];

        return [
            'ok'           => true,
            'room_id'      => $room['room_id'],
            'token'        => $token,
            'is_host'      => false,
            'is_spectator' => false,
        ];
    });
}

function pg_room_dissolve(string $gameSlug, string $roomId, string $token): array
{
    $room = pg_room_read($gameSlug, $roomId);
    if ($room === null) {
        return ['error' => 'room not found'];
    }

    $me = pg_room_find_player($room, $token);
    if ($me === null || empty($me['is_host'])) {
        return ['error' => empty($me['is_host']) ? 'host only' : 'invalid token'];
    }

    if (!@unlink(pg_room_file($gameSlug, $roomId))) {
        return ['error' => 'dissolve failed'];
    }

    return ['ok' => true, 'dissolved' => true];
}

function pg_room_leave(string $gameSlug, string $roomId, string $token): array
{
    $room = pg_room_read($gameSlug, $roomId);
    if ($room === null) {
        return ['error' => 'room not found'];
    }

    if (pg_room_is_kicked($room, $token)) {
        return ['error' => 'kicked'];
    }

    $me = pg_room_find_member($room, $token);
    if ($me === null) {
        return ['error' => 'invalid token'];
    }

    if (!empty($me['is_spectator'])) {
        $room['spectators'] = array_values(array_filter(
            $room['spectators'] ?? [],
            static function (array $spectator) use ($token): bool {
                return ($spectator['token'] ?? '') !== $token;
            }
        ));
        pg_room_write($gameSlug, $roomId, $room);

        return ['ok' => true, 'left' => true];
    }

    $wasHost = !empty($me['is_host']);
    $room['players'] = array_values(array_filter(
        $room['players'],
        static function (array $player) use ($token): bool {
            return ($player['token'] ?? '') !== $token;
        }
    ));

    if ($room['players'] === []) {
        @unlink(pg_room_file($gameSlug, $roomId));

        return ['ok' => true, 'left' => true, 'empty' => true];
    }

    if ($wasHost) {
        foreach ($room['players'] as $index => &$player) {
            $player['is_host'] = ($index === 0);
        }
        unset($player);
    }

    pg_room_write($gameSlug, $roomId, $room);

    return ['ok' => true, 'left' => true];
}

function pg_room_kick(string $gameSlug, string $roomId, string $token, string $targetId, string $type = 'player'): array
{
    return pg_room_update($gameSlug, $roomId, static function (array &$room) use ($token, $targetId, $type): array {
        $me = pg_room_find_player($room, $token);
        if ($me === null || empty($me['is_host'])) {
            return ['error' => empty($me['is_host']) ? 'host only' : 'invalid token'];
        }

        if ($type === 'spectator') {
            $targetToken = '';
            $room['spectators'] = array_values(array_filter(
                $room['spectators'] ?? [],
                static function (array $spectator) use ($targetId, &$targetToken): bool {
                    if (($spectator['id'] ?? '') !== $targetId) {
                        return true;
                    }
                    $targetToken = $spectator['token'] ?? '';

                    return false;
                }
            ));
            pg_room_mark_kicked($room, $targetToken);

            return ['ok' => true];
        }

        if (($room['phase'] ?? '') !== 'lobby') {
            return ['error' => 'cannot kick after start'];
        }

        $targetToken = '';
        $room['players'] = array_values(array_filter(
            $room['players'],
            static function (array $player) use ($targetId, &$targetToken): bool {
                if (($player['id'] ?? '') !== $targetId) {
                    return true;
                }
                if (!empty($player['is_host'])) {
                    return true;
                }
                $targetToken = $player['token'] ?? '';

                return false;
            }
        ));
        pg_room_mark_kicked($room, $targetToken);

        return ['ok' => true];
    });
}

function pg_room_public_players(array $players): array
{
    $public = [];
    foreach ($players as $player) {
        $public[] = [
            'id'      => $player['id'] ?? '',
            'name'    => $player['name'] ?? '',
            'is_host' => !empty($player['is_host']),
        ];
    }

    return $public;
}

function pg_room_public_spectators(array $spectators): array
{
    $public = [];
    foreach ($spectators as $spectator) {
        $public[] = [
            'id'   => $spectator['id'] ?? '',
            'name' => $spectator['name'] ?? '',
        ];
    }

    return $public;
}

function pg_room_base_state(array $room): array
{
    return [
        'room_id'         => $room['room_id'] ?? '',
        'phase'           => $room['phase'] ?? 'lobby',
        'level'           => $room['level'] ?? 'normal',
        'player_count'    => count($room['players'] ?? []),
        'spectator_count' => count($room['spectators'] ?? []),
        'players'         => pg_room_public_players($room['players'] ?? []),
        'spectators'      => pg_room_public_spectators($room['spectators'] ?? []),
    ];
}

function pg_room_me_payload(?array $me): ?array
{
    if ($me === null) {
        return null;
    }

    return [
        'id'           => $me['id'] ?? '',
        'name'         => $me['name'] ?? '',
        'is_host'      => !empty($me['is_host']),
        'is_spectator' => !empty($me['is_spectator']),
    ];
}

function pg_room_handle_api(string $gameSlug, callable $buildState, callable $handleAction): void
{
    $action = $_GET['action'] ?? 'state';

    if ($action === 'room_create') {
        $name = trim((string) ($_GET['name'] ?? ''));
        $level = (string) ($_GET['level'] ?? 'normal');
        $result = pg_room_create($gameSlug, $name, ['level' => $level]);
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $room = pg_room_read($gameSlug, $result['room_id']);
        $me = pg_room_find_player($room, $result['token']);
        $result['state'] = $buildState($room, $me);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'room_join') {
        $roomId = (string) ($_GET['room'] ?? '');
        $name = trim((string) ($_GET['name'] ?? ''));
        $asSpectator = !empty($_GET['spectate']);
        $result = pg_room_join($gameSlug, $roomId, $name, $asSpectator);
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $room = pg_room_read($gameSlug, $result['room_id']);
        $me = pg_room_find_member($room, $result['token']);
        $result['state'] = $buildState($room, $me);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');

    if ($action === 'room_state') {
        $room = pg_room_read($gameSlug, $roomId);
        if ($room === null) {
            http_response_code(400);
            echo json_encode(['error' => 'room not found'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (pg_room_is_kicked($room, $token)) {
            http_response_code(400);
            echo json_encode(['error' => 'kicked'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $me = pg_room_find_member($room, $token);
        if ($me === null) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid token'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode($buildState($room, $me), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'room_kick') {
        $targetId = (string) ($_GET['target'] ?? '');
        $type = (string) ($_GET['type'] ?? 'player');
        $result = pg_room_kick($gameSlug, $roomId, $token, $targetId, $type);
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $room = pg_room_read($gameSlug, $roomId);
        $me = pg_room_find_player($room, $token);
        echo json_encode(['ok' => true, 'state' => $buildState($room, $me)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'room_leave') {
        $result = pg_room_leave($gameSlug, $roomId, $token);
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'room_dissolve') {
        $result = pg_room_dissolve($gameSlug, $roomId, $token);
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $handleAction($action, $gameSlug, $roomId, $token, $buildState);
}
