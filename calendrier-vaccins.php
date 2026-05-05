<?php
session_start();
include('db.php');

$nom_doc    = $_SESSION['nom']    ?? 'Médecin';
$prenom_doc = $_SESSION['prenom'] ?? '';
$email_doc  = $_SESSION['email']  ?? '';
$id_doc     = $_SESSION['id_docteur']  ;

// ─── Action : Valider une vaccination ──────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'valider' && isset($_GET['id_rdv'])) {
    $id_rdv = intval($_GET['id_rdv']);

    // Marquer le RDV comme Terminé
    mysqli_query($conn, "UPDATE rendez_vous SET statut='Confirmé' WHERE id_rdv=$id_rdv");

    // Mettre à jour la vaccination liée
    $rdv_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_enfant, id_vaccin FROM rendez_vous WHERE id_rdv=$id_rdv"));
    if ($rdv_row) {
        mysqli_query($conn,
            "UPDATE vaccination SET statut='Réalisé', date_effectuee=CURDATE()
             WHERE id_enfant={$rdv_row['id_enfant']} AND id_vaccin={$rdv_row['id_vaccin']}
             AND statut='Prévu' LIMIT 1");
    }
    header("Location: admin.php?msg=validated");
    exit();
}

// ─── Statistiques dynamiques ──────────────────────────────
// 1. Patients (enfants uniques suivis par ce médecin)
$stat_patients = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT id_enfant) c FROM rendez_vous WHERE id_docteur=$id_doc"))['c'] ?? 0;

// 2. Vaccinations prévues aujourd'hui
$stat_today = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM rendez_vous
     WHERE id_docteur=$id_doc AND date_rdv=CURDATE() AND statut != 'Confirmé'"))['c'] ?? 0;

