<?php
session_start();

// --- CONFIGURATION ---
define('DATA_DIR', __DIR__ . '/db_data');
define('USER_FILE', DATA_DIR . '/users.json');

// Ensure data directory
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// --- HELPERS ---
function read_json($filepath) {
    if (!file_exists($filepath)) return [];
    $content = file_get_contents($filepath);
    return json_decode($content, true) ?? [];
}

function write_json($filepath, $data) {
    $fp = fopen($filepath, 'c+');
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function get_chat_file($user1, $user2) {
    $users = [$user1, $user2];
    sort($users);
    $id = md5($users[0] . '_' . $users[1]);
    return DATA_DIR . '/chat_' . $id . '.json';
}

function get_active_user() {
    if (isset($_SESSION['user'])) {
        return ['username' => $_SESSION['user'], 'color' => $_SESSION['color']];
    }
    if (isset($_COOKIE['chat_user'])) {
        $cookieData = json_decode($_COOKIE['chat_user'], true);
        $users = read_json(USER_FILE);
        if (isset($users[$cookieData['u']])) {
            $_SESSION['user'] = $cookieData['u'];
            $_SESSION['color'] = $users[$cookieData['u']]['avatar_color'];
            return ['username' => $_SESSION['user'], 'color' => $_SESSION['color']];
        }
    }
    return null;
}

// --- ROUTING ---
header('Content-Type: application/json');
 $input = json_decode(file_get_contents('php://input'), true);
 $action = $_GET['action'] ?? '';
 $user = get_active_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // REGISTER
    if ($action === 'register') {
        $u = trim($input['username'] ?? '');
        $p = $input['password'] ?? '';
        if (!$u || !$p) { echo json_encode(['success' => false, 'message' => 'Empty fields']); exit; }

        $users = read_json(USER_FILE);
        if (isset($users[$u])) { echo json_encode(['success' => false, 'message' => 'Username taken']); exit; }

        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        $users[$u] = ['password' => password_hash($p, PASSWORD_DEFAULT), 'avatar_color' => $color];
        write_json(USER_FILE, $users);
        
        $_SESSION['user'] = $u;
        $_SESSION['color'] = $color;
        setcookie('chat_user', json_encode(['u' => $u]), time() + (86400 * 30), "/");
        
        echo json_encode(['success' => true, 'username' => $u, 'color' => $color]);
    }
    // LOGIN
    elseif ($action === 'login') {
        $u = trim($input['username'] ?? '');
        $p = $input['password'] ?? '';
        
        $users = read_json(USER_FILE);
        if (isset($users[$u]) && password_verify($p, $users[$u]['password'])) {
            $_SESSION['user'] = $u;
            $_SESSION['color'] = $users[$u]['avatar_color'];
            setcookie('chat_user', json_encode(['u' => $u]), time() + (86400 * 30), "/");
            echo json_encode(['success' => true, 'username' => $u, 'color' => $_SESSION['color']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }
    // SEND MESSAGE
    elseif ($action === 'send') {
        if (!$user) { echo json_encode(['success' => false]); exit; }
        $target = $input['target'] ?? '';
        $text = trim($input['text'] ?? '');
        
        if (!$target || !$text) { echo json_encode(['success' => false]); exit; }

        $file = get_chat_file($user['username'], $target);
        $msgs = read_json($file);
        $msgs[] = [
            'id' => uniqid(),
            'sender' => $user['username'],
            'text' => htmlspecialchars($text),
            'time' => time(),
            'read' => false
        ];
        write_json($file, $msgs);
        echo json_encode(['success' => true]);
    }
    // MARK AS READ
    elseif ($action === 'read') {
        if (!$user) { echo json_encode(['success' => false]); exit; }
        $target = $input['target'] ?? '';
        $file = get_chat_file($user['username'], $target);
        $msgs = read_json($file);
        $updated = false;
        foreach ($msgs as &$m) {
            if ($m['sender'] !== $user['username'] && isset($m['read']) && $m['read'] === false) {
                $m['read'] = true;
                $updated = true;
            }
        }
        if ($updated) write_json($file, $msgs);
        echo json_encode(['success' => true]);
    }
    exit;
}

// --- GET ---
if ($action === 'me') {
    if ($user) echo json_encode(['success' => true, 'user' => $user]);
    else echo json_encode(['success' => false]);
    exit;
}

if ($action === 'users') {
    if (!$user) { echo json_encode(['error' => 'Not logged in']); exit; }
    $allUsers = read_json(USER_FILE);
    unset($allUsers[$user['username']]);
    $list = [];
    foreach ($allUsers as $username => $data) {
        $chatFile = get_chat_file($user['username'], $username);
        $msgs = read_json($chatFile);
        $unreadCount = 0;
        foreach ($msgs as $m) {
            // FIX: Added isset($m['read']) check here
            if ($m['sender'] !== $user['username'] && isset($m['read']) && $m['read'] === false) {
                $unreadCount++;
            }
        }
        $list[] = [
            'username' => $username,
            'avatar_color' => $data['avatar_color'],
            'unread' => $unreadCount
        ];
    }
    echo json_encode($list);
    exit;
}

if ($action === 'poll') {
    if (!$user) { echo json_encode([]); exit; }
    $target = $_GET['target'] ?? '';
    $lastTime = (int)($_GET['last'] ?? 0);
    if (!$target) { echo json_encode([]); exit; }
    $file = get_chat_file($user['username'], $target);
    $msgs = read_json($file);
    $new = [];
    foreach ($msgs as $m) {
        if ($m['time'] > $lastTime) $new[] = $m;
    }
    echo json_encode($new);
    exit;
}

if ($action === 'logout') {
    session_destroy();
    setcookie('chat_user', '', time() - 3600, "/");
    echo json_encode(['success' => true]);
    exit;
}