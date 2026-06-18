<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';
require_once dirname(__DIR__, 2) . '/includes/room.php';

$action = $_GET['action'] ?? '';

if ($action === 'room_create') {
    $name = trim((string) ($_GET['name'] ?? ''));
    $level = tod_normalize_level((string) ($_GET['level'] ?? 'normal'));
    $result = pg_room_create('truth-or-dare', $name, ['level' => $level]);
    if (isset($result['error'])) {
        tod_json_response($result, 400);
        exit;
    }
    $room = pg_room_read('truth-or-dare', $result['room_id']);
    $me = pg_room_find_player($room, $result['token']);
    $result['state'] = tod_room_public_state($room, $me);
    tod_json_response($result);
    exit;
}

if ($action === 'room_join') {
    $roomId = (string) ($_GET['room'] ?? '');
    $name = trim((string) ($_GET['name'] ?? ''));
    $asSpectator = !empty($_GET['spectate']);
    $result = pg_room_join('truth-or-dare', $roomId, $name, $asSpectator);
    if (isset($result['error'])) {
        tod_json_response($result, 400);
        exit;
    }
    $room = pg_room_read('truth-or-dare', $roomId);
    $me = pg_room_find_member($room, $result['token']);
    $result['state'] = tod_room_public_state($room, $me);
    tod_json_response($result);
    exit;
}

if ($action === 'room_state') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $room = pg_room_read('truth-or-dare', $roomId);
    if ($room === null) {
        tod_json_response(['error' => 'room not found'], 400);
        exit;
    }
    if (pg_room_is_kicked($room, $token)) {
        tod_json_response(['error' => 'kicked'], 400);
        exit;
    }
    $me = pg_room_find_member($room, $token);
    if ($me === null) {
        tod_json_response(['error' => 'invalid token'], 400);
        exit;
    }
    tod_json_response(tod_room_public_state($room, $me));
    exit;
}

if ($action === 'room_kick') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $targetId = (string) ($_GET['target'] ?? '');
    $type = (string) ($_GET['type'] ?? 'player');
    $result = pg_room_kick('truth-or-dare', $roomId, $token, $targetId, $type);
    if (isset($result['error'])) {
        tod_json_response($result, 400);
        exit;
    }
    $room = pg_room_read('truth-or-dare', $roomId);
    $me = pg_room_find_player($room, $token);
    tod_json_response(['ok' => true, 'state' => tod_room_public_state($room, $me)]);
    exit;
}

if ($action === 'room_leave') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $result = pg_room_leave('truth-or-dare', $roomId, $token);
    if (isset($result['error'])) {
        tod_json_response($result, 400);
        exit;
    }
    tod_json_response($result);
    exit;
}

if ($action === 'room_dissolve') {
    $roomId = (string) ($_GET['room'] ?? '');
    $token = (string) ($_GET['token'] ?? '');
    $result = pg_room_dissolve('truth-or-dare', $roomId, $token);
    if (isset($result['error'])) {
        tod_json_response($result, 400);
        exit;
    }
    tod_json_response($result);
    exit;
}

if ($action === 'room_start') {
    tod_room_host_action('truth-or-dare', $_GET, static function (array &$room): array {
        $room['phase'] = 'spin';
        $room['current_player'] = '';
        $room['current_type'] = '';
        $room['current_text'] = '';
        return ['ok' => true];
    });
    exit;
}

if ($action === 'room_spin') {
    tod_room_host_action('truth-or-dare', $_GET, static function (array &$room): array {
        $names = pg_room_player_names($room);
        if ($names === []) {
            return ['error' => 'no players'];
        }
        $room['current_player'] = $names[random_int(0, count($names) - 1)];
        $room['current_type'] = '';
        $room['current_text'] = '';
        $room['phase'] = 'choose';
        return ['ok' => true];
    });
    exit;
}

if ($action === 'room_choose') {
    $type = (string) ($_GET['type'] ?? '');
    if (!in_array($type, ['truth', 'dare'], true)) {
        tod_json_response(['error' => 'invalid type'], 400);
        exit;
    }
    tod_room_host_action('truth-or-dare', $_GET, static function (array &$room) use ($type): array {
        if (($room['phase'] ?? '') !== 'choose') {
            return ['error' => 'not in choose phase'];
        }
        $exclude = $room['current_text'] ?? '';
        $question = tod_pick_question($type, $room['level'] ?? 'normal', $exclude !== '' ? $exclude : null);
        if ($question === null) {
            return ['error' => 'no question'];
        }
        $room['current_type'] = $type;
        $room['current_text'] = $question['text'] ?? '';
        $room['phase'] = 'reveal';
        return ['ok' => true];
    });
    exit;
}

if ($action === 'room_swap') {
    tod_room_host_action('truth-or-dare', $_GET, static function (array &$room): array {
        if (($room['phase'] ?? '') !== 'reveal') {
            return ['error' => 'not in reveal phase'];
        }
        $type = $room['current_type'] ?? '';
        if (!in_array($type, ['truth', 'dare'], true)) {
            return ['error' => 'invalid type'];
        }
        $exclude = $room['current_text'] ?? '';
        $question = tod_pick_question($type, $room['level'] ?? 'normal', $exclude !== '' ? $exclude : null);
        if ($question === null) {
            return ['error' => 'no question'];
        }
        $room['current_text'] = $question['text'] ?? '';
        return ['ok' => true];
    });
    exit;
}

if ($action === 'room_next') {
    tod_room_host_action('truth-or-dare', $_GET, static function (array &$room): array {
        $room['phase'] = 'spin';
        $room['current_type'] = '';
        $room['current_text'] = '';
        return ['ok' => true];
    });
    exit;
}

if ($action === 'room_back') {
    tod_room_host_action('truth-or-dare', $_GET, static function (array &$room): array {
        $phase = $room['phase'] ?? '';
        if (in_array($phase, ['choose', 'spin'], true)) {
            $room['phase'] = 'lobby';
            $room['current_player'] = '';
            $room['current_type'] = '';
            $room['current_text'] = '';
            return ['ok' => true];
        }
        return ['error' => 'cannot go back'];
    });
    exit;
}

if ($action === 'room_set_level') {
    $level = tod_normalize_level((string) ($_GET['level'] ?? 'normal'));
    tod_room_host_action('truth-or-dare', $_GET, static function (array &$room) use ($level): array {
        if (($room['phase'] ?? '') !== 'lobby') {
            return ['error' => 'lobby only'];
        }
        $room['level'] = $level;
        return ['ok' => true];
    });
    exit;
}

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
