<?php
session_start();

// Uncomment untuk proteksi session di production:
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }

// --- Database Config ---
$host   = "localhost";
$dbname = "game_topup";
$dbuser = "root";
$dbpass = "";

$user_data    = [];
$transactions = [];
$stats        = ['total_topup' => 0, 'total_transaksi' => 0, 'success_count' => 0, 'pending_count' => 0];
$top_games    = [];
$db_error     = false;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default user 1 for demo

    // User info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Stats
    $stmt = $pdo->prepare("SELECT
        COALESCE(SUM(amount),0) AS total_topup,
        COUNT(*) AS total_transaksi,
        SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) AS success_count,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending_count
        FROM transactions WHERE user_id = ?");
    $stmt->execute([$uid]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Recent transactions (last 6)
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([$uid]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top games
    $stmt = $pdo->prepare("SELECT game_name, COUNT(*) as cnt, SUM(amount) as total FROM transactions WHERE user_id = ? GROUP BY game_name ORDER BY cnt DESC LIMIT 4");
    $stmt->execute([$uid]);
    $top_games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly chart data (last 6 months)
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%b') as month, SUM(amount) as total FROM transactions WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY created_at ASC");
    $stmt->execute([$uid]);
    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = true;
    // Demo data fallback
    $user_data  = ['username' => 'gamer01', 'full_name' => 'Budi Santoso', 'email' => 'gamer01@gmail.com', 'balance' => 250000, 'role' => 'user'];
    $stats      = ['total_topup' => 1875000, 'total_transaksi' => 14, 'success_count' => 12, 'pending_count' => 2];
    $transactions = [
        ['id'=>1,'game_name'=>'Mobile Legends','item_name'=>'86 Diamonds','amount'=>19000,'status'=>'success','payment_method'=>'DANA','created_at'=>'2025-04-06 14:22:00'],
        ['id'=>2,'game_name'=>'Free Fire','item_name'=>'100 Diamonds','amount'=>16000,'status'=>'success','payment_method'=>'OVO','created_at'=>'2025-04-05 09:15:00'],
        ['id'=>3,'game_name'=>'PUBG Mobile','item_name'=>'60 UC','amount'=>20000,'status'=>'pending','payment_method'=>'GoPay','created_at'=>'2025-04-04 20:45:00'],
        ['id'=>4,'game_name'=>'Genshin Impact','item_name'=>'60 Primogems','amount'=>15000,'status'=>'success','payment_method'=>'BCA','created_at'=>'2025-04-03 11:00:00'],
        ['id'=>5,'game_name'=>'Mobile Legends','item_name'=>'172 Diamonds','amount'=>35000,'status'=>'success','payment_method'=>'DANA','created_at'=>'2025-04-02 16:30:00'],
        ['id'=>6,'game_name'=>'Valorant','item_name'=>'475 VP','amount'=>50000,'status'=>'failed','payment_method'=>'BRI','created_at'=>'2025-04-01 08:00:00'],
    ];
    $top_games  = [
        ['game_name'=>'Mobile Legends','cnt'=>6,'total'=>560000],
        ['game_name'=>'Free Fire','cnt'=>4,'total'=>320000],
        ['game_name'=>'PUBG Mobile','cnt'=>3,'total'=>420000],
        ['game_name'=>'Genshin Impact','cnt'=>1,'total'=>575000],
    ];
    $chart_data = [
        ['month'=>'Nov','total'=>180000],
        ['month'=>'Dec','total'=>320000],
        ['month'=>'Jan','total'=>250000],
        ['month'=>'Feb','total'=>410000],
        ['month'=>'Mar','total'=>295000],
        ['month'=>'Apr','total'=>420000],
    ];
}

// Format currency
function rupiah($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

$chart_labels = json_encode(array_column(isset($chart_data) ? $chart_data : array(), 'month'));
$chart_values = json_encode(array_column(isset($chart_data) ? $chart_data : array(), 'total'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameVault — Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary:    #00f0ff;
            --secondary:  #ff6b00;
            --accent:     #7b2fff;
            --green:      #00f064;
            --red:        #ff3250;
            --yellow:     #ffd700;
            --dark:       #060a14;
            --dark2:      #0a1220;
            --dark3:      #0d1828;
            --card-bg:    rgba(10,18,35,0.95);
            --border:     rgba(0,240,255,0.12);
            --border2:    rgba(0,240,255,0.06);
            --sidebar-w:  260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        html, body { height:100%; overflow-x:hidden; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--dark);
            color: #e0eaff;
        }

        /* ===== BACKGROUND ===== */
        .bg-scene {
            position: fixed; inset:0; z-index:0; pointer-events:none;
            background:
                radial-gradient(ellipse 70% 50% at 80% 20%, rgba(123,47,255,.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 10% 80%, rgba(0,240,255,.07) 0%, transparent 60%),
                var(--dark);
        }
        .grid-bg {
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background-image:
                linear-gradient(rgba(0,240,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,240,255,.025) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top:0; left:0; bottom:0;
            width: var(--sidebar-w);
            background: var(--card-bg);
            border-right: 1px solid var(--border);
            z-index: 100;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(20px);
            transition: transform .3s ease;
        }

        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid var(--border2);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            border-radius: 12px;
            display: flex; align-items:center; justify-content:center;
            font-size: 20px;
            box-shadow: 0 0 20px rgba(0,240,255,.35);
            flex-shrink: 0;
        }

        .logo-text {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px; font-weight: 900;
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-sub {
            font-size: 9px; color: rgba(255,255,255,.3);
            letter-spacing: 2px; text-transform: uppercase;
            font-weight: 300;
        }

        /* User card in sidebar */
        .sidebar-user {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border2);
        }

        .user-avatar {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--accent)80, var(--primary)80);
            border-radius: 14px;
            display: flex; align-items:center; justify-content:center;
            font-family:'Orbitron',sans-serif; font-size:18px; font-weight:900;
            color:#fff; border: 2px solid rgba(0,240,255,.3);
            flex-shrink:0;
        }

        .user-name {
            font-weight: 700; font-size: 15px; color:#fff;
            white-space: nowrap; overflow:hidden; text-overflow:ellipsis;
        }

        .user-role {
            font-size: 10px; letter-spacing:2px; text-transform:uppercase;
            color: var(--primary); font-weight:600;
        }

        .balance-badge {
            background: linear-gradient(90deg, rgba(0,240,255,.1), rgba(123,47,255,.1));
            border: 1px solid rgba(0,240,255,.2);
            border-radius: 8px;
            padding: 8px 12px;
            margin-top: 12px;
            display:flex; align-items:center; justify-content:space-between;
        }

        .balance-label { font-size:10px; color:rgba(255,255,255,.4); letter-spacing:1px; text-transform:uppercase; }
        .balance-value { font-family:'Orbitron',sans-serif; font-size:13px; color: var(--green); font-weight:700; }

        /* Nav */
        .sidebar-nav { flex:1; padding: 12px 0; overflow-y:auto; }

        .nav-section-title {
            font-size:9px; letter-spacing:3px; text-transform:uppercase;
            color: rgba(255,255,255,.2); padding: 14px 24px 6px;
            font-weight:700;
        }

        .nav-item a, .nav-item button {
            display:flex; align-items:center; gap:12px;
            padding: 11px 24px;
            color: rgba(255,255,255,.45);
            text-decoration:none; font-size:14px; font-weight:600;
            border:none; background:none; width:100%; cursor:pointer;
            transition: all .25s ease;
            position:relative;
            letter-spacing:.5px;
        }

        .nav-item a:hover, .nav-item button:hover {
            color: #fff;
            background: rgba(0,240,255,.06);
        }

        .nav-item a.active {
            color: var(--primary);
            background: rgba(0,240,255,.08);
        }

        .nav-item a.active::before {
            content:'';
            position:absolute; left:0; top:0; bottom:0;
            width:3px; background:var(--primary);
            border-radius:0 3px 3px 0;
            box-shadow: 0 0 10px var(--primary);
        }

        .nav-item i { font-size:17px; width:20px; text-align:center; }

        .nav-badge {
            margin-left:auto; background:var(--secondary);
            color:#fff; font-size:10px; font-weight:700;
            padding:1px 7px; border-radius:20px;
        }

        .sidebar-footer {
            padding:16px 24px;
            border-top:1px solid var(--border2);
        }

        .btn-logout {
            display:flex; align-items:center; gap:10px;
            width:100%; padding:10px 14px;
            background:rgba(255,50,80,.08);
            border:1px solid rgba(255,50,80,.2);
            border-radius:10px; color:#ff6680;
            font-family:'Rajdhani',sans-serif; font-size:13px; font-weight:700;
            cursor:pointer; transition:all .3s; letter-spacing:1px;
            text-transform:uppercase; text-decoration:none;
        }

        .btn-logout:hover {
            background:rgba(255,50,80,.15);
            color:#ff3250;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            position:relative; z-index:1;
        }

        /* Top Bar */
        .topbar {
            position:sticky; top:0; z-index:50;
            background: rgba(6,10,20,.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display:flex; align-items:center; justify-content:space-between;
        }

        .topbar-left { display:flex; align-items:center; gap:16px; }

        .page-title {
            font-family:'Orbitron',sans-serif;
            font-size:18px; font-weight:700;
            color:#fff; letter-spacing:2px;
        }

        .topbar-right { display:flex; align-items:center; gap:12px; }

        .topbar-btn {
            width:38px; height:38px;
            background:rgba(0,240,255,.06);
            border:1px solid var(--border);
            border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            color:rgba(255,255,255,.5); cursor:pointer;
            transition:all .3s; text-decoration:none;
            position:relative;
        }
        .topbar-btn:hover { background:rgba(0,240,255,.12); color:var(--primary); border-color:rgba(0,240,255,.3); }

        .notif-dot {
            position:absolute; top:6px; right:6px;
            width:8px; height:8px; background:var(--secondary);
            border-radius:50%; border:2px solid var(--dark);
        }

        /* Sidebar Toggle (mobile) */
        .sidebar-toggle {
            display:none;
            background:rgba(0,240,255,.06);
            border:1px solid var(--border);
            border-radius:10px;
            width:38px; height:38px;
            align-items:center; justify-content:center;
            color:#fff; cursor:pointer;
        }

        /* ===== PAGE BODY ===== */
        .page-body { padding: 28px 32px; }

        /* Greeting */
        .greeting-bar {
            margin-bottom:28px;
            animation: fadeInDown .6s ease;
        }

        @keyframes fadeInDown {
            from { opacity:0; transform:translateY(-16px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .greeting-title {
            font-family:'Orbitron',sans-serif;
            font-size:22px; font-weight:700; color:#fff;
        }

        .greeting-title span { color:var(--primary); }

        .greeting-sub { color:rgba(255,255,255,.35); font-size:14px; margin-top:4px; }

        /* ===== STAT CARDS ===== */
        .stats-row { margin-bottom:28px; }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius:16px;
            padding:22px 24px;
            position:relative; overflow:hidden;
            transition: transform .3s, box-shadow .3s;
            animation: fadeInUp .6s ease both;
            backdrop-filter:blur(12px);
        }

        .stat-card:hover {
            transform:translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,.5);
        }

        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(24px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .stat-card:nth-child(1) { animation-delay:.05s; }
        .stat-card:nth-child(2) { animation-delay:.1s; }
        .stat-card:nth-child(3) { animation-delay:.15s; }
        .stat-card:nth-child(4) { animation-delay:.2s; }

        .stat-card::before {
            content:'';
            position:absolute; top:0; left:0; right:0; height:2px;
        }
        .stat-card.c-cyan::before   { background:linear-gradient(90deg, transparent, var(--primary), transparent); }
        .stat-card.c-orange::before { background:linear-gradient(90deg, transparent, var(--secondary), transparent); }
        .stat-card.c-green::before  { background:linear-gradient(90deg, transparent, var(--green), transparent); }
        .stat-card.c-purple::before { background:linear-gradient(90deg, transparent, var(--accent), transparent); }

        .stat-icon {
            width:48px; height:48px; border-radius:14px;
            display:flex; align-items:center; justify-content:center;
            font-size:22px; margin-bottom:16px; flex-shrink:0;
        }
        .ic-cyan   { background:rgba(0,240,255,.1);  color:var(--primary);   box-shadow:0 0 20px rgba(0,240,255,.2);   }
        .ic-orange { background:rgba(255,107,0,.1);  color:var(--secondary); box-shadow:0 0 20px rgba(255,107,0,.2);   }
        .ic-green  { background:rgba(0,240,100,.1);  color:var(--green);     box-shadow:0 0 20px rgba(0,240,100,.2);   }
        .ic-purple { background:rgba(123,47,255,.1); color:var(--accent);    box-shadow:0 0 20px rgba(123,47,255,.2);  }

        .stat-label {
            font-size:10px; letter-spacing:2px; text-transform:uppercase;
            color:rgba(255,255,255,.35); font-weight:700;
        }

        .stat-value {
            font-family:'Orbitron',sans-serif;
            font-size:22px; font-weight:900; color:#fff;
            margin:6px 0 4px; line-height:1;
        }

        .stat-change {
            font-size:12px; font-weight:600;
            display:flex; align-items:center; gap:4px;
        }
        .stat-change.up   { color:var(--green); }
        .stat-change.down { color:var(--red); }

        .stat-bg-icon {
            position:absolute; right:16px; bottom:16px;
            font-size:60px; opacity:.04; pointer-events:none;
        }

        /* ===== CHART CARD ===== */
        .chart-card {
            background:var(--card-bg);
            border:1px solid var(--border);
            border-radius:16px;
            padding:24px;
            backdrop-filter:blur(12px);
            animation: fadeInUp .6s ease .25s both;
        }

        .card-header-custom {
            display:flex; align-items:center; justify-content:space-between;
            margin-bottom:20px;
        }

        .card-title-custom {
            font-family:'Orbitron',sans-serif;
            font-size:13px; font-weight:700; letter-spacing:2px;
            text-transform:uppercase; color:#fff;
        }

        .card-badge {
            font-size:10px; letter-spacing:1px; text-transform:uppercase;
            padding:4px 10px; border-radius:20px; font-weight:700;
        }
        .badge-cyan   { background:rgba(0,240,255,.1); color:var(--primary); border:1px solid rgba(0,240,255,.2); }
        .badge-orange { background:rgba(255,107,0,.1); color:var(--secondary); border:1px solid rgba(255,107,0,.2); }

        /* ===== TOP GAMES ===== */
        .game-item {
            display:flex; align-items:center; gap:14px;
            padding:14px 0;
            border-bottom:1px solid var(--border2);
            transition: all .2s;
        }
        .game-item:last-child { border-bottom:none; padding-bottom:0; }
        .game-item:first-child { padding-top:0; }

        .game-icon {
            width:40px; height:40px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            font-size:18px; flex-shrink:0;
        }

        .game-name  { font-size:14px; font-weight:700; color:#fff; }
        .game-count { font-size:12px; color:rgba(255,255,255,.35); }
        .game-amount { font-family:'Orbitron',sans-serif; font-size:12px; color:var(--primary); font-weight:700; margin-left:auto; }

        .progress-game {
            height:3px; border-radius:10px;
            background:rgba(255,255,255,.05);
            margin-top:6px;
        }

        .progress-fill {
            height:100%; border-radius:10px;
            transition: width 1s ease;
        }

        /* ===== TRANSACTIONS TABLE ===== */
        .tx-card {
            background:var(--card-bg);
            border:1px solid var(--border);
            border-radius:16px;
            overflow:hidden;
            backdrop-filter:blur(12px);
            animation: fadeInUp .6s ease .35s both;
        }

        .tx-header {
            padding:20px 24px;
            border-bottom:1px solid var(--border2);
            display:flex; align-items:center; justify-content:space-between;
        }

        .table-custom { margin:0; }

        .table-custom thead th {
            background:rgba(0,240,255,.04);
            color:rgba(255,255,255,.3);
            font-size:10px; letter-spacing:2px; text-transform:uppercase;
            font-weight:700; padding:12px 20px;
            border-bottom:1px solid var(--border2);
            border-top:none;
            white-space:nowrap;
        }

        .table-custom tbody td {
            padding:14px 20px;
            border-bottom:1px solid var(--border2);
            vertical-align:middle;
            color:rgba(255,255,255,.75);
            font-size:14px;
        }

        .table-custom tbody tr:last-child td { border-bottom:none; }
        .table-custom tbody tr:hover td { background:rgba(0,240,255,.03); }

        .tx-game { font-weight:700; color:#fff; }
        .tx-item { font-size:12px; color:rgba(255,255,255,.4); }

        .tx-amount { font-family:'Orbitron',sans-serif; font-size:13px; color:var(--primary); font-weight:700; }

        .status-badge {
            display:inline-flex; align-items:center; gap:5px;
            font-size:11px; font-weight:700; letter-spacing:1px;
            text-transform:uppercase; padding:4px 10px; border-radius:20px;
        }
        .st-success { background:rgba(0,240,100,.1);  color:var(--green); border:1px solid rgba(0,240,100,.25); }
        .st-pending { background:rgba(255,215,0,.1);  color:var(--yellow);border:1px solid rgba(255,215,0,.25); }
        .st-failed  { background:rgba(255,50,80,.1);  color:var(--red);   border:1px solid rgba(255,50,80,.25); }

        .payment-chip {
            background:rgba(255,255,255,.05);
            border:1px solid rgba(255,255,255,.1);
            border-radius:6px;
            padding:3px 8px; font-size:11px;
            color:rgba(255,255,255,.5); font-weight:600;
        }

        /* ===== TOP UP CTA ===== */
        .topup-cta {
            background: linear-gradient(135deg, rgba(123,47,255,.3), rgba(0,240,255,.15));
            border:1px solid rgba(0,240,255,.2);
            border-radius:16px;
            padding:24px;
            display:flex; align-items:center; justify-content:space-between;
            gap:20px;
            margin-bottom:28px;
            animation: fadeInUp .6s ease .1s both;
            position:relative; overflow:hidden;
        }

        .topup-cta::before {
            content:'';
            position:absolute; top:-50%; right:-10%;
            width:300px; height:300px;
            background:radial-gradient(circle, rgba(0,240,255,.08) 0%, transparent 70%);
            pointer-events:none;
        }

        .cta-title {
            font-family:'Orbitron',sans-serif;
            font-size:16px; font-weight:700; color:#fff;
            margin-bottom:6px;
        }

        .cta-sub { color:rgba(255,255,255,.4); font-size:13px; }

        .btn-topup {
            background:linear-gradient(135deg, var(--primary), #0080ff);
            border:none; border-radius:12px;
            font-family:'Orbitron',sans-serif;
            font-size:11px; font-weight:700; letter-spacing:2px;
            color:#fff; padding:13px 24px;
            cursor:pointer; white-space:nowrap;
            transition:all .3s; text-decoration:none; display:inline-block;
            box-shadow:0 4px 20px rgba(0,200,212,.35);
        }

        .btn-topup:hover { transform:translateY(-2px); box-shadow:0 8px 30px rgba(0,200,212,.5); color:#fff; }

        /* ===== QUICK GAMES GRID ===== */
        .games-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:28px; }

        .game-card {
            background:var(--card-bg);
            border:1px solid var(--border);
            border-radius:14px;
            padding:18px 14px;
            text-align:center;
            cursor:pointer;
            transition:all .3s;
            animation: fadeInUp .6s ease both;
        }

        .game-card:hover {
            border-color:rgba(0,240,255,.35);
            background:rgba(0,240,255,.05);
            transform:translateY(-4px);
            box-shadow:0 10px 30px rgba(0,0,0,.4);
        }

        .game-card-icon {
            width:52px; height:52px; border-radius:14px;
            display:flex; align-items:center; justify-content:center;
            font-size:24px; margin:0 auto 12px;
        }

        .game-card-name { font-size:12px; font-weight:700; color:#fff; }
        .game-card-sub  { font-size:10px; color:rgba(255,255,255,.3); margin-top:2px; }

        /* ===== RESPONSIVE ===== */
        @media (max-width:991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content { margin-left:0; }
            .sidebar-toggle { display:flex; }
            .page-body { padding:20px 16px; }
            .topbar { padding:14px 16px; }
            .games-grid { grid-template-columns:repeat(2,1fr); }
        }

        @media (max-width:576px) {
            .games-grid { grid-template-columns:repeat(2,1fr); }
            .topup-cta { flex-direction:column; align-items:flex-start; }
        }

        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display:none;
            position:fixed; inset:0; z-index:99;
            background:rgba(0,0,0,.6); backdrop-filter:blur(4px);
        }
        .sidebar-overlay.open { display:block; }

        /* Scrollbar */
        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-track { background:var(--dark2); }
        ::-webkit-scrollbar-thumb { background:rgba(0,240,255,.2); border-radius:10px; }
        ::-webkit-scrollbar-thumb:hover { background:rgba(0,240,255,.4); }

        /* Demo alert */
        .demo-alert {
            background:rgba(255,107,0,.1);
            border:1px solid rgba(255,107,0,.25);
            border-radius:10px;
            padding:10px 16px;
            font-size:13px; color:var(--secondary);
            margin-bottom:20px; display:flex; align-items:center; gap:8px;
        }
    </style>
</head>
<body>

<div class="bg-scene"></div>
<div class="grid-bg"></div>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<nav class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="bi bi-controller text-white"></i></div>
            <div>
                <div class="logo-text">GameVault</div>
                <div class="logo-sub">Top Up Platform</div>
            </div>
        </div>
    </div>

    <!-- User Card -->
<div class="sidebar-user">
    <div class="d-flex align-items-center gap-3">
        <div class="user-avatar">
            <?php
            $username = isset($user_data['username']) ? $user_data['username'] : 'G';
            echo strtoupper(substr($username, 0, 1));
            ?>
        </div>

        <div style="min-width:0">
            <div class="user-name">
                <?php
                if (isset($user_data['full_name']) && $user_data['full_name'] != '') {
                    echo htmlspecialchars($user_data['full_name']);
                } elseif (isset($user_data['username'])) {
                    echo htmlspecialchars($user_data['username']);
                } else {
                    echo 'Gamer';
                }
                ?>
            </div>

            <div class="user-role">
                <?php
                echo htmlspecialchars(isset($user_data['role']) ? $user_data['role'] : 'user');
                ?>
            </div>
        </div>
    </div>

    <div class="balance-badge">
        <div>
            <div class="balance-label">Saldo</div>
            <div class="balance-value">
                <?php
                echo rupiah(isset($user_data['balance']) ? $user_data['balance'] : 0);
                ?>
            </div>
        </div>
        <i class="bi bi-wallet2 text-white" style="opacity:.4;font-size:20px"></i>
    </div>
</div>

    <!-- Nav -->
    <div class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <div class="nav-item">
            <a href="dashboard.php" class="active">
                <i class="bi bi-grid-fill"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="topup.php">
                <i class="bi bi-lightning-charge-fill"></i> Top Up
                <span class="nav-badge">HOT</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="riwayat.php">
                <i class="bi bi-clock-history"></i> Riwayat Transaksi
            </a>
        </div>
        <div class="nav-item">
            <a href="voucher.php">
                <i class="bi bi-ticket-perforated"></i> Voucher
            </a>
        </div>

        <div class="nav-section-title">Akun</div>
        <div class="nav-item">
            <a href="profil.php">
                <i class="bi bi-person-fill"></i> Profil Saya
            </a>
        </div>
        <div class="nav-item">
            <a href="deposit.php">
                <i class="bi bi-plus-circle-fill"></i> Deposit Saldo
            </a>
        </div>
        <div class="nav-item">
            <a href="pengaturan.php">
                <i class="bi bi-gear-fill"></i> Pengaturan
            </a>
        </div>
    </div>

    <!-- Logout -->
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-left"></i> Keluar
        </a>
    </div>
</nav>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div class="page-title">DASHBOARD</div>
        </div>
        <div class="topbar-right">
            <a href="topup.php" class="topbar-btn" title="Top Up">
                <i class="bi bi-lightning-charge-fill" style="color:var(--secondary)"></i>
            </a>
            <div class="topbar-btn" title="Notifikasi" style="cursor:pointer">
                <i class="bi bi-bell-fill"></i>
                <span class="notif-dot"></span>
            </div>
            <a href="profil.php" class="topbar-btn" title="Profil">
                <i class="bi bi-person-fill"></i>
            </a>
        </div>
    </div>

    <!-- Page Body -->
    <div class="page-body">

        <?php if ($db_error): ?>
        <div class="demo-alert">
            <i class="bi bi-info-circle-fill"></i>
            Mode Demo — Database tidak terkoneksi. Menampilkan data contoh.
        </div>
        <?php endif; ?>

        <!-- Greeting -->
<div class="greeting-bar">
    <div class="greeting-title">
        Halo, 
        <span>
            <?php
            if (isset($user_data['full_name']) && $user_data['full_name'] != '') {
                echo htmlspecialchars($user_data['full_name']);
            } elseif (isset($user_data['username'])) {
                echo htmlspecialchars($user_data['username']);
            } else {
                echo 'Gamer';
            }
            ?>
        </span> 👾
    </div>

    <div class="greeting-sub">
        <?php echo date('l, d F Y'); ?> &nbsp;·&nbsp; Semangat top up hari ini!
    </div>
</div>

<!-- CTA Banner -->
<div class="topup-cta">
    <div>
        <div class="cta-title">⚡ Top Up Sekarang & Dapatkan Bonus</div>
        <div class="cta-sub">Promo spesial: Bonus 5% untuk setiap top up di atas Rp 50.000 hari ini!</div>
    </div>
    <a href="topup.php" class="btn-topup">
        <i class="bi bi-lightning-charge-fill me-2"></i>TOP UP SEKARANG
    </a>
</div>
        

        <!-- Chart + Top Games -->
       <!-- Stat Cards -->
<div class="row g-3 stats-row">

    <div class="col-6 col-xl-3">
        <div class="stat-card c-cyan">
            <div class="stat-icon ic-cyan"><i class="bi bi-wallet-fill"></i></div>
            <div class="stat-label">Total Top Up</div>
            <div class="stat-value">
                <?php echo rupiah(isset($stats['total_topup']) ? $stats['total_topup'] : 0); ?>
            </div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i>+12% bulan ini</div>
            <i class="bi bi-wallet-fill stat-bg-icon"></i>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card c-orange">
            <div class="stat-icon ic-orange"><i class="bi bi-receipt"></i></div>
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value">
                <?php echo number_format(isset($stats['total_transaksi']) ? $stats['total_transaksi'] : 0); ?>
            </div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i>+3 transaksi baru</div>
            <i class="bi bi-receipt stat-bg-icon"></i>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card c-green">
            <div class="stat-icon ic-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-label">Berhasil</div>
            <div class="stat-value">
                <?php echo number_format(isset($stats['success_count']) ? $stats['success_count'] : 0); ?>
            </div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i>Tingkat sukses tinggi</div>
            <i class="bi bi-check-circle stat-bg-icon"></i>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card c-purple">
            <div class="stat-icon ic-purple"><i class="bi bi-piggy-bank-fill"></i></div>
            <div class="stat-label">Saldo Aktif</div>
            <div class="stat-value">
                <?php echo rupiah(isset($user_data['balance']) ? $user_data['balance'] : 0); ?>
            </div>
            <div class="stat-change"><i class="bi bi-dash"></i>Saldo tersedia</div>
            <i class="bi bi-piggy-bank stat-bg-icon"></i>
        </div>
    </div>

</div>

<!-- Quick Game Access -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="card-title-custom">Game Populer</div>
    <a href="topup.php" style="font-size:12px;color:var(--primary);text-decoration:none;font-weight:700;letter-spacing:1px">Lihat Semua →</a>
</div>

<div class="games-grid mb-4">
<?php
$games_quick = array(
    array('name'=>'Mobile Legends','sub'=>'Top Up Diamond','icon'=>'🎮','grad'=>'linear-gradient(135deg,#1a1464,#2980b9)'),
    array('name'=>'Free Fire','sub'=>'Top Up Diamond','icon'=>'🔥','grad'=>'linear-gradient(135deg,#f39c12,#e74c3c)'),
    array('name'=>'PUBG Mobile','sub'=>'Top Up UC','icon'=>'🎯','grad'=>'linear-gradient(135deg,#f4a200,#e74c3c)'),
    array('name'=>'Genshin Impact','sub'=>'Top Up Genesis','icon'=>'✨','grad'=>'linear-gradient(135deg,#6f42c1,#0dcaf0)'),
    array('name'=>'Valorant','sub'=>'Top Up VP','icon'=>'🔺','grad'=>'linear-gradient(135deg,#ff4655,#ff8c69)'),
    array('name'=>'Clash of Clans','sub'=>'Top Up Gems','icon'=>'💎','grad'=>'linear-gradient(135deg,#1a8cff,#00c6ff)'),
    array('name'=>'Honkai: SR','sub'=>'Top Up Stellar','icon'=>'⭐','grad'=>'linear-gradient(135deg,#a855f7,#6366f1)'),
    array('name'=>'Roblox','sub'=>'Top Up Robux','icon'=>'🎲','grad'=>'linear-gradient(135deg,#e74c3c,#c0392b)')
);

$i = 0;
foreach ($games_quick as $g) {
?>
    <div class="game-card" style="animation-delay:<?php echo $i * 0.05; ?>s">
        <div class="game-card-icon" style="background:<?php echo $g['grad']; ?>">
            <?php echo $g['icon']; ?>
        </div>
        <div class="game-card-name"><?php echo $g['name']; ?></div>
        <div class="game-card-sub"><?php echo $g['sub']; ?></div>
    </div>
<?php
    $i++;
}
?>
</div>

        <!-- Recent Transactions -->
<div class="tx-card">
    <div class="tx-header">
        <div class="card-title-custom">Transaksi Terbaru</div>
        <a href="riwayat.php" style="font-size:12px;color:var(--primary);text-decoration:none;font-weight:700;letter-spacing:1px">Lihat Semua →</a>
    </div>

    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Game</th>
                    <th>Item</th>
                    <th>Nominal</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($transactions)) { ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:rgba(255,255,255,.25);padding:40px">
                        Belum ada transaksi
                    </td>
                </tr>
            <?php } else { ?>

                <?php foreach ($transactions as $tx) { ?>
                <tr>

                    <td style="color:rgba(255,255,255,.3);font-size:12px">
                        <?php echo str_pad($tx['id'], 4, '0', STR_PAD_LEFT); ?>
                    </td>

                    <td>
                        <div class="tx-game">
                            <?php echo htmlspecialchars($tx['game_name']); ?>
                        </div>
                    </td>

                    <td>
                        <div class="tx-item">
                            <?php echo htmlspecialchars($tx['item_name']); ?>
                        </div>
                    </td>

                    <td>
                        <span class="tx-amount">
                            <?php echo rupiah($tx['amount']); ?>
                        </span>
                    </td>

                    <td>
                        <span class="payment-chip">
                            <?php echo htmlspecialchars($tx['payment_method']); ?>
                        </span>
                    </td>

                    <td>
                        <?php if ($tx['status'] == 'success') { ?>
                            <span class="status-badge st-success">
                                <i class="bi bi-check-circle-fill"></i>Berhasil
                            </span>
                        <?php } elseif ($tx['status'] == 'pending') { ?>
                            <span class="status-badge st-pending">
                                <i class="bi bi-clock-fill"></i>Pending
                            </span>
                        <?php } else { ?>
                            <span class="status-badge st-failed">
                                <i class="bi bi-x-circle-fill"></i>Gagal
                            </span>
                        <?php } ?>
                    </td>

                    <td style="font-size:12px;color:rgba(255,255,255,.35)">
                        <?php echo date('d M Y', strtotime($tx['created_at'])); ?><br>
                        <span style="font-size:11px">
                            <?php echo date('H:i', strtotime($tx['created_at'])); ?>
                        </span>
                    </td>

                </tr>
                <?php } ?>

            <?php } ?>

            </tbody>
        </table>
    </div>
</div>

        <!-- Footer -->
<div style="text-align:center;padding:28px 0 8px;color:rgba(255,255,255,.15);font-size:12px;letter-spacing:1px">
    © 2025 GameVault &nbsp;·&nbsp; All rights reserved
</div>

</div><!-- /page-body -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Chart

var labels = <?php echo isset($chart_labels) ? $chart_labels : '["Nov","Des","Jan","Feb","Mar","Apr"]'; ?>;
var values = <?php echo isset($chart_values) ? $chart_values : '[180000,320000,250000,410000,295000,420000]'; ?>;

var ctx = document.getElementById('spendChart').getContext('2d');

var grad = ctx.createLinearGradient(0, 0, 0, 280);
grad.addColorStop(0, 'rgba(0,240,255,0.3)');
grad.addColorStop(1, 'rgba(0,240,255,0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Pengeluaran',
            data: values,
            fill: true,
            backgroundColor: grad,
            borderColor: '#00f0ff',
            borderWidth: 2.5,
            pointBackgroundColor: '#00f0ff',
            pointBorderColor: '#060a14',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 8,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(10,18,35,0.95)',
                borderColor: 'rgba(0,240,255,0.3)',
                borderWidth: 1,
                titleColor: '#00f0ff',
                bodyColor: '#fff',
                padding: 12,
                callbacks: {
                    label: function(ctx) {
                        return ' Rp ' + ctx.raw.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: {
                    color: 'rgba(255,255,255,0.35)',
                    font: { size: 12 }
                }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: {
                    color: 'rgba(255,255,255,0.35)',
                    callback: function(v) {
                        return 'Rp ' + (v / 1000).toFixed(0) + 'k';
                    }
                }
            }
        }
    }
});

// Sidebar toggle
function toggleSidebar() {
    var sb = document.getElementById('sidebar');
    var ov = document.getElementById('sidebarOverlay');

    if (sb.classList.contains('open')) {
        sb.classList.remove('open');
        ov.classList.remove('open');
    } else {
        sb.classList.add('open');
        ov.classList.add('open');
    }
}
</script>

</body>
</html>