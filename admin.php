
<?php
session_start();
include('db.php');

if (!isset($_SESSION['id_docteur']) || empty($_SESSION['id_docteur'])) {
    header("Location: loginp.php"); exit();
}

$nom_doc    = $_SESSION['nom']    ?? 'Médecin';
$prenom_doc = $_SESSION['prenom'] ?? '';
$id_doc     = intval($_SESSION['id_docteur']);

// ─── Statistiques vaccins globales ────────────────────────
$r1 = mysqli_query($conn, "SELECT COUNT(*) c FROM enfant");
$stat_enfants = $r1 ? (mysqli_fetch_assoc($r1)['c'] ?? 0) : 0;

$r2 = mysqli_query($conn, "SELECT COUNT(*) c FROM vaccination WHERE statut='Réalisé'");
$stat_realises = $r2 ? (mysqli_fetch_assoc($r2)['c'] ?? 0) : 0;

$r3 = mysqli_query($conn, "SELECT COUNT(*) c FROM vaccin");
$stat_catalogue = $r3 ? (mysqli_fetch_assoc($r3)['c'] ?? 0) : 0;

$r4 = mysqli_query($conn, "SELECT COUNT(*) c FROM vaccination WHERE statut='Prévu' AND date_prevue >= CURDATE()");
$stat_prevus = $r4 ? (mysqli_fetch_assoc($r4)['c'] ?? 0) : 0;

$r5 = mysqli_query($conn, "SELECT COUNT(*) c FROM vaccination WHERE statut IN ('Prévu','En retard') AND date_prevue < CURDATE()");
$stat_retard = $r5 ? (mysqli_fetch_assoc($r5)['c'] ?? 0) : 0;

$r6 = mysqli_query($conn, "SELECT COUNT(*) c FROM rendez_vous WHERE id_docteur=$id_doc AND date_rdv=CURDATE()");
$stat_today = $r6 ? (mysqli_fetch_assoc($r6)['c'] ?? 0) : 0;

// ─── Tableau suivi vaccinal par enfant ─────────────────────
$sql_enfants_vac = "
    SELECT
        e.id_enfant, e.prenom_enfant, e.nom_enfant,
        e.date_naissance, e.genre,
        p.prenom_parent, p.nom_parent,
        COUNT(v.id_vaccination)                                         AS total_vac,
        SUM(v.statut = 'Réalisé')                                       AS nb_realise,
        SUM(v.statut IN ('Prévu','En retard') AND v.date_prevue < CURDATE()) AS nb_retard,
        SUM(v.statut = 'Prévu' AND v.date_prevue >= CURDATE())          AS nb_prevu
    FROM enfant e
    JOIN parent p ON p.id_parent = e.id_parent
    LEFT JOIN vaccination v ON v.id_enfant = e.id_enfant
    GROUP BY e.id_enfant
    ORDER BY nb_retard DESC, e.nom_enfant ASC
