<?php
session_start();
include('db.php');

if (!isset($_SESSION['id_docteur']) || empty($_SESSION['id_docteur'])) {
    header("Location: loginp.php");
    exit();
}

$nom_doc    = $_SESSION['nom']        ?? 'Médecin';
$prenom_doc = $_SESSION['prenom']     ?? '';
$id_doc     = intval($_SESSION['id_docteur']);

// ✅ CORRECTION : suppression de r.lieu qui n'existe pas dans rendez_vous
$sql_hist = "
    SELECT
        r.id_rdv, r.date_rdv, r.heure_rdv, r.statut AS statut_rdv,
        e.id_enfant, e.prenom_enfant, e.nom_enfant, e.date_naissance, e.genre,
        p.prenom_parent, p.nom_parent, p.telephone,
        v.nom_complet AS nom_vaccin, v.type_vacs,
        vac.date_effectuee, vac.statut AS statut_vac
    FROM rendez_vous r
    JOIN enfant  e   ON e.id_enfant  = r.id_enfant
    JOIN parent  p   ON p.id_parent  = r.id_parent
    LEFT JOIN vaccin v   ON v.id_vaccin  = r.id_vaccin
    LEFT JOIN vaccination vac ON vac.id_enfant = r.id_enfant
                              AND vac.id_vaccin = r.id_vaccin
    WHERE r.id_docteur = $id_doc
      AND (r.statut = 'Terminé' OR vac.statut = 'Réalisé')
    GROUP BY r.id_rdv
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
";
$res_hist = mysqli_query($conn, $sql_hist);

$all_rows = [];
if ($res_hist && mysqli_num_rows($res_hist) > 0) {
    while ($row = mysqli_fetch_assoc($res_hist)) {
        $all_rows[] = $row;
    }
}
$total_hist = count($all_rows);