// 3. RDV cette semaine
//$stat_week = mysqli_fetch_assoc(
 $sql= mysqli_query($conn,"SELECT COUNT(*) c FROM rendez_vous
     WHERE id_docteur='$id_doc' and statut='En attente'");
     $stat_week = mysqli_fetch_assoc($sql);
       //AND 
       //statut='En attente'"));
  //YEARWEEK(date_rdv, 1) = YEARWEEK(CURDATE(), 1)"))['c'] ?? 0;

// 4. Vaccinations en retard (date_prevue < aujourd'hui et statut='Prévu')
$stat_retard = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM vaccination v
     JOIN rendez_vous r ON r.id_enfant = v.id_enfant
     WHERE r.id_docteur=$id_doc
       AND v.statut='Prévu'
       AND v.date_prevue < CURDATE()"))['c'] ?? 0;

// ─── Prochains RDV (aujourd'hui + à venir) ─────────────────
$sql_rdv = "
    SELECT r.id_rdv, r.date_rdv, r.heure_rdv, r.statut,
           e.prenom_enfant, e.nom_enfant,
           p.nom_parent, p.prenom_parent,
           v.nom_complet AS nom_vaccin
    FROM rendez_vous r
    JOIN enfant  e ON e.id_enfant  = r.id_enfant
    JOIN parent  p ON p.id_parent  = r.id_parent
    LEFT JOIN vaccin v ON v.id_vaccin = r.id_vaccin
    WHERE r.id_docteur = '$id_doc'
      AND r.date_rdv >= CURDATE()
      
    ORDER BY r.date_rdv ASC, r.heure_rdv ASC
    LIMIT 20
";
$res_rdv = mysqli_query($conn, $sql_rdv);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Espace Médecin — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="admin-vaccines.css">
  <style>
    /* ── Flash alert ── */
    .flash-alert {
      padding: 12px 18px; border-radius: 12px; margin-bottom: 1.2rem;
      display: flex; align-items: center; gap: 10px; font-size: .9rem;
      font-weight: 600; animation: slideDown .4s ease;
    }
    .flash-alert.success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
    .flash-alert.danger  { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
    @keyframes slideDown {
      from { transform: translateY(-10px); opacity: 0; }
      to   { transform: translateY(0);     opacity: 1; }
    }

    /* ── Sidebar spécifique médecin ── */
    .sidebar-section-label {
      font-size: .7rem; font-weight: 800; color: #a0aec0;
      text-transform: uppercase; letter-spacing: .08em;
      padding: .9rem 1.2rem .3rem; display: block;
    }
    .sidebar-nav a {
      display: flex; align-items: center; gap: .65rem;
    }
    .sidebar-nav a .nav-icon { font-size: 1rem; width: 22px; text-align: center; }

    /* ── Stat cards couleurs médecin ── */
    .stat-icon-teal   { background: hsla(174,72%,42%,.12); color: #0d9488; }
    .stat-icon-orange { background: hsla(25,95%,53%,.12);  color: #ea580c; }
    .stat-icon-purple { background: hsla(262,83%,58%,.12); color: #7c3aed; }
    .stat-icon-red    { background: hsla(0,84%,60%,.12);   color: #dc2626; }

    /* ── Stat retard clignotant si > 0 ── */
    .stat-blink { animation: blink 1.4s infinite; }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }

    /* ── Badges statut RDV ── */
    .badge-confirmed { background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:20px; font-size:.78rem; font-weight:700; white-space:nowrap; }
    .badge-pending   { background:#fff3cd; color:#92400e; padding:4px 10px; border-radius:20px; font-size:.78rem; font-weight:700; white-space:nowrap; }
    .badge-today     { background:#dbeafe; color:#1e40af; padding:4px 10px; border-radius:20px; font-size:.78rem; font-weight:700; white-space:nowrap; }

    /* ── Bouton valider ── */
    .btn-valider {
      padding: 7px 16px; border-radius: 20px; font-size: .82rem;
      font-weight: 700; cursor: pointer; border: none;
      font-family: 'Quicksand', sans-serif; transition: .2s;
      background: #d1fae5; color: #065f46; text-decoration: none;
      display: inline-block;
    }
    .btn-valider:hover { background: #10b981; color: #fff; }

    .btn-voir {
      padding: 7px 14px; border-radius: 20px; font-size: .82rem;
      font-weight: 700; cursor: pointer; border: none;
      font-family: 'Quicksand', sans-serif; transition: .2s;
      background: #e0f2fe; color: #0369a1; text-decoration: none;
      display: inline-block;
    }
    .btn-voir:hover { background: #2196f3; color: #fff; }

    /* ── Enfant + parent cell ── */
    .patient-cell { display: flex; flex-direction: column; }
    .patient-name { font-weight: 700; font-size: .92rem; color: #1a1a2e; }
    .patient-parent { font-size: .78rem; color: #888; margin-top: 2px; }

    /* ── Heure pill ── */
    .heure-pill {
      background: #f1f5f9; color: #475569; padding: 4px 10px;
      border-radius: 8px; font-size: .82rem; font-weight: 700;
      font-family: 'Nunito', monospace; display: inline-block;
    }
    .heure-pill.today { background: #dbeafe; color: #1d4ed8; }

    /* ── Date badge ── */
    .date-badge {
      font-size: .8rem; color: #64748b; font-weight: 600;
    }
    .date-badge.today-date { color: #2563eb; font-weight: 800; }

    /* ── Vaccin tag ── */
    .vaccin-tag {
      font-size: .8rem; background: #f0fdf4; color: #166534;
      padding: 3px 8px; border-radius: 8px; font-weight: 600;
      display: inline-block; max-width: 180px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ── Empty state ── */
    .empty-rdv {
      text-align: center; padding: 3rem; color: #aaa;
    }
    .empty-rdv .empty-icon { font-size: 3rem; margin-bottom: .8rem; display: block; }

    /* ── Section header ── */
    .section-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 1.2rem;
    }
    .section-header h2 {
      font-family: 'Nunito', sans-serif; font-size: 1.05rem;
      font-weight: 800; color: #1a1a2e; margin: 0;
    }

    /* ── Search ── */
    .admin-search { position: relative; min-width: 260px; }
    .admin-search input {
      width: 100%; padding: 9px 12px 9px 36px;
      border: 1px solid #dde3ec; border-radius: 20px;
      font-size: .87rem; outline: none; transition: border .2s;
    }
    .admin-search input:focus { border-color: #2196f3; }
    .admin-search span { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); }

    /* ── Badge count sidebar ── */
    .badge-count {
      background: #ff0000; color: #fff; font-size: 10px; font-weight: 800;
      padding: 2px 6px; border-radius: 50px; margin-left: auto;
      display: inline-block; line-height: 1;
    }

    /* ── Résultat count ── */
    #resultCount { font-size: .82rem; color: #888; }
  </style>
</head>
<body class="admin-blue">
<div class="app-wrapper">

  <!-- ===== Sidebar Médecin ===== -->
<aside class="sidebar">
    <div class="logo">
      <div class="logo-icon">💉</div>
      <span class="logo-text">Talqih<span class="text-blue">Sghiri</span></span>
    </div>
    <nav class="sidebar-nav">
      <a href="admin.php" >📊 Tableau de bord</a>
      <a href="dossiers-patients.php">👶 Liste des Enfants</a>
      <a href="calendrier-vaccins.php" class="active">📅 Liste rendez-vous</a>
      <a href="historique.php">📋 Historique</a>
      <a href="index.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <!-- ===== Main Content ===== -->
  <main class="main-content">

    <!-- Top bar -->
    <div class="admin-topbar">
      <div>
        <h1 class="page-title">Salut, <?= htmlspecialchars($prenom_doc . ' ' . $nom_doc) ?> ! 👋</h1>
        <p>Gérez vos patients, rendez-vous et suivis vaccinaux</p>
      </div>
      <div class="admin-user">
        <div>
          <div class="admin-name"><?= htmlspecialchars($prenom_doc . ' ' . $nom_doc) ?></div>
          <div class="admin-role">Médecin</div>
        </div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

    <!-- Flash message -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'validated'): ?>
      <div class="flash-alert success">✅ Rendez vous confirmé avec succès.</div>
    <?php endif; ?>

    <!-- ─── Statistiques ─── -->
    <div class="admin-stats">

      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-teal">👶</div>
        <div>
          <span class="admin-stat-num"><?= $stat_patients ?></span>
          <span class="admin-stat-lbl">Enfants suivis</span>
        </div>
      </div>

      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-orange">💉</div>
        <div>
          <span class="admin-stat-num"><?= $stat_today ?></span>
          <span class="admin-stat-lbl">Vaccinations aujourd'hui</span>
        </div>
      </div>

      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-purple">📅</div>
        <div>
          <span class="admin-stat-num"><?= $stat_week['c']; ?></span>
          <span class="admin-stat-lbl">RDV en attente</span>
        </div>
      </div>

      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-red <?= $stat_retard > 0 ? 'stat-blink' : '' ?>">⚠️</div>
        <div>
          <span class="admin-stat-num" style="<?= $stat_retard > 0 ? 'color:#dc2626;' : '' ?>">
            <?= $stat_retard ?>
          </span>
          <span class="admin-stat-lbl">Vaccins en retard</span>
        </div>
      </div>

    </div>

    <!-- ─── Tableau des prochains RDV ─── -->
    <div class="admin-table-wrapper">

      <div class="section-header">
        <h2>📅 Prochains Rendez-vous</h2>
        <span id="resultCount"></span>
      </div>

      <div class="admin-toolbar" style="margin-bottom:1rem;">
        <div class="admin-search">
          <span>🔍</span>
          <input type="text" id="searchRdv" placeholder="Rechercher un enfant, parent, vaccin...">
        </div>
        <div style="display:flex;gap:.6rem;">
          <button class="filter-chip active" data-filter="all">Tous</button>
          <button class="filter-chip" data-filter="aujourd'hui">Aujourd'hui</button>
          <button class="filter-chip" data-filter="Confirmé">Confirmé</button>
          <button class="filter-chip" data-filter="En attente">En attente</button>
        </div>
      </div>

      <table class="admin-table" id="rdvTable">
        <thead>
          <tr>
            <th>Patient (Enfant)</th>
            <th>Parent</th>
            <th>Vaccin</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="rdvBody">
          <?php
          $today = date('Y-m-d');
          if ($res_rdv && mysqli_num_rows($res_rdv) > 0):
            while ($rdv = mysqli_fetch_assoc($res_rdv)):
              $is_today    = ($rdv['date_rdv'] === $today);
              $statut_db   = $rdv['statut'];
              $badge_class = match(true) {
                $is_today           => 'badge-today',
                $statut_db === 'Confirmé'   => 'badge-confirmed',
                default             => 'badge-pending',
              };
              $statut_label = $is_today ? 'Aujourd\'hui' : $statut_db;
              $date_fmt     = date('d/m/Y', strtotime($rdv['date_rdv']));
              $heure_fmt    = substr($rdv['heure_rdv'], 0, 5);
          ?>
          <tr class="rdv-row"
              data-statut="<?= htmlspecialchars($statut_db) ?>"
              data-today="<?= $is_today ? 'aujourd\'hui' : '' ?>"
              data-search="<?= strtolower(htmlspecialchars(
                $rdv['prenom_enfant'] . ' ' . $rdv['nom_enfant'] . ' ' .
                $rdv['prenom_parent'] . ' ' . $rdv['nom_parent'] . ' ' .
                ($rdv['nom_vaccin'] ?? '')
              )) ?>">

            <td>
              <div class="user-cell">
                <div class="user-cell-avatar" style="background:hsla(174,72%,42%,.1);">👶</div>
                <div class="patient-cell">
                  <span class="patient-name">
                    <?= htmlspecialchars($rdv['prenom_enfant'] . ' ' . $rdv['nom_enfant']) ?>
                  </span>
                </div>
              </div>
            </td>

            <td>
              <span class="patient-parent">
                👤 <?= htmlspecialchars($rdv['prenom_parent'] . ' ' . $rdv['nom_parent']) ?>
              </span>
            </td>

            <td>
              <span class="vaccin-tag" title="<?= htmlspecialchars($rdv['nom_vaccin'] ?? '') ?>">
                💉 <?= htmlspecialchars($rdv['nom_vaccin'] ?? '—') ?>
              </span>
            </td>

            <td>
              <span class="date-badge <?= $is_today ? 'today-date' : '' ?>">
                <?= $is_today ? '📌 Aujourd\'hui' : '📅 ' . $date_fmt ?>
              </span>
            </td>

            <td>
              <span class="heure-pill <?= $is_today ? 'today' : '' ?>">
                🕐 <?= $heure_fmt ?>
              </span>
            </td>


            <td>
              <span class="badge <?= $badge_class ?>"><?= $statut_label ?></span>
            </td>

            <td>
              <div class="admin-actions">
                <a href="admin.php?action=valider&id_rdv=<?= $rdv['id_rdv'] ?>"
                   class="btn-valider"
                   onclick="return confirm('Confirmer la vaccination de <?= htmlspecialchars($rdv['prenom_enfant']) ?> ?');">
                  confirmé 
                </a>
                <a href="dossier-enfant.php?id=<?= $rdv['id_enfant'] ?? '' ?>"
                   class="btn-voir">
                   Supprimer
                </a>
              </div>
            </td>

          </tr>
          <?php endwhile;
          else: ?>
          <tr id="emptyRow">
            <td colspan="8">
              <div class="empty-rdv">
                <span class="empty-icon">📬</span>
                <p>Aucun rendez-vous à venir pour le moment.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div id="noResult" style="display:none;text-align:center;padding:2rem;color:#aaa;">
        Aucun résultat trouvé.
      </div>

    </div><!-- /admin-table-wrapper -->

  </main>
</div>

<style>
  /* ── Filtre chips ── */
  .filter-chip {
    padding: 6px 16px; border-radius: 20px; border: 1px solid #dde3ec;
    background: #fff; color: #555; font-weight: 600; font-size: .82rem;
    cursor: pointer; transition: .2s; font-family: 'Quicksand', sans-serif;
  }
  .filter-chip.active, .filter-chip:hover {
    background: #2196f3; color: #fff; border-color: #2196f3;
  }
  .admin-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
  .admin-actions { display: flex; gap: .5rem; }
</style>

<script>
// ── Filtre + Recherche ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

  const searchInput = document.getElementById('searchRdv');
  const chips       = document.querySelectorAll('.filter-chip');
  const rows        = document.querySelectorAll('.rdv-row');
  const noResult    = document.getElementById('noResult');
  const countEl     = document.getElementById('resultCount');

  let activeFilter = 'all';
  let searchText   = '';

  function applyFilters() {
    let visible = 0;
    rows.forEach(row => {
      const statut  = row.dataset.statut  || '';
      const today   = row.dataset.today   || '';
      const search  = row.dataset.search  || '';

      let matchFilter = false;
      if (activeFilter === 'all')          matchFilter = true;
      else if (activeFilter === "aujourd'hui") matchFilter = today === "aujourd'hui";
      else matchFilter = statut === activeFilter;

      const matchSearch = !searchText || search.includes(searchText);

      if (matchFilter && matchSearch) { row.style.display = ''; visible++; }
      else row.style.display = 'none';
    });

    countEl.textContent = visible + ' rendez-vous affiché(s)';
    noResult.style.display = visible === 0 && rows.length > 0 ? 'block' : 'none';
  }

  chips.forEach(chip => {
    chip.addEventListener('click', function () {
      chips.forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      activeFilter = this.dataset.filter;
      applyFilters();
    });
  });

  searchInput.addEventListener('input', function () {
    searchText = this.value.toLowerCase().trim();
    applyFilters();
  });

  applyFilters();

  // Auto-masquer flash alert
  setTimeout(() => {
    document.querySelectorAll('.flash-alert').forEach(a => a.style.display = 'none');
  }, 4000);
});
</script>

</body>
</html>