";
$res_vac_list = mysqli_query($conn, $sql_enfants_vac);
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
    .stat-icon-teal   { background:hsla(174,72%,42%,.12); color:#0d9488; }
    .stat-icon-orange { background:hsla(25,95%,53%,.12);  color:#ea580c; }
    .stat-icon-purple { background:hsla(262,83%,58%,.12); color:#7c3aed; }
    .stat-icon-red    { background:hsla(0,84%,60%,.12);   color:#dc2626; }
    .stat-blink { animation:blink 1.4s infinite; }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }
    .sidebar-nav a { display:flex; align-items:center; gap:.65rem; }
    .sidebar-nav a .nav-icon { font-size:1rem; width:22px; text-align:center; }
    .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
    .section-header h2 { font-family:'Nunito',sans-serif; font-size:1.05rem; font-weight:800; color:#1a1a2e; margin:0; }
    .admin-search { position:relative; }
    .admin-search input { width:100%; padding:9px 12px 9px 36px; border:1px solid #dde3ec; border-radius:20px; font-size:.87rem; outline:none; transition:border .2s; background:#fff; }
    .admin-search input:focus { border-color:#2196f3; }
    .admin-search span { position:absolute; left:12px; top:50%; transform:translateY(-50%); }
    .user-cell { display:flex; align-items:center; gap:.65rem; }
    .user-cell-avatar { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .user-cell-name  { font-weight:700; font-size:.9rem; color:#1a1a2e; }
    .user-cell-email { font-size:.75rem; color:#888; margin-top:1px; }
    #resultCount { font-size:.82rem; color:#888; }
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
      <a href="admin.php" class="active">📊 Tableau de bord</a>
      <a href="dossiers-patients.php">👶 Liste des Enfants</a>
      <a href="calendrier-vaccins.php" >📅 Liste rendez-vous</a>
      <a href="historique.php">📋 Historique</a>
      <a href="index.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">

    <div class="admin-topbar">
      <div>
        <h1 class="page-title">Salut, <?= htmlspecialchars($prenom_doc.' '.$nom_doc) ?> ! 👋</h1>
        <p>Gérez vos patients, rendez-vous et suivis vaccinaux</p>
      </div>
      <div class="admin-user">
        <div>
          <div class="admin-name"><?= htmlspecialchars($prenom_doc.' '.$nom_doc) ?></div>
          <div class="admin-role">Médecin</div>
        </div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

    <!-- Statistiques vaccins globales — ligne 1 -->
    <div class="admin-stats" style="grid-template-columns:repeat(3,1fr);">
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-teal">👶</div>
        <div>
          <span class="admin-stat-num"><?= $stat_enfants ?></span>
          <span class="admin-stat-lbl">Enfants enregistrés</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-orange">💉</div>
        <div>
          <span class="admin-stat-num" style="color:#0d9488;"><?= $stat_realises ?></span>
          <span class="admin-stat-lbl">Vaccinations réalisées</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-purple">📋</div>
        <div>
          <span class="admin-stat-num"><?= $stat_catalogue ?></span>
          <span class="admin-stat-lbl">Vaccins au catalogue</span>
        </div>
      </div>
    </div>

    <!-- Statistiques vaccins globales — ligne 2 -->
    <div class="admin-stats" style="grid-template-columns:repeat(3,1fr);margin-top:1rem;">
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-purple">🗓️</div>
        <div>
          <span class="admin-stat-num"><?= $stat_prevus ?></span>
          <span class="admin-stat-lbl">Vaccinations prévues</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-red <?= $stat_retard > 0 ? 'stat-blink' : '' ?>">⚠️</div>
        <div>
          <span class="admin-stat-num" style="<?= $stat_retard > 0 ? 'color:#dc2626;' : '' ?>">
            <?= $stat_retard ?>
          </span>
          <span class="admin-stat-lbl">Vaccinations en retard</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-orange">📌</div>
        <div>
          <span class="admin-stat-num"><?= $stat_today ?></span>
          <span class="admin-stat-lbl">RDV aujourd'hui</span>
        </div>
      </div>
    </div>

    <!-- Tableau suivi vaccinal par enfant -->
    <div class="admin-table-wrapper" style="margin-top:1.5rem;">
      <div class="section-header">
        <h2>💉 Suivi vaccinal — Vue globale</h2>
        <span id="resultCount"></span>
      </div>
      <div style="margin-bottom:1rem;">
        <div class="admin-search" style="max-width:300px;">
          <span>🔍</span>
          <input type="text" id="searchVac" placeholder="Chercher un enfant...">
        </div>
      </div>

      <table class="admin-table">
        <thead>
          <tr>
            <th>Enfant</th>
            <th>Parent</th>
            <th>Réalisés</th>
            <th>Prévus</th>
            <th>En retard</th>
            <th>Progression</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="vacBody">
          <?php if ($res_vac_list && mysqli_num_rows($res_vac_list) > 0):
            while ($ev = mysqli_fetch_assoc($res_vac_list)):
              $genre   = strtolower($ev['genre'] ?? '');
              $avatar  = ($genre === 'garcon' || $genre === 'm') ? '👦' : '👧';
              $total   = intval($ev['total_vac']);
              $realise = intval($ev['nb_realise']);
              $retard  = intval($ev['nb_retard']);
              $prevu   = intval($ev['nb_prevu']);
              $pct     = $total > 0 ? round(($realise / $total) * 100) : 0;
              $age_j   = (new DateTime($ev['date_naissance']))->diff(new DateTime())->days;
              $age_txt = $age_j < 365 ? round($age_j/30).' mois' : round($age_j/365,1).' ans';
              $bar_color = $pct >= 80
                ? 'linear-gradient(90deg,#10b981,#34d399)'
                : ($pct >= 40 ? 'linear-gradient(90deg,#f59e0b,#fbbf24)'
                              : 'linear-gradient(90deg,#ef4444,#f87171)');
          ?>
          <tr class="vac-row"
              data-search="<?= strtolower(htmlspecialchars($ev['prenom_enfant'].' '.$ev['nom_enfant'].' '.$ev['prenom_parent'].' '.$ev['nom_parent'])) ?>">
            <td>
              <div class="user-cell">
                <div class="user-cell-avatar" style="background:hsla(174,72%,42%,.1);"><?= $avatar ?></div>
                <div>
                  <div class="user-cell-name"><?= htmlspecialchars($ev['prenom_enfant'].' '.$ev['nom_enfant']) ?></div>
                  <div class="user-cell-email">🎂 <?= $age_txt ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:.85rem;">
              👤 <?= htmlspecialchars($ev['prenom_parent'].' '.$ev['nom_parent']) ?>
            </td>
            <td>
              <span style="background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:20px;font-size:.78rem;font-weight:700;">
                ✅ <?= $realise ?>
              </span>
            </td>
            <td>
              <span style="background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:20px;font-size:.78rem;font-weight:700;">
                🗓️ <?= $prevu ?>
              </span>
            </td>
            <td>
              <?php if ($retard > 0): ?>
              <span style="background:#fee2e2;color:#dc2626;padding:4px 10px;border-radius:20px;font-size:.78rem;font-weight:700;">
                ⚠️ <?= $retard ?>
              </span>
              <?php else: ?>
              <span style="color:#aaa;font-size:.82rem;">—</span>
              <?php endif; ?>
            </td>
            <td style="min-width:130px;">
              <div style="display:flex;align-items:center;gap:.5rem;">
                <div style="flex:1;height:7px;background:#f1f5f9;border-radius:10px;overflow:hidden;">
                  <div style="width:<?= $pct ?>%;height:100%;border-radius:10px;background:<?= $bar_color ?>;transition:width .5s;"></div>
                </div>
                <span style="font-size:.75rem;font-weight:700;color:#555;white-space:nowrap;"><?= $pct ?>%</span>
              </div>
            </td>
            <td>
              <a href="dossier-enfant.php?id=<?= $ev['id_enfant'] ?>"
                 style="padding:6px 14px;border-radius:10px;background:#e0f2fe;color:#0369a1;
                        font-size:.8rem;font-weight:700;text-decoration:none;display:inline-block;transition:.2s;"
                 onmouseover="this.style.background='#2196f3';this.style.color='#fff'"
                 onmouseout="this.style.background='#e0f2fe';this.style.color='#0369a1'">
                 Dossier
              </a>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr>
            <td colspan="7" style="text-align:center;padding:2.5rem;color:#aaa;">
              <div style="font-size:2.5rem;margin-bottom:.8rem;">👶</div>
              <p>Aucun enfant enregistré pour le moment.</p>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div id="noVacResult" style="display:none;text-align:center;padding:2rem;color:#aaa;">
        Aucun enfant trouvé.
      </div>
    </div>

  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchVac');
  const rows        = document.querySelectorAll('.vac-row');
  const noRes       = document.getElementById('noVacResult');
  const countEl     = document.getElementById('resultCount');

  function filterTable() {
    const q = searchInput ? searchInput.value.toLowerCase().trim() : '';
    let v = 0;
    rows.forEach(row => {
      const s = row.dataset.search || '';
      if (!q || s.includes(q)) { row.style.display=''; v++; }
      else row.style.display='none';
    });
    if (countEl) countEl.textContent = v + ' enfant(s)';
    if (noRes)   noRes.style.display = v === 0 ? 'block' : 'none';
  }

  if (searchInput) searchInput.addEventListener('input', filterTable);
  filterTable();
});
</script>
</body>
</html>
