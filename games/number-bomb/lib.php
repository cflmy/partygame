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
