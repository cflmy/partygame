<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$level = $_GET['level'] ?? 'normal';
$exclude = $_GET['exclude'] ?? null;

$statement = nhie_pick_statement($level, $exclude);

if ($statement === null) {
    nhie_json_response(['error' => 'no statement available'], 404);
    exit;
}

nhie_json_response([
    'level' => nhie_normalize_level($level),
    'text'  => $statement['text'] ?? '',
]);
