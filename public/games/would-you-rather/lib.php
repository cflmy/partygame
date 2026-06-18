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
