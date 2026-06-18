<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$action = $_GET['action'] ?? 'start';

if ($action === 'start') {
    $min = (int) ($_GET['min'] ?? 1);
    $max = (int) ($_GET['max'] ?? 100);
    nb_json_response(nb_start_game($min, $max));
    exit;
}

if ($action === 'guess') {
    $guess = (int) ($_GET['guess'] ?? 0);
    $result = nb_guess($guess);

    if (isset($result['error']) && $result['error'] === 'game not started') {
        nb_json_response($result, 400);
        exit;
    }

    nb_json_response($result);
    exit;
}

nb_json_response(['error' => 'invalid action'], 400);
