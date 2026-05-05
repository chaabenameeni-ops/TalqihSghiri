<?php
session_start();
include('db.php');

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM vaccin WHERE id_vaccin = $id");
    header("Location: admin-vaccines.php?status=deleted");
    exit();
}

if (isset($_POST['modifier_vaccin_btn'])) {
    $id    = intval($_POST['id_vaccin']);
    $nom   = $conn->real_escape_string($_POST['nom_complet']);
    $mal   = $conn->real_escape_string($_POST['maladie']);
    $age   = $conn->real_escape_string($_POST['age_recommande']);
    $doses = intval($_POST['nombre_dose']);
    $type  = $conn->real_escape_string($_POST['type_vacs']);
    $stat  = $conn->real_escape_string($_POST['statut']);
    $voie  = $conn->real_escape_string($_POST['voie_administration']);
    $site  = $conn->real_escape_string($_POST['site_injection']);
    $conn->query("UPDATE vaccin SET nom_complet='$nom', maladie='$mal', age_recommande='$age',
        nombre_dose=$doses, type_vacs='$type', statut='$stat',
        voie_administration='$voie', site_injection='$site' WHERE id_vaccin=$id");
    header("Location: admin-vaccines.php?status=updated");
    exit();
}

if (isset($_POST['ajouter_vaccin_btn'])) {
    $nom   = $conn->real_escape_string($_POST['nom_complet']);
    $mal   = $conn->real_escape_string($_POST['maladie']);
    $age   = $conn->real_escape_string($_POST['age_recommande']);
    $doses = intval($_POST['nombre_dose']);
    $type  = $conn->real_escape_string($_POST['type_vacs']);
    $voie  = $conn->real_escape_string($_POST['voie_administration']);
    $site  = $conn->real_escape_string($_POST['site_injection']);
    $conn->query("INSERT INTO vaccin (nom_complet, maladie, age_recommande, nombre_dose, type_vacs, voie_administration, site_injection)
                  VALUES ('$nom','$mal','$age',$doses,'$type','$voie','$site')");
    header("Location: admin-vaccines.php?status=added");
    exit();
}

$total_users   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur"))['c'];
$parents_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur WHERE role='parent'"))['c'];
$doctors_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur WHERE role='docteur'"))['c'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM utilisateur WHERE statut='en_attente'"))['c'];
$total_vaccins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM vaccin"))['c'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des Vaccins — TalqihSghiri</title>
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
  .flash-alert { animation: slideDown .4s ease both !important; }

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
  .admin-stats .admin-stat-card:nth-child(5) { animation-delay:.42s; }
  .admin-stat-num { display:block; animation: countPop .6s .5s ease both; }
  .admin-stat-icon { transition: transform .3s ease; }
  .admin-stat-card:hover .admin-stat-icon { transform: scale(1.18) rotate(8deg); }

  /* ── Tabs ── */
  .admin-tabs { animation: fadeInDown .5s .35s ease both; }
  .admin-tab {
    transition: all .25s ease;
    position: relative;
  }
  .admin-tab::after {
    content: '';
    position: absolute;
    bottom: 0; left: 50%;
    transform: translateX(-50%);
    width: 0; height: 2px;
    background: #2196f3;
    transition: width .3s ease;
    border-radius: 2px;
  }
  .admin-tab.active::after,
  .admin-tab:hover::after { width: 80%; }

  /* ── Table wrapper ── */
  .admin-table-wrapper { animation: fadeInUp .5s .5s ease both; }

  /* ── Rows ── */
  .admin-table tbody tr {
    transition: background .2s, transform .2s, box-shadow .2s;
  }
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
  .btn-modifier {
    background:#e7f3ff; color:#007bff; border:1px solid #cce5ff;
    padding:6px 14px; border-radius:20px; font-weight:600;
    cursor:pointer; transition:all .25s ease; font-size:.8rem;
  }
  .btn-modifier:hover {
    background:#007bff; color:#fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,.3);
  }
  .btn-supprimer {
    background:#ffeef0; color:#dc3545; border:1px solid #f8d7da;
    padding:6px 14px; border-radius:20px; font-weight:600;
    cursor:pointer; transition:all .25s ease; font-size:.8rem;
  }
  .btn-supprimer:hover {
    background:#dc3545; color:#fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220,53,69,.3);
  }

  /* ── Filter chips ── */
  .filter-chip {
    padding:6px 16px; border-radius:20px; border:1px solid #dde3ec;
    background:#fff; color:#555; font-weight:600; font-size:.82rem;
    cursor:pointer; transition:all .2s ease;
  }
  .filter-chip.active, .filter-chip:hover {
    background:#2196f3; color:#fff; border-color:#2196f3;
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(33,150,243,.25);
  }

  /* ── Search ── */
  .admin-search input {
    transition: all .3s ease;
  }
  .admin-search input:focus {
    border-color: #2196f3 !important;
    box-shadow: 0 0 0 3px rgba(33,150,243,.15) !important;
    transform: scale(1.01);
  }

  /* ── Modals ── */
  .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; display:none; align-items:center; justify-content:center; }
  .modal-overlay.open { display:flex; }
  .modal-box {
    background:#fff; border-radius:16px; width:520px; max-width:95vw;
    overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,.25);
    animation: zoomIn .3s ease both;
  }
  .modal-header { background:#e3f2fd; padding:16px 20px; border-bottom:1px solid #bbdefb; text-align:center; }
  .modal-header h3 { margin:0; color:#1976d2; font-family:'Nunito',sans-serif; }
  .modal-body { padding:22px; }
  .modal-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .modal-grid .full { grid-column:span 2; }
  .modal-body label { display:block; font-size:.8rem; font-weight:700; color:#555; margin-bottom:4px; }
  .modal-body input, .modal-body select {
    width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;
    font-size:.9rem; box-sizing:border-box; transition:border .2s;
  }
  .modal-body input:focus, .modal-body select:focus { border-color:#2196f3; outline:none; }
  .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
  .btn-cancel { padding:10px 22px; background:#f5f5f5; border:none; border-radius:8px; cursor:pointer; font-weight:600; transition:.2s; }
  .btn-cancel:hover { background:#e0e0e0; }
  .btn-save { padding:10px 26px; background:#2196f3; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:.2s; }
  .btn-save:hover { background:#1565c0; transform:translateY(-1px); box-shadow:0 4px 12px rgba(33,150,243,.3); }

  /* ── Delete modal ── */
  .delete-box {
    background:#fff; border-radius:16px; width:380px; max-width:95vw;
    padding:30px; text-align:center; box-shadow:0 12px 40px rgba(0,0,0,.25);
    animation: zoomIn .3s ease both;
  }
  .delete-box .del-icon { font-size:2.8rem; margin-bottom:10px; }
  .delete-box h3 { margin:0 0 8px; color:#1a1a2e; }
  .delete-box p { color:#666; font-size:.9rem; margin-bottom:22px; }
  .delete-actions { display:flex; gap:12px; justify-content:center; }
  .btn-del-confirm { padding:10px 24px; background:#dc3545; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:.2s; }
  .btn-del-confirm:hover { background:#c82333; transform:translateY(-1px); }
  .btn-del-cancel  { padding:10px 24px; background:#f5f5f5; border:none; border-radius:8px; font-weight:600; cursor:pointer; transition:.2s; }

  /* Badge */
  .badge-count {
    background:#e53e3e; color:#fff; font-size:10px; font-weight:800;
    padding:2px 6px; border-radius:50px; margin-left:8px;
    display:inline-block; line-height:1; vertical-align:middle;
  }

  /* Vaccine detail cards */
  .vaccine-detail-card {
    animation: fadeInUp .4s ease both;
    transition: transform .3s, box-shadow .3s;
  }
  .vaccine-detail-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,.1);
  }

  /* Add btn */
  .btn.btn-primary.btn-sm {
    transition: all .25s ease !important;
  }
  .btn.btn-primary.btn-sm:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 16px rgba(33,150,243,.35) !important;
  }

  tr.hidden-row { display:none; }
  #resultCount { margin-top:10px; font-size:.85rem; color:#888; text-align:right; }
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
      <a href="admin-vaccines.php" class="active">🧬 Gestion Vaccins</a>
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
        <h1>🧬 Gestion des Vaccins</h1>
        <p>Ajoutez, modifiez et gérez le calendrier vaccinal national</p>
      </div>
      <div class="admin-user">
        <div><div class="admin-role">Administrateur</div></div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

    <?php if (isset($_GET['status'])): ?>
      <?php if ($_GET['status'] === 'updated'): ?>
        <div class="flash-alert">✅ Vaccin modifié avec succès.</div>
      <?php elseif ($_GET['status'] === 'deleted'): ?>
        <div class="flash-alert danger">🗑️ Vaccin supprimé.</div>
      <?php elseif ($_GET['status'] === 'added'): ?>
        <div class="flash-alert">✅ Vaccin ajouté avec succès.</div>
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
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-blue">💉</div>
        <div><span class="admin-stat-num"><?= $total_vaccins ?></span><span class="admin-stat-lbl">Total Vaccins</span></div>
      </div>
    </div>

    <div class="admin-tabs">
      <button class="admin-tab active" data-tab="list">📋 Liste des Vaccins</button>
      <button class="admin-tab" data-tab="calendar">📅 Par Âge</button>
    </div>

    <div class="tab-content active" id="tab-list">
      <div class="admin-table-wrapper">
        <div class="admin-toolbar">
          <div class="admin-toolbar-left" style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;">
            <div class="admin-search" style="position:relative;min-width:240px;">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);">🔍</span>
              <input type="text" id="searchInput" placeholder="Rechercher un vaccin..."
                     style="width:100%;padding:9px 12px 9px 36px;border:1px solid #dde3ec;border-radius:20px;font-size:.87rem;outline:none;">
            </div>
            <div style="display:flex;gap:.5rem;">
              <button class="filter-chip active" data-filter="all">Tous</button>
              <button class="filter-chip" data-filter="Obligatoire">Obligatoire</button>
              <button class="filter-chip" data-filter="Recommandé">Recommandé</button>
            </div>
          </div>
          <button class="btn btn-primary btn-sm btn-round" onclick="openAddModal()">+ Ajouter un vaccin</button>
        </div>

        <table class="admin-table" id="vaccinsTable">
          <thead>
            <tr>
              <th>Vaccin</th><th>Maladie ciblée</th><th>Âge recommandé</th>
              <th>Doses</th><th>Type</th><th>Statut</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="vaccinsBody">
            <?php
            $result = $conn->query("SELECT * FROM vaccin ORDER BY id_vaccin ASC");
            if ($result && $result->num_rows > 0):
              while ($row = $result->fetch_assoc()):
                $typeClass   = ($row['type_vacs'] === 'Obligatoire') ? 'badge-green' : 'badge-orange';
                $statutLabel = !empty($row['statut']) ? htmlspecialchars($row['statut']) : 'Actif';
                $statutClass = ($statutLabel === 'Actif') ? 'status-active' : 'badge-muted';
            ?>
            <tr data-type="<?= htmlspecialchars($row['type_vacs']) ?>"
                data-name="<?= strtolower(htmlspecialchars($row['nom_complet'])) ?>"
                data-disease="<?= strtolower(htmlspecialchars($row['maladie'])) ?>">
              <td>
                <div class="user-cell">
                  <div class="user-cell-avatar" style="background:hsla(200,65%,55%,.1);">💉</div>
                  <div>
                    <div class="user-cell-name"><?= htmlspecialchars($row['nom_complet']) ?></div>
                    <div class="user-cell-email"><?= htmlspecialchars($row['maladie']) ?></div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($row['maladie']) ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($row['age_recommande']) ?></span></td>
              <td>
                <div class="dose-timeline">
                  <?php for ($i=1; $i<=intval($row['nombre_dose']); $i++): ?>
                    <div class="dose-dot completed"><?= $i ?></div>
                    <?php if ($i < intval($row['nombre_dose'])): ?><div class="dose-line"></div><?php endif; ?>
                  <?php endfor; ?>
                </div>
              </td>
              <td><span class="badge <?= $typeClass ?>"><?= htmlspecialchars($row['type_vacs']) ?></span></td>
              <td><span class="badge <?= $statutClass ?>"><?= $statutLabel ?></span></td>
              <td>
                <div class="admin-actions" style="display:flex;gap:6px;">
                  <button class="btn-modifier" onclick='openEditModal(<?= json_encode($row) ?>)'>Modifier</button>
                  <button class="btn-supprimer" onclick="confirmDelete(<?= $row['id_vaccin'] ?>)">Supprimer</button>
                </div>
              </td>
            </tr>
            <?php endwhile;
            else: ?>
            <tr><td colspan="7" style="text-align:center;padding:20px;color:#999;">Aucun vaccin.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div id="resultCount"></div>
      </div>
    </div>

    <div class="tab-content" id="tab-calendar">
      <?php
      $res_ages = mysqli_query($conn, "SELECT DISTINCT age_recommande FROM vaccin ORDER BY
        FIELD(age_recommande,'Naissance','2 mois','3 mois','4 mois','6 mois','9 mois','11 mois','12 mois','18 mois')");
      if ($res_ages && mysqli_num_rows($res_ages) > 0):
        while ($age_row = mysqli_fetch_assoc($res_ages)):
          $cur = $age_row['age_recommande'];
      ?>
        <h2 style="font-family:var(--font-heading);font-size:1.1rem;font-weight:700;margin:1.5rem 0 .75rem;">
          <span class="badge badge-blue" style="font-size:.8rem;">📅 <?= $cur ?></span>
        </h2>
        <?php
        $res_vacs = mysqli_query($conn, "SELECT * FROM vaccin WHERE age_recommande='$cur'");
        while ($vac = mysqli_fetch_assoc($res_vacs)):
          $tc = ($vac['type_vacs'] === 'Obligatoire') ? 'badge-green' : 'badge-muted';
        ?>
        <div class="vaccine-detail-card">
          <div class="vaccine-detail-header">
            <div class="vaccine-detail-title">
              <div class="vaccine-detail-icon">💉</div>
              <div>
                <h3><?= htmlspecialchars($vac['nom_complet']) ?></h3>
                <p><?= htmlspecialchars($vac['maladie']) ?> — <?= intval($vac['nombre_dose']) ?> dose(s)</p>
              </div>
            </div>
            <span class="badge <?= $tc ?>"><?= htmlspecialchars($vac['type_vacs']) ?></span>
          </div>
          <div class="vaccine-detail-body">
            <div class="vaccine-detail-field"><label>Voie</label><span><?= htmlspecialchars($vac['voie_administration'] ?? 'Non spécifié') ?></span></div>
            <div class="vaccine-detail-field"><label>Site d'injection</label><span><?= !empty($vac['site_injection']) ? htmlspecialchars($vac['site_injection']) : 'Non spécifié' ?></span></div>
            <div class="vaccine-detail-field"><label>Statut</label><span style="color:var(--success);font-weight:700;"><?= !empty($vac['statut']) ? htmlspecialchars($vac['statut']) : 'Actif' ?></span></div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php endwhile;
      else: ?>
        <p style="text-align:center;padding:20px;color:#999;">Aucun vaccin ajouté.</p>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- Modal Ajouter -->
<div class="modal-overlay" id="addVaccineModal">
  <div class="modal-box">
    <div class="modal-header"><h3>➕ Ajouter un vaccin</h3></div>
    <form method="POST" class="modal-body">
      <div class="modal-grid">
        <div class="full"><label>Nom complet</label><input type="text" name="nom_complet" required placeholder="Ex: BCG"></div>
        <div><label>Maladie ciblée</label><input type="text" name="maladie" required></div>
        <div><label>Âge recommandé</label>
          <select name="age_recommande" required>
            <option value="">-- Choisir --</option>
            <?php foreach(['Naissance','2 mois','3 mois','4 mois','6 mois','9 mois','11 mois','12 mois','18 mois','2 ans','5 ans'] as $a): ?>
              <option value="<?= $a ?>"><?= $a ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label>Nombre de doses</label><input type="number" name="nombre_dose" min="1" max="10" value="1" required></div>
        <div><label>Type</label>
          <select name="type_vacs" required>
            <option value="Obligatoire">Obligatoire</option>
            <option value="Recommandé">Recommandé</option>
          </select>
        </div>
        <div><label>Voie d'administration</label>
          <select name="voie_administration">
            <option value="">-- Choisir --</option>
            <option value="Intramusculaire">Intramusculaire (IM)</option>
            <option value="Sous-cutanée">Sous-cutanée (SC)</option>
            <option value="Intradermique">Intradermique (ID)</option>
            <option value="Orale">Orale</option>
            <option value="Nasale">Nasale</option>
          </select>
        </div>
        <div class="full"><label>Site d'injection</label>
          <select name="site_injection">
            <option value="">-- Choisir --</option>
            <option value="Cuisse gauche">Cuisse gauche</option>
            <option value="Cuisse droite">Cuisse droite</option>
            <option value="Bras gauche">Bras gauche</option>
            <option value="Bras droit">Bras droit</option>
            <option value="Non applicable">Non applicable</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeAddModal()">Annuler</button>
        <button type="submit" name="ajouter_vaccin_btn" class="btn-save">✅ Ajouter</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Modifier -->
<div class="modal-overlay" id="editVaccineModal">
  <div class="modal-box">
    <div class="modal-header"><h3>✏️ Modifier le vaccin</h3></div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="id_vaccin" id="edit_id">
      <div class="modal-grid">
        <div class="full"><label>Nom complet</label><input type="text" name="nom_complet" id="edit_nom" required></div>
        <div><label>Maladie ciblée</label><input type="text" name="maladie" id="edit_maladie"></div>
        <div><label>Âge recommandé</label>
          <select name="age_recommande" id="edit_age">
            <?php foreach(['Naissance','2 mois','3 mois','4 mois','6 mois','9 mois','11 mois','12 mois','18 mois','2 ans','5 ans'] as $a): ?>
              <option value="<?= $a ?>"><?= $a ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label>Doses</label><input type="number" name="nombre_dose" id="edit_doses" min="1" max="10"></div>
        <div><label>Type</label>
          <select name="type_vacs" id="edit_type">
            <option value="Obligatoire">Obligatoire</option>
            <option value="Recommandé">Recommandé</option>
          </select>
        </div>
        <div><label>Statut</label>
          <select name="statut" id="edit_statut">
            <option value="Actif">Actif</option>
            <option value="Inactif">Inactif</option>
          </select>
        </div>
        <div><label>Voie d'administration</label>
          <select name="voie_administration" id="edit_voie">
            <option value="">-- Choisir --</option>
            <option value="Intramusculaire">Intramusculaire (IM)</option>
            <option value="Sous-cutanée">Sous-cutanée (SC)</option>
            <option value="Intradermique">Intradermique (ID)</option>
            <option value="Orale">Orale</option>
            <option value="Nasale">Nasale</option>
          </select>
        </div>
        <div><label>Site d'injection</label>
          <select name="site_injection" id="edit_site">
            <option value="">-- Choisir --</option>
            <option value="Cuisse gauche">Cuisse gauche</option>
            <option value="Cuisse droite">Cuisse droite</option>
            <option value="Bras gauche">Bras gauche</option>
            <option value="Bras droit">Bras droit</option>
            <option value="Non applicable">Non applicable</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuler</button>
        <button type="submit" name="modifier_vaccin_btn" class="btn-save">💾 Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Suppression -->
<div class="modal-overlay" id="customDeleteModal">
  <div class="delete-box">
    <div class="del-icon">🗑️</div>
    <h3>Confirmer la suppression</h3>
    <p>Cette action est irréversible.</p>
    <div class="delete-actions">
      <button class="btn-del-cancel" onclick="closeDeleteModal()">Annuler</button>
      <a id="confirmDeleteLink" href="#" class="btn-del-confirm">Supprimer</a>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.admin-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    tab.classList.add('active');
    const el = document.getElementById('tab-' + tab.dataset.tab);
    if (el) el.classList.add('active');
  });
});

let currentFilter = 'all', currentSearch = '';

document.querySelectorAll('.filter-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    currentFilter = chip.dataset.filter;
    applyFilters();
  });
});

document.getElementById('searchInput').addEventListener('input', function() {
  currentSearch = this.value.toLowerCase().trim();
  applyFilters();
});

function applyFilters() {
  const rows = document.querySelectorAll('#vaccinsBody tr[data-type]');
  let visible = 0;
  rows.forEach(row => {
    const matchType   = currentFilter === 'all' || row.dataset.type === currentFilter;
    const matchSearch = !currentSearch || row.dataset.name.includes(currentSearch) || row.dataset.disease.includes(currentSearch);
    if (matchType && matchSearch) { row.style.display = ''; visible++; }
    else row.style.display = 'none';
  });
  const el = document.getElementById('resultCount');
  if (el) el.textContent = visible + ' vaccin(s) affiché(s)';
}
applyFilters();

function openAddModal()  { document.getElementById('addVaccineModal').classList.add('open'); }
function closeAddModal() { document.getElementById('addVaccineModal').classList.remove('open'); }

function openEditModal(v) {
  document.getElementById('edit_id').value      = v.id_vaccin;
  document.getElementById('edit_nom').value     = v.nom_complet;
  document.getElementById('edit_maladie').value = v.maladie;
  document.getElementById('edit_doses').value   = v.nombre_dose;
  document.getElementById('edit_statut').value  = v.statut || 'Actif';
  setVal('edit_age',  v.age_recommande);
  setVal('edit_type', v.type_vacs);
  setVal('edit_voie', v.voie_administration || '');
  setVal('edit_site', v.site_injection || '');
  document.getElementById('editVaccineModal').classList.add('open');
}
function setVal(id, val) {
  const sel = document.getElementById(id);
  if (!sel) return;
  for (let o of sel.options) if (o.value === val) { sel.value = val; return; }
  if (val) { sel.add(new Option(val, val, true, true)); sel.value = val; }
}
function closeEditModal() { document.getElementById('editVaccineModal').classList.remove('open'); }

function confirmDelete(id) {
  document.getElementById('confirmDeleteLink').href = "admin-vaccines.php?delete_id=" + id;
  document.getElementById('customDeleteModal').classList.add('open');
}
function closeDeleteModal() { document.getElementById('customDeleteModal').classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
});

setTimeout(() => {
  document.querySelectorAll('.flash-alert').forEach(a => {
    a.style.transition = 'opacity .5s'; a.style.opacity = '0';
    setTimeout(() => a.remove(), 500);
  });
}, 4000);
</script>
</body>
</html>