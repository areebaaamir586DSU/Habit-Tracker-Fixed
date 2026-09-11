<?php
require_once __DIR__ . '/config.php';

// "/" resolves to this file under both php -S and Apache. Show the landing
// page when logged out, the app when logged in.
if (!isset($_SESSION['user_id'])) {
    require __DIR__ . '/landing.php';
    exit;
}
requireAuth();

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$originalFile = __DIR__ . '/habit-tracker-v2.html';

if (!file_exists($originalFile)) {
    die('Error: habit-tracker-v2.html not found.');
}

$html = file_get_contents($originalFile);

// === RESPONSIVE CSS ===
$respCSS = '
<style id="responsive-overrides">
*,*::before,*::after{box-sizing:border-box}

/* AUTH BAR */
.auth-bar{position:fixed;top:0;left:0;right:0;height:40px;background:var(--bg-secondary);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:9999;font-size:12px;font-weight:600;color:var(--text-muted)}
.auth-bar-left{display:flex;align-items:center;gap:6px;min-width:0;overflow:hidden}
.auth-bar-left span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.auth-bar strong{color:var(--text-primary)}
.auth-bar-right{display:flex;align-items:center;flex-shrink:0}
.auth-bar a{color:var(--accent);text-decoration:none;font-weight:700;white-space:nowrap}
body{padding-top:40px!important;overflow-x:hidden}

/* PREVENT FIXED-WIDTH OVERFLOW BELOW 1440 (tabs/stats force .app-container min-content to 1440) */
.app-container{min-width:0}

/* TABLET */
@media(max-width:900px){
.sidebar-toggle{top:46px!important;left:12px!important;z-index:91!important}
.header-actions{flex-wrap:wrap;gap:6px!important}
}

