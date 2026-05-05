<?php
session_start();
include('db.php');

if (empty($_SESSION['id_docteur'])) {
    header("Location: loginp.php");
    exit();
}

$nom_doc    = $_SESSION['nom']    ?? '';
$prenom_doc = $_SESSION['prenom'] ?? '';
$email_doc  = $_SESSION['email']  ?? '';
$id_doc     = intval($_SESSION['id_docteur']);

// ─── Action : Marquer tout comme lu ───────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $em = mysqli_real_escape_string($conn, $email_doc);
    mysqli_query($conn, "UPDATE notification SET statut='Lu' WHERE email='$em'");
    header("Location: notifications-admin.php?msg=read");
    exit();
}

// ─── Action : Marquer une notif comme lue ─────────────────
if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['id'])) {
    $id_n = intval($_GET['id']);
    $em   = mysqli_real_escape_string($conn, $email_doc);
    mysqli_query($conn, "UPDATE notification SET statut='Lu' WHERE id_notification=$id_n AND email='$em'");
    header("Location: notifications-admin.php");
    exit();
}

// ─── Récupérer les notifications du docteur ───────────────
$em    = mysqli_real_escape_string($conn, $email_doc);
$sql_n = "SELECT * FROM notification WHERE email='$em' ORDER BY date_notification DESC";
$res_n = mysqli_query($conn, $sql_n);
$notifs = [];
if ($res_n) {
    while ($n = mysqli_fetch_assoc($res_n)) $notifs[] = $n;
}
$total    = count($notifs);
$non_lues = count(array_filter($notifs, fn($n) => $n['statut'] === 'Non lu'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin.css">
  <style>
    .notif-card {
      background: #fff;
      border: 1px solid #eef0f5;
      border-left: 4px solid #3b82f6;
      border-radius: 14px;
      padding: 1rem 1.2rem;
      display: flex;
      align-items: flex-start;
      gap: .9rem;
      margin-bottom: .75rem;
      transition: box-shadow .2s;
    }
    .notif-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
    .notif-card.unread { background: #f8faff; }
    .unread-dot {
      width: 9px; height: 9px; border-radius: 50%;
      background: #2563eb; flex-shrink: 0; margin-top: 5px;
    }
    .notif-icon-wrap {
      width: 42px; height: 42px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; flex-shrink: 0;
    }
    .notif-title {
      font-family: 'Nunito', sans-serif;
      font-weight: 800; font-size: .92rem;
      color: #1a1a2e; margin: 0 0 .25rem;
      display: flex; align-items: center; gap: .4rem;
    }
    .notif-msg  { font-size: .85rem; color: #555; line-height: 1.5; margin: 0 0 .35rem; }
    .notif-date { font-size: .75rem; color: #aaa; font-weight: 600; }
    .btn-mark-read {
      margin-left: auto; flex-shrink: 0; padding: 5px 12px;
      border-radius: 8px; border: 1px solid #eef0f5;
      background: #f8fafc; color: #64748b; font-size: .75rem;
      font-weight: 700; cursor: pointer; font-family: 'Quicksand', sans-serif;
      transition: .2s; text-decoration: none; display: inline-block; white-space: nowrap;
    }
    .btn-mark-read:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
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
    .badge-nonlu {
      background: #2563eb; color: #fff;
      font-size: .72rem; font-weight: 800;
      padding: 2px 8px; border-radius: 20px; margin-left: .4rem;
    }
    .chip {
      padding: 6px 16px; border-radius: 20px;
      border: 1px solid #eef0f5; background: #fff;
      color: #64748b; font-weight: 700; font-size: .8rem;
      cursor: pointer; transition: .2s;
      font-family: 'Quicksand', sans-serif;
    }
    .chip.active, .chip:hover {
      background: #2563eb; color: #fff;
      border-color: #2563eb;
    }
    .flash {
      padding: 12px 18px; border-radius: 12px; margin-bottom: 1.2rem;
      background: #f0fdf4; border: 1px solid #86efac;
      color: #166534; font-size: .9rem; font-weight: 600;
    }
    .empty-notif { text-align: center; padding: 3.5rem 1rem; color: #aaa; }
    .empty-notif .ei { font-size: 3rem; display: block; margin-bottom: .8rem; }
    .notif-badge {
      background: #e53e3e; color: #fff; font-size: 10px; font-weight: 800;
      padding: 2px 6px; border-radius: 50px; margin-left: 4px;
      display: inline-block; line-height: 1; vertical-align: middle;
    }
    #noResultNotif { display:none; text-align:center; padding:2rem; color:#aaa; }
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
      <a href="admin.php">📊 Tableau de bord</a>
      <a href="dossiers-patients.php">👶 Liste des Enfants</a>
      <a href="calendrier-vaccins.php">📅 Liste rendez-vous</a>
      <a href="historique.php">📋 Historique</a>
      <a href="notifications-admin.php" class="active">🔔 Notifications
        <?php if ($non_lues > 0): ?>
          <span class="notif-badge"><?= $non_lues ?></span>
        <?php endif; ?>
      </a>
      <a href="index.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">

    <div class="admin-topbar">
      <div>
        <h1 class="page-title">Salut, Dr. <?= htmlspecialchars($prenom_doc . ' ' . $nom_doc) ?> ! 👋</h1>
        <p>Vos notifications médicales</p>
      </div>
      <div class="admin-user">
        <div>
          <div class="admin-name"><?= htmlspecialchars($prenom_doc . ' ' . $nom_doc) ?></div>
          <div class="admin-role">Médecin</div>
        </div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

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
        <button class="chip active" data-filter="all">Toutes</button>
        <button class="chip" data-filter="Rappel">📅 Rappels</button>
        <button class="chip" data-filter="Alerte">⚠️ Alertes</button>
        <button class="chip" data-filter="Info">ℹ️ Infos</button>
        <?php if ($non_lues > 0): ?>
          <a href="notifications-admin.php?action=mark_all_read" class="btn-all-read">✔ Tout marquer lu</a>
        <?php endif; ?>
      </div>
    </div>

    <div id="notifList">
      <?php if (!empty($notifs)): ?>
        <?php foreach ($notifs as $n):
          $is_unread  = ($n['statut'] === 'Non lu');
          $type       = $n['type'] ?? 'Info';
          $icon_emoji = match($type) {
            'Rappel' => '📅',
            'Alerte' => '⚠️',
            default  => 'ℹ️',
          };
          $border_color = match($type) {
            'Rappel' => '#27ae60',
            'Alerte' => '#e53e3e',
            default  => '#2563eb',
          };
          $icon_bg = match($type) {
            'Rappel' => 'hsla(150,50%,50%,.12)',
            'Alerte' => 'hsla(0,84%,60%,.1)',
            default  => 'hsla(217,91%,60%,.1)',
          };
          $date_fmt = date('d/m/Y à H:i', strtotime($n['date_notification']));
        ?>
        <div class="notif-card <?= $is_unread ? 'unread' : '' ?>"
             style="border-left-color: <?= $border_color ?>;"
             data-type="<?= htmlspecialchars($type) ?>">

          <?php if ($is_unread): ?><div class="unread-dot"></div><?php endif; ?>

          <div class="notif-icon-wrap" style="background:<?= $icon_bg ?>">
            <?= $icon_emoji ?>
          </div>

          <div style="flex:1;min-width:0;">
            <div class="notif-title">
              <?= htmlspecialchars($type) ?>
              <?php if ($is_unread): ?>
                <span style="font-size:.68rem;background:#2563eb;color:#fff;
                             padding:1px 7px;border-radius:10px;font-weight:800;">Nouveau</span>
              <?php endif; ?>
            </div>
            <p class="notif-msg"><?= htmlspecialchars($n['message']) ?></p>
            <span class="notif-date">🕐 <?= $date_fmt ?></span>
          </div>

          <?php if ($is_unread): ?>
            <a href="notifications-admin.php?action=read&id=<?= $n['id_notification'] ?>"
               class="btn-mark-read">✔ Lu</a>
          <?php endif; ?>

        </div>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-notif">
          <span class="ei">📬</span>
          <p>Aucune notification pour le moment.</p>
        </div>
      <?php endif; ?>
    </div>

    <div id="noResultNotif">
      <span style="font-size:2rem;">🔍</span>
      <p>Aucune notification dans cette catégorie.</p>
    </div>

  </main>
</div>

<script>
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
    document.getElementById('noResultNotif').style.display = visible === 0 ? 'block' : 'none';
  });
});

setTimeout(() => {
  document.querySelectorAll('.flash').forEach(a => a.style.display = 'none');
}, 4000);
</script>
</body>
</html>