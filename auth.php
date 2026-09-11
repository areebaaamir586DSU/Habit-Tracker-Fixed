<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'signup') {
    try {
        checkRateLimit('signup');
        verifyCsrf();

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            jsonError('All fields are required');
        }
        if (strlen($username) < 3 || strlen($username) > 30) {
            jsonError('Username must be 3-30 characters');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            jsonError('Username can only contain letters, numbers, and underscores');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Invalid email address');
        }
        if (strlen($password) < 8) {
            jsonError('Password must be at least 8 characters');
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            jsonError('Password must contain uppercase, lowercase, and a number');
        }
        if ($password !== $confirm) {
            jsonError('Passwords do not match');
        }

        $db = getDb();

        $cols = [];
        $result = $db->query("PRAGMA table_info(users)");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cols[] = $row['name'];
        }
        if (!in_array('verify_token', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN verify_token TEXT");
        }
        if (!in_array('email_verified', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN email_verified INTEGER NOT NULL DEFAULT 0");
        }
        if (!in_array('reset_token', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN reset_token TEXT");
        }
        if (!in_array('reset_expires', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN reset_expires TEXT");
        }

        $udCols = [];
        $udResult = $db->query("PRAGMA table_info(user_data)");
        while ($row = $udResult->fetchArray(SQLITE3_ASSOC)) {
            $udCols[] = $row['name'];
        }
        if (empty($udCols)) {
            $db->exec("CREATE TABLE IF NOT EXISTS user_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                data_key TEXT NOT NULL,
                data_value TEXT NOT NULL DEFAULT '',
                updated_at TEXT NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(user_id, data_key)
            )");
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':e', $email, SQLITE3_TEXT);
        $result = $stmt->execute();
        if ($result->fetchArray()) {
            $db->close();
            jsonError('Username or email already exists');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $verifyToken = bin2hex(random_bytes(32));

        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, email_verified, verify_token) VALUES (:u, :e, :p, 0, :t)');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $stmt->bindValue(':e', $email, SQLITE3_TEXT);
        $stmt->bindValue(':p', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':t', $verifyToken, SQLITE3_TEXT);
        $stmt->execute();

        $userId = $db->lastInsertRowID();

        $defaultProfile = json_encode([[
            'id' => 'default',
            'name' => $username,
            'initial' => strtoupper(substr($username, 0, 1)),
            'createdAt' => date('c')
        ]]);

        $defaultSettings = json_encode([
            'theme' => 'light',
            'reminders' => false,
            'reminderTime' => '09:00',
            'sound' => true,
            'animations' => true,
            'accentColor' => '#6366f1',
            'locationEnabled' => false,
            'cloudUrl' => '',
            'cloudToken' => '',
            'autoSync' => false
        ]);

        $defaultQuests = json_encode(['daily' => [], 'date' => date('Y-m-d')]);
        $defaultChallenges = json_encode(['active' => [], 'completed' => []]);

        $initData = [
            'habits_default' => '[]',
            'xp_default' => '0',
            'freezes_default' => '0',
            'bundles_default' => '[]',
            'settings_default' => $defaultSettings,
            'profiles' => $defaultProfile,
            'currentProfile' => 'default',
            'quests' => $defaultQuests,
            'challenges' => $defaultChallenges,
            'moodData' => '[]',
            'sleepData' => '[]',
            'waterData' => '{}',
            'penalties' => '{}',
            'tags' => '[]',
            'partnerData' => 'null',
            'chatHistory' => '[]'
        ];

        $stmt = $db->prepare('INSERT INTO user_data (user_id, data_key, data_value) VALUES (:uid, :k, :v)');
        foreach ($initData as $key => $value) {
            $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':k', $key, SQLITE3_TEXT);
            $stmt->bindValue(':v', $value, SQLITE3_TEXT);
            $stmt->execute();
        }

        $db->close();

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['created'] = time();

        jsonResponse(['success' => true, 'redirect' => BASE_URL . '/index.php']);
    } catch (\Throwable $e) {
        error_log('[Signup Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        jsonError('Signup failed. Please try again.');
    }

} elseif ($action === 'login') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonError('Username and password are required');
    }

    $db = getDb();

    $stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE username = :u OR email = :u');
    $stmt->bindValue(':u', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        checkRateLimit('login');
        $db->close();
        jsonError('Invalid username or password');
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
        $stmt->bindValue(':h', $newHash, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();
    }

    $db->close();

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['created'] = time();
    $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');

    jsonResponse(['success' => true, 'redirect' => BASE_URL . '/index.php']);

} elseif ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '/');
    exit;

} elseif ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        jsonResponse(['loggedIn' => true, 'username' => $_SESSION['username']]);
    } else {
        jsonResponse(['loggedIn' => false]);
    }

} elseif ($action === 'request_reset') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Valid email required');
    }

    $db = getDb();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :e');
    $stmt->bindValue(':e', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        $resetToken = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $db->prepare('UPDATE users SET reset_token = :t, reset_expires = :e WHERE id = :id');
        $stmt->bindValue(':t', $resetToken, SQLITE3_TEXT);
        $stmt->bindValue(':e', $expires, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();
        error_log("[Password Reset] Reset requested for email: {$email}");
        checkRateLimit('reset');
    }
    $db->close();
    jsonResponse(['success' => true, 'message' => 'If an account exists with that email, a reset link has been generated.']);

} elseif ($action === 'reset_password') {
    checkRateLimit('reset_confirm');
    verifyCsrf();
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($newPassword)) jsonError('All fields required');
    if (strlen($newPassword) < 8) jsonError('Password must be at least 8 characters');
    if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        jsonError('Password must contain uppercase, lowercase, and a number');
    }
    if ($newPassword !== $confirm) jsonError('Passwords do not match');

    $db = getDb();
    $stmt = $db->prepare('SELECT id, reset_expires FROM users WHERE reset_token = :t');
    $stmt->bindValue(':t', $token, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (!$user || strtotime($user['reset_expires']) < time()) {
        $db->close();
        jsonError('Invalid or expired reset token');
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('UPDATE users SET password_hash = :h, reset_token = NULL, reset_expires = NULL WHERE id = :id');
    $stmt->bindValue(':h', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
    jsonResponse(['success' => true, 'message' => 'Password reset successfully. You can now log in.']);

} elseif ($action === 'change_password') {
    verifyCsrf();
    if (!isset($_SESSION['user_id'])) jsonError('Not authenticated', 401);

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new)) jsonError('All fields are required');
    if (strlen($new) < 8) jsonError('New password must be at least 8 characters');
    if (!preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        jsonError('Password must contain uppercase, lowercase, and a number');
    }
    if ($new !== $confirm) jsonError('Passwords do not match');

    $db = getDb();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $db->close();
        jsonError('Current password is incorrect');
    }

    $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
    $stmt->bindValue(':h', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();

    jsonResponse(['success' => true, 'message' => 'Password changed successfully']);

} elseif ($action === 'delete_account') {
    verifyCsrf();
    if (!isset($_SESSION['user_id'])) jsonError('Not authenticated', 401);

    $password = $_POST['password'] ?? '';
    if (empty($password)) jsonError('Password required to delete account');

    $db = getDb();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $db->close();
        jsonError('Incorrect password');
    }

    $stmt = $db->prepare('DELETE FROM user_data WHERE user_id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->execute();

    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();

    $_SESSION = [];
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Account deleted']);

} else {
    jsonError('Invalid action');
}
