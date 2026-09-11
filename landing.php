<?php
require_once __DIR__ . '/config.php';
// If logged in, go to app
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$fullBase = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Habit Tracker Pro - Build Better Habits, One Day at a Time</title>
<meta name="description" content="Track your daily habits, build streaks, and become the best version of yourself. Free, beautiful, and backed by science.">
<meta name="keywords" content="habit tracker, habit building, streak tracking, productivity, self improvement, daily routine">
<meta name="author" content="Habit Tracker Pro">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#6366f1">
<!-- Open Graph -->
<meta property="og:title" content="Habit Tracker Pro - Build Better Habits">
<meta property="og:description" content="Track daily habits, build powerful streaks, and transform your life. Free, beautiful habit tracker.">
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo htmlspecialchars($fullBase . '/'); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($fullBase . '/public/icon.svg'); ?>">
<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Habit Tracker Pro">
<meta name="twitter:description" content="Track daily habits, build streaks, and transform your life.">
<link rel="canonical" href="<?php echo htmlspecialchars($fullBase . '/'); ?>">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
:root{--bg-primary:#f5f3ff;--bg-secondary:#ffffff;--text-primary:#1e1b4b;--text-secondary:#4c4983;--text-muted:#8b87b0;--accent:#6366f1;--accent-light:#818cf8;--accent-dark:#4f46e5;--success:#10b981;--danger:#ef4444;--border:rgba(99,102,241,0.1);--shadow:0 8px 32px rgba(99,102,241,0.08);--radius:20px;--radius-sm:12px;--radius-xs:8px;--transition:all 0.3s cubic-bezier(0.4,0,0.2,1);--gradient-1:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);--gradient-2:linear-gradient(135deg,#06b6d4 0%,#3b82f6 100%);--gradient-3:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%)}
[data-theme="dark"]{--bg-primary:#0f0a1a;--bg-secondary:#1a1030;--text-primary:#e8e5f5;--text-secondary:#b0adcc;--text-muted:#6b6790;--accent:#818cf8;--accent-light:#a5b4fc;--accent-dark:#6366f1;--border:rgba(129,140,248,0.12);--shadow:0 8px 32px rgba(0,0,0,0.3);--gradient-1:linear-gradient(135deg,#818cf8 0%,#a78bfa 100%);--gradient-2:linear-gradient(135deg,#22d3ee 0%,#60a5fa 100%);--gradient-3:linear-gradient(135deg,#fbbf24 0%,#f87171 100%)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,-apple-system,sans-serif;background:var(--bg-primary);color:var(--text-primary);line-height:1.6;overflow-x:hidden}
body::before{content:'';position:fixed;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle at 30% 50%,rgba(99,102,241,0.06) 0%,transparent 50%),radial-gradient(circle at 70% 80%,rgba(139,92,246,0.05) 0%,transparent 50%);z-index:-1;animation:bgFloat 20s ease-in-out infinite}
@keyframes bgFloat{0%,100%{transform:translate(0,0)}50%{transform:translate(-2%,-2%)}}
body::before{animation:none!important}
.shape{animation:none!important}
.navbar{backdrop-filter:none!important;-webkit-backdrop-filter:none!important}
a{color:var(--accent);text-decoration:none;font-weight:600}
a:hover{text-decoration:underline}
img{max-width:100%}

/* NAVBAR */
.navbar{position:fixed;top:0;left:0;right:0;height:64px;background:rgba(255,255,255,0.8);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 clamp(16px,5vw,80px);z-index:1000;transition:var(--transition)}
[data-theme="dark"] .navbar{background:rgba(15,10,26,0.85)}
.nav-brand{display:flex;align-items:center;gap:10px;font-size:20px;font-weight:900;background:var(--gradient-1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-brand span{font-size:24px;-webkit-text-fill-color:initial}
.nav-links{display:flex;align-items:center;gap:8px}
.nav-links a{padding:8px 18px;border-radius:var(--radius-xs);font-size:14px;font-weight:600;color:var(--text-secondary);transition:var(--transition);text-decoration:none}
.nav-links a:hover{color:var(--accent);background:rgba(99,102,241,0.06)}
.nav-links .btn-nav{background:var(--gradient-1);color:#fff!important;padding:10px 24px;border-radius:var(--radius-sm);box-shadow:0 4px 15px rgba(99,102,241,0.3)}
.nav-links .btn-nav:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(99,102,241,0.4);text-decoration:none}
.theme-btn{background:none;border:2px solid var(--border);border-radius:50%;width:38px;height:38px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:var(--transition)}
.theme-btn:hover{transform:scale(1.1);border-color:var(--accent)}
.nav-toggle{display:none;background:none;border:none;font-size:24px;cursor:pointer;color:var(--text-primary);padding:4px}

/* HERO */
.hero{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:100px clamp(16px,5vw,80px) 60px;position:relative}
.hero-content{max-width:800px;animation:fadeUp 0.8s ease-out}
@keyframes fadeUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 20px;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.15);border-radius:50px;font-size:13px;font-weight:600;color:var(--accent);margin-bottom:24px}
.hero h1{font-size:clamp(36px,6vw,64px);font-weight:900;line-height:1.1;margin-bottom:20px;letter-spacing:-1.5px}
.hero h1 .gradient{background:var(--gradient-1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero p{font-size:clamp(16px,2.5vw,20px);color:var(--text-secondary);max-width:560px;margin:0 auto 36px;line-height:1.7}
.hero-actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:16px 36px;border:none;border-radius:var(--radius-sm);font-size:16px;font-weight:700;cursor:pointer;transition:var(--transition);font-family:inherit;text-decoration:none}
.btn-lg{padding:18px 42px;font-size:17px}
.btn-primary{background:var(--gradient-1);color:#fff;box-shadow:0 4px 20px rgba(99,102,241,0.35)}
.btn-primary:hover{transform:translateY(-3px);box-shadow:0 12px 35px rgba(99,102,241,0.45);text-decoration:none}
.btn-secondary{background:var(--bg-secondary);color:var(--text-primary);border:2px solid var(--border);box-shadow:var(--shadow)}
.btn-secondary:hover{transform:translateY(-3px);border-color:var(--accent);text-decoration:none}

/* FLOATING SHAPES */
.hero-shapes{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.shape{position:absolute;border-radius:50%;opacity:0.12;animation:float 6s ease-in-out infinite}
.shape-1{width:300px;height:300px;background:var(--accent);top:10%;left:-5%;animation-delay:0s}
.shape-2{width:200px;height:200px;background:#8b5cf6;top:60%;right:-3%;animation-delay:2s}
.shape-3{width:150px;height:150px;background:#06b6d4;bottom:10%;left:20%;animation-delay:4s}
@keyframes float{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-20px) rotate(5deg)}}

/* SOCIAL PROOF */
.social-proof{display:flex;align-items:center;justify-content:center;gap:32px;padding:20px;flex-wrap:wrap;margin-top:20px}
.proof-item{display:flex;align-items:center;gap:8px;font-size:14px;color:var(--text-muted);font-weight:500}
.proof-item strong{color:var(--text-primary);font-weight:800}

/* FEATURES */
.features{padding:100px clamp(16px,5vw,80px);max-width:1200px;margin:0 auto}
.section-header{text-align:center;margin-bottom:60px}
.section-header .tag{display:inline-block;padding:6px 16px;background:rgba(99,102,241,0.08);border-radius:50px;font-size:12px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px}
.section-header h2{font-size:clamp(28px,4vw,42px);font-weight:900;letter-spacing:-1px;margin-bottom:14px}
.section-header p{font-size:17px;color:var(--text-secondary);max-width:560px;margin:0 auto}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
.feature-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);padding:36px;transition:var(--transition);position:relative;overflow:hidden}
.feature-card:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(99,102,241,0.12);border-color:rgba(99,102,241,0.2)}
.feature-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--gradient-1);opacity:0;transition:var(--transition)}
.feature-card:hover::before{opacity:1}
.feature-icon{width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:20px}
.fi-purple{background:rgba(99,102,241,0.1)}
.fi-blue{background:rgba(6,182,212,0.1)}
.fi-green{background:rgba(16,185,129,0.1)}
.fi-orange{background:rgba(245,158,11,0.1)}
.fi-pink{background:rgba(236,72,153,0.1)}
.fi-red{background:rgba(239,68,68,0.1)}
.feature-card h3{font-size:19px;font-weight:800;margin-bottom:10px}
.feature-card p{font-size:14px;color:var(--text-secondary);line-height:1.7}

