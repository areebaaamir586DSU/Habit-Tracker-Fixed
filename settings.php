<?php
require_once __DIR__ . '/config.php';
requireAuth();
$csrfToken = generateCsrfToken();
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

$db = getDb();
$stmt = $db->prepare('SELECT email, created_at FROM users WHERE id = :id');
$stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);
$email = htmlspecialchars($user['email'] ?? '');
$memberSince = date('M Y', strtotime($user['created_at'] ?? 'now'));
$db->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Habit Tracker Pro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--bg:#f8f7ff;--card:#ffffff;--text:#1a1035;--sub:#6b6494;--muted:#a09bbe;--accent:#6366f1;--accent-glow:rgba(99,102,241,0.25);--success:#10b981;--danger:#ef4444;--border:rgba(99,102,241,0.08);--shadow:0 1px 3px rgba(0,0,0,0.04),0 8px 32px rgba(99,102,241,0.08);--radius:16px;--radius-sm:10px;--radius-xs:8px;--gradient:linear-gradient(135deg,#6366f1,#8b5cf6,#a78bfa)}
[data-theme="dark"]{--bg:#0c0817;--card:#16102b;--text:#ece9f7;--sub:#9d96bb;--muted:#5e5785;--accent:#818cf8;--accent-glow:rgba(129,140,248,0.2);--success:#34d399;--danger:#f87171;--border:rgba(129,140,248,0.1);--shadow:0 1px 3px rgba(0,0,0,0.2),0 8px 32px rgba(0,0,0,0.3)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:20px}
.container{max-width:640px;margin:40px auto}
.back-link{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:var(--card);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--sub);font-size:13px;font-weight:600;text-decoration:none;transition:all 0.2s;box-shadow:var(--shadow);margin-bottom:24px}
.back-link:hover{border-color:var(--accent);color:var(--accent)}
h1{font-size:28px;font-weight:800;margin-bottom:4px}
.subtitle{color:var(--sub);font-size:14px;margin-bottom:32px}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:28px;margin-bottom:16px;box-shadow:var(--shadow)}
.card h2{font-size:17px;font-weight:800;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.card>p{font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.5}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--sub);text-transform:uppercase;letter-spacing:0.5px}
.form-group input,.form-group select{width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:var(--radius-xs);font-size:14px;background:var(--bg);color:var(--text);transition:all 0.2s;font-family:inherit}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow)}
.form-group .hint{font-size:11px;color:var(--muted);margin-top:4px}
.btn{padding:12px 24px;border:none;border-radius:var(--radius-sm);cursor:pointer;font-size:14px;font-weight:700;transition:all 0.2s;font-family:inherit;display:inline-flex;align-items:center;gap:8px}
.btn-primary{background:var(--gradient);color:#fff;box-shadow:0 4px 16px var(--accent-glow)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 8px 24px var(--accent-glow)}
.btn-danger{background:rgba(239,68,68,0.08);color:var(--danger);border:1.5px solid rgba(239,68,68,0.15)}
.btn-danger:hover{background:var(--danger);color:#fff}
.btn:disabled{opacity:0.6;cursor:not-allowed;transform:none}
.msg{padding:12px 16px;border-radius:var(--radius-xs);font-size:13px;font-weight:600;margin-bottom:16px;display:none}
.msg.visible{display:block}
.msg.error{background:rgba(239,68,68,0.08);color:var(--danger);border:1px solid rgba(239,68,68,0.12)}
.msg.success{background:rgba(16,185,129,0.08);color:var(--success);border:1px solid rgba(16,185,129,0.12)}
.danger-zone{border-color:rgba(239,68,68,0.15)}
.danger-zone h2{color:var(--danger)}
.info-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border)}
.info-row:last-child{border-bottom:none}
.info-label{font-size:13px;color:var(--sub)}
.info-value{font-size:13px;font-weight:600}
.theme-btn{position:fixed;top:20px;right:20px;background:var(--card);border:1.5px solid var(--border);border-radius:50%;width:40px;height:40px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;box-shadow:var(--shadow);z-index:10}
.theme-btn:hover{transform:scale(1.08);border-color:var(--accent)}
.setting-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)}
.setting-row:last-child{border-bottom:none}
.setting-info{flex:1}
.setting-info .label{font-size:14px;font-weight:600}
.setting-info .desc{font-size:12px;color:var(--muted);margin-top:2px}
.toggle{position:relative;width:44px;height:24px;cursor:pointer}
.toggle input{opacity:0;width:0;height:0}
.toggle .slider{position:absolute;inset:0;background:var(--border);border-radius:12px;transition:0.3s}
.toggle .slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:0.3s}
.toggle input:checked+.slider{background:var(--accent)}
.toggle input:checked+.slider:before{transform:translateX(20px)}
@media(max-width:600px){.container{margin:16px auto;padding:0}}
</style>
</head>
<body>
<button class="theme-btn" onclick="toggleTheme()" title="Toggle theme">🌙</button>
<div class="container">
    <a href="index.php" class="back-link">← Back to App</a>
    <h1>⚙️ Settings</h1>
    <p class="subtitle">Manage your account and preferences</p>

    <!-- Profile Info -->
    <div class="card">
        <h2>👤 Profile</h2>
        <p>Your account information</p>
        <div class="info-row">
            <span class="info-label">Username</span>
            <span class="info-value"><?php echo $username; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value"><?php echo $email; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Member since</span>
            <span class="info-value"><?php echo htmlspecialchars($memberSince); ?></span>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card">
        <h2>🔑 Change Password</h2>
        <p>Update your password to keep your account secure</p>
        <div class="msg" id="pwMsg"></div>
        <form id="pwForm" onsubmit="return changePassword(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
                <div class="hint">At least 8 characters with uppercase, lowercase & number</div>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary" id="pwBtn">Update Password</button>
        </form>
    </div>

    <!-- Preferences -->
    <div class="card">
        <h2>🎨 Preferences</h2>
        <p>Customize your experience</p>
        <div class="setting-row">
            <div class="setting-info">
                <div class="label">Dark Mode</div>
                <div class="desc">Toggle dark/light theme</div>
            </div>
            <label class="toggle">
                <input type="checkbox" id="prefDarkMode" onchange="toggleTheme()">
                <span class="slider"></span>
            </label>
        </div>
        <div class="setting-row">
            <div class="setting-info">
                <div class="label">Sound Effects</div>
                <div class="desc">Play sounds on completions</div>
            </div>
            <label class="toggle">
                <input type="checkbox" id="prefSound" checked>
                <span class="slider"></span>
            </label>
        </div>
        <div class="setting-row">
            <div class="setting-info">
                <div class="label">Animations</div>
                <div class="desc">Enable smooth transitions</div>
            </div>
            <label class="toggle">
                <input type="checkbox" id="prefAnimations" checked>
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <!-- Data -->
    <div class="card">
        <h2>📦 Your Data</h2>
        <p>Export or reset your habit data</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="export.php" class="btn btn-primary" style="text-decoration:none">📥 Export JSON</a>
            <button class="btn btn-danger" onclick="if(confirm('Reset ALL data to defaults? This cannot be undone.')){resetData();}">🔄 Reset All Data</button>
        </div>
        <div class="msg" id="dataMsg" style="margin-top:12px"></div>
    </div>

    <!-- Danger Zone -->
    <div class="card danger-zone">
        <h2>⚠️ Danger Zone</h2>
        <p>Permanently delete your account and all data. This cannot be undone.</p>
        <div class="msg" id="delMsg"></div>
        <form id="delForm" onsubmit="return deleteAccount(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="form-group">
                <label>Enter your password to confirm</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-danger" id="delBtn">Delete My Account</button>
        </form>
    </div>
