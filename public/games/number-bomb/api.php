<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';
require_once dirname(__DIR__, 2) . '/includes/room.php';

$action = $_GET['action'] ?? '';

if ($action !== '' && strpos($action, 'room_') === 0) {
    nb_room_handle_action($action, $_GET);
    exit;
}

if ($action === 'start') {
    $min = (int) ($_GET['min'] ?? 1);
    $max = (int) ($_GET['max'] ?? 100);
    nb_json_response(nb_start_game($min, $max));
    exit;
}

if ($action === 'guess') {
    $guess = (int) ($_GET['guess'] ?? 0);
    nb_json_response(nb_guess($guess));
    exit;
}

nb_json_response(['error' => 'invalid action'], 400);
