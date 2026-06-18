<?php

declare(strict_types=1);

function uc_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function uc_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function uc_data_dir(): string
{
    return __DIR__ . '/data';
}

function uc_load_word_pairs(): array
{
    $file = uc_data_dir() . '/word_pairs.json';
    if (!is_readable($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function uc_pick_word_pair(): ?array
{
    $pairs = uc_load_word_pairs();
    if ($pairs === []) {
        return null;
    }

    return $pairs[random_int(0, count($pairs) - 1)];
}

function uc_get_game(): ?array
{
    uc_start_session();
    $game = $_SESSION['undercover_game'] ?? null;

    return is_array($game) ? $game : null;
}

function uc_set_game(array $game): void
{
    uc_start_session();
    $_SESSION['undercover_game'] = $game;
}

function uc_alive_players(array $game): array
{
    return array_values(array_filter(
        $game['players'],
        static function (array $player): bool {
            return !empty($player['alive']);
        }
    ));
}

function uc_count_roles(array $players, string $role): int
{
    $count = 0;
    foreach ($players as $player) {
        if (!empty($player['alive']) && ($player['role'] ?? '') === $role) {
            $count++;
        }
    }

    return $count;
}

function uc_public_players(array $players, bool $revealAll = false): array
{
    $public = [];
    foreach ($players as $index => $player) {
        $item = [
            'index'  => $index,
            'name'   => $player['name'] ?? '',
            'alive'  => !empty($player['alive']),
            'role'   => null,
            'word'   => null,
        ];

        if (!$item['alive'] || $revealAll) {
            $item['role'] = $player['role'] ?? null;
            $item['word'] = $player['word'] ?? null;
        }

        $public[] = $item;
    }

    return $public;
}

function uc_check_winner(array $game): ?string
{
    if (($game['phase'] ?? '') === 'lobby') {
        return null;
    }

    $players = $game['players'];
    $hasRole = false;
    foreach ($players as $player) {
        $role = $player['role'] ?? null;
        if ($role === 'undercover' || $role === 'civilian') {
            $hasRole = true;
            break;
        }
    }
    if (!$hasRole) {
        return null;
    }

    $undercover = uc_count_roles($players, 'undercover');
    $civilian = uc_count_roles($players, 'civilian');

    if ($undercover === 0) {
        return 'civilian';
    }

    if ($undercover >= $civilian) {
        return 'undercover';
    }

    return null;
}

function uc_start_game(array $names, int $undercoverCount): array
{
    $names = array_values(array_filter(array_map('trim', $names)));
    $count = count($names);

    if ($count < 4) {
        return ['error' => 'need at least 4 players'];
    }

    if ($count > 12) {
        return ['error' => 'too many players'];
    }

    $maxUndercover = $count >= 8 ? 2 : 1;
    $undercoverCount = max(1, min($maxUndercover, $undercoverCount));

    $pair = uc_pick_word_pair();
    if ($pair === null) {
        return ['error' => 'no word pairs'];
    }

    $civilianWord = $pair['civilian'] ?? '';
    $undercoverWord = $pair['undercover'] ?? '';

    $indexes = range(0, $count - 1);
    shuffle($indexes);
    $undercoverIndexes = array_slice($indexes, 0, $undercoverCount);

    $players = [];
    foreach ($names as $i => $name) {
        $isUndercover = in_array($i, $undercoverIndexes, true);
        $players[] = [
            'name'  => $name,
            'role'  => $isUndercover ? 'undercover' : 'civilian',
            'word'  => $isUndercover ? $undercoverWord : $civilianWord,
            'alive' => true,
        ];
    }

    $game = [
        'pair'        => ['civilian' => $civilianWord, 'undercover' => $undercoverWord],
        'players'     => $players,
        'phase'       => 'reveal',
        'reveal_idx'  => 0,
        'round'       => 1,
        'last_vote'   => null,
    ];

    uc_set_game($game);

    return [
        'ok'              => true,
        'player_count'    => $count,
        'undercover_count'=> $undercoverCount,
        'phase'           => 'reveal',
    ];
}

function uc_public_state(array $game): array
{
    $winner = uc_check_winner($game);
    if ($winner !== null) {
        $game['phase'] = 'ended';
    }

    return [
        'phase'           => $game['phase'],
        'round'           => $game['round'] ?? 1,
        'reveal_idx'      => $game['reveal_idx'] ?? 0,
        'player_count'    => count($game['players']),
        'alive_count'     => count(uc_alive_players($game)),
        'undercover_count'=> uc_count_roles($game['players'], 'undercover'),
        'civilian_count'  => uc_count_roles($game['players'], 'civilian'),
        'players'         => uc_public_players($game['players'], $game['phase'] === 'ended'),
        'last_vote'       => $game['last_vote'] ?? null,
        'winner'          => $winner,
        'pair'            => $game['phase'] === 'ended' ? $game['pair'] : null,
    ];
}

function uc_reveal_current(array $game): array
{
    if (($game['phase'] ?? '') !== 'reveal') {
        return ['error' => 'not in reveal phase'];
    }

    $idx = (int) ($game['reveal_idx'] ?? 0);
    $players = $game['players'];

    if ($idx >= count($players)) {
        return ['error' => 'all revealed'];
    }

    $player = $players[$idx];

    return [
        'index'  => $idx,
        'total'  => count($players),
        'name'   => $player['name'],
        'word'   => $player['word'],
    ];
}

function uc_confirm_reveal(): array
{
    $game = uc_get_game();
    if ($game === null) {
        return ['error' => 'game not started'];
    }

    if (($game['phase'] ?? '') !== 'reveal') {
        return ['error' => 'not in reveal phase'];
    }

    $game['reveal_idx'] = ((int) ($game['reveal_idx'] ?? 0)) + 1;

    if ($game['reveal_idx'] >= count($game['players'])) {
        $game['phase'] = 'describe';
        $game['reveal_idx'] = count($game['players']);
    }

    uc_set_game($game);

    return uc_public_state($game);
}

function uc_begin_vote(): array
{
    $game = uc_get_game();
    if ($game === null) {
        return ['error' => 'game not started'];
    }

    if (($game['phase'] ?? '') !== 'describe') {
        return ['error' => 'not in describe phase'];
    }

    $game['phase'] = 'vote';
    uc_set_game($game);

    return uc_public_state($game);
}

function uc_submit_vote(int $index): array
{
    $game = uc_get_game();
    if ($game === null) {
        return ['error' => 'game not started'];
    }

    if (($game['phase'] ?? '') !== 'vote') {
        return ['error' => 'not in vote phase'];
    }

    if (!isset($game['players'][$index])) {
        return ['error' => 'invalid player'];
    }

    if (empty($game['players'][$index]['alive'])) {
        return ['error' => 'player already out'];
    }

    $target = $game['players'][$index];
    $game['players'][$index]['alive'] = false;
    $game['last_vote'] = [
        'index' => $index,
        'name'  => $target['name'],
        'role'  => $target['role'],
        'word'  => $target['word'],
    ];

    $winner = uc_check_winner($game);
    if ($winner !== null) {
        $game['phase'] = 'ended';
    } else {
        $game['phase'] = 'describe';
        $game['round'] = ((int) ($game['round'] ?? 1)) + 1;
    }

    uc_set_game($game);

    $state = uc_public_state($game);
    $state['eliminated'] = $game['last_vote'];

    return $state;
}

function uc_get_state(): array
{
    $game = uc_get_game();
    if ($game === null) {
        return ['error' => 'game not started'];
    }

    return uc_public_state($game);
}

// --- Room (multi-device) mode ---

function uc_room_dir(): string
{
    return uc_data_dir() . '/rooms';
}

function uc_room_ensure_dir(): void
{
    $dir = uc_room_dir();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function uc_room_file(string $roomId): string
{
    $safe = preg_replace('/[^0-9]/', '', $roomId);

    return uc_room_dir() . '/' . $safe . '.json';
}

function uc_token(): string
{
    return bin2hex(random_bytes(16));
}

function uc_generate_room_id(): string
{
    uc_room_ensure_dir();

    for ($i = 0; $i < 30; $i++) {
        $id = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!is_file(uc_room_file($id))) {
            return $id;
        }
    }

    return (string) random_int(100000, 999999);
}

function uc_room_read(string $roomId): ?array
{
    $path = uc_room_file($roomId);
    if (!is_file($path)) {
        return null;
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? $decoded : null;
}

function uc_room_write(string $roomId, array $room): bool
{
    uc_room_ensure_dir();
    $room['updated_at'] = time();

    return file_put_contents(
        uc_room_file($roomId),
        json_encode($room, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}

/**
 * @return array{room?: array, error?: string}
 */
function uc_room_update(string $roomId, callable $mutator): array
{
    $path = uc_room_file($roomId);
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

    uc_room_cleanup_if_expired($room, $roomId);

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

function uc_room_cleanup_if_expired(array &$room, string $roomId): void
{
    $updated = (int) ($room['updated_at'] ?? 0);
    if ($updated > 0 && (time() - $updated) > 7200) {
        @unlink(uc_room_file($roomId));
        $room = [];
    }
}

function uc_room_find_player(array $room, string $token): ?array
{
    foreach ($room['players'] as $index => $player) {
        if (($player['token'] ?? '') === $token) {
            return array_merge($player, ['index' => $index]);
        }
    }

    return null;
}

function uc_room_alive_players(array $room): array
{
    return array_values(array_filter(
        $room['players'],
        static function (array $player): bool {
            return !empty($player['alive']) && empty($player['is_spectator']);
        }
    ));
}

function uc_room_participant_count(array $room): int
{
    return count(array_filter(
        $room['players'],
        static function (array $player): bool {
            return empty($player['is_spectator']);
        }
    ));
}

function uc_room_find_spectator(array $room, string $token): ?array
{
    foreach ($room['spectators'] ?? [] as $index => $spectator) {
        if (($spectator['token'] ?? '') === $token) {
            return array_merge($spectator, ['index' => $index]);
        }
    }

    return null;
}

function uc_room_find_member(array $room, string $token): ?array
{
    $player = uc_room_find_player($room, $token);
    if ($player !== null) {
        $player['is_spectator'] = false;

        return $player;
    }

    $spectator = uc_room_find_spectator($room, $token);
    if ($spectator !== null) {
        $spectator['is_spectator'] = true;

        return $spectator;
    }

    return null;
}

function uc_room_assign_roles(array &$room): ?string
{
    $names = array_map(static function (array $player): string {
        return $player['name'] ?? '';
    }, $room['players']);
    $count = count($names);

    if ($count < 4) {
        return 'need at least 4 players';
    }

    if ($count > 12) {
        return 'too many players';
    }

    $maxUndercover = $count >= 8 ? 2 : 1;
    $undercoverCount = max(1, min($maxUndercover, (int) ($room['undercover_count'] ?? 1)));
    $room['undercover_count'] = $undercoverCount;

    $pair = uc_pick_word_pair();
    if ($pair === null) {
        return 'no word pairs';
    }

    $civilianWord = $pair['civilian'] ?? '';
    $undercoverWord = $pair['undercover'] ?? '';

    $indexes = range(0, $count - 1);
    shuffle($indexes);
    $undercoverIndexes = array_slice($indexes, 0, $undercoverCount);

    foreach ($room['players'] as $i => &$player) {
        $isUndercover = in_array($i, $undercoverIndexes, true);
        $player['role'] = $isUndercover ? 'undercover' : 'civilian';
        $player['word'] = $isUndercover ? $undercoverWord : $civilianWord;
        $player['alive'] = true;
        $player['word_seen'] = false;
    }
    unset($player);

    $room['pair'] = ['civilian' => $civilianWord, 'undercover' => $undercoverWord];
    $room['phase'] = 'word';
    $room['round'] = 1;
    $room['votes'] = [];
    $room['last_vote'] = null;
    $room['vote_deadline'] = null;

    return null;
}

function uc_room_all_word_seen(array $room): bool
{
    foreach (uc_room_alive_players($room) as $player) {
        if (empty($player['word_seen'])) {
            return false;
        }
    }

    return uc_room_alive_players($room) !== [];
}

function uc_room_resolve_votes(array &$room): void
{
    $votes = $room['votes'] ?? [];
    $alive = uc_room_alive_players($room);
    if ($alive === [] || $votes === []) {
        return;
    }

    $counts = [];
    foreach ($votes as $targetId) {
        if (!isset($counts[$targetId])) {
            $counts[$targetId] = 0;
        }
        $counts[$targetId]++;
    }

    $max = max($counts);
    $candidates = array_keys(array_filter($counts, static function (int $c) use ($max): bool {
        return $c === $max;
    }));
    $targetId = $candidates[random_int(0, count($candidates) - 1)];

    foreach ($room['players'] as $index => &$player) {
        if (($player['id'] ?? '') !== $targetId) {
            continue;
        }
        $player['alive'] = false;
        $room['last_vote'] = [
            'id'    => $player['id'],
            'name'  => $player['name'],
            'role'  => $player['role'],
            'word'  => $player['word'],
            'index' => $index,
        ];
        break;
    }
    unset($player);

    $room['votes'] = [];
    $winner = uc_check_winner($room);
    if ($winner !== null) {
        $room['phase'] = 'ended';
    } else {
        $room['phase'] = 'describe';
        $room['round'] = ((int) ($room['round'] ?? 1)) + 1;
    }
    $room['vote_deadline'] = null;
}

function uc_room_public_players(array $players, bool $revealAll = false): array
{
    $public = [];
    foreach ($players as $index => $player) {
        $item = [
            'id'         => $player['id'] ?? '',
            'index'      => $index,
            'name'       => $player['name'] ?? '',
            'alive'      => !empty($player['alive']),
            'is_host'    => !empty($player['is_host']),
            'word_seen'  => !empty($player['word_seen']),
            'role'       => null,
            'word'       => null,
            'has_voted'  => !empty($player['has_voted']),
        ];

        if (!$item['alive'] || $revealAll) {
            $item['role'] = $player['role'] ?? null;
            $item['word'] = $player['word'] ?? null;
        }

        $public[] = $item;
    }

    return $public;
}

function uc_room_public_state(array $room, ?array $me = null): array
{
    $phase = $room['phase'] ?? 'lobby';
    $winner = null;
    if ($phase !== 'lobby') {
        $winner = uc_check_winner($room);
        if ($winner !== null) {
            $phase = 'ended';
        }
    }

    $alive = uc_room_alive_players($room);
    $voteCount = count($room['votes'] ?? []);
    $voteDeadline = (int) ($room['vote_deadline'] ?? 0);
    $voteSecondsLeft = 0;
    if ($phase === 'vote' && $voteDeadline > 0) {
        $voteSecondsLeft = max(0, $voteDeadline - time());
    }

    $spectators = [];
    foreach ($room['spectators'] ?? [] as $spectator) {
        $spectators[] = [
            'id'   => $spectator['id'] ?? '',
            'name' => $spectator['name'] ?? '',
        ];
    }

    $mePayload = null;
    if ($me !== null) {
        $mePayload = [
            'id'           => $me['id'] ?? '',
            'name'         => $me['name'] ?? '',
            'is_host'      => !empty($me['is_host']),
            'is_spectator' => !empty($me['is_spectator']),
            'alive'        => !empty($me['alive']),
            'word_seen'    => !empty($me['word_seen']),
            'has_voted'    => !empty($me['has_voted']),
        ];
    }

    return [
        'room_id'          => $room['room_id'] ?? '',
        'phase'            => $phase,
        'round'            => $room['round'] ?? 1,
        'player_count'     => uc_room_participant_count($room),
        'spectator_count'  => count($spectators),
        'alive_count'      => count($alive),
        'undercover_count' => (int) ($room['undercover_count'] ?? 1),
        'word_seen_count'  => count(array_filter($alive, static function (array $p): bool {
            return !empty($p['word_seen']);
        })),
        'vote_count'       => $voteCount,
        'vote_needed'      => count($alive),
        'vote_deadline'    => $voteDeadline > 0 ? $voteDeadline : null,
        'vote_seconds_left'=> $voteSecondsLeft,
        'players'          => uc_room_public_players($room['players'], $phase === 'ended'),
        'spectators'       => $spectators,
        'last_vote'        => $room['last_vote'] ?? null,
        'winner'           => $winner,
        'pair'             => $phase === 'ended' ? ($room['pair'] ?? null) : null,
        'me'               => $mePayload,
    ];
}

function uc_room_create(string $hostName, int $undercoverCount): array
{
    uc_room_ensure_dir();
    $hostName = trim($hostName);
    if ($hostName === '') {
        return ['error' => 'empty name'];
    }

    $roomId = uc_generate_room_id();
    $token = uc_token();
    $playerId = substr(uc_token(), 0, 12);

    $room = [
        'room_id'          => $roomId,
        'undercover_count' => max(1, min(2, $undercoverCount)),
        'phase'            => 'lobby',
        'pair'             => null,
        'players'          => [[
            'id'        => $playerId,
            'token'     => $token,
            'name'      => $hostName,
            'is_host'   => true,
            'role'      => null,
            'word'      => null,
            'alive'     => true,
            'word_seen' => false,
            'has_voted' => false,
        ]],
        'round'            => 1,
        'votes'            => [],
        'last_vote'        => null,
        'vote_deadline'    => null,
        'created_at'       => time(),
        'updated_at'       => time(),
        'spectators'       => [],
    ];

    if (!uc_room_write($roomId, $room)) {
        return ['error' => 'create failed'];
    }

    $me = uc_room_find_player($room, $token);

    return [
        'ok'       => true,
        'room_id'  => $roomId,
        'token'    => $token,
        'is_host'  => true,
        'state'    => uc_room_public_state($room, $me),
    ];
}

function uc_room_join(string $roomId, string $name, bool $asSpectator = false): array
{
    $roomId = preg_replace('/[^0-9]/', '', $roomId);
    if (strlen($roomId) !== 6) {
        return ['error' => 'invalid room'];
    }

    $name = trim($name);
    if ($name === '') {
        return ['error' => 'empty name'];
    }

    return uc_room_update($roomId, static function (array &$room) use ($name, $asSpectator): array {
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

        $token = uc_token();

        if ($asSpectator) {
            $memberId = substr(uc_token(), 0, 12);
            $room['spectators'][] = [
                'id'         => $memberId,
                'token'      => $token,
                'name'       => $name,
                'joined_at'  => time(),
            ];
            $me = uc_room_find_spectator($room, $token);

            return [
                'ok'           => true,
                'room_id'      => $room['room_id'],
                'token'        => $token,
                'is_host'      => false,
                'is_spectator' => true,
                'state'        => uc_room_public_state($room, $me),
            ];
        }

        if (uc_room_participant_count($room) >= 12) {
            return ['error' => 'room full'];
        }

        $playerId = substr(uc_token(), 0, 12);
        $room['players'][] = [
            'id'        => $playerId,
            'token'     => $token,
            'name'      => $name,
            'is_host'   => false,
            'role'      => null,
            'word'      => null,
            'alive'     => true,
            'word_seen' => false,
            'has_voted' => false,
        ];
        $me = uc_room_find_player($room, $token);

        return [
            'ok'           => true,
            'room_id'      => $room['room_id'],
            'token'        => $token,
            'is_host'      => false,
            'is_spectator' => false,
            'state'        => uc_room_public_state($room, $me),
        ];
    });
}

function uc_room_process_vote_timeout(string $roomId, array &$room): bool
{
    if (($room['phase'] ?? '') !== 'vote') {
        return false;
    }

    $deadline = (int) ($room['vote_deadline'] ?? 0);
    if ($deadline <= 0 || time() < $deadline) {
        return false;
    }

    if (count($room['votes'] ?? []) > 0) {
        uc_room_resolve_votes($room);
    } else {
        $room['phase'] = 'describe';
        $room['vote_deadline'] = null;
    }

    return true;
}

function uc_room_get_state(string $roomId, string $token): array
{
    $room = uc_room_read($roomId);
    if ($room === null || $room === []) {
        return ['error' => 'room not found'];
    }

    if (uc_room_find_kicked_token($room, $token)) {
        return ['error' => 'kicked'];
    }

    if (uc_room_process_vote_timeout($roomId, $room)) {
        uc_room_write($roomId, $room);
    }

    $me = uc_room_find_member($room, $token);
    if ($me === null) {
        return ['error' => 'invalid token'];
    }

    return uc_room_public_state($room, $me);
}

function uc_room_start(string $roomId, string $token): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (empty($me['is_host'])) {
            return ['error' => 'host only'];
        }
        if (($room['phase'] ?? '') !== 'lobby') {
            return ['error' => 'already started'];
        }

        $err = uc_room_assign_roles($room);
        if ($err !== null) {
            return ['error' => $err];
        }

        $me = uc_room_find_player($room, $token);

        return ['ok' => true, 'state' => uc_room_public_state($room, $me)];
    });
}

function uc_room_my_word(string $roomId, string $token): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }

        $phase = $room['phase'] ?? '';
        if (!in_array($phase, ['word', 'describe', 'vote'], true)) {
            return ['error' => 'not in game'];
        }
        if (empty($me['alive'])) {
            return ['error' => 'player out'];
        }

        $idx = (int) $me['index'];
        $word = $room['players'][$idx]['word'] ?? '';
        if ($phase === 'word') {
            $room['players'][$idx]['word_seen'] = true;
            if (uc_room_all_word_seen($room)) {
                $room['phase'] = 'describe';
            }
        }

        $me = uc_room_find_player($room, $token);

        return [
            'ok'    => true,
            'word'  => $word,
            'state' => uc_room_public_state($room, $me),
        ];
    });
}