/* HOW IT WORKS */
.how-it-works{padding:100px clamp(16px,5vw,80px);background:var(--bg-secondary);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:40px;max-width:1000px;margin:0 auto}
.step{text-align:center;position:relative}
.step-num{width:56px;height:56px;border-radius:50%;background:var(--gradient-1);color:#fff;font-size:22px;font-weight:900;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 4px 20px rgba(99,102,241,0.3)}
.step h3{font-size:18px;font-weight:800;margin-bottom:8px}
.step p{font-size:14px;color:var(--text-secondary);max-width:280px;margin:0 auto}

/* STATS */
.stats-section{padding:80px clamp(16px,5vw,80px)}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;max-width:900px;margin:0 auto;text-align:center}
.stat-item .stat-number{font-size:clamp(36px,5vw,52px);font-weight:900;background:var(--gradient-1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.stat-item .stat-label{font-size:14px;color:var(--text-muted);font-weight:600;margin-top:4px}

/* TESTIMONIALS */
.testimonials{padding:100px clamp(16px,5vw,80px);max-width:1000px;margin:0 auto}
.testimonial-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
.testimonial-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);padding:32px;transition:var(--transition)}
.testimonial-card:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
.testimonial-stars{color:#f59e0b;font-size:16px;margin-bottom:12px}
.testimonial-card blockquote{font-size:15px;color:var(--text-secondary);line-height:1.7;margin-bottom:16px;font-style:italic}
.testimonial-author{display:flex;align-items:center;gap:12px}
.testimonial-avatar{width:42px;height:42px;border-radius:50%;background:var(--gradient-1);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px}
.testimonial-name{font-size:14px;font-weight:700}
.testimonial-role{font-size:12px;color:var(--text-muted)}

/* CTA */
.cta{padding:100px clamp(16px,5vw,80px);text-align:center}
.cta-box{background:var(--gradient-1);border-radius:var(--radius);padding:60px 40px;max-width:700px;margin:0 auto;color:#fff;position:relative;overflow:hidden}
.cta-box::before{content:'';position:absolute;top:-50%;right:-20%;width:400px;height:400px;background:rgba(255,255,255,0.08);border-radius:50%}
.cta-box::after{content:'';position:absolute;bottom:-30%;left:-10%;width:300px;height:300px;background:rgba(255,255,255,0.05);border-radius:50%}
.cta-box h2{font-size:clamp(28px,4vw,38px);font-weight:900;margin-bottom:14px;position:relative;z-index:1}
.cta-box p{font-size:17px;opacity:0.9;margin-bottom:30px;position:relative;z-index:1}
.cta-box .btn{background:#fff;color:var(--accent-dark);box-shadow:0 4px 20px rgba(0,0,0,0.15);position:relative;z-index:1}
.cta-box .btn:hover{transform:translateY(-3px);box-shadow:0 12px 35px rgba(0,0,0,0.2)}

/* FOOTER */
.footer{padding:40px clamp(16px,5vw,80px);border-top:1px solid var(--border);text-align:center;font-size:13px;color:var(--text-muted)}
.footer a{color:var(--accent)}

/* RESPONSIVE */
@media(max-width:768px){
.navbar{padding:0 16px;height:56px}
.nav-links a:not(.btn-nav){display:none}
.nav-toggle{display:block}
.hero{padding:90px 16px 40px;min-height:auto}
.hero h1{font-size:32px;letter-spacing:-0.5px}
.hero p{font-size:15px}
.hero-actions{flex-direction:column;align-items:stretch}
.btn-lg{padding:16px 32px;font-size:15px}
.social-proof{gap:16px}
.proof-item{font-size:12px}
.features{padding:60px 16px}
.features-grid{grid-template-columns:1fr}
.feature-card{padding:28px}
.how-it-works{padding:60px 16px}
.steps{grid-template-columns:1fr;gap:32px}
.stats-section{padding:50px 16px}
.stats-row{grid-template-columns:1fr 1fr}
.testimonials{padding:60px 16px}
.testimonial-grid{grid-template-columns:1fr}
.cta{padding:60px 16px}
.cta-box{padding:40px 24px}
}
@media(max-width:480px){
.stats-row{grid-template-columns:1fr}
.hero-badge{font-size:11px;padding:6px 14px}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-brand"><span>🎯</span> Habit Tracker Pro</div>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#how">How It Works</a>
        <a href="login.php" class="btn-nav">Get Started</a>
        <button class="theme-btn" onclick="toggleTheme()" title="Toggle theme">🌙</button>
    </div>
    <button class="nav-toggle" onclick="toggleMobileNav()">☰</button>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge">✨ Trusted by hundreds of users worldwide</div>
        <h1>Build Better Habits,<br><span class="gradient">One Day at a Time</span></h1>
        <p>Track daily routines, build powerful streaks, and transform your life with the most beautiful habit tracker. Science-backed, beautifully designed, completely free.</p>
        <div class="hero-actions">
            <a href="signup.php" class="btn btn-primary btn-lg">Start Free Today →</a>
            <a href="#features" class="btn btn-secondary btn-lg">See Features</a>
        </div>
        <div class="social-proof">
            <div class="proof-item">🔒 <strong>Private & Secure</strong></div>
            <div class="proof-item">📊 <strong>Smart Analytics</strong></div>
            <div class="proof-item">🎯 <strong>Goal Tracking</strong></div>
            <div class="proof-item">💯 <strong>100% Free</strong></div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="features" id="features">
    <div class="section-header">
        <div class="tag">Features</div>
        <h2>Everything You Need to<br>Build Lasting Habits</h2>
        <p>Packed with powerful tools designed to help you stay consistent, motivated, and on track every single day.</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon fi-purple">📅</div>
            <h3>Daily Habit Tracking</h3>
            <p>Track unlimited habits with daily, weekly, custom schedules, or weekdays-only. One tap to mark complete — it's that simple.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-orange">🔥</div>
            <h3>Streak Building</h3>
            <p>Watch your streaks grow and stay motivated. Earn streak freezes for when life gets in the way. Recovery mode helps you bounce back.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-blue">📊</div>
            <h3>Smart Analytics</h3>
            <p>Visualize your progress with weekly charts, heatmaps, category breakdowns, and detailed reports. Know exactly where you stand.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-green">🏆</div>
            <h3>Achievements & Quests</h3>
            <p>Unlock 20+ achievements, complete daily & weekly quests, and earn XP. Gamification that actually works.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-pink">🤝</div>
            <h3>Partner Accountability</h3>
            <p>Connect with a friend or partner. Compare weekly progress, share streaks, and keep each other accountable.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-red">🤖</div>
            <h3>AI-Powered Insights</h3>
            <p>Get personalized tips, weekly reports, and smart suggestions based on your actual data. Your personal habit coach.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-blue">💧</div>
            <h3>Water & Sleep Tracking</h3>
            <p>Track daily water intake and sleep hours alongside your habits. Get the full picture of your health.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-green">🌙</div>
            <h3>Dark Mode & Themes</h3>
            <p>Beautiful light and dark themes that adapt to your preference. Easy on the eyes, day or night.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon fi-purple">☁️</div>
            <h3>Cloud Sync</h3>
            <p>Your data is safely stored on our servers. Log in from any device and pick up right where you left off.</p>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works" id="how">
    <div class="section-header">
        <div class="tag">How It Works</div>
        <h2>Get Started in 3 Simple Steps</h2>
        <p>No complicated setup. No learning curve. Just results.</p>
    </div>
    <div class="steps">
        <div class="step">
            <div class="step-num">1</div>
            <h3>Create Your Account</h3>
            <p>Sign up for free in seconds. No credit card required. Your data stays private and secure.</p>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <h3>Add Your Habits</h3>
            <p>Choose from templates or create custom habits. Set goals, categories, and schedules that work for you.</p>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <h3>Track & Improve</h3>
            <p>Check off habits daily, watch your streaks grow, and use AI insights to continuously improve.</p>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats-section">
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-number">9+</div>
            <div class="stat-label">Powerful Features</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Cloud Synced</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">100%</div>
            <div class="stat-label">Free to Use</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">∞</div>
            <div class="stat-label">Unlimited Habits</div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials">
    <div class="section-header">
        <div class="tag">Testimonials</div>
        <h2>Loved by Habit Builders</h2>
        <p>See what our users have to say about their transformation.</p>
    </div>
    <div class="testimonial-grid">
        <div class="testimonial-card">
            <div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
            <blockquote>"This app completely changed my morning routine. The streak feature keeps me accountable and the analytics show real progress."</blockquote>
            <div class="testimonial-author">
                <div class="testimonial-avatar">A</div>
                <div><div class="testimonial-name">Alex M.</div><div class="testimonial-role">Morning Person Since 2024</div></div>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
            <blockquote>"Finally, a habit tracker that's both beautiful AND functional. The AI insights are surprisingly helpful — like having a personal coach."</blockquote>
            <div class="testimonial-author">
                <div class="testimonial-avatar">S</div>
                <div><div class="testimonial-name">Sarah K.</div><div class="testimonial-role">Fitness Enthusiast</div></div>
            </div>
        </div>
        <div class="testimonial-card">
            <div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
            <blockquote>"The partner accountability feature is genius. My wife and I track our habits together and it's made us both more consistent."</blockquote>
            <div class="testimonial-author">
                <div class="testimonial-avatar">R</div>
                <div><div class="testimonial-name">Raj P.</div><div class="testimonial-role">Building 12 Habits</div></div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div class="cta-box">
        <h2>Ready to Transform Your Life?</h2>
        <p>Join hundreds of people building better habits every day. Start your journey today — it's completely free.</p>
        <a href="signup.php" class="btn btn-lg">Get Started Free →</a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <p>© 2026 Habit Tracker Pro. Built with ❤️ for habit builders everywhere.</p>
    <p style="margin-top:8px"><a href="login.php">Sign In</a> · <a href="signup.php">Sign Up</a></p>
</footer>

<script>
function toggleTheme(){
    var t=document.documentElement.getAttribute('data-theme');
    var next=t==='dark'?'light':'dark';
    document.documentElement.setAttribute('data-theme',next);
    localStorage.setItem('authTheme',next);
    document.querySelector('.theme-btn').textContent=next==='dark'?'☀️':'🌙';
}
(function(){
    var saved=localStorage.getItem('authTheme');
    if(saved){document.documentElement.setAttribute('data-theme',saved);document.querySelector('.theme-btn').textContent=saved==='dark'?'☀️':'🌙';}
    else if(window.matchMedia&&window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark');document.querySelector('.theme-btn').textContent='☀️';}
})();
function toggleMobileNav(){
    var links=document.querySelector('.nav-links');
    if(links.style.display==='flex'){links.style.display='none';}
    else{links.style.display='flex';links.style.flexDirection='column';links.style.position='absolute';links.style.top='56px';links.style.right='16px';links.style.background='var(--bg-secondary)';links.style.padding='16px';links.style.borderRadius='var(--radius-sm)';links.style.boxShadow='var(--shadow)';links.style.border='1px solid var(--border)';links.style.zIndex='1001';}
}
</script>
</body>
</html>
