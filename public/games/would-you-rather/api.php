<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

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