function uc_room_begin_vote(string $roomId, string $token): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (empty($me['is_host'])) {
            return ['error' => 'host only'];
        }
        if (($room['phase'] ?? '') !== 'describe') {
            return ['error' => 'not in describe phase'];
        }

        $room['phase'] = 'vote';
        $room['votes'] = [];
        $room['vote_deadline'] = time() + 60;
        foreach ($room['players'] as $i => &$player) {
            if (!empty($player['alive'])) {
                $player['has_voted'] = false;
            }
        }
        unset($player);

        $me = uc_room_find_player($room, $token);

        return ['ok' => true, 'state' => uc_room_public_state($room, $me)];
    });
}

function uc_room_vote(string $roomId, string $token, string $targetId): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token, $targetId): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (($room['phase'] ?? '') !== 'vote') {
            return ['error' => 'not in vote phase'];
        }
        if (empty($me['alive'])) {
            return ['error' => 'player out'];
        }
        if (!empty($me['has_voted'])) {
            return ['error' => 'already voted'];
        }
        if (($me['id'] ?? '') === $targetId) {
            return ['error' => 'cannot vote self'];
        }

        $targetValid = false;
        foreach (uc_room_alive_players($room) as $player) {
            if (($player['id'] ?? '') === $targetId) {
                $targetValid = true;
                break;
            }
        }
        if (!$targetValid) {
            return ['error' => 'invalid target'];
        }

        $idx = (int) $me['index'];
        $room['players'][$idx]['has_voted'] = true;
        $room['votes'][$me['id']] = $targetId;

        $aliveCount = count(uc_room_alive_players($room));
        if (count($room['votes']) >= $aliveCount) {
            uc_room_resolve_votes($room);
        }

        $me = uc_room_find_player($room, $token);
        $state = uc_room_public_state($room, $me);
        if ($room['last_vote'] !== null) {
            $state['eliminated'] = $room['last_vote'];
        }

        return ['ok' => true, 'state' => $state];
    });
}

