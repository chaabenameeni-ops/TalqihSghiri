<?php
/**
 * notifications.php — PAGE PARENT UNIQUEMENT
 * Affiche uniquement les notifications du parent connecté
 * basées sur son email. Rien à voir avec l'admin.
 */
session_start();
include('db.php');

// ─── Protection session ────────────────────────────────────
if (empty($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'parent') {
    header("Location: loginp.php");
    exit();
}

$nom_pa    = $_SESSION['nom']    ?? '';
$prenom_pa = $_SESSION['prenom'] ?? '';
$email     = $_SESSION['email'];

// ─── Récupérer id_parent ───────────────────────────────────
if (empty($_SESSION['id_parent'])) {
    $rp = mysqli_query($conn, "SELECT id_parent FROM parent WHERE email='$email'");
    $up = $rp ? mysqli_fetch_assoc($rp) : null;
    if ($up) $_SESSION['id_parent'] = $up['id_parent'];
}

// ─── Action : Marquer tout comme lu ───────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $em = mysqli_real_escape_string($conn, $email);
    mysqli_query($conn, "UPDATE notification SET statut='Lu' WHERE email='$em'");
    header("Location: notifications.php?msg=read");
    exit();
}

// ─── Action : Marquer une notif comme lue ─────────────────
if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['id'])) {
    $id_n = intval($_GET['id']);
    $em   = mysqli_real_escape_string($conn, $email);
    mysqli_query($conn, "UPDATE notification SET statut='Lu' WHERE id_notification=$id_n AND email='$em'");
    header("Location: notifications.php");
    exit();
}

