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
