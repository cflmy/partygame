<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$type = $_GET['type'] ?? '';
$level = $_GET['level'] ?? 'normal';
$exclude = $_GET['exclude'] ?? null;

if (!in_array($type, ['truth', 'dare'], true)) {
    tod_json_response(['error' => 'invalid type'], 400);
    exit;
}

$question = tod_pick_question($type, $level, $exclude);

if ($question === null) {
    tod_json_response(['error' => 'no question available'], 404);
    exit;
}

tod_json_response([
    'type'  => $type,
    'level' => tod_normalize_level($level),
    'text'  => $question['text'] ?? '',
]);
