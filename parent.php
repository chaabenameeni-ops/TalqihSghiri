<?php
session_start();
include('db.php');
include_once('auto-trigger.php'); // notifications automatiques

if (empty($_SESSION['email'])) { header("Location: loginp.php"); exit(); }

$nom_pa    = $_SESSION['nom']    ?? '';
$prenom_pa = $_SESSION['prenom'] ?? '';
$email     = $_SESSION['email'];

$res_p = mysqli_query($conn, "SELECT id_parent FROM parent WHERE email='$email'");
$user  = $res_p ? mysqli_fetch_assoc($res_p) : null;
if (!$user) { session_destroy(); header("Location: loginp.php"); exit(); }
$id_parent = $user['id_parent'];
$_SESSION['id_parent'] = $id_parent;

$r1 = mysqli_query($conn, "SELECT COUNT(*) total FROM rendez_vous WHERE id_parent='$id_parent' AND statut='Terminé'");
$total_completes = $r1 ? mysqli_fetch_assoc($r1)['total'] : 0;

$r2 = mysqli_query($conn, "SELECT COUNT(*) total FROM rendez_vous WHERE id_parent='$id_parent' AND date_rdv >= CURDATE() AND statut != 'Terminé'");
$total_avenir = $r2 ? mysqli_fetch_assoc($r2)['total'] : 0;

// Retard = vaccination prévue dépassée NON réalisée, enfants de CE parent uniquement
$r3 = mysqli_query($conn,
    "SELECT COUNT(*) total FROM vaccination v
     JOIN enfant e ON e.id_enfant = v.id_enfant
     WHERE e.id_parent = '$id_parent' AND v.statut = 'Prévu' AND v.date_prevue < CURDATE()");
$total_retard = $r3 ? mysqli_fetch_assoc($r3)['total'] : 0;

$r4 = mysqli_query($conn, "SELECT COUNT(*) total FROM notification WHERE email='$email' AND statut='Non lu'");
$total_notifs = $r4 ? mysqli_fetch_assoc($r4)['total'] : 0;

$res_next = mysqli_query($conn,
    "SELECT r.*, e.nom_enfant, e.prenom_enfant, d.nom_docteur, d.prenom_docteur
     FROM rendez_vous r
     JOIN enfant e  ON r.id_enfant  = e.id_enfant
     JOIN docteur d ON r.id_docteur = d.id_docteur
     WHERE r.id_parent = '$id_parent' AND r.date_rdv >= CURDATE() AND r.statut != 'Terminé'
     ORDER BY r.date_rdv ASC, r.heure_rdv ASC LIMIT 1");
$next_rdv = ($res_next && mysqli_num_rows($res_next) > 0) ? mysqli_fetch_assoc($res_next) : null;

$res_rd = mysqli_query($conn,
    "SELECT v.date_prevue, e.nom_enfant, e.prenom_enfant, vac.nom_complet AS nom_vaccin
     FROM vaccination v
     JOIN enfant e ON e.id_enfant = v.id_enfant
     LEFT JOIN vaccin vac ON vac.id_vaccin = v.id_vaccin
     WHERE e.id_parent = '$id_parent' AND v.statut = 'Prévu' AND v.date_prevue < CURDATE()
     ORDER BY v.date_prevue ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .retard-alert {
      background:#fff5f5; border:1.5px solid #feb2b2;
      border-left:4px solid #e53e3e; border-radius:14px;
      padding:1rem 1.2rem; margin-bottom:1.5rem;
    }
    .retard-alert-title {
      font-family:'Nunito',sans-serif; font-weight:800;
      font-size:.95rem; color:#c53030; margin-bottom:.6rem;
    }
    .retard-item {
      display:flex; align-items:center; gap:.6rem;
      padding:.4rem 0; border-bottom:1px solid #fed7d7;
      font-size:.85rem; color:#742a2a; flex-wrap:wrap;
    }
    .retard-item:last-child { border-bottom:none; }
    .retard-date { font-size:.75rem; color:#e53e3e; font-weight:700; margin-left:auto; }
    .notif-badge {
      background:#e53e3e; color:#fff; font-size:10px; font-weight:800;
      padding:2px 6px; border-radius:50px; margin-left:4px;
      display:inline-block; line-height:1; vertical-align:middle;
    }
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
      <a href="parent.php" class="active">📊 Tableau de bord</a>
      <a href="children.php">👶 Mes enfants</a>
      <a href="appointments.php">📅 Rendez-vous</a>
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
    <p class="page-sub">Voici un résumé de la santé de vos enfants</p>

    <div class="card-grid">
      <div class="card stat-card">
        <div><span class="stat-icon" style="color:var(--success)">✅</span><span class="stat-lbl">Complétés</span></div>
        <strong class="stat-num"><?= $total_completes ?></strong>
      </div>
      <div class="card stat-card">
        <div><span class="stat-icon" style="color:var(--pink)">🕐</span><span class="stat-lbl">À venir</span></div>
        <strong class="stat-num"><?= $total_avenir ?></strong>
      </div>
      <div class="card stat-card">
        <div><span class="stat-icon" style="color:var(--danger)">⚠️</span><span class="stat-lbl">En retard</span></div>
        <strong class="stat-num" style="<?= $total_retard > 0 ? 'color:#e53e3e;' : '' ?>"><?= $total_retard ?></strong>
      </div>
      <div class="card stat-card">
        <div><span class="stat-icon" style="color:var(--warning)">🔔</span><span class="stat-lbl">Notifications</span></div>
        <strong class="stat-num"><?= $total_notifs ?></strong>
      </div>
    </div>

    <?php if ($total_retard > 0 && $res_rd && mysqli_num_rows($res_rd) > 0): ?>
    <div class="retard-alert">
      <div class="retard-alert-title">⚠️ Vaccinations en retard — Action requise</div>
      <?php while ($rd = mysqli_fetch_assoc($res_rd)): ?>
      <div class="retard-item">
        <span>👶 <strong><?= htmlspecialchars($rd['prenom_enfant'] . ' ' . $rd['nom_enfant']) ?></strong></span>
        <span>💉 <?= htmlspecialchars($rd['nom_vaccin'] ?? 'Vaccin non précisé') ?></span>
        <span class="retard-date">Prévu le <?= date('d/m/Y', strtotime($rd['date_prevue'])) ?></span>
      </div>
      <?php endwhile; ?>
      <div style="margin-top:.75rem;">
        <a href="appointments.php" class="btn btn-outline btn-sm" style="font-size:.8rem;border-color:#e53e3e;color:#e53e3e;">
          Prendre rendez-vous →
        </a>
      </div>
    </div>
    <?php endif; ?>

    <div class="card next-rdv">
      <div class="rdv-content">
        <div class="rdv-icon">📅</div>
        <div class="rdv-info">
          <strong>Prochain rendez-vous</strong>
          <?php if ($next_rdv): ?>
            <p><?= htmlspecialchars($next_rdv['prenom_enfant'] . ' ' . $next_rdv['nom_enfant']) ?> —
               <?= date('d M Y', strtotime($next_rdv['date_rdv'])) ?> à
               <?= substr($next_rdv['heure_rdv'], 0, 5) ?></p>
            <p>Dr. <?= htmlspecialchars($next_rdv['prenom_docteur'] . ' ' . $next_rdv['nom_docteur']) ?></p>
          <?php else: ?>
            <p>Aucun rendez-vous prévu.</p>
          <?php endif; ?>
        </div>
        <a href="appointments.php" class="btn btn-outline btn-sm">Voir</a>
      </div>
    </div>

  </main>
</div>
<?php include 'chatbot.php'; ?>
</body>
</html>