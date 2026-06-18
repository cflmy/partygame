<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$action = $_GET['action'] ?? 'state';

if ($action === 'start') {
    $rawNames = $_GET['players'] ?? '';
    $names = array_filter(array_map('trim', explode(',', $rawNames)));
    $undercoverCount = (int) ($_GET['undercovers'] ?? 1);
    $result = uc_start_game($names, $undercoverCount);

    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }

    uc_json_response($result);
    exit;
}

if ($action === 'state') {
    $result = uc_get_state();
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'reveal') {
    $game = uc_get_game();
    if ($game === null) {
        uc_json_response(['error' => 'game not started'], 400);
        exit;
    }
    $result = uc_reveal_current($game);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'confirm_reveal') {
    $result = uc_confirm_reveal();
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'begin_vote') {
    $result = uc_begin_vote();
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'vote') {
    $index = (int) ($_GET['index'] ?? -1);
    $result = uc_submit_vote($index);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

// --- Room mode ---

if ($action === 'room_create') {
    $name = trim((string) ($_GET['name'] ?? ''));
    $undercoverCount = (int) ($_GET['undercovers'] ?? 1);
    $result = uc_room_create($name, $undercoverCount);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_join') {
    $roomId = (string) ($_GET['room'] ?? '');
    $name = trim((string) ($_GET['name'] ?? ''));
    $asSpectator = !empty($_GET['spectate']);
    $result = uc_room_join($roomId, $name, $asSpectator);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_state') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $result = uc_room_get_state($roomId, $token);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_start') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $result = uc_room_start($roomId, $token);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_word') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $result = uc_room_my_word($roomId, $token);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_begin_vote') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $result = uc_room_begin_vote($roomId, $token);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_vote') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $targetId = (string) ($_GET['target'] ?? '');
    $result = uc_room_vote($roomId, $token, $targetId);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_settings') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $undercoverCount = (int) ($_GET['undercovers'] ?? 1);
    $result = uc_room_update_settings($roomId, $token, $undercoverCount);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

if ($action === 'room_kick') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $targetId = (string) ($_GET['target'] ?? '');
    $type = (string) ($_GET['type'] ?? 'player');
    $result = $type === 'spectator'
        ? uc_room_kick_spectator($roomId, $token, $targetId)
        : uc_room_kick($roomId, $token, $targetId);
    if (isset($result['error'])) {
        uc_json_response($result, 400);
        exit;
    }
    uc_json_response($result);
    exit;
}

uc_json_response(['error' => 'invalid action'], 400);
