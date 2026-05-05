<?php 
include('db.php'); 

$total_users   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM utilisateur"))['count'];
$parents_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM utilisateur WHERE role='parent'"))['count'];
$doctors_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM utilisateur WHERE role='docteur'"))['count'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM utilisateur WHERE statut='en_attente'"))['count'];

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action == 'accept') {
        $sql = "UPDATE utilisateur SET statut = 'actif' WHERE id_utilisateur = $id";
    } elseif ($action == 'refuse') {
        $sql = "DELETE FROM utilisateur WHERE id_utilisateur = $id";
    }
    if (isset($sql)) mysqli_query($conn, $sql);
    header("Location: tableaudebord.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="admin-vaccines.css">
  <style>
  /* ════════════════════════════════════════
     KEYFRAMES
  ════════════════════════════════════════ */
  @keyframes fadeInDown {
    from { opacity:0; transform:translateY(-22px); }
    to   { opacity:1; transform:translateY(0); }
  }
  @keyframes fadeInUp {
    from { opacity:0; transform:translateY(25px); }
    to   { opacity:1; transform:translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity:0; }
    to   { opacity:1; }
  }
  @keyframes scaleIn {
    from { opacity:0; transform:scale(.92); }
    to   { opacity:1; transform:scale(1); }
  }
  @keyframes slideInLeft {
    from { opacity:0; transform:translateX(-30px); }
    to   { opacity:1; transform:translateX(0); }
  }
  @keyframes shimmer {
    0%   { background-position:200% center; }
    100% { background-position:-200% center; }
  }
  @keyframes float {
    0%,100% { transform:translateY(0); }
    50%     { transform:translateY(-7px); }
  }
  @keyframes countPop {
    0%  { transform:scale(.5); opacity:0; }
    70% { transform:scale(1.12); }
    100%{ transform:scale(1);  opacity:1; }
  }
  @keyframes slideDown {
    from { transform:translateY(-12px); opacity:0; }
    to   { transform:translateY(0);     opacity:1; }
  }
  @keyframes gradientBG {
    0%   { background-position:0% 50%; }
    50%  { background-position:100% 50%; }
    100% { background-position:0% 50%; }
  }

  /* ════════════════════════════════════════
     SIDEBAR
  ════════════════════════════════════════ */
  .sidebar {
    animation: slideInLeft .5s ease both;
    background: linear-gradient(180deg, #1a237e 0%, #283593 60%, #1565c0 100%) !important;
  }
  .logo-icon { animation: float 3s ease-in-out infinite; }
  .logo-text .text-blue {
    background: linear-gradient(90deg, #90caf9, #e3f2fd, #90caf9);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: shimmer 3s linear infinite;
  }
  .sidebar-nav a {
    transition: all .25s ease;
    position: relative;
    overflow: hidden;
  }
  .sidebar-nav a::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: #90caf9;
    transform: scaleY(0);
    transition: transform .25s ease;
    border-radius: 0 3px 3px 0;
  }
  .sidebar-nav a:hover::before,
  .sidebar-nav a.active::before { transform: scaleY(1); }
  .sidebar-nav a:hover {
    transform: translateX(5px);
    background: rgba(255,255,255,.12) !important;
  }

  /* ════════════════════════════════════════
     TOPBAR
  ════════════════════════════════════════ */
  .admin-topbar { animation: fadeInDown .5s .1s ease both; }

  /* ════════════════════════════════════════
     STAT CARDS
  ════════════════════════════════════════ */
  .admin-stat-card {
    transition: transform .3s ease, box-shadow .3s ease;
    animation: scaleIn .5s ease both;
  }
  .admin-stat-card:hover {
    transform: translateY(-7px) scale(1.02);
    box-shadow: 0 14px 32px rgba(0,0,0,.12);
  }
  .admin-stats .admin-stat-card:nth-child(1) { animation-delay:.1s; }
  .admin-stats .admin-stat-card:nth-child(2) { animation-delay:.18s; }
  .admin-stats .admin-stat-card:nth-child(3) { animation-delay:.26s; }
  .admin-stats .admin-stat-card:nth-child(4) { animation-delay:.34s; }

  .admin-stat-num {
    display: block;
    animation: countPop .6s .5s ease both;
  }
  .admin-stat-icon {
    transition: transform .3s ease;
  }
  .admin-stat-card:hover .admin-stat-icon {
    transform: scale(1.18) rotate(8deg);
  }

  /* Badge count */
  .badge-count {
    background: #e53e3e; color: #fff; font-size: 10px; font-weight: 800;
    padding: 2px 6px; border-radius: 50px; margin-left: 8px;
    display: inline-block; line-height: 1; vertical-align: middle;
    animation: countPop .5s ease both;
  }
  </style>
</head>
<body class="admin-blue">
<div class="app-wrapper">

  <aside class="sidebar">
    <div class="logo">
      <div class="logo-icon">💉</div>
      <span class="logo-text">Talqih<span class="text-blue">Sghiri</span></span>
    </div>
    <nav class="sidebar-nav">
      <a href="tableaudebord.php" class="active">📊 Tableau de bord</a>
      <a href="admin-vaccines.php">🧬 Gestion Vaccins</a>
      <a href="gestion-utilisateur.php">👥 Gestion des Utilisateurs</a>
      <a href="notification-admin.php">🔔 Notifications
        <?php if ($pending_count > 0): ?>
          <span class="badge-count"><?= $pending_count ?></span>
        <?php endif; ?>
      </a>
      <a href="login-admin.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">
    <div class="admin-topbar">
      <div>
        <h1>📊 Tableau de bord</h1>
        <p>Gérez les comptes des parents et des médecins sur la plateforme</p>
      </div>
      <div class="admin-user">
        <div><div class="admin-role">Administrateur</div></div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

    <div class="admin-stats">
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-blue">👥</div>
        <div>
          <span class="admin-stat-num"><?= $total_users ?></span>
          <span class="admin-stat-lbl">Total Utilisateurs</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-green">👨‍👩‍👧</div>
        <div>
          <span class="admin-stat-num"><?= $parents_count ?></span>
          <span class="admin-stat-lbl">Parents</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-red">👨‍⚕️</div>
        <div>
          <span class="admin-stat-num"><?= $doctors_count ?></span>
          <span class="admin-stat-lbl">Docteurs</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-red">🔔</div>
        <div>
          <span class="admin-stat-num"><?= $pending_count ?></span>
          <span class="admin-stat-lbl">Notifications inscriptions</span>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
setTimeout(() => {
  document.querySelectorAll('.flash-alert').forEach(a => {
    a.style.transition = 'opacity .5s';
    a.style.opacity = '0';
    setTimeout(() => a.remove(), 500);
  });
}, 4000);
</script>
</body>
</html>