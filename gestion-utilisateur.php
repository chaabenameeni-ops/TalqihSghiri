<?php
session_start();
include('db.php');

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action == 'accept') $sql = "UPDATE utilisateur SET statut='actif' WHERE id_utilisateur=$id";
    elseif ($action == 'refuse') $sql = "DELETE FROM utilisateur WHERE id_utilisateur=$id";
    if (isset($sql)) mysqli_query($conn, $sql);
    header("Location: gestion-utilisateur.php?status=" . ($action=='accept' ? 'accepted' : 'deleted'));
    exit();
}

$total_users   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur"))['c'];
$parents_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur WHERE role='parent'"))['c'];
$doctors_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur WHERE role='docteur'"))['c'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur WHERE statut='en_attente'"))['c'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des Utilisateurs — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="admin-vaccines.css">
  <style>
  @keyframes fadeInDown {
    from { opacity:0; transform:translateY(-22px); }
    to   { opacity:1; transform:translateY(0); }
  }
  @keyframes fadeInUp {
    from { opacity:0; transform:translateY(25px); }
    to   { opacity:1; transform:translateY(0); }
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
  @keyframes rowIn {
    from { opacity:0; transform:translateX(-15px); }
    to   { opacity:1; transform:translateX(0); }
  }
  @keyframes zoomIn {
    from { transform:scale(.8); opacity:0; }
    to   { transform:scale(1);  opacity:1; }
  }

  /* ── Sidebar ── */
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

  /* ── Topbar ── */
  .admin-topbar { animation: fadeInDown .5s .1s ease both; }

  /* ── Flash ── */
  .flash-alert { animation: slideDown .4s ease both; }
  .flash-alert { background:#f0fdf4; border:1px solid #86efac; color:#166534; padding:12px 18px; border-radius:12px; margin-bottom:16px; display:flex; align-items:center; gap:10px; font-size:.9rem; }
  .flash-alert.danger { background:#fef2f2; border-color:#fca5a5; color:#991b1b; }

  /* ── Stat cards ── */
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
  .admin-stat-num { display:block; animation: countPop .6s .5s ease both; }
  .admin-stat-icon { transition: transform .3s ease; }
  .admin-stat-card:hover .admin-stat-icon { transform: scale(1.18) rotate(8deg); }

  /* ── Tabs ── */
  .admin-tabs { animation: fadeInDown .5s .35s ease both; }
  .admin-tab { cursor:pointer; border:none; background:none; padding:10px 20px; transition:all .25s ease; font-family:'Quicksand',sans-serif; font-weight:600; position:relative; }
  .admin-tab::after { content:''; position:absolute; bottom:0; left:50%; transform:translateX(-50%); width:0; height:2px; background:#2196f3; transition:width .3s ease; border-radius:2px; }
  .admin-tab.active { color:#3498db; border-bottom:3px solid #3498db; font-weight:700; }
  .admin-tab.active::after, .admin-tab:hover::after { width:80%; }

  /* ── Table ── */
  .admin-table-wrapper { animation: fadeInUp .5s .5s ease both; }
  .admin-table tbody tr { transition: background .2s, transform .2s, box-shadow .2s; }
  .admin-table tbody tr:hover {
    background: #f0f7ff !important;
    transform: scale(1.003);
    box-shadow: 0 2px 10px rgba(33,150,243,.08);
  }
  .admin-table tbody tr:nth-child(1)  { animation: rowIn .4s .55s ease both; }
  .admin-table tbody tr:nth-child(2)  { animation: rowIn .4s .60s ease both; }
  .admin-table tbody tr:nth-child(3)  { animation: rowIn .4s .65s ease both; }
  .admin-table tbody tr:nth-child(4)  { animation: rowIn .4s .70s ease both; }
  .admin-table tbody tr:nth-child(5)  { animation: rowIn .4s .75s ease both; }
  .admin-table tbody tr:nth-child(6)  { animation: rowIn .4s .80s ease both; }
  .admin-table tbody tr:nth-child(7)  { animation: rowIn .4s .85s ease both; }
  .admin-table tbody tr:nth-child(8)  { animation: rowIn .4s .90s ease both; }
  .admin-table tbody tr:nth-child(9)  { animation: rowIn .4s .95s ease both; }
  .admin-table tbody tr:nth-child(10) { animation: rowIn .4s 1.0s ease both; }

  /* ── Boutons ── */
  .admin-actions { display:flex; gap:8px; }
  .btn-action {
    padding:6px 14px; border-radius:20px; font-weight:600;
    font-family:'Quicksand',sans-serif; cursor:pointer;
    transition:all .25s ease; font-size:.85rem; border:none; text-decoration:none;
  }
  .btn-accept { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
  .btn-accept:hover { background:#28a745; color:#fff; transform:translateY(-2px); box-shadow:0 4px 12px rgba(40,167,69,.3); }
  .btn-refuse { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
  .btn-refuse:hover { background:#dc3545; color:#fff; transform:translateY(-2px); box-shadow:0 4px 12px rgba(220,53,69,.3); }

  /* ── Filter chips ── */
  .filter-chip { padding:6px 16px; border-radius:20px; border:1px solid #dde3ec; background:#fff; color:#555; font-weight:600; font-size:.82rem; cursor:pointer; transition:all .2s ease; }
  .filter-chip.active, .filter-chip:hover { background:#2196f3; color:#fff; border-color:#2196f3; transform:translateY(-1px); box-shadow:0 3px 10px rgba(33,150,243,.25); }

  /* ── Search ── */
  .admin-search { position:relative; min-width:240px; }
  .admin-search input { width:100%; padding:9px 12px 9px 36px; border:1px solid #dde3ec; border-radius:20px; font-size:.87rem; outline:none; transition:all .3s ease; }
  .admin-search input:focus { border-color:#2196f3; box-shadow:0 0 0 3px rgba(33,150,243,.15); transform:scale(1.01); }
  .admin-search-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:.85rem; }

  /* ── Badges ── */
  .badge-count { background:#e53e3e; color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:50px; margin-left:8px; display:inline-block; line-height:1; vertical-align:middle; }
  .badge-pending  { background:#fff3cd; color:#856404; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:600; }
  .badge-accepted { background:#d4edda; color:#155724; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:600; }

  /* ── Toolbar ── */
  .admin-toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; gap:1rem; flex-wrap:wrap; }

  /* ── Modal ── */
  .custom-modal { display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,.4); backdrop-filter:blur(5px); align-items:center; justify-content:center; }
  .custom-modal.open { display:flex; }
  .modal-content { background:#fff; padding:40px; border-radius:24px; width:100%; max-width:400px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,.1); animation:zoomIn .3s ease both; }
  .modal-icon { font-size:50px; margin-bottom:15px; display:block; }
  .modal-content h2 { font-family:'Quicksand',sans-serif; color:#333; margin-bottom:12px; font-weight:700; }
  .modal-content p  { color:#777; font-size:.95rem; line-height:1.5; margin-bottom:25px; }
  .modal-actions { display:flex; gap:12px; justify-content:center; }
  .btn-cancel { background:#f1f3f5; color:#5d6d7e; border:none; padding:12px 24px; border-radius:15px; font-weight:600; cursor:pointer; transition:.3s; }
  .btn-cancel:hover { background:#e9ecef; }
  .btn-confirm { background:#ff4d4d; color:#fff; border:none; padding:12px 24px; border-radius:15px; font-weight:600; cursor:pointer; transition:.3s; display:inline-block; }
  .btn-confirm:hover { background:#e60000; transform:translateY(-2px); }
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
      <a href="tableaudebord.php">📊 Tableau de bord</a>
      <a href="admin-vaccines.php">🧬 Gestion Vaccins</a>
      <a href="gestion-utilisateur.php" class="active">👥 Gestion des Utilisateurs</a>
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
        <h1>👥 Gestion des Utilisateurs</h1>
        <p>Gérez les comptes des parents et des médecins sur la plateforme</p>
      </div>
      <div class="admin-user">
        <div><div class="admin-role">Administrateur</div></div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

    <?php if (isset($_GET['status'])): ?>
      <?php if ($_GET['status'] === 'accepted'): ?>
        <div class="flash-alert">✅ Compte activé avec succès.</div>
      <?php elseif ($_GET['status'] === 'deleted'): ?>
        <div class="flash-alert danger">🗑️ Utilisateur supprimé.</div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="admin-stats">
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-blue">👥</div>
        <div><span class="admin-stat-num"><?= $total_users ?></span><span class="admin-stat-lbl">Total Utilisateurs</span></div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-green">👨‍👩‍👧</div>
        <div><span class="admin-stat-num"><?= $parents_count ?></span><span class="admin-stat-lbl">Parents</span></div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-red">👨‍⚕️</div>
        <div><span class="admin-stat-num"><?= $doctors_count ?></span><span class="admin-stat-lbl">Docteurs</span></div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-red">🔔</div>
        <div><span class="admin-stat-num"><?= $pending_count ?></span><span class="admin-stat-lbl">Notifications inscriptions</span></div>
      </div>
    </div>

    <div class="admin-tabs">
      <button class="admin-tab active" data-tab="all">📋 Tous les Utilisateurs</button>
      <button class="admin-tab" data-tab="parent">👨‍👩‍👧 Liste Parents</button>
      <button class="admin-tab" data-tab="docteur">👨‍⚕️ Liste Docteurs</button>
    </div>

    <div class="admin-table-wrapper">
      <div class="admin-toolbar">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
          <div class="admin-search">
            <span class="admin-search-icon">🔍</span>
            <input type="text" id="search-users" placeholder="Rechercher un nom, email...">
          </div>
          <div style="display:flex;gap:.5rem;">
            <button class="filter-chip active" data-filter="all">Tous</button>
            <button class="filter-chip" data-filter="en_attente">En attente</button>
            <button class="filter-chip" data-filter="actif">Accepté</button>
          </div>
        </div>
        <span id="resultCount" style="font-size:.85rem;color:#888;"></span>
      </div>

      <table class="admin-table">
        <thead>
          <tr>
            <th>Utilisateur</th><th>Rôle</th><th>Email</th>
            <th>Téléphone</th><th>Statut</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="users-tbody">
          <?php
          $result = mysqli_query($conn, "SELECT * FROM utilisateur WHERE role='parent' OR role='docteur' ORDER BY id_utilisateur DESC");
          while ($user = mysqli_fetch_assoc($result)):
            $statut_db   = strtolower(trim($user['statut']));
            $is_pending  = ($statut_db === 'en_attente');
            $statut_class = $is_pending ? 'badge-pending' : 'badge-accepted';
            $statut_text  = $is_pending ? 'En attente' : 'Accepté';
            $role_db      = strtolower(trim($user['role']));
          ?>
          <tr class="user-row"
              data-role="<?= htmlspecialchars($role_db) ?>"
              data-statut="<?= htmlspecialchars($statut_db) ?>"
              data-search="<?= strtolower(htmlspecialchars($user['email'].' '.$user['role'])) ?>">
            <td>
              <div class="user-cell">
                <div class="user-cell-avatar" style="background:hsla(200,65%,55%,.1);">👤</div>
                <div>
                  <div class="user-cell-name"><?= htmlspecialchars($user['email']) ?></div>
                  <div class="user-cell-email">Rôle : <?= ucfirst($user['role']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge <?= ($role_db==='docteur') ? 'badge-blue' : 'badge-green' ?>"><?= ucfirst($user['role']) ?></span></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : '-- -- --' ?></td>
            <td><span class="badge <?= $statut_class ?>"><?= $statut_text ?></span></td>
            <td>
              <div class="admin-actions">
                <?php if ($is_pending): ?>
                  <a href="gestion-utilisateur.php?id=<?= $user['id_utilisateur'] ?>&action=accept"
                     class="btn-action btn-accept"
                     onclick="return confirm('Confirmer l\'activation ?');">Accepter</a>
                <?php endif; ?>
                <button type="button" class="btn-action btn-refuse"
                        onclick="openDeleteModal('gestion-utilisateur.php?id=<?= $user['id_utilisateur'] ?>&action=refuse')">
                  Supprimer
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <div id="noResult" style="display:none;text-align:center;padding:30px;color:#999;">Aucun utilisateur trouvé.</div>
    </div>

  </main>
</div>

<!-- Modal Suppression -->
<div id="deleteModal" class="custom-modal">
  <div class="modal-content">
    <div class="modal-icon">🗑️</div>
    <h2>Suppression définitive</h2>
    <p>Voulez-vous vraiment supprimer cet utilisateur ?<br><b>Cette action est irréversible.</b></p>
    <div class="modal-actions">
      <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Annuler</button>
      <button type="button" class="btn-confirm" onclick="executeDelete()">Oui, Supprimer</button>
    </div>
  </div>
</div>

<!-- Modal Succès -->
<div id="successModal" class="custom-modal">
  <div class="modal-content" style="border-top:5px solid #28a745;">
    <div class="modal-icon" style="color:#28a745;">✅</div>
    <h2>Supprimé avec succès !</h2>
    <p>L'utilisateur a été retiré définitivement.</p>
    <div class="modal-actions">
      <button type="button" class="btn-confirm" style="background:#28a745;" onclick="location.reload()">D'accord</button>
    </div>
  </div>
</div>

<script>
let urlToDelete = "";
function openDeleteModal(url) { urlToDelete = url; document.getElementById('deleteModal').classList.add('open'); }
function closeDeleteModal()   { document.getElementById('deleteModal').classList.remove('open'); }
function executeDelete() {
  fetch(urlToDelete).then(() => {
    closeDeleteModal();
    document.getElementById('successModal').classList.add('open');
  }).catch(() => {
    closeDeleteModal();
    document.getElementById('successModal').classList.add('open');
  });
}

document.querySelectorAll('.custom-modal').forEach(m => {
  m.addEventListener('click', function(e) {
    if (e.target === this) {
      if (this.id === 'successModal') location.reload();
      else this.classList.remove('open');
    }
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('search-users');
  const tabs        = document.querySelectorAll('.admin-tab');
  const chips       = document.querySelectorAll('.filter-chip');
  const rows        = document.querySelectorAll('.user-row');
  const resultCount = document.getElementById('resultCount');
  const noResult    = document.getElementById('noResult');
  let activeRole = 'all', activeStatut = 'all', searchText = '';

  function applyFilters() {
    let visible = 0;
    rows.forEach(row => {
      const role   = row.dataset.role   || '';
      const statut = row.dataset.statut || '';
      const search = row.dataset.search || '';
      const matchRole   = activeRole   === 'all' || role   === activeRole;
      const matchStatut = activeStatut === 'all' || statut === activeStatut;
      const matchSearch = !searchText  || search.includes(searchText);
      if (matchRole && matchStatut && matchSearch) { row.style.display = ''; visible++; }
      else row.style.display = 'none';
    });
    resultCount.textContent = visible + ' utilisateur(s) affiché(s)';
    noResult.style.display  = visible === 0 ? 'block' : 'none';
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', function () {
      tabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      activeRole = this.dataset.tab;
      applyFilters();
    });
  });

  chips.forEach(chip => {
    chip.addEventListener('click', function () {
      chips.forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      activeStatut = this.dataset.filter;
      applyFilters();
    });
  });

  searchInput.addEventListener('input', function () {
    searchText = this.value.toLowerCase().trim();
    applyFilters();
  });

  applyFilters();

  setTimeout(() => {
    document.querySelectorAll('.flash-alert').forEach(a => {
      a.style.transition = 'opacity .5s'; a.style.opacity = '0';
      setTimeout(() => a.remove(), 500);
    });
  }, 4000);
});
</script>
</body>
</html>