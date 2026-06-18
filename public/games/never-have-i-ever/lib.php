<?php

declare(strict_types=1);

function nhie_data_dir(): string
{
    return __DIR__ . '/data';
}

function nhie_load_statements(): array
{
    $file = nhie_data_dir() . '/statements.json';
    if (!is_readable($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function nhie_normalize_level(string $level): string
{
    if (in_array($level, ['easy', 'normal', 'bold'], true)) {
        return $level;
    }

    return 'normal';
}

function nhie_pick_statement(string $level, ?string $exclude = null): ?array
{
    $level = nhie_normalize_level($level);
    $items = array_values(array_filter(
        nhie_load_statements(),
        static function (array $item) use ($level): bool {
            return ($item['level'] ?? 'normal') === $level;
        }
    ));

    if ($exclude !== null && $exclude !== '') {
        $filtered = array_values(array_filter(
            $items,
            static function (array $item) use ($exclude): bool {
                return ($item['text'] ?? '') !== $exclude;
            }
        ));
        if ($filtered !== []) {
            $items = $filtered;
        }
    }

    if ($items === []) {
        return null;
    }

    return $items[random_int(0, count($items) - 1)];
}

function nhie_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function nhie_room_public_state(array $room, ?array $me): array
{
    return array_merge(pg_room_base_state($room), [
        'statement' => $room['statement'] ?? '',
        'round'     => (int) ($room['round'] ?? 1),
        'me'        => pg_room_me_payload($me),
    ]);
}

function nhie_room_handle_action(string $action, array $query): void
{
    $roomId = (string) ($query['room'] ?? '');
    $token = (string) ($query['token'] ?? '');

    if ($action === 'room_create') {
        $name = trim((string) ($query['name'] ?? ''));
        $level = nhie_normalize_level((string) ($query['level'] ?? 'normal'));
        $result = pg_room_create('never-have-i-ever', $name, [
            'level' => $level, 'statement' => '', 'round' => 1,
        ]);
        if (isset($result['error'])) {
            nhie_json_response($result, 400);
            return;
        }
        $room = pg_room_read('never-have-i-ever', $result['room_id']);
        $me = pg_room_find_player($room, $result['token']);
        $result['state'] = nhie_room_public_state($room, $me);
        nhie_json_response($result);
        return;
    }

    if ($action === 'room_join') {
        $result = pg_room_join('never-have-i-ever', $roomId, trim((string) ($query['name'] ?? '')), !empty($query['spectate']));
        if (isset($result['error'])) {
            nhie_json_response($result, 400);
            return;
        }
        $room = pg_room_read('never-have-i-ever', $roomId);
        $me = pg_room_find_member($room, $result['token']);
        $result['state'] = nhie_room_public_state($room, $me);
        nhie_json_response($result);
        return;
    }

    if ($action === 'room_state') {
        $room = pg_room_read('never-have-i-ever', $roomId);
        if ($room === null) {
            nhie_json_response(['error' => 'room not found'], 400);
            return;
        }
        if (pg_room_is_kicked($room, $token)) {
            nhie_json_response(['error' => 'kicked'], 400);
            return;
        }
        $me = pg_room_find_member($room, $token);
        if ($me === null) {
            nhie_json_response(['error' => 'invalid token'], 400);
            return;
        }
        nhie_json_response(nhie_room_public_state($room, $me));
        return;
    }

    if ($action === 'room_kick') {
        $result = pg_room_kick('never-have-i-ever', $roomId, $token, (string) ($query['target'] ?? ''), (string) ($query['type'] ?? 'player'));
        if (isset($result['error'])) {
            nhie_json_response($result, 400);
            return;
        }
        $room = pg_room_read('never-have-i-ever', $roomId);
        $me = pg_room_find_player($room, $token);
        nhie_json_response(['ok' => true, 'state' => nhie_room_public_state($room, $me)]);
        return;
    }

    if ($action === 'room_leave') {
        $result = pg_room_leave('never-have-i-ever', $roomId, $token);
        if (isset($result['error'])) {
            nhie_json_response($result, 400);
            return;
        }
        nhie_json_response($result);
        return;
    }

    if ($action === 'room_dissolve') {
        $result = pg_room_dissolve('never-have-i-ever', $roomId, $token);
        if (isset($result['error'])) {
            nhie_json_response($result, 400);
            return;
        }
        nhie_json_response($result);
        return;
    }

    pg_room_update('never-have-i-ever', $roomId, static function (array &$room) use ($action, $token): array {
        $me = pg_room_find_player($room, $token);
        if ($me === null || empty($me['is_host'])) {
            return ['error' => empty($me['is_host']) ? 'host only' : 'invalid token'];
        }
        if ($action === 'room_start') {
            $room['phase'] = 'play';
            $room['round'] = 1;
            $q = nhie_pick_statement($room['level'] ?? 'normal', null);
            $room['statement'] = $q['text'] ?? '从来没有…';
            return ['ok' => true];
        }
        if ($action === 'room_next') {
            $room['round'] = ((int) ($room['round'] ?? 1)) + 1;
            $q = nhie_pick_statement($room['level'] ?? 'normal', $room['statement'] ?? null);
            $room['statement'] = $q['text'] ?? '从来没有…';
            return ['ok' => true];
        }
        return ['error' => 'invalid action'];
    });

    $room = pg_room_read('never-have-i-ever', $roomId);
    $me = pg_room_find_player($room, $token);
    nhie_json_response(['ok' => true, 'state' => nhie_room_public_state($room, $me)]);
}