function uc_room_reset_to_lobby(array &$room): void
{
    $room['phase'] = 'lobby';
    $room['pair'] = null;
    $room['round'] = 1;
    $room['votes'] = [];
    $room['last_vote'] = null;
    $room['vote_deadline'] = null;
    foreach ($room['players'] as &$player) {
        $player['role'] = null;
        $player['word'] = null;
        $player['alive'] = true;
        $player['word_seen'] = false;
        $player['has_voted'] = false;
    }
    unset($player);
}

function uc_room_back(string $roomId, string $token): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (empty($me['is_host'])) {
            return ['error' => 'host only'];
        }
        if (($room['phase'] ?? 'lobby') === 'lobby') {
            return ['error' => 'cannot go back'];
        }

        uc_room_reset_to_lobby($room);
        $me = uc_room_find_player($room, $token);

        return ['ok' => true, 'state' => uc_room_public_state($room, $me)];
    });
}

function uc_room_update_settings(string $roomId, string $token, int $undercoverCount): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token, $undercoverCount): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (empty($me['is_host'])) {
            return ['error' => 'host only'];
        }
        if (($room['phase'] ?? '') !== 'lobby') {
            return ['error' => 'already started'];
        }

        $count = uc_room_participant_count($room);
        $maxUndercover = $count >= 8 ? 2 : 1;
        $room['undercover_count'] = max(1, min($maxUndercover, $undercoverCount));

        $me = uc_room_find_player($room, $token);

        return ['ok' => true, 'state' => uc_room_public_state($room, $me)];
    });
}