/* MOBILE */
@media(max-width:768px){
.auth-bar{height:36px;padding:0 10px;font-size:11px}
body{padding-top:36px!important}
.auth-bar a.logout-btn{padding:4px 12px;border-radius:6px;background:var(--accent);color:#fff;font-size:10px;font-weight:700}

.sidebar{width:260px!important;padding:16px 12px!important}
.sidebar-toggle{top:44px!important;left:10px!important;z-index:91!important}
.sidebar-toggle.hidden{display:none!important}
.sidebar-section{padding:12px!important}
.sidebar-stat-value{font-size:22px!important}
.sidebar-header h3{font-size:14px!important}
.sidebar-actions .btn{font-size:11px!important;padding:8px 12px!important}

.app-container{padding:10px!important;padding-top:50px!important;max-width:100%!important}
.app-container.sidebar-open{margin-left:0!important}

.header{padding:8px 0 12px!important;gap:8px!important}
.header-left h1{font-size:20px!important;letter-spacing:-0.3px!important}
.header-left p{font-size:12px!important;margin-top:2px!important}
.header-actions{gap:4px!important;width:100%!important}
.header-actions .btn{padding:7px 12px!important;font-size:11px!important}
.header-actions .btn-icon{width:36px!important;height:36px!important;padding:7px!important}
.header-actions .btn-glow{animation:none!important}

.tabs{gap:2px!important;padding:3px!important;margin-bottom:12px!important;border-radius:12px!important;overflow-x:auto!important;-webkit-overflow-scrolling:touch!important}
.tabs::-webkit-scrollbar{display:none}
.tab{padding:7px 10px!important;font-size:10px!important;white-space:nowrap!important;border-radius:6px!important}
.tab.active{box-shadow:0 2px 8px rgba(99,102,241,0.3)!important}

.stats-grid{grid-template-columns:1fr 1fr!important;gap:8px!important}
.stat-card{padding:14px!important;border-radius:12px!important}
.stat-icon{width:36px!important;height:36px!important;border-radius:10px!important;font-size:18px!important;margin-bottom:8px!important}
.stat-value{font-size:22px!important}
.stat-label{font-size:11px!important;margin-top:4px!important}

.habits-grid{grid-template-columns:1fr!important;gap:10px!important}
.habit-card{padding:14px!important;border-radius:12px!important}
.habit-header{gap:8px!important}
.habit-info h3{font-size:14px!important}
.habit-category{font-size:10px!important;padding:3px 8px!important}
.streak-container{gap:6px!important}
.streak-badge{font-size:11px!important;padding:4px 8px!important}
.habit-actions button{padding:5px!important;font-size:13px!important}
.progress-bar-container{height:6px!important}

.modal-overlay{padding:10px!important}
.modal{padding:20px!important;max-width:100%!important;border-radius:16px!important}
.modal h2{font-size:18px!important;margin-bottom:16px!important}
.modal label{font-size:12px!important}
.modal input,.modal select,.modal textarea{font-size:13px!important;padding:10px!important}
.modal-actions{flex-wrap:wrap;gap:6px!important}
.modal-actions .btn{flex:1;min-width:80px;justify-content:center}

.toast-container{top:44px!important;right:8px!important;left:8px!important}
.toast{min-width:0!important;width:100%!important;padding:12px 16px!important;font-size:12px!important;border-radius:10px!important}

.install-banner{left:10px!important;right:10px!important;bottom:70px!important;padding:12px 16px!important;border-radius:12px!important}

.notification-banner{padding:10px 16px!important;border-radius:10px!important;flex-wrap:wrap}
.notification-banner p{font-size:12px!important}

.chat-panel{left:8px!important;right:8px!important;width:auto!important;bottom:80px!important;height:65vh!important;border-radius:14px!important}

.analytics-grid{grid-template-columns:1fr!important}
.reports-grid{grid-template-columns:1fr!important}
.challenge-templates-grid{grid-template-columns:1fr!important}

.year-review-grid{grid-template-columns:1fr!important}
.compare-column{min-width:0!important}
.profile-card{padding:14px!important}
.bundle-card{padding:14px!important}
.tag-pill{font-size:11px!important;padding:4px 10px!important}
.insight-card{padding:14px!important}
.template-card{padding:14px!important}

.setting-row{flex-direction:column;align-items:stretch!important;gap:8px!important;padding:12px 0!important}
.setting-label{font-size:13px!important}
.setting-desc{font-size:11px!important}

.pomodoro-display{font-size:48px!important}
.water-grid{grid-template-columns:repeat(7,1fr)!important;gap:4px!important}
.mood-grid{grid-template-columns:repeat(5,1fr)!important;gap:6px!important}
.sleep-chart{height:200px!important}

.timeline-item{padding:10px!important}
.calendar-grid{gap:2px!important}
.calendar-day{min-height:32px!important;font-size:11px!important}
.achievement-card{padding:10px!important}
.quest-card{padding:12px!important}

/* SMALL PHONES */
@media(max-width:400px){
.auth-bar{height:32px;padding:0 6px;font-size:10px}
body{padding-top:32px!important}
.sidebar-toggle{top:38px!important;left:6px!important;font-size:16px!important;padding:8px 10px!important}
.app-container{padding:6px!important;padding-top:42px!important}
.header-left h1{font-size:17px!important}
.header-actions .btn{padding:6px 8px!important;font-size:10px!important}
.stats-grid{gap:6px!important}
.stat-card{padding:10px!important}
.stat-icon{width:30px!important;height:30px!important;font-size:15px!important}
.stat-value{font-size:18px!important}
.habit-card{padding:10px!important}
.habit-info h3{font-size:13px!important}
.modal{padding:16px!important}
.modal h2{font-size:16px!important}
.toast{padding:10px 12px!important;font-size:11px!important}
}
</style>';

$authBarHTML = '<div class="auth-bar">
    <div class="auth-bar-left"><span>🎯 Logged in as <strong>' . $username . '</strong></span></div>
    <div class="auth-bar-right"><a href="settings.php" style="margin-right:8px">Settings</a><a href="logout.php" class="logout-btn">Logout</a></div>
</div>';

function replaceFirst($subject, $search, $replace) {
    $pos = strpos($subject, $search);
    if ($pos === false) return $subject;
    return substr_replace($subject, $replace, $pos, strlen($search));
}

function replaceLast($subject, $search, $replace) {
    $pos = strrpos($subject, $search);
    if ($pos === false) return $subject;
    return substr_replace($subject, $replace, $pos, strlen($search));
}

$html = replaceFirst($html, '</head>', $respCSS . "\n</head>");
$html = replaceFirst($html, '<body>', '<body>' . "\n" . $authBarHTML);

// === SERVER STORAGE BRIDGE ===
$bridgeScript = '<script id="server-bridge">
var __SRV=(function(){
var API="api.php",cache={},pending={},pendingRemoves=null,saveTimer=null;
var CSRF="' . generateCsrfToken() . '";

function loadAll(){
try{
var x=new XMLHttpRequest();
x.open("GET",API+"?action=load&_cb="+Date.now(),false);
x.withCredentials=true;
x.send();
if(x.status===200){
var d=JSON.parse(x.responseText);
for(var k in d)if(d.hasOwnProperty(k)){
var v=d[k];
cache[k]=(typeof v==="string")?v:JSON.stringify(v);
}
}
}catch(e){console.error("[Bridge] load error:",e);}
}

function flushPending(){
if(!Object.keys(pending).length)return;
var s={};for(var k in pending)s[k]=pending[k];pending={};
var body=JSON.stringify(s);
try{
if(navigator.sendBeacon){
var b=new Blob([body],{type:"application/json;charset=UTF-8"});
navigator.sendBeacon(API+"?action=save&_csrf="+CSRF,b);
}else{
var x=new XMLHttpRequest();
x.open("POST",API+"?action=save&_csrf="+CSRF,true);
x.setRequestHeader("Content-Type","application/json;charset=UTF-8");
x.send(body);
}
}catch(e){console.error("[Bridge] save error:",e);}
}

function flushRemoves(){
if(!pendingRemoves)return;
var ks=Object.keys(pendingRemoves);pendingRemoves=null;
if(!ks.length)return;
var body=JSON.stringify({keys:ks});
try{
var u=API+"?action=delete&_csrf="+CSRF;
if(navigator.sendBeacon){
navigator.sendBeacon(u,new Blob([body],{type:"application/json;charset=UTF-8"}));
}else{
var x=new XMLHttpRequest();
x.open("POST",u,true);
x.setRequestHeader("Content-Type","application/json;charset=UTF-8");
x.send(body);
}
}catch(e){console.error("[Bridge] delete error:",e);}
}

function queueSave(k,v){pending[k]=v;if(saveTimer)clearTimeout(saveTimer);saveTimer=setTimeout(flushPending,80);}

loadAll();
window.__CSRF=CSRF;
window.addEventListener("beforeunload",function(){flushPending();flushRemoves();});
window.addEventListener("pagehide",function(){flushPending();flushRemoves();});
setInterval(function(){flushPending();flushRemoves();},3000);

return{
get:function(k){return cache.hasOwnProperty(k)?cache[k]:null;},
set:function(k,v){var s=String(v);cache[k]=s;queueSave(k,s);},
remove:function(k){delete cache[k];if(!pendingRemoves)pendingRemoves={};pendingRemoves[k]=1;},
clear:function(){var ks=Object.keys(cache);if(ks.length){if(!pendingRemoves)pendingRemoves={};for(var i=0;i<ks.length;i++)pendingRemoves[ks[i]]=1;}cache={};}
};
})();
</script>';

// Replace all localStorage API calls with bridge calls
$html = str_replace('localStorage.getItem(', '__SRV.get(', $html);
$html = str_replace('localStorage.setItem(', '__SRV.set(', $html);
$html = str_replace('localStorage.removeItem(', '__SRV.remove(', $html);
$html = str_replace('localStorage.clear()', '__SRV.clear()', $html);

// Fix "Suggestions updated!" toast - only show when user explicitly clicks the button
$html = str_replace(
    "if(targetId!=='nextStepsPanel')showToast('Suggestions updated!','success')",
    "if(!targetId)showToast('Suggestions updated!','success')",
    $html
);

// Insert bridge script before first <script> tag
$html = str_replace('<script>', $bridgeScript . "\n<script>", $html);

// === PRO FEATURES INJECTION ===
$proFeatures = <<<PROFEATURES
<!-- ONBOARDING OVERLAY -->
<div id="onboardingOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99999;align-items:center;justify-content:center;padding:20px">
<div style="background:var(--bg-secondary);border-radius:20px;padding:40px;max-width:480px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideIn .4s ease">
<div id="onboardingStep"></div>
<div style="display:flex;gap:10px;justify-content:center;margin-top:24px">
<button class="btn" onclick="skipOnboarding()" style="padding:10px 20px;background:var(--bg-primary);border:2px solid var(--border);border-radius:10px;cursor:pointer;font-weight:600;color:var(--text-muted)">Skip</button>
<button class="btn" id="onboardingNext" onclick="nextOnboardingStep()" style="padding:10px 24px;background:var(--gradient-1);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:700">Next</button>
</div>
<div style="margin-top:16px;font-size:12px;color:var(--text-muted)" id="onboardingProgress"></div>
</div>
</div>

<!-- NOTIFICATION CENTER -->
<div id="notifPanel" style="display:none;position:fixed;top:40px;right:16px;width:340px;max-height:400px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,0.15);z-index:9998;overflow:hidden">
<div style="padding:16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
<span style="font-weight:800;font-size:15px">🔔 Notifications</span>
<button onclick="markAllRead()" style="background:none;border:none;color:var(--accent);cursor:pointer;font-size:12px;font-weight:600">Mark all read</button>
</div>
<div id="notifList" style="max-height:340px;overflow-y:auto;padding:8px"></div>
</div>

<!-- THEME CUSTOMIZER PANEL -->
<div id="themePanel" style="display:none;position:fixed;top:40px;right:16px;width:300px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,0.15);z-index:9998;padding:20px">
<h3 style="font-size:16px;font-weight:800;margin-bottom:16px">🎨 Theme</h3>
<div style="margin-bottom:16px">
<div style="font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase">Accent Color</div>
<div style="display:flex;gap:8px;flex-wrap:wrap" id="accentColors"></div>
</div>
<div style="margin-bottom:16px">
<div style="font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase">Font Size</div>
<div style="display:flex;gap:6px">
<button onclick="setFontSize(12)" class="fs-btn" style="padding:6px 12px;border:2px solid var(--border);border-radius:8px;background:var(--bg-primary);cursor:pointer;font-size:12px">A</button>
<button onclick="setFontSize(14)" class="fs-btn" style="padding:6px 14px;border:2px solid var(--border);border-radius:8px;background:var(--bg-primary);cursor:pointer;font-size:14px;font-weight:600">A</button>
<button onclick="setFontSize(16)" class="fs-btn" style="padding:6px 16px;border:2px solid var(--border);border-radius:8px;background:var(--bg-primary);cursor:pointer;font-size:16px;font-weight:700">A</button>
</div>
</div>
<div style="margin-bottom:16px">
<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600">
<input type="checkbox" id="compactMode" onchange="toggleCompact()" style="accent-color:var(--accent)"> Compact Mode
</label>
</div>
<div style="margin-bottom:16px">
<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600">
<input type="checkbox" id="animationsToggle" onchange="toggleAnimations()" checked style="accent-color:var(--accent)"> Animations
</label>
</div>
<button onclick="closeThemePanel()" style="width:100%;padding:10px;background:var(--bg-primary);border:2px solid var(--border);border-radius:10px;cursor:pointer;font-weight:600;color:var(--text-muted)">Done</button>
</div>

<!-- KEYBOARD SHORTCUT HINT -->
<div id="kbdHint" style="display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:var(--bg-secondary);border:1px solid var(--border);border-radius:12px;padding:12px 20px;box-shadow:0 8px 30px rgba(0,0,0,0.15);z-index:99997;font-size:13px;color:var(--text-muted);display:none;gap:12px">
<span><kbd style="padding:2px 6px;background:var(--bg-primary);border:1px solid var(--border);border-radius:4px;font-size:11px;font-family:monospace">N</kbd> New habit</span>
<span><kbd style="padding:2px 6px;background:var(--bg-primary);border:1px solid var(--border);border-radius:4px;font-size:11px;font-family:monospace">/</kbd> Search</span>
<span><kbd style="padding:2px 6px;background:var(--bg-primary);border:1px solid var(--border);border-radius:4px;font-size:11px;font-family:monospace">?</kbd> Shortcuts</span>
<span><kbd style="padding:2px 6px;background:var(--bg-primary);border:1px solid var(--border);border-radius:4px;font-size:11px;font-family:monospace">Esc</kbd> Close</span>
</div>

<script>
// === ONBOARDING ===
var onboardingSteps=[
{icon:"🎯",title:"Welcome to Habit Tracker Pro!",desc:"Build better habits, track streaks, and become the best version of yourself. Let us show you around."},
{icon:"✅",title:"Track Habits",desc:"Click the day dots on any habit card to mark it complete. Right-click to add notes. Your data syncs automatically."},
{icon:"🔥",title:"Build Streaks",desc:"Complete habits daily to build streaks. Use streak freezes when life gets in the way. Recovery mode helps you bounce back."},
{icon:"📊",title:"Analytics & Reports",desc:"Check the Analytics tab for weekly charts, heatmaps, and category breakdowns. The Reports tab shows daily & weekly summaries."},
{icon:"🤝",title:"Partner Up",desc:"Share your invite code with a friend to compare weekly progress and stay accountable together."},
{icon:"⌨️",title:"Keyboard Shortcuts",desc:"Press N for new habit, / to search, ? for all shortcuts. Power user features at your fingertips."},
{icon:"🚀",title:"You\\'re All Set!",desc:"Start by creating your first habit. We\\'ll be here to help you build lasting routines. Good luck!"}
];
var obStep=0;
function showOnboarding(){obStep=0;renderObStep();document.getElementById("onboardingOverlay").style.display="flex";}
function renderObStep(){var s=onboardingSteps[obStep];document.getElementById("onboardingStep").innerHTML="<div style=font-size:48px;margin-bottom:16px>"+s.icon+"</div><h2 style=font-size:22px;font-weight:800;margin-bottom:8px>"+s.title+"</h2><p style=font-size:14px;color:var(--text-secondary);line-height:1.7>"+s.desc+"</p>";document.getElementById("onboardingProgress").textContent=(obStep+1)+" / "+onboardingSteps.length;document.getElementById("onboardingNext").textContent=obStep===onboardingSteps.length-1?"Get Started":"Next";}
function nextOnboardingStep(){obStep++;if(obStep>=onboardingSteps.length){skipOnboarding();return;}renderObStep();}
function skipOnboarding(){document.getElementById("onboardingOverlay").style.display="none";__SRV.set("onboarding_done","1");}
if(!__SRV.get("onboarding_done"))setTimeout(showOnboarding,800);

// === NOTIFICATIONS ===
var notifCount=0;
function toggleNotifs(){var p=document.getElementById("notifPanel");p.style.display=p.style.display==="block"?"none":"block";if(p.style.display==="block")loadNotifs();}
function loadNotifs(){fetch("api.php?action=notifications").then(function(r){return r.json()}).then(function(n){var c=document.getElementById("notifList");if(!n.length){c.innerHTML="<div style=padding:24px;text-align:center;color:var(--text-muted);font-size:13px>No notifications yet</div>";return;}c.innerHTML=n.slice(0,20).map(function(x){return "<div style=padding:10px 12px;border-radius:8px;margin:4px 8px;font-size:13px;background:"+(x.read?"transparent":"rgba(99,102,241,0.06)")+";cursor:pointer;border:1px solid var(--border)"+"><div style=font-weight:"+(x.read?"400":"700")+">"+(x.icon||"📌")+" "+x.title+"</div>"+(x.desc?"<div style=font-size:11px;color:var(--text-muted);margin-top:2px>"+x.desc+"</div>":"")+"</div>";}).join("");}).catch(function(){});}
function markAllRead(){fetch("api.php?action=notifications&_csrf="+encodeURIComponent(window.__CSRF||""),{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({mark_read:true})}).then(function(){loadNotifs();});}
function addNotification(icon,title,desc){fetch("api.php?action=notifications").then(function(r){return r.json()}).then(function(n){n.unshift({icon:icon,title:title,desc:desc,read:false,time:Date.now()});n=n.slice(0,50);fetch("api.php?action=notifications&_csrf="+encodeURIComponent(window.__CSRF||""),{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(n)});});}

// === THEME CUSTOMIZER ===
var accentColors=["#6366f1","#8b5cf6","#ec4899","#ef4444","#f59e0b","#10b981","#06b6d4","#3b82f6"];
function openThemePanel(){var p=document.getElementById("themePanel");p.style.display=p.style.display==="block"?"none":"block";if(p.style.display==="block"){var c=document.getElementById("accentColors");c.innerHTML=accentColors.map(function(cl){return '<button onclick="setAccent(this.dataset.c)" data-c="'+cl+'" style="width:32px;height:32px;border-radius:50%;background:'+cl+';border:3px solid '+(getCurrentAccent()===cl?"var(--text-primary)":"transparent")+';cursor:pointer;transition:all .2s"></button>';}).join("");}}
function closeThemePanel(){document.getElementById("themePanel").style.display="none";}
function getCurrentAccent(){return __SRV.get("accentColor")||"#6366f1";}
function setAccent(c){__SRV.set("accentColor",c);document.documentElement.style.setProperty("--accent",c);openThemePanel();showToast("Accent color updated!","success");}
function setFontSize(s){document.documentElement.style.fontSize=s+"px";__SRV.set("fontSize",s);}
function toggleCompact(){var c=document.getElementById("compactMode").checked;document.body.classList.toggle("compact-mode",c);__SRV.set("compactMode",c);}
function toggleAnimations(){var on=document.getElementById("animationsToggle").checked;document.documentElement.style.setProperty("--transition",on?"all 0.3s cubic-bezier(0.4,0,0.2,1)":"none");__SRV.set("animations",on);}
(function(){var fs=__SRV.get("fontSize");if(fs)document.documentElement.style.fontSize=fs+"px";var cm=__SRV.get("compactMode");if(cm==="true"){document.getElementById("compactMode").checked=true;document.body.classList.add("compact-mode");}var an=__SRV.get("animations");if(an==="false"){document.getElementById("animationsToggle").checked=false;document.documentElement.style.setProperty("--transition","none");}})();

// === KEYBOARD SHORTCUTS ===
document.addEventListener("keydown",function(e){if(e.target.tagName==="INPUT"||e.target.tagName==="TEXTAREA"||e.target.tagName==="SELECT")return;switch(e.key){case "n":case "N":e.preventDefault();var fab=document.querySelector(".fab");if(fab)fab.click();break;case "/":e.preventDefault();var search=document.querySelector(".search-input,.filter-input,input[type=search]");if(search)search.focus();break;case "?":e.preventDefault();var el=document.getElementById("kbdHint");el.style.display=el.style.display==="flex"?"none":"flex";break;case "Escape":closeThemePanel();document.getElementById("notifPanel").style.display="none";document.getElementById("kbdHint").style.display="none";break;}});

// === SERVICE WORKER ===
if("serviceWorker" in navigator){navigator.serviceWorker.register("service-worker.js",{scope:"."}).catch(function(){});}

// === ADD NAV BUTTONS ===
function addProButtons(){var bar=document.querySelector(".auth-bar-right");if(!bar)return;
var notifBtn=document.createElement("a");notifBtn.href="#";notifBtn.textContent="🔔";notifBtn.style.cssText="margin-right:8px;font-size:18px;text-decoration:none";notifBtn.onclick=function(e){e.preventDefault();toggleNotifs();};
var themeBtn=document.createElement("a");themeBtn.href="#";themeBtn.textContent="🎨";themeBtn.style.cssText="margin-right:8px;font-size:18px;text-decoration:none";themeBtn.onclick=function(e){e.preventDefault();openThemePanel();};
bar.insertBefore(themeBtn,bar.firstChild);bar.insertBefore(notifBtn,themeBtn);
}
if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",addProButtons);}else{addProButtons();}
</script>
PROFEATURES;

$html = replaceLast($html, '</body>', $proFeatures . "\n</body>");

// No-cache headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo $html;
