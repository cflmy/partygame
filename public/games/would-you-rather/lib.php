<?php

declare(strict_types=1);

function wyr_data_dir(): string
{
    return __DIR__ . '/data';
}

function wyr_load_questions(): array
{
    $file = wyr_data_dir() . '/questions.json';
    if (!is_readable($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function wyr_normalize_level(string $level): string
{
    if (in_array($level, ['easy', 'normal', 'bold'], true)) {
        return $level;
    }

    return 'normal';
}

function wyr_question_key(array $item): string
{
    return ($item['option_a'] ?? '') . '|' . ($item['option_b'] ?? '');
}

function wyr_pick_question(string $level, ?string $exclude = null): ?array
{
    $level = wyr_normalize_level($level);
    $items = array_values(array_filter(
        wyr_load_questions(),
        static function (array $item) use ($level): bool {
            return ($item['level'] ?? 'normal') === $level;
        }
    ));

    if ($exclude !== null && $exclude !== '') {
        $filtered = array_values(array_filter(
            $items,
            static function (array $item) use ($exclude): bool {
                return wyr_question_key($item) !== $exclude;
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

function wyr_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function wyr_room_public_state(array $room, ?array $me): array
{
    $votes = $room['votes'] ?? [];
    $countA = 0;
    $countB = 0;
    foreach ($votes as $choice) {
        if ($choice === 'a') $countA++;
        if ($choice === 'b') $countB++;
    }

    return array_merge(pg_room_base_state($room), [
        'option_a'   => $room['option_a'] ?? '',
        'option_b'   => $room['option_b'] ?? '',
        'votes_a'    => $countA,
        'votes_b'    => $countB,
        'my_vote'    => ($me && !empty($me['is_spectator'])) ? null : ($votes[$me['id'] ?? ''] ?? null),
        'me'         => pg_room_me_payload($me),
    ]);
}

function wyr_room_handle_action(string $action, array $query): void
{
    $roomId = (string) ($query['room'] ?? '');
    $token = (string) ($query['token'] ?? '');

    if ($action === 'room_create') {
        $name = trim((string) ($query['name'] ?? ''));
        $level = wyr_normalize_level((string) ($query['level'] ?? 'normal'));
        $result = pg_room_create('would-you-rather', $name, [
            'level' => $level, 'option_a' => '', 'option_b' => '', 'votes' => [],
        ]);
        if (isset($result['error'])) {
            wyr_json_response($result, 400);
            return;
        }
        $room = pg_room_read('would-you-rather', $result['room_id']);
        $me = pg_room_find_player($room, $result['token']);
        $result['state'] = wyr_room_public_state($room, $me);
        wyr_json_response($result);
        return;
    }

    if ($action === 'room_join') {
        $result = pg_room_join('would-you-rather', $roomId, trim((string) ($query['name'] ?? '')), !empty($query['spectate']));
        if (isset($result['error'])) {
            wyr_json_response($result, 400);
            return;
        }
        $room = pg_room_read('would-you-rather', $roomId);
        $me = pg_room_find_member($room, $result['token']);
        $result['state'] = wyr_room_public_state($room, $me);
        wyr_json_response($result);
        return;
    }

    if ($action === 'room_state') {
        $room = pg_room_read('would-you-rather', $roomId);
        if ($room === null) {
            wyr_json_response(['error' => 'room not found'], 400);
            return;
        }
        if (pg_room_is_kicked($room, $token)) {
            wyr_json_response(['error' => 'kicked'], 400);
            return;
        }
        $me = pg_room_find_member($room, $token);
        if ($me === null) {
            wyr_json_response(['error' => 'invalid token'], 400);
            return;
        }
        wyr_json_response(wyr_room_public_state($room, $me));
        return;
    }

    if ($action === 'room_kick') {
        $result = pg_room_kick('would-you-rather', $roomId, $token, (string) ($query['target'] ?? ''), (string) ($query['type'] ?? 'player'));
        if (isset($result['error'])) {
            wyr_json_response($result, 400);
            return;
        }
        $room = pg_room_read('would-you-rather', $roomId);
        $me = pg_room_find_player($room, $token);
        wyr_json_response(['ok' => true, 'state' => wyr_room_public_state($room, $me)]);
        return;
    }

    if ($action === 'room_leave') {
        $result = pg_room_leave('would-you-rather', $roomId, $token);
        if (isset($result['error'])) {
            wyr_json_response($result, 400);
            return;
        }
        wyr_json_response($result);
        return;
    }

    if ($action === 'room_dissolve') {
        $result = pg_room_dissolve('would-you-rather', $roomId, $token);
        if (isset($result['error'])) {
            wyr_json_response($result, 400);
            return;
        }
        wyr_json_response($result);
        return;
    }

    pg_room_update('would-you-rather', $roomId, static function (array &$room) use ($action, $token, $query): array {
        $me = pg_room_find_member($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }

        if ($action === 'room_start') {
            if (empty($me['is_host'])) {
                return ['error' => 'host only'];
            }
            $room['phase'] = 'play';
            $room['votes'] = [];
            $q = wyr_pick_question($room['level'] ?? 'normal', null);
            $room['option_a'] = $q['option_a'] ?? '';
            $room['option_b'] = $q['option_b'] ?? '';
            return ['ok' => true];
        }

        if ($action === 'room_vote') {
            if (($room['phase'] ?? '') !== 'play') {
                return ['error' => 'not in play'];
            }
            if (!empty($me['is_spectator'])) {
                return ['error' => 'spectator cannot vote'];
            }
            $choice = (string) ($query['choice'] ?? '');
            if (!in_array($choice, ['a', 'b'], true)) {
                return ['error' => 'invalid choice'];
            }
            if (!isset($room['votes'])) {
                $room['votes'] = [];
            }
            $room['votes'][$me['id']] = $choice;
            return ['ok' => true];
        }

        if ($action === 'room_next') {
            if (empty($me['is_host'])) {
                return ['error' => 'host only'];
            }
            $room['votes'] = [];
            $q = wyr_pick_question($room['level'] ?? 'normal', wyr_question_key([
                'option_a' => $room['option_a'] ?? '',
                'option_b' => $room['option_b'] ?? '',
            ]));
            $room['option_a'] = $q['option_a'] ?? '';
            $room['option_b'] = $q['option_b'] ?? '';
            return ['ok' => true];
        }

        return ['error' => 'invalid action'];
    });

    $room = pg_room_read('would-you-rather', $roomId);
    $me = pg_room_find_member($room, $token);
    wyr_json_response(['ok' => true, 'state' => wyr_room_public_state($room, $me)]);
}