</div>
<script>
var CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
function toggleTheme(){var t=document.documentElement.getAttribute('data-theme'),n=t==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',n);localStorage.setItem('authTheme',n);document.querySelector('.theme-btn').textContent=n==='dark'?'☀️':'🌙';}
(function(){var s=localStorage.getItem('authTheme');if(s){document.documentElement.setAttribute('data-theme',s);document.querySelector('.theme-btn').textContent=s==='dark'?'☀️':'🌙';}else if(matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark');document.querySelector('.theme-btn').textContent='☀️';}})();
function showMsg(id,type,msg){var el=document.getElementById(id);el.textContent=msg;el.className='msg visible '+type;}
function changePassword(e){
    e.preventDefault();
    var btn=document.getElementById('pwBtn');btn.disabled=true;
    var fd=new FormData(document.getElementById('pwForm'));
    fd.append('action','change_password');
    fetch('auth.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.success){showMsg('pwMsg','success',d.message);document.getElementById('pwForm').reset();}
        else{showMsg('pwMsg','error',d.error||'Failed');}
        btn.disabled=false;
    }).catch(function(){showMsg('pwMsg','error','Network error');btn.disabled=false;});
    return false;
}
function deleteAccount(e){
    e.preventDefault();
    if(!confirm('This will permanently delete your account and ALL data. Are you sure?'))return false;
    var btn=document.getElementById('delBtn');btn.disabled=true;
    var fd=new FormData(document.getElementById('delForm'));
    fd.append('action','delete_account');
    fetch('auth.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.success){showMsg('delMsg','success','Account deleted. Redirecting...');setTimeout(function(){window.location.href='landing.php';},1500);}
        else{showMsg('delMsg','error',d.error||'Failed');btn.disabled=false;}
    }).catch(function(){showMsg('delMsg','error','Network error');btn.disabled=false;});
    return false;
}
function resetData(){
    fetch('api.php?action=reset&_csrf='+encodeURIComponent(CSRF_TOKEN),{method:'POST',headers:{'Content-Type':'application/json'}}).then(function(r){return r.json()}).then(function(d){
        if(d.success){showMsg('dataMsg','success','Data reset. Reloading...');setTimeout(function(){window.location.reload();},1000);}
        else{showMsg('dataMsg','error',d.error||'Failed');}
    }).catch(function(){showMsg('dataMsg','error','Network error');});
}
</script>
</body>
</html>