function uc_room_kick(string $roomId, string $token, string $targetId): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token, $targetId): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (empty($me['is_host'])) {
            return ['error' => 'host only'];
        }
        if (($room['phase'] ?? '') !== 'lobby') {
            return ['error' => 'cannot kick after start'];
        }

        $removed = false;
        $targetToken = '';
        $room['players'] = array_values(array_filter(
            $room['players'],
            static function (array $player) use ($targetId, &$removed, &$targetToken): bool {
                if (($player['id'] ?? '') !== $targetId) {
                    return true;
                }
                if (!empty($player['is_host'])) {
                    return true;
                }
                $removed = true;
                $targetToken = $player['token'] ?? '';

                return false;
            }
        ));

        if (!$removed) {
            return ['error' => 'player not found'];
        }

        uc_room_mark_kicked($room, $targetToken);

        $me = uc_room_find_player($room, $token);

        return ['ok' => true, 'state' => uc_room_public_state($room, $me)];
    });
}

function uc_room_mark_kicked(array &$room, string $targetToken): void
{
    if ($targetToken === '') {
        return;
    }
    if (!isset($room['kicked']) || !is_array($room['kicked'])) {
        $room['kicked'] = [];
    }
    $room['kicked'][$targetToken] = time();
    // 清理 1 小时前的记录
    foreach ($room['kicked'] as $tok => $at) {
        if ((time() - (int) $at) > 3600) {
            unset($room['kicked'][$tok]);
        }
    }
}

