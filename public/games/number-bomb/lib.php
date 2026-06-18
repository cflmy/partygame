<?php

declare(strict_types=1);

function nb_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function nb_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function nb_normalize_bounds(int $min, int $max): array
{
    $min = max(1, $min);
    $max = min(9999, max($min + 1, $max));

    return [$min, $max];
}

function nb_start_game(int $min, int $max): array
{
    nb_start_session();
    [$min, $max] = nb_normalize_bounds($min, $max);
    $bomb = random_int($min, $max);

    $_SESSION['number_bomb'] = [
        'bomb'  => $bomb,
        'low'   => $min,
        'high'  => $max,
        'turns' => 0,
    ];

    return [
        'min'  => $min,
        'max'  => $max,
        'low'  => $min,
        'high' => $max,
    ];
}

function nb_guess(int $guess): array
{
    nb_start_session();
    $state = $_SESSION['number_bomb'] ?? null;

    if (!is_array($state)) {
        return ['error' => 'game not started'];
    }

    $low = (int) $state['low'];
    $high = (int) $state['high'];
    $bomb = (int) $state['bomb'];

    if ($guess < $low || $guess > $high) {
        return [
            'error'  => 'out of range',
            'low'    => $low,
            'high'   => $high,
            'message'=> "请输入 {$low} 到 {$high} 之间的数字",
        ];
    }

    $state['turns'] = ((int) ($state['turns'] ?? 0)) + 1;
    $_SESSION['number_bomb'] = $state;

    if ($guess === $bomb) {
        unset($_SESSION['number_bomb']);

        return [
            'result' => 'boom',
            'bomb'   => $bomb,
            'guess'  => $guess,
            'turns'  => $state['turns'],
        ];
    }

    if ($guess < $bomb) {
        $state['low'] = $guess + 1;
    } else {
        $state['high'] = $guess - 1;
    }

    $_SESSION['number_bomb'] = $state;

    return [
        'result' => $guess < $bomb ? 'low' : 'high',
        'guess'  => $guess,
        'low'    => $state['low'],
        'high'   => $state['high'],
        'turns'  => $state['turns'],
        'remaining' => $state['high'] - $state['low'] + 1,
    ];
}

function nb_room_public_state(array $room, ?array $me): array
{
    $payload = [
        'min'            => (int) ($room['min'] ?? 1),
        'max'            => (int) ($room['max'] ?? 100),
        'low'            => (int) ($room['low'] ?? 1),
        'high'           => (int) ($room['high'] ?? 100),
        'player_index'   => (int) ($room['player_index'] ?? 0),
        'history'        => $room['history'] ?? [],
        'current_player' => nb_room_current_name($room),
        'bomb'           => ($room['phase'] ?? '') === 'ended' ? ($room['bomb'] ?? null) : null,
    ];

    return array_merge(pg_room_base_state($room), $payload, [
        'me' => pg_room_me_payload($me),
    ]);
}

function nb_room_current_name(array $room): string
{
    $players = $room['players'] ?? [];
    $idx = (int) ($room['player_index'] ?? 0);
    if ($players === []) {
        return '玩家';
    }

    return $players[$idx % count($players)]['name'] ?? '玩家';
}

