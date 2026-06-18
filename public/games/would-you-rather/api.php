<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';
require_once dirname(__DIR__, 2) . '/includes/room.php';

$action = $_GET['action'] ?? '';

if ($action !== '' && strpos($action, 'room_') === 0) {
    wyr_room_handle_action($action, $_GET);
    exit;
}

$level = $_GET['level'] ?? 'normal';
$exclude = $_GET['exclude'] ?? null;
$question = wyr_pick_question($level, $exclude);

if ($question === null) {
    wyr_json_response(['error' => 'no question available'], 404);
    exit;
}

wyr_json_response([
    'level'    => wyr_normalize_level($level),
    'option_a' => $question['option_a'] ?? '',
    'option_b' => $question['option_b'] ?? '',
]);