// ─── Récupérer les notifications DE CE PARENT uniquement ──
$em      = mysqli_real_escape_string($conn, $email);
$sql_n   = "SELECT * FROM notification WHERE email='$em' ORDER BY date_notification DESC";
$res_n   = mysqli_query($conn, $sql_n);
$notifs  = [];
if ($res_n) {
    while ($n = mysqli_fetch_assoc($res_n)) $notifs[] = $n;
}
$total       = count($notifs);
$non_lues    = count(array_filter($notifs, fn($n) => $n['statut'] === 'Non lu'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── Notif card ── */
    .notif-card {
      background: #fff;
      border: 1px solid #eef0f5;
      border-radius: 14px;
      padding: 1rem 1.2rem;
      display: flex;
      align-items: flex-start;
      gap: .9rem;
      margin-bottom: .75rem;
      transition: box-shadow .2s;
      position: relative;
    }
    .notif-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }

    /* Bande gauche selon type */
    .notif-card.type-Rappel  { border-left: 4px solid var(--success, #27ae60); }
    .notif-card.type-Alerte  { border-left: 4px solid #e53e3e; }
    .notif-card.type-Info    { border-left: 4px solid #3b82f6; }

    /* Non lue */
    .notif-card.unread { background: #fafbff; }
    .unread-dot {
      width: 9px; height: 9px; border-radius: 50%;
      background: var(--pink, #d63384); flex-shrink: 0; margin-top: 5px;
    }

    /* Icône ronde */
    .notif-icon-wrap {
      width: 42px; height: 42px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; flex-shrink: 0;
    }
    .icon-rappel { background: hsla(150,50%,50%,.12); }
    .icon-alerte { background: hsla(0,84%,60%,.1); }
    .icon-info   { background: hsla(217,91%,60%,.1); }

    /* Texte */
    .notif-title {
      font-family: 'Nunito', sans-serif;
      font-weight: 800; font-size: .92rem;
      color: #1a1a2e; margin: 0 0 .25rem;
      display: flex; align-items: center; gap: .4rem;
    }
    .notif-msg  { font-size: .85rem; color: #555; line-height: 1.5; margin: 0 0 .35rem; }
    .notif-date { font-size: .75rem; color: #aaa; font-weight: 600; }

    /* Bouton marquer lu */
    .btn-mark-read {
      margin-left: auto; flex-shrink: 0; padding: 5px 12px;
      border-radius: 8px; border: 1px solid #eef0f5;
      background: #f8fafc; color: #64748b; font-size: .75rem;
      font-weight: 700; cursor: pointer; font-family: 'Quicksand', sans-serif;
      transition: .2s; text-decoration: none; display: inline-block; white-space: nowrap;
    }
    .btn-mark-read:hover { background: var(--pink, #d63384); color: #fff; border-color: var(--pink, #d63384); }

    /* Header actions */
    .notif-header-row {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-bottom: 1.2rem; flex-wrap: wrap; gap: .7rem;
    }
    .notif-header-row h2 {
      font-family: 'Nunito', sans-serif;
      font-size: 1.05rem; font-weight: 900;
      color: #1a1a2e; margin: 0;
    }
    .btn-all-read {
      padding: 8px 18px; border-radius: 20px;
      background: #fff; border: 1px solid #eef0f5;
      font-size: .82rem; font-weight: 700;
      cursor: pointer; font-family: 'Quicksand', sans-serif;
      color: #1a1a2e; text-decoration: none; transition: .2s;
    }
    .btn-all-read:hover { background: #1a1a2e; color: #fff; }

    /* Badge non lues */
    .badge-nonlu {
      background: var(--pink, #d63384); color: #fff;
      font-size: .72rem; font-weight: 800;
      padding: 2px 8px; border-radius: 20px; margin-left: .4rem;
    }

    /* Filtre chips */
    .chip {
      padding: 6px 16px; border-radius: 20px;
      border: 1px solid #eef0f5; background: #fff;
      color: #64748b; font-weight: 700; font-size: .8rem;
      cursor: pointer; transition: .2s;
      font-family: 'Quicksand', sans-serif;
    }
    .chip.active, .chip:hover {
      background: var(--pink, #d63384); color: #fff;
      border-color: var(--pink, #d63384);
    }

    /* Flash */
    .flash {
      padding: 12px 18px; border-radius: 12px; margin-bottom: 1.2rem;
      background: #f0fdf4; border: 1px solid #86efac;
      color: #166534; font-size: .9rem; font-weight: 600;
      animation: sld .4s ease;
    }
    @keyframes sld { from{transform:translateY(-8px);opacity:0} to{transform:translateY(0);opacity:1} }

    /* Empty */
    .empty-notif { text-align: center; padding: 3.5rem 1rem; color: #aaa; }
    .empty-notif .ei { font-size: 3rem; display: block; margin-bottom: .8rem; }

    /* Sidebar notif badge */
    .notif-badge {
      background: #e53e3e; color: #fff; font-size: 10px; font-weight: 800;
      padding: 2px 6px; border-radius: 50px; margin-left: 4px;
      display: inline-block; line-height: 1; vertical-align: middle;
    }
  </style>
</head>
<body>
<div class="app-wrapper">

  <!-- SIDEBAR PARENT -->
  <aside class="sidebar">
    <div class="logo">
      <div class="logo-icon">💉</div>
      <span class="logo-text">Talqih<span class="text-pink">Sghiri</span></span>
    </div>
    <nav class="sidebar-nav">
      <a href="parent.php">📊 Tableau de bord</a>
      <a href="children.php">👶 Mes enfants</a>
      <a href="appointments.php">📅 Rendez-vous</a>
      <a href="notifications.php" class="active">🔔 Notifications
        <?php if ($non_lues > 0): ?>
          <span class="notif-badge"><?= $non_lues ?></span>
        <?php endif; ?>
      </a>
      <a href="index.php" id="logoutBtn">🚪 Déconnexion</a>
    </nav>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <h1 class="page-title">Salut, <?= htmlspecialchars($nom_pa . ' ' . $prenom_pa) ?> ! 👋</h1>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'read'): ?>
      <div class="flash">✅ Toutes les notifications ont été marquées comme lues.</div>
    <?php endif; ?>

    <div class="notif-header-row">
      <h2>
        🔔 Mes Notifications
        <?php if ($non_lues > 0): ?>
          <span class="badge-nonlu"><?= $non_lues ?> non lue(s)</span>
        <?php endif; ?>
      </h2>
      <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <!-- Filtres -->
        <button class="chip active" data-filter="all">Toutes</button>
        <button class="chip" data-filter="Rappel">📅 Rappels</button>
        <button class="chip" data-filter="Alerte">⚠️ Alertes</button>
        <button class="chip" data-filter="Info">ℹ️ Infos</button>
        <?php if ($non_lues > 0): ?>
          <a href="notifications.php?action=mark_all_read" class="btn-all-read">✔ Tout marquer lu</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Liste des notifications -->
    <div id="notifList">
      <?php if (!empty($notifs)): ?>
        <?php foreach ($notifs as $n):
          $is_unread  = ($n['statut'] === 'Non lu');
          $type       = $n['type'] ?? 'Info';
          $icon_class = match($type) {
            'Rappel' => 'icon-rappel',
            'Alerte' => 'icon-alerte',
            default  => 'icon-info',
          };
          $icon_emoji = match($type) {
            'Rappel' => '📅',
            'Alerte' => '⚠️',
            default  => 'ℹ️',
          };
          $date_fmt = date('d/m/Y à H:i', strtotime($n['date_notification']));
        ?>
        <div class="notif-card <?= $is_unread ? 'unread' : '' ?> type-<?= htmlspecialchars($type) ?>"
             data-type="<?= htmlspecialchars($type) ?>">

          <?php if ($is_unread): ?><div class="unread-dot"></div><?php endif; ?>

          <div class="notif-icon-wrap <?= $icon_class ?>"><?= $icon_emoji ?></div>

          <div style="flex:1;min-width:0;">
            <div class="notif-title">
              <?= htmlspecialchars($type) ?>
              <?php if ($is_unread): ?>
                <span style="font-size:.68rem;background:var(--pink,#d63384);color:#fff;
                             padding:1px 7px;border-radius:10px;font-weight:800;">Nouveau</span>
              <?php endif; ?>
            </div>
            <p class="notif-msg"><?= htmlspecialchars($n['message']) ?></p>
            <span class="notif-date">🕐 <?= $date_fmt ?></span>
          </div>

          <?php if ($is_unread): ?>
            <a href="notifications.php?action=read&id=<?= $n['id_notification'] ?>"
               class="btn-mark-read">✔ Lu</a>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-notif">
          <span class="ei">📬</span>
          <p>Aucune notification pour le moment.<br>
             Les rappels de vaccination apparaîtront ici automatiquement.</p>
        </div>
      <?php endif; ?>
    </div>

    <div id="noResult" style="display:none;text-align:center;padding:2.5rem;color:#aaa;">
      Aucune notification dans cette catégorie.
    </div>

  </main>
</div>

<?php include 'chatbot.php'; ?>

<script>
// ── Filtres chips ──────────────────────────────────────────
document.querySelectorAll('.chip').forEach(chip => {
  chip.addEventListener('click', function () {
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    this.classList.add('active');
    const filter = this.dataset.filter;
    const cards  = document.querySelectorAll('.notif-card');
    let visible  = 0;
    cards.forEach(card => {
      const match = filter === 'all' || card.dataset.type === filter;
      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    document.getElementById('noResult').style.display = visible === 0 ? 'block' : 'none';
  });
});

// Auto-masquer flash
setTimeout(() => {
  document.querySelectorAll('.flash').forEach(a => a.style.display = 'none');
}, 4000);
</script>
</body>
</html>