function uc_room_find_kicked_token(array $room, string $token): bool
{
    if ($token === '' || empty($room['kicked']) || !is_array($room['kicked'])) {
        return false;
    }

    return isset($room['kicked'][$token]);
}

function uc_room_kick_spectator(string $roomId, string $token, string $targetId): array
{
    return uc_room_update($roomId, static function (array &$room) use ($token, $targetId): array {
        $me = uc_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (empty($me['is_host'])) {
            return ['error' => 'host only'];
        }

        $before = count($room['spectators'] ?? []);
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

        if (count($room['spectators']) === $before) {
            return ['error' => 'spectator not found'];
        }

        uc_room_mark_kicked($room, $targetToken);

        $me = uc_room_find_player($room, $token);

        return ['ok' => true, 'state' => uc_room_public_state($room, $me)];
    });
}

function uc_room_dissolve(string $roomId, string $token): array
{
    $room = uc_room_read($roomId);
    if ($room === null) {
        return ['error' => 'room not found'];
    }

    $me = uc_room_find_player($room, $token);
    if ($me === null || empty($me['is_host'])) {
        return ['error' => empty($me['is_host']) ? 'host only' : 'invalid token'];
    }

    if (!@unlink(uc_room_file($roomId))) {
        return ['error' => 'dissolve failed'];
    }

    return ['ok' => true, 'dissolved' => true];
}

function uc_room_leave(string $roomId, string $token): array
{
    $room = uc_room_read($roomId);
    if ($room === null) {
        return ['error' => 'room not found'];
    }

    if (uc_room_find_kicked_token($room, $token)) {
        return ['error' => 'kicked'];
    }

    $me = uc_room_find_member($room, $token);
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
        uc_room_write($roomId, $room);

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
        @unlink(uc_room_file($roomId));

        return ['ok' => true, 'left' => true, 'empty' => true];
    }

    if ($wasHost) {
        foreach ($room['players'] as $index => &$player) {
            $player['is_host'] = ($index === 0);
        }
        unset($player);
    }

    uc_room_write($roomId, $room);

    return ['ok' => true, 'left' => true];
}