$r1 = mysqli_query($conn,
    "SELECT COUNT(*) c FROM vaccination vac
     JOIN rendez_vous r ON r.id_enfant=vac.id_enfant AND r.id_vaccin=vac.id_vaccin
     WHERE r.id_docteur=$id_doc AND vac.statut='Réalisé'");
$stat_realises = ($r1) ? (mysqli_fetch_assoc($r1)['c'] ?? 0) : 0;

$r2 = mysqli_query($conn,
    "SELECT COUNT(DISTINCT r.id_enfant) c FROM rendez_vous r
     WHERE r.id_docteur=$id_doc AND r.statut='Terminé'");
$stat_enfants = ($r2) ? (mysqli_fetch_assoc($r2)['c'] ?? 0) : 0;

$r3 = mysqli_query($conn,
    "SELECT COUNT(*) c FROM rendez_vous r
     WHERE r.id_docteur=$id_doc AND r.statut='Terminé'
       AND MONTH(r.date_rdv)=MONTH(CURDATE()) AND YEAR(r.date_rdv)=YEAR(CURDATE())");
$stat_ce_mois = ($r3) ? (mysqli_fetch_assoc($r3)['c'] ?? 0) : 0;

$mois_labels = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$mois_dispo = [];
foreach ($all_rows as $r) {
    $key = date('Y-m', strtotime($r['date_rdv']));
    if (!in_array($key, $mois_dispo)) $mois_dispo[] = $key;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="admin-vaccines.css">
  <style>
    :root {
      --teal:#0d9488; --teal-lt:hsla(174,72%,42%,.1);
      --blue:#2563eb; --blue-lt:hsla(217,91%,60%,.1);
      --green:#16a34a; --grn-lt:hsla(142,72%,29%,.1);
      --red:#dc2626; --red-lt:hsla(0,84%,60%,.1);
      --orange:#ea580c; --org-lt:hsla(25,95%,53%,.1);
      --purple:#7c3aed; --pur-lt:hsla(262,83%,58%,.1);
      --text:#1a1a2e; --muted:#6b7280; --border:#eef0f5; --radius:14px;
    }
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.8rem; }
    .stat-card { background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1rem 1.2rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon-wrap { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
    .stat-num { font-family:'Nunito',sans-serif; font-size:1.6rem; font-weight:900; line-height:1; color:var(--text); }
    .stat-lbl { font-size:.76rem; color:var(--muted); font-weight:600; margin-top:2px; }
    .toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.2rem; }
    .search-box { position:relative; min-width:260px; }
    .search-box input { width:100%; padding:9px 14px 9px 36px; border:1px solid var(--border); border-radius:20px; font-size:.87rem; outline:none; background:#fff; font-family:'Quicksand',sans-serif; transition:border .2s; }
    .search-box input:focus { border-color:var(--blue); }
    .search-box .si { position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:.85rem; }
    .chip { padding:5px 14px; border-radius:20px; border:1px solid var(--border); background:#fff; color:var(--muted); font-weight:700; font-size:.78rem; cursor:pointer; transition:.2s; font-family:'Quicksand',sans-serif; }
    .chip.active, .chip:hover { background:var(--teal); color:#fff; border-color:var(--teal); }
    .mois-select { padding:9px 14px; border:1px solid var(--border); border-radius:20px; font-size:.85rem; font-family:'Quicksand',sans-serif; font-weight:600; outline:none; background:#fff; color:var(--text); cursor:pointer; }
    .mois-select:focus { border-color:var(--blue); }
    .table-wrapper { background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
    .hist-table { width:100%; border-collapse:collapse; }
    .hist-table thead tr { background:#f8fafc; border-bottom:2px solid var(--border); }
    .hist-table thead th { padding:.85rem 1rem; text-align:left; font-family:'Nunito',sans-serif; font-size:.75rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
    .hist-table tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
    .hist-table tbody tr:last-child { border-bottom:none; }
    .hist-table tbody tr:hover { background:#f8fafc; }
    .hist-table tbody td { padding:.8rem 1rem; vertical-align:middle; }
    .enfant-cell { display:flex; align-items:center; gap:.65rem; }
    .enfant-avatar { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .enfant-name { font-family:'Nunito',sans-serif; font-weight:800; font-size:.9rem; color:var(--text); }
    .enfant-age { font-size:.73rem; color:var(--muted); margin-top:1px; }
    .parent-cell { font-size:.83rem; color:var(--text); font-weight:600; }
    .parent-phone { font-size:.73rem; color:var(--muted); margin-top:2px; }
    .vaccin-cell { display:inline-flex; align-items:center; gap:.35rem; background:var(--grn-lt); color:var(--green); font-size:.8rem; font-weight:700; padding:4px 10px; border-radius:8px; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .date-cell { font-size:.83rem; font-weight:600; color:var(--text); white-space:nowrap; }
    .date-cell .heure { font-size:.73rem; color:var(--muted); margin-top:2px; }
    .badge-ok     { background:var(--grn-lt); color:var(--green); padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:700; white-space:nowrap; }
    .badge-retard { background:var(--red-lt); color:var(--red); padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .type-oblig { background:var(--grn-lt); color:var(--green); font-size:.7rem; font-weight:800; padding:2px 8px; border-radius:6px; display:inline-block; margin-top:3px; }
    .type-recom { background:var(--org-lt); color:var(--orange); font-size:.7rem; font-weight:800; padding:2px 8px; border-radius:6px; display:inline-block; margin-top:3px; }
    .btn-mini { padding:5px 12px; border-radius:8px; font-size:.76rem; font-weight:700; cursor:pointer; border:none; font-family:'Quicksand',sans-serif; transition:.2s; background:var(--blue-lt); color:var(--blue); text-decoration:none; display:inline-block; }
    .btn-mini:hover { background:var(--blue); color:#fff; }
    .pagination { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.2rem; border-top:1px solid var(--border); font-size:.83rem; color:var(--muted); }
    .pag-btns { display:flex; gap:.4rem; }
    .pag-btn { padding:5px 12px; border-radius:8px; border:1px solid var(--border); background:#fff; font-size:.8rem; font-weight:600; cursor:pointer; transition:.2s; font-family:'Quicksand',sans-serif; }
    .pag-btn:hover, .pag-btn.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pag-btn:disabled { opacity:.4; cursor:not-allowed; }
    .empty-hist { text-align:center; padding:3.5rem; color:var(--muted); }
    .empty-hist .ei { font-size:3rem; display:block; margin-bottom:.8rem; }
    .btn-export { padding:8px 18px; border-radius:20px; font-size:.82rem; font-weight:700; cursor:pointer; border:1px solid var(--border); font-family:'Quicksand',sans-serif; transition:.2s; background:#fff; color:var(--text); display:inline-flex; align-items:center; gap:.4rem; }
    .btn-export:hover { background:var(--text); color:#fff; border-color:var(--text); }
    #resCount { font-size:.82rem; color:var(--muted); }
    /* ✅ noResultHist ajouté */
    #noResultHist { display:none; text-align:center; padding:2rem; color:var(--muted); }
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
      <a href="historique.php" class="active">📋 Historique</a>
      <a href="index.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">
    <div class="admin-topbar">
      <div>
        <h1>📋 Historique des Vaccinations</h1>
        <p>Consultez l'ensemble des vaccinations réalisées</p>
      </div>
      <div class="admin-user">
        <div>
          <div class="admin-name"><?= htmlspecialchars($prenom_doc . ' ' . $nom_doc) ?></div>
          <div class="admin-role">Médecin</div>
        </div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon-wrap" style="background:var(--grn-lt);">✅</div>
        <div><div class="stat-num" style="color:var(--green)"><?= $stat_realises ?></div><div class="stat-lbl">Vaccinations réalisées</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap" style="background:var(--teal-lt);">👶</div>
        <div><div class="stat-num"><?= $stat_enfants ?></div><div class="stat-lbl">Enfants vaccinés</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap" style="background:var(--blue-lt);">📅</div>
        <div><div class="stat-num"><?= $stat_ce_mois ?></div><div class="stat-lbl">Ce mois</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap" style="background:var(--pur-lt);">📋</div>
        <div><div class="stat-num"><?= $total_hist ?></div><div class="stat-lbl">Total consultations</div></div>
      </div>
    </div>

    <div class="toolbar">
      <div style="display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;">
        <div class="search-box">
          <span class="si">🔍</span>
          <input type="text" id="searchHist" placeholder="Chercher enfant, parent, vaccin...">
        </div>
        <select class="mois-select" id="moisFilter">
          <option value="all">🗓️ Tous les mois</option>
          <?php foreach ($mois_dispo as $m):
            $y = substr($m, 0, 4);
            $mo = (int)substr($m, 5, 2);
            echo "<option value=\"$m\">{$mois_labels[$mo]} $y</option>";
          endforeach; ?>
        </select>
        <div style="display:flex;gap:.4rem;">
          <button class="chip active" data-filter="all">Tous</button>
          <button class="chip" data-filter="Réalisé">✅ Réalisé</button>
          <button class="chip" data-filter="Terminé">🏁 Terminé</button>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <span id="resCount"><?= $total_hist ?> entrée(s)</span>
        <button class="btn-export" onclick="exportCSV()">⬇️ Exporter CSV</button>
      </div>
    </div>

    <div class="table-wrapper">
      <?php if ($total_hist > 0): ?>
      <table class="hist-table" id="histTable">
        <thead>
          <tr>
            <th>Enfant</th>
            <th>Parent</th>
            <th>Vaccin</th>
            <th>Date RDV</th>
            <th>Date Réalisée</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="histBody">
          <?php foreach ($all_rows as $h):
            $genre  = strtoupper(substr($h['genre'] ?? 'U', 0, 1));
            $avatar = match($genre) { 'M' => '👦', 'F' => '👧', default => '🧒' };
            $av_bg  = match($genre) { 'M' => 'var(--blue-lt)', 'F' => 'hsla(330,80%,60%,.1)', default => 'var(--pur-lt)' };
            $age_j  = (new DateTime($h['date_naissance']))->diff(new DateTime())->days;
            $age_txt = $age_j < 30 ? $age_j.'j' : ($age_j < 365 ? round($age_j/30).' mois' : round($age_j/365,1).' ans');
            $date_rdv_fmt = date('d/m/Y', strtotime($h['date_rdv']));
            $heure_fmt    = substr($h['heure_rdv'], 0, 5);
            $date_eff_fmt = $h['date_effectuee'] ? date('d/m/Y', strtotime($h['date_effectuee'])) : '—';
            $mois_key     = date('Y-m', strtotime($h['date_rdv']));
            $statut_vac   = $h['statut_vac'] ?? $h['statut_rdv'];
            $badge_class  = ($statut_vac === 'Réalisé') ? 'badge-ok' : 'badge-retard';
            $type_class   = ($h['type_vacs'] === 'Obligatoire') ? 'type-oblig' : 'type-recom';
          ?>
          <tr class="hist-row"
              data-statut="<?= htmlspecialchars($statut_vac) ?>"
              data-mois="<?= $mois_key ?>"
              data-search="<?= strtolower(htmlspecialchars(
                $h['prenom_enfant'].' '.$h['nom_enfant'].' '.
                $h['prenom_parent'].' '.$h['nom_parent'].' '.
                ($h['nom_vaccin'] ?? '')
              )) ?>">

            <td>
              <div class="enfant-cell">
                <div class="enfant-avatar" style="background:<?= $av_bg ?>"><?= $avatar ?></div>
                <div>
                  <div class="enfant-name"><?= htmlspecialchars($h['prenom_enfant'] . ' ' . $h['nom_enfant']) ?></div>
                  <div class="enfant-age">🎂 <?= $age_txt ?></div>
                </div>
              </div>
            </td>
            <td>
              <div class="parent-cell"><?= htmlspecialchars($h['prenom_parent'] . ' ' . $h['nom_parent']) ?></div>
              <div class="parent-phone">📞 <?= htmlspecialchars($h['telephone'] ?? '—') ?></div>
            </td>
            <td>
              <span class="vaccin-cell" title="<?= htmlspecialchars($h['nom_vaccin'] ?? '') ?>">
                💉 <?= htmlspecialchars($h['nom_vaccin'] ?? 'Non précisé') ?>
              </span>
              <?php if ($h['type_vacs']): ?>
                <br><span class="<?= $type_class ?>"><?= htmlspecialchars($h['type_vacs']) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="date-cell">
                <?= $date_rdv_fmt ?>
                <div class="heure">🕐 <?= $heure_fmt ?></div>
              </div>
            </td>
            <td>
              <div class="date-cell">
                <?php if ($h['date_effectuee']): ?>
                  <span style="color:var(--green);font-weight:700;">✅ <?= $date_eff_fmt ?></span>
                <?php else: ?>
                  <span style="color:var(--muted);">—</span>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <span class="badge <?= $badge_class ?>">
                <?= $statut_vac === 'Réalisé' ? '✅ Réalisé' : '🏁 Terminé' ?>
              </span>
            </td>
            <td>
              <a href="dossier-enfant.php?id=<?= $h['id_enfant'] ?>" class="btn-mini">👁️ Dossier</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="pagination">
        <span id="paginationInfo">Affichage de <b id="showFrom">1</b>–<b id="showTo">10</b> sur <b id="showTotal"><?= $total_hist ?></b></span>
        <div class="pag-btns" id="pagBtns"></div>
      </div>

      <?php else: ?>
      <div class="empty-hist">
        <span class="ei">📭</span>
        <p>Aucune vaccination enregistrée pour le moment.</p>
      </div>
      <?php endif; ?>

      <!-- ✅ CORRECTION : ajout de noResultHist -->
      <div id="noResultHist"></div>
    </div>

  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchHist');
  const moisSelect  = document.getElementById('moisFilter');
  const chips       = document.querySelectorAll('.chip');
  const allRows     = Array.from(document.querySelectorAll('.hist-row'));
  // ✅ CORRECTION : noResultHist existe maintenant
  const noRes       = document.getElementById('noResultHist');
  const resCount    = document.getElementById('resCount');

  let activeFilter = 'all';
  let activeMois   = 'all';
  let searchText   = '';
  const PER_PAGE   = 10;
  let currentPage  = 1;
  let visibleRows  = [];

  function applyFilters() {
    visibleRows = allRows.filter(row => {
      const statut = row.dataset.statut || '';
      const mois   = row.dataset.mois   || '';
      const s      = row.dataset.search || '';
      const mf = activeFilter === 'all' || statut === activeFilter;
      const mm = activeMois   === 'all' || mois   === activeMois;
      const ms = !searchText  || s.includes(searchText);
      row.style.display = 'none';
      return mf && mm && ms;
    });
    resCount.textContent = visibleRows.length + ' entrée(s)';
    if (noRes) noRes.style.display = visibleRows.length === 0 ? 'block' : 'none';
    currentPage = 1;
    renderPage();
  }

  function renderPage() {
    allRows.forEach(r => r.style.display = 'none');
    const start = (currentPage - 1) * PER_PAGE;
    const end   = Math.min(start + PER_PAGE, visibleRows.length);
    for (let i = start; i < end; i++) visibleRows[i].style.display = '';
    const sf = document.getElementById('showFrom');
    const st = document.getElementById('showTo');
    const sv = document.getElementById('showTotal');
    if (sf) sf.textContent = visibleRows.length > 0 ? start + 1 : 0;
    if (st) st.textContent = end;
    if (sv) sv.textContent = visibleRows.length;
    renderPagBtns();
  }

  function renderPagBtns() {
    const container = document.getElementById('pagBtns');
    if (!container) return;
    const totalPages = Math.ceil(visibleRows.length / PER_PAGE);
    container.innerHTML = '';
    const prev = document.createElement('button');
    prev.className = 'pag-btn';
    prev.textContent = '← Préc.';
    prev.disabled = currentPage === 1;
    prev.addEventListener('click', () => { currentPage--; renderPage(); });
    container.appendChild(prev);
    for (let i = 1; i <= totalPages; i++) {
      if (totalPages > 7 && Math.abs(i - currentPage) > 2 && i !== 1 && i !== totalPages) {
        if (i === currentPage - 3 || i === currentPage + 3) {
          const dots = document.createElement('span');
          dots.textContent = '…'; dots.style.padding = '0 4px';
          container.appendChild(dots);
        }
        continue;
      }
      const btn = document.createElement('button');
      btn.className = 'pag-btn' + (i === currentPage ? ' active' : '');
      btn.textContent = i;
      btn.addEventListener('click', (function(p){ return function(){ currentPage=p; renderPage(); }; })(i));
      container.appendChild(btn);
    }
    const next = document.createElement('button');
    next.className = 'pag-btn';
    next.textContent = 'Suiv. →';
    next.disabled = currentPage === totalPages || totalPages === 0;
    next.addEventListener('click', () => { currentPage++; renderPage(); });
    container.appendChild(next);
  }

  chips.forEach(ch => ch.addEventListener('click', function () {
    chips.forEach(c => c.classList.remove('active'));
    this.classList.add('active');
    activeFilter = this.dataset.filter;
    applyFilters();
  }));

  moisSelect.addEventListener('change', function () {
    activeMois = this.value;
    applyFilters();
  });

  searchInput.addEventListener('input', function () {
    searchText = this.value.toLowerCase().trim();
    applyFilters();
  });

  applyFilters();
});

function exportCSV() {
  const rows = document.querySelectorAll('#histBody .hist-row');
  let csv = 'Enfant,Parent,Vaccin,Date RDV,Date Réalisée,Statut\n';
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    const enfant  = cells[0]?.querySelector('.enfant-name')?.textContent.trim() ?? '';
    const parent  = cells[1]?.querySelector('.parent-cell')?.textContent.trim() ?? '';
    const vaccin  = cells[2]?.querySelector('.vaccin-cell')?.textContent.replace('💉','').trim() ?? '';
    const dateRdv = cells[3]?.querySelector('.date-cell')?.childNodes[0]?.textContent.trim() ?? '';
    const dateEff = cells[4]?.textContent.replace('✅','').trim() ?? '';
    const statut  = cells[5]?.textContent.replace('✅','').replace('🏁','').trim() ?? '';
    csv += `"${enfant}","${parent}","${vaccin}","${dateRdv}","${dateEff}","${statut}"\n`;
  });
  const blob = new Blob([csv], { type:'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'historique_vaccinations.csv';
  a.click();
}
</script>
</body>
</html>