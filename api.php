<?php
require_once __DIR__ . '/config.php';
requireAuth();

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$db = getDb();

// Verify CSRF for all write actions
if ($method === 'POST' && in_array($action, ['save', 'delete', 'reset', 'import', 'invite', 'join', 'disconnect_partner', 'notifications'])) {
    verifyCsrf();
}

// Habit/XP/etc. data is stored under the active profile scope (e.g. habits_default)
function scopedDataKey(SQLite3 $db, int $userId, string $baseKey): string {
    $stmt = $db->prepare('SELECT data_value FROM user_data WHERE user_id = :uid AND data_key = :k');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', 'currentProfile', SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $profile = $row['data_value'] ?? 'default';
    return $baseKey . '_' . $profile;
}

if ($action === 'load') {
    // Load ALL user data as a flat key-value map
    $stmt = $db->prepare('SELECT data_key, data_value FROM user_data WHERE user_id = :uid');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $data = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Return raw JSON string so client preserves object vs array types
        // (PHP json_decode('{}', true) returns [] which breaks JS object storage)
        $data[$row['data_key']] = $row['data_value'];
    }

    $db->close();
    jsonResponse($data);

} elseif ($action === 'save' && $method === 'POST') {
    // Save one or more data keys (upsert)
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !is_array($input)) {
        jsonError('Invalid data');
    }

    $stmt = $db->prepare('
        INSERT INTO user_data (user_id, data_key, data_value, updated_at) 
        VALUES (:uid, :k, :v, datetime("now"))
        ON CONFLICT(user_id, data_key) 
        DO UPDATE SET data_value = :v2, updated_at = datetime("now")
    ');

    $saved = [];
    foreach ($input as $key => $value) {
        // Skip internal meta keys (starting with _)
        if (strpos($key, '_') === 0) continue;

        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':k', $key, SQLITE3_TEXT);
        $jsonVal = is_string($value) ? $value : json_encode($value);
        $stmt->bindValue(':v', $jsonVal, SQLITE3_TEXT);
        $stmt->bindValue(':v2', $jsonVal, SQLITE3_TEXT);
        $stmt->execute();
        $saved[] = $key;
    }

    $db->close();
    jsonResponse(['success' => true, 'saved' => $saved]);

} elseif ($action === 'delete' && $method === 'POST') {
    // Delete specific data keys
    $input = json_decode(file_get_contents('php://input'), true);
    $keys = $input['keys'] ?? [];

    if (!empty($keys)) {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $db->prepare("DELETE FROM user_data WHERE user_id = ? AND data_key IN ($placeholders)");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        foreach ($keys as $i => $key) {
            $stmt->bindValue($i + 2, $key, SQLITE3_TEXT);
        }
        $stmt->execute();
    }

    $db->close();
    jsonResponse(['success' => true]);

} elseif ($action === 'reset' && $method === 'POST') {
    // Delete ALL data for this user and re-initialize
    $stmt = $db->prepare('DELETE FROM user_data WHERE user_id = :uid');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->execute();

    // Re-initialize with defaults
    $stmt = $db->prepare('INSERT INTO user_data (user_id, data_key, data_value) VALUES (:uid, :k, :v)');
    $defaultSettings = json_encode([
        'theme' => 'light', 'reminders' => false, 'reminderTime' => '09:00',
        'sound' => true, 'animations' => true, 'accentColor' => '#6366f1',
        'locationEnabled' => false, 'cloudUrl' => '', 'cloudToken' => '', 'autoSync' => false
    ]);
    $defaultProfiles = json_encode([[
        'id' => 'default',
        'name' => $_SESSION['username'],
        'initial' => strtoupper(substr($_SESSION['username'], 0, 1)),
        'createdAt' => date('c')
    ]]);
    $initData = [
        // Profile-scoped data (bridge saves with these keys)
        'habits_default' => '[]',
        'xp_default' => '0',
        'freezes_default' => '0',
        'bundles_default' => '[]',
        'settings_default' => $defaultSettings,
        // Global data
        'profiles' => $defaultProfiles,
        'currentProfile' => 'default',
        'quests' => json_encode(['daily' => [], 'date' => date('Y-m-d')]),
        'challenges' => json_encode(['active' => [], 'completed' => []]),
        'moodData' => '[]',
        'sleepData' => '[]',
        'waterData' => '{}',
        'penalties' => '{}',
        'tags' => '[]',
        'partnerData' => 'null',
        'chatHistory' => '[]'
    ];

    foreach ($initData as $key => $value) {
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':k', $key, SQLITE3_TEXT);
        $stmt->bindValue(':v', $value, SQLITE3_TEXT);
        $stmt->execute();
    }

    $db->close();
    jsonResponse(['success' => true, 'message' => 'Data reset to defaults']);

} elseif ($action === 'import' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) jsonError('Invalid import data');

    $stmt = $db->prepare('
        INSERT INTO user_data (user_id, data_key, data_value, updated_at) 
        VALUES (:uid, :k, :v, datetime("now"))
        ON CONFLICT(user_id, data_key) 
        DO UPDATE SET data_value = :v2, updated_at = datetime("now")
    ');

    $imported = [];
    foreach ($input as $key => $value) {
        if (strpos($key, '_') === 0 || $key === 'exported_at' || $key === 'version') continue;
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':k', $key, SQLITE3_TEXT);
        $jsonVal = is_string($value) ? $value : json_encode($value);
        $stmt->bindValue(':v', $jsonVal, SQLITE3_TEXT);
        $stmt->bindValue(':v2', $jsonVal, SQLITE3_TEXT);
        $stmt->execute();
        $imported[] = $key;
    }

    $db->close();
    jsonResponse(['success' => true, 'imported' => $imported, 'count' => count($imported)]);

} elseif ($action === 'export_csv') {
    $db->close();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="habits-' . date('Y-m-d') . '.csv"');

    $db2 = getDb();
    $habitsKey = scopedDataKey($db2, $userId, 'habits');
    $stmt = $db2->prepare('SELECT data_value FROM user_data WHERE user_id = :uid AND data_key = :k');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', $habitsKey, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $habits = json_decode($row['data_value'] ?? '[]', true) ?: [];

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Habit', 'Category', 'Frequency', 'Goal', 'Total Completions', 'Best Streak', 'Created']);
    foreach ($habits as $h) {
        fputcsv($output, [
            $h['name'] ?? '',
            $h['category'] ?? '',
            $h['frequency'] ?? 'daily',
            $h['goal'] ?? 7,
            count($h['completions'] ?? []),
            $h['bestStreak'] ?? 0,
            $h['createdAt'] ?? ''
        ]);
    }
    fclose($output);
    $db2->close();
    exit;

} elseif ($action === 'notifications') {
    if ($method === 'GET') {
        $stmt = $db->prepare('SELECT data_value FROM user_data WHERE user_id = :uid AND data_key = :k');
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':k', 'notifications', SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $notifs = json_decode($row['data_value'] ?? '[]', true) ?: [];
        $db->close();
        jsonResponse($notifs);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['mark_read'])) {
            $stmt = $db->prepare('SELECT data_value FROM user_data WHERE user_id = :uid AND data_key = :k');
            $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':k', 'notifications', SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $notifs = json_decode($row['data_value'] ?? '[]', true) ?: [];
            foreach ($notifs as &$n) { $n['read'] = true; }
            unset($n);
            $json = json_encode($notifs);
            $stmt2 = $db->prepare('INSERT INTO user_data (user_id, data_key, data_value, updated_at) VALUES (:uid, :k, :v, datetime("now")) ON CONFLICT(user_id, data_key) DO UPDATE SET data_value = :v2, updated_at = datetime("now")');
            $stmt2->bindValue(':uid', $userId, SQLITE3_INTEGER);
            $stmt2->bindValue(':k', 'notifications', SQLITE3_TEXT);
            $stmt2->bindValue(':v', $json, SQLITE3_TEXT);
            $stmt2->bindValue(':v2', $json, SQLITE3_TEXT);
            $stmt2->execute();
        }
        $db->close();
        jsonResponse(['success' => true]);
    }

} elseif ($action === 'invite' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = strtoupper(bin2hex(random_bytes(4)));

    $stmt = $db->prepare('INSERT INTO user_data (user_id, data_key, data_value, updated_at) VALUES (:uid, :k, :v, datetime("now")) ON CONFLICT(user_id, data_key) DO UPDATE SET data_value = :v2, updated_at = datetime("now")');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', 'invite_code', SQLITE3_TEXT);
    $stmt->bindValue(':v', $code, SQLITE3_TEXT);
    $stmt->bindValue(':v2', $code, SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
    jsonResponse(['success' => true, 'code' => $code]);

} elseif ($action === 'join' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = trim($input['code'] ?? '');
    if (empty($code)) jsonError('Invite code required');

    $stmt = $db->prepare('SELECT user_id FROM user_data WHERE data_key = :k AND data_value = :v');
    $stmt->bindValue(':k', 'invite_code', SQLITE3_TEXT);
    $stmt->bindValue(':v', $code, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if (!$row) { $db->close(); jsonError('Invalid invite code'); }
    if ($row['user_id'] == $userId) { $db->close(); jsonError("Can't join your own code"); }

    $partnerData = json_encode(['partner_id' => (int)$row['user_id'], 'connected_at' => date('c')]);
    $stmt2 = $db->prepare('INSERT INTO user_data (user_id, data_key, data_value, updated_at) VALUES (:uid, :k, :v, datetime("now")) ON CONFLICT(user_id, data_key) DO UPDATE SET data_value = :v2, updated_at = datetime("now")');
    $stmt2->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt2->bindValue(':k', 'partnerData', SQLITE3_TEXT);
    $stmt2->bindValue(':v', $partnerData, SQLITE3_TEXT);
    $stmt2->bindValue(':v2', $partnerData, SQLITE3_TEXT);
    $stmt2->execute();
    $db->close();
    jsonResponse(['success' => true, 'message' => 'Connected with partner!']);

} elseif ($action === 'partner_data' && $method === 'GET') {
    // Fetch connected partner's habit data
    $stmt = $db->prepare('SELECT data_value FROM user_data WHERE user_id = :uid AND data_key = :k');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', 'partnerData', SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $partnerData = json_decode($row['data_value'] ?? 'null', true);

    if (!$partnerData || empty($partnerData['partner_id'])) {
        $db->close();
        jsonResponse(['connected' => false]);
    }

    $partnerId = (int)$partnerData['partner_id'];

    // Fetch partner's habits (profile-scoped)
    $habitsKey = scopedDataKey($db, $partnerId, 'habits');
    $xpKey = scopedDataKey($db, $partnerId, 'xp');
    $stmt = $db->prepare('SELECT data_value FROM user_data WHERE user_id = :pid AND data_key = :k');
    $stmt->bindValue(':pid', $partnerId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', $habitsKey, SQLITE3_TEXT);
    $result = $stmt->execute();
    $prow = $result->fetchArray(SQLITE3_ASSOC);
    $partnerHabits = json_decode($prow['data_value'] ?? '[]', true) ?: [];

    // Fetch partner's XP
    $stmt = $db->prepare('SELECT data_value FROM user_data WHERE user_id = :pid AND data_key = :k');
    $stmt->bindValue(':pid', $partnerId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', $xpKey, SQLITE3_TEXT);
    $result = $stmt->execute();
    $prow = $result->fetchArray(SQLITE3_ASSOC);
    $partnerXp = (int)($prow['data_value'] ?? 0);

    // Fetch partner's profile name
    $stmt = $db->prepare('SELECT data_value FROM user_data WHERE user_id = :pid AND data_key = :k');
    $stmt->bindValue(':pid', $partnerId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', 'profiles', SQLITE3_TEXT);
    $result = $stmt->execute();
    $prow = $result->fetchArray(SQLITE3_ASSOC);
    $profiles = json_decode($prow['data_value'] ?? '[]', true) ?: [];
    $partnerName = $profiles[0]['name'] ?? 'Partner';

    // Calculate partner's today stats
    $today = date('Y-m-d');
    $completedToday = 0;
    $totalToday = 0;
    foreach ($partnerHabits as $h) {
        if (empty($h['archived'])) {
            $totalToday++;
            $completions = $h['completions'] ?? [];
            if (in_array($today, array_column($completions, 'date'))) {
                $completedToday++;
            }
        }
    }

    // Calculate partner's streaks
    $bestStreak = 0;
    foreach ($partnerHabits as $h) {
        $s = $h['bestStreak'] ?? 0;
        if ($s > $bestStreak) $bestStreak = $s;
    }

    $db->close();
    jsonResponse([
        'connected' => true,
        'partner_name' => $partnerName,
        'today_completed' => $completedToday,
        'today_total' => $totalToday,
        'best_streak' => $bestStreak,
        'xp' => $partnerXp,
        'habits_count' => count($partnerHabits),
        'connected_at' => $partnerData['connected_at'] ?? ''
    ]);

} elseif ($action === 'disconnect_partner' && $method === 'POST') {
    $stmt = $db->prepare('INSERT INTO user_data (user_id, data_key, data_value, updated_at) VALUES (:uid, :k, :v, datetime("now")) ON CONFLICT(user_id, data_key) DO UPDATE SET data_value = :v2, updated_at = datetime("now")');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':k', 'partnerData', SQLITE3_TEXT);
    $stmt->bindValue(':v', 'null', SQLITE3_TEXT);
    $stmt->bindValue(':v2', 'null', SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
    jsonResponse(['success' => true, 'message' => 'Disconnected from partner']);

} else {
    $db->close();
    jsonError('Invalid action or method');
}