function nb_room_handle_action(string $action, array $query): void
{
    $roomId = (string) ($query['room'] ?? '');
    $token = (string) ($query['token'] ?? '');

    if ($action === 'room_create') {
        $name = trim((string) ($query['name'] ?? ''));
        $min = (int) ($query['min'] ?? 1);
        $max = (int) ($query['max'] ?? 100);
        [$min, $max] = nb_normalize_bounds($min, $max);
        $result = pg_room_create('number-bomb', $name, [
            'min' => $min, 'max' => $max, 'low' => $min, 'high' => $max,
            'player_index' => 0, 'history' => [], 'bomb' => null,
        ]);
        if (isset($result['error'])) {
            nb_json_response($result, 400);
            return;
        }
        $room = pg_room_read('number-bomb', $result['room_id']);
        $me = pg_room_find_player($room, $result['token']);
        $result['state'] = nb_room_public_state($room, $me);
        nb_json_response($result);
        return;
    }

    if ($action === 'room_join') {
        $name = trim((string) ($query['name'] ?? ''));
        $asSpectator = !empty($query['spectate']);
        $result = pg_room_join('number-bomb', $roomId, $name, $asSpectator);
        if (isset($result['error'])) {
            nb_json_response($result, 400);
            return;
        }
        $room = pg_room_read('number-bomb', $roomId);
        $me = pg_room_find_member($room, $result['token']);
        $result['state'] = nb_room_public_state($room, $me);
        nb_json_response($result);
        return;
    }

    if ($action === 'room_state') {
        $room = pg_room_read('number-bomb', $roomId);
        if ($room === null) {
            nb_json_response(['error' => 'room not found'], 400);
            return;
        }
        if (pg_room_is_kicked($room, $token)) {
            nb_json_response(['error' => 'kicked'], 400);
            return;
        }
        $me = pg_room_find_member($room, $token);
        if ($me === null) {
            nb_json_response(['error' => 'invalid token'], 400);
            return;
        }
        nb_json_response(nb_room_public_state($room, $me));
        return;
    }

    if ($action === 'room_kick') {
        $targetId = (string) ($query['target'] ?? '');
        $type = (string) ($query['type'] ?? 'player');
        $result = pg_room_kick('number-bomb', $roomId, $token, $targetId, $type);
        if (isset($result['error'])) {
            nb_json_response($result, 400);
            return;
        }
        $room = pg_room_read('number-bomb', $roomId);
        $me = pg_room_find_player($room, $token);
        nb_json_response(['ok' => true, 'state' => nb_room_public_state($room, $me)]);
        return;
    }

    if ($action === 'room_leave') {
        $result = pg_room_leave('number-bomb', $roomId, $token);
        if (isset($result['error'])) {
            nb_json_response($result, 400);
            return;
        }
        nb_json_response($result);
        return;
    }

    if ($action === 'room_dissolve') {
        $result = pg_room_dissolve('number-bomb', $roomId, $token);
        if (isset($result['error'])) {
            nb_json_response($result, 400);
            return;
        }
        nb_json_response($result);
        return;
    }

    pg_room_update('number-bomb', $roomId, static function (array &$room) use ($action, $token, $query): array {
        $me = pg_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }

        if ($action === 'room_start') {
            if (empty($me['is_host'])) {
                return ['error' => 'host only'];
            }
            $room['phase'] = 'play';
            $room['bomb'] = random_int((int) $room['min'], (int) $room['max']);
            $room['low'] = (int) $room['min'];
            $room['high'] = (int) $room['max'];
            $room['player_index'] = 0;
            $room['history'] = [];
            return ['ok' => true];
        }

        if ($action === 'room_guess') {
            if (($room['phase'] ?? '') !== 'play') {
                return ['error' => 'not in play'];
            }
            $current = nb_room_current_name($room);
            if (($me['name'] ?? '') !== $current && empty($me['is_host'])) {
                return ['error' => 'not your turn'];
            }
            $guess = (int) ($query['guess'] ?? 0);
            $low = (int) $room['low'];
            $high = (int) $room['high'];
            if ($guess < $low || $guess > $high) {
                return ['error' => 'out of range'];
            }
            if ($guess === (int) $room['bomb']) {
                $room['phase'] = 'ended';
                $room['history'][] = "{$current} 猜 {$guess} → 踩雷！";
                return ['ok' => true];
            }
            if ($guess < (int) $room['bomb']) {
                $room['low'] = $guess + 1;
            } else {
                $room['high'] = $guess - 1;
            }
            $room['history'][] = "{$current} 猜 {$guess}";
            $room['player_index'] = ((int) $room['player_index'] + 1) % max(1, count($room['players']));
            return ['ok' => true];
        }

        if ($action === 'room_back') {
            if (empty($me['is_host'])) {
                return ['error' => 'host only'];
            }
            if (!in_array($room['phase'] ?? '', ['play', 'ended'], true)) {
                return ['error' => 'cannot go back'];
            }
            $room['phase'] = 'lobby';
            $room['bomb'] = null;
            $room['history'] = [];
            $room['player_index'] = 0;
            $room['low'] = (int) ($room['min'] ?? 1);
            $room['high'] = (int) ($room['max'] ?? 100);
            return ['ok' => true];
        }

        if ($action === 'room_set_range') {
            if (empty($me['is_host'])) {
                return ['error' => 'host only'];
            }
            if (($room['phase'] ?? '') !== 'lobby') {
                return ['error' => 'lobby only'];
            }
            [$min, $max] = nb_normalize_bounds((int) ($query['min'] ?? 1), (int) ($query['max'] ?? 100));
            $room['min'] = $min;
            $room['max'] = $max;
            $room['low'] = $min;
            $room['high'] = $max;
            return ['ok' => true];
        }

        return ['error' => 'invalid action'];
    });

    $room = pg_room_read('number-bomb', $roomId);
    $me = pg_room_find_member($room, $token);
    nb_json_response(['ok' => true, 'state' => nb_room_public_state($room, $me)]);
}
