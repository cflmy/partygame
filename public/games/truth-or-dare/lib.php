<?php

declare(strict_types=1);

function tod_data_dir(): string
{
    return __DIR__ . '/data';
}

function tod_load_questions(string $type): array
{
    $file = tod_data_dir() . '/' . ($type === 'dare' ? 'dare' : 'truth') . '.json';
    if (!is_readable($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function tod_normalize_level(string $level): string
{
    if (in_array($level, ['easy', 'normal', 'bold'], true)) {
        return $level;
    }

    return 'normal';
}

function tod_pick_question(string $type, string $level, ?string $exclude = null): ?array
{
    $level = tod_normalize_level($level);
    $items = array_values(array_filter(
        tod_load_questions($type),
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

function tod_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function tod_room_public_state(array $room, ?array $me): array
{
    return array_merge(pg_room_base_state($room), [
        'current_player' => $room['current_player'] ?? '',
        'current_type'   => $room['current_type'] ?? '',
        'current_text'   => $room['current_text'] ?? '',
        'me'             => pg_room_me_payload($me),
    ]);
}

function tod_room_host_action(string $gameSlug, array $query, callable $mutator): void
{
    $roomId = (string) ($query['room'] ?? '');
    $token = (string) ($query['token'] ?? '');

    $result = pg_room_update($gameSlug, $roomId, static function (array &$room) use ($token, $mutator): array {
        $me = pg_room_find_player($room, $token);
        if ($me === null) {
            return ['error' => 'invalid token'];
        }
        if (empty($me['is_host'])) {
            return ['error' => 'host only'];
        }
        return $mutator($room);
    });

    if (isset($result['error'])) {
        tod_json_response($result, 400);
        return;
    }

    $room = pg_room_read($gameSlug, $roomId);
    $me = pg_room_find_player($room, $token);
    tod_json_response(['ok' => true, 'state' => tod_room_public_state($room, $me)]);
}
