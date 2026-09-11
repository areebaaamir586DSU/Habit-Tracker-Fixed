<?php
require_once __DIR__ . '/config.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 - Not Found</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
:root{--bg:#f5f3ff;--card:#fff;--text:#1e1b4b;--muted:#8b87b0;--accent:#6366f1;--border:rgba(99,102,241,0.1);--gradient:linear-gradient(135deg,#6366f1,#8b5cf6)}
[data-theme="dark"]{--bg:#0f0a1a;--card:#1a1030;--text:#e8e5f5;--muted:#6b6790;--accent:#818cf8;--border:rgba(129,140,248,0.12);--gradient:linear-gradient(135deg,#818cf8,#a78bfa)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{text-align:center;max-width:400px}
.code{font-size:120px;font-weight:900;background:var(--gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1}
h1{font-size:24px;font-weight:800;margin:16px 0 8px}
p{color:var(--muted);font-size:15px;margin-bottom:28px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:14px 32px;background:var(--gradient);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;transition:all 0.3s}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(99,102,241,0.4);text-decoration:none}
</style>
</head>
<body>
<div class="box">
    <div class="code">404</div>
    <h1>Page Not Found</h1>
    <p>The page you're looking for doesn't exist or has been moved.</p>
    <a href="<?php echo htmlspecialchars(BASE_URL . '/'); ?>" class="btn">← Back to Home</a>
</div>
</body>
</html>
