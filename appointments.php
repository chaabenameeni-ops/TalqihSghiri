<?php
session_start();
include('db.php');

if (empty($_SESSION['email'])) { header("Location: loginp.php"); exit(); }

$nom_pa    = $_SESSION['nom']    ?? '';
$prenom_pa = $_SESSION['prenom'] ?? '';
$email     = $_SESSION['email'];

// Récupérer id_parent si absent de session
if (empty($_SESSION['id_parent'])) {
    $rp = mysqli_query($conn, "SELECT id_parent FROM parent WHERE email='$email'");
    $up = $rp ? mysqli_fetch_assoc($rp) : null;
    if (!$up) { session_destroy(); header("Location: loginp.php"); exit(); }
    $_SESSION['id_parent'] = $up['id_parent'];
}
$r4 = mysqli_query($conn, "SELECT COUNT(*) total FROM notification WHERE email='$email' AND statut='Non lu'");
$total_notifs = $r4 ? mysqli_fetch_assoc($r4)['total'] : 0;

$id_parent = $_SESSION['id_parent'];

// Récupérer les RDV — jointure avec "vaccin" (pas "vaccins")
$sql_rdv = "
    SELECT r.*, e.nom_enfant, e.prenom_enfant, v.nom_complet AS nom_vaccin,
           d.nom_docteur, d.prenom_docteur
    FROM rendez_vous r
    JOIN enfant e  ON r.id_enfant  = e.id_enfant
    LEFT JOIN vaccin v  ON r.id_vaccin = v.id_vaccin
    LEFT JOIN docteur d ON r.id_docteur = d.id_docteur
    WHERE r.id_parent = '$id_parent'
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
";
//$sql_rdv="select* from rendez_vous WHERE id_parent = '$id_parent'
  //  ORDER BY date_rdv DESC, heure_rdv DESC";
$res_rdv = mysqli_query($conn, $sql_rdv);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rendez-vous — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .empty-state-wrapper { display:flex; justify-content:center; align-items:center; min-height:450px; width:100%; padding:20px; }
    .empty-state-card {
      background:#fff; padding:40px; border-radius:30px;
      text-align:center; max-width:450px; width:100%;
      box-shadow:0 15px 35px rgba(0,0,0,.05); border:1px solid #f0f0f0;
    }
    .empty-state-card::before { content:"📅"; font-size:4.5rem; display:block; margin-bottom:20px; }
    .empty-state-card h3 { color:var(--danger,#e74c3c); font-size:1.5rem; font-weight:700; margin-bottom:15px; }
    .empty-state-card p  { color:#64748b; line-height:1.7; font-size:1rem; margin:0; }
    .empty-state-card b  { color:var(--pink,#e84393); font-weight:600; }
.notif-badge { background:#e53e3e; color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:50px; margin-left:4px; display:inline-block; line-height:1; vertical-align:middle; }

    .apt-card { display:flex; width:100%; align-items:center; gap:1rem; margin-bottom:1rem; }
    .badge-pink { background:hsla(330,80%,60%,.15); color:#d63384; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:700; }
    .badge-gray { background:#f1f5f9; color:#64748b; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:700; }
    .badge-green { background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:700; }
    .apt-meta   { font-size:.82rem; color:#64748b; margin-top:.2rem; display:flex; gap:.8rem; flex-wrap:wrap; }
  </style>
</head>
<body>
<div class="app-wrapper">
  <aside class="sidebar">
    <div class="logo">
      <div class="logo-icon">💉</div>
      <span class="logo-text">Talqih<span class="text-pink">Sghiri</span></span>
    </div>
    <nav class="sidebar-nav">
      <a href="parent.php">📊 Tableau de bord</a>
      <a href="children.php">👶 Mes enfants</a>
      <a href="appointments.php" class="active">📅 Rendez-vous</a>
        <a href="notifications.php">🔔 Notifications
        <?php if ($total_notifs > 0): ?>
          <span class="notif-badge"><?= $total_notifs ?></span>
        <?php endif; ?>
      </a>
      <a href="index.php" id="logoutBtn">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">
    <h1 class="page-title">Salut, <?= htmlspecialchars($nom_pa . ' ' . $prenom_pa) ?> ! 👋</h1>
    <h1 class="page-title">Rendez-vous 📅</h1>
    <p class="page-sub">Gérez les rendez-vous de vaccination</p>

    <a href="new-appointment.php" class="btn btn-primary btn-round" style="margin-bottom:2rem;display:inline-block;">
      + Nouveau
    </a>

    <?php if ($res_rdv): ?>
    <div style="display:flex;flex-direction:column;gap:1rem;">
      <?php 

      while ($rdv = mysqli_fetch_assoc($res_rdv)):
        
        $badge = match(strtolower($rdv['statut'] ?? '')) {
          'terminé'    => 'badge-green',
          'confirmé'   => 'badge-pink',
          default      => 'badge-gray',
        };
        $is_past = (strtotime($rdv['date_rdv']) < strtotime('today'));
      ?>
      <div class="card apt-card" style="<?= ($is_past && $rdv['statut'] !== 'Confirmé') ? 'opacity:.7;' : '' ?>">
        <span style="font-size:1.6rem;"><?= ($rdv['genre'] ?? '') === 'garcon' ? '👦' : '👧' ?></span>
        <div style="flex:1;">
          <div style="display:flex;justify-content:space-between;align-items:start;">
            <div>
              <strong>
                <?= htmlspecialchars($rdv['prenom_enfant'] . ' ' . $rdv['nom_enfant']) ?>
                — <?= htmlspecialchars($rdv['nom_vaccin'] ?? 'Vaccin non précisé') ?>
              </strong>
              <div class="apt-meta">
                <span>📅 <?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></span>
                <span>🕐 <?= substr($rdv['heure_rdv'], 0, 5) ?></span>
                <?php if (!empty($rdv['nom_docteur'])): ?>
                  <span>👨‍⚕️ Dr. <?= htmlspecialchars($rdv['prenom_docteur'] . ' ' . $rdv['nom_docteur']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <span class="badge <?= $badge ?>"><?= htmlspecialchars($rdv['statut']) ?></span>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="empty-state-wrapper">
      <div class="empty-state-card">
        <h3>Aucun rendez-vous trouvé</h3>
        <p>Vous n'avez actuellement aucun rendez-vous planifié.<br>
           Cliquez sur <b>+ Nouveau</b> pour en planifier un.</p>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>
</body>
</html>