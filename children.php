<?php
session_start();
include('db.php');

if (empty($_SESSION['email'])) { header("Location: loginp.php"); exit(); }

$nom_pa    = $_SESSION['nom']    ?? '';
$prenom_pa = $_SESSION['prenom'] ?? '';
$email     = $_SESSION['email'];

// Récupérer id_parent si pas en session
if (empty($_SESSION['id_parent'])) {
    $rp = mysqli_query($conn, "SELECT id_parent FROM parent WHERE email='$email'");
    $up = $rp ? mysqli_fetch_assoc($rp) : null;
    if (!$up) { session_destroy(); header("Location: loginp.php"); exit(); }
    $_SESSION['id_parent'] = $up['id_parent'];
}
$r4 = mysqli_query($conn, "SELECT COUNT(*) total FROM notification WHERE email='$email' AND statut='Non lu'");
$total_notifs = $r4 ? mysqli_fetch_assoc($r4)['total'] : 0;

$id_parent = $_SESSION['id_parent'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes enfants — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .empty-state-wrapper { display:flex; justify-content:center; align-items:center; min-height:50vh; width:100%; }
    .empty-state-card {
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      padding:3rem; border:2px dashed #feb2b2; border-radius:20px;
      background:#fff5f5; max-width:500px; width:100%; text-align:center;
    }
    .empty-state-card h3 { color:#e53e3e; font-family:'Quicksand',sans-serif; font-size:1.5rem; margin:0 0 .5rem; }
    .empty-state-card p  { color:#c53030; font-size:1rem; line-height:1.5; margin:0; }
    .progress-bar  { background:#eee; border-radius:10px; height:10px; width:100%; overflow:hidden; margin-top:.5rem; }
    .notif-badge { background:#e53e3e; color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:50px; margin-left:4px; display:inline-block; line-height:1; vertical-align:middle; }
    .progress-fill { background:var(--pink); height:100%; border-radius:10px; transition:width .5s ease; }
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
      <a href="children.php" class="active">👶 Mes enfants</a>
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

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
      <h1 class="page-title">Mes enfants 👶</h1>
      <div style="display:flex;gap:10px;">
        <a href="ajouterchildren.php" class="btn btn-primary btn-round">+ Ajouter un enfant</a>
      </div>
    </div>

    <?php
    $sql2   = "SELECT * FROM enfant WHERE id_parent='$id_parent'";
    $result2 = mysqli_query($conn, $sql2);

    if (!$result2 || mysqli_num_rows($result2) == 0):
    ?>
    <div class="empty-state-wrapper">
      <div class="empty-state-card">
        <span style="font-size:3rem;margin-bottom:1rem;">👶</span>
        <h3>Aucun enfant trouvé</h3>
        <p>Cliquez sur <b>+ Ajouter un enfant</b> pour commencer.</p>
      </div>
    </div>
    <?php else:
    while ($enfant = mysqli_fetch_assoc($result2)):
        $id_enfant = $enfant['id_enfant'];

        // Progression vaccination pour CET enfant
        $r_total = mysqli_query($conn, "SELECT COUNT(*) total FROM vaccination WHERE id_enfant='$id_enfant'");
        $r_faits = mysqli_query($conn, "SELECT COUNT(*) total FROM vaccination WHERE id_enfant='$id_enfant' AND statut='Réalisé'");
        $nb_total = ($r_total) ? mysqli_fetch_assoc($r_total)['total'] : 0;
        $nb_faits = ($r_faits) ? mysqli_fetch_assoc($r_faits)['total'] : 0;
        $percent  = ($nb_total > 0) ? round(($nb_faits / $nb_total) * 100) : 0;

        // Retard pour cet enfant
        $r_retard = mysqli_query($conn,
            "SELECT COUNT(*) total FROM vaccination WHERE id_enfant='$id_enfant' AND statut='Prévu' AND date_prevue < CURDATE()");
        $nb_retard = ($r_retard) ? mysqli_fetch_assoc($r_retard)['total'] : 0;
    ?>
    <div class="card" style="margin-bottom:1.5rem;padding:1.5rem;background:white;border-radius:15px;
         <?= $nb_retard > 0 ? 'border-left:4px solid #e53e3e;' : '' ?>">
      <div style="display:flex;align-items:center;gap:1.2rem;margin-bottom:1rem;">
        <span style="font-size:3rem;"><?= ($enfant['genre'] === 'garcon') ? '👦' : '👧' ?></span>
        <div style="flex:1;">
          <h2 style="font-size:1.4rem;font-weight:700;margin:0;">
            <?= htmlspecialchars($enfant['nom_enfant'] . ' ' . $enfant['prenom_enfant']) ?>
          </h2>
          <p style="font-size:.85rem;color:#64748b;">
            📅 Né(e) le : <?= date('d/m/Y', strtotime($enfant['date_naissance'])) ?>
          </p>
          <?php if ($nb_retard > 0): ?>
            <span style="font-size:.75rem;background:#fff5f5;color:#e53e3e;
                         border:1px solid #feb2b2;padding:2px 8px;border-radius:6px;font-weight:700;">
              ⚠️ <?= $nb_retard ?> vaccin(s) en retard
            </span>
          <?php endif; ?>
        </div>
        <div style="text-align:center;">
          <span style="font-size:1.8rem;font-weight:700;color:var(--pink);"><?= $percent ?>%</span><br>
          <span style="font-size:.75rem;color:var(--text-muted);">
            <?= $percent == 100 ? 'Complété ✅' : ($percent > 0 ? 'En cours...' : 'En attente') ?>
          </span>
        </div>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width:<?= $percent ?>%;"></div>
      </div>
      <div style="margin-top:.9rem;display:flex;gap:.6rem;">
        <a href="dossier-enfant.php?id=<?= $id_enfant ?>" class="btn btn-outline btn-sm">📄 Voir dossier</a>
      </div>
    </div>
    <?php endwhile; endif; ?>

  </main>
</div>
</body>
</html>