<?php
session_start();
include('db.php');

$nom_doc    = $_SESSION['nom']        ?? 'Médecin';
$prenom_doc = $_SESSION['prenom']     ?? '';
$id_doc     = $_SESSION['id_docteur'] ?? 0;

// Récupérer tous les enfants suivis par ce médecin
$sql = "
    SELECT DISTINCT
        e.id_enfant, e.prenom_enfant, e.nom_enfant, e.date_naissance,
        e.genre, e.groupe_sanguin, e.poids, e.taille, e.allergie, e.notes,
        p.nom_parent, p.prenom_parent, p.email, p.telephone,
        (SELECT COUNT(*) FROM vaccination v WHERE v.id_enfant = e.id_enfant AND v.statut = 'Réalisé') AS vaccins_faits,
        (SELECT COUNT(*) FROM vaccination v WHERE v.id_enfant = e.id_enfant AND v.statut = 'Prévu' AND v.date_prevue < CURDATE()) AS vaccins_retard,
        (SELECT MAX(r2.date_rdv) FROM rendez_vous r2 WHERE r2.id_enfant = e.id_enfant AND r2.statut='Terminé') AS dernier_rdv
    FROM rendez_vous r
    JOIN enfant e ON e.id_enfant = r.id_enfant
    JOIN parent p ON p.id_parent = e.id_parent
    WHERE r.id_docteur = $id_doc
    ORDER BY e.nom_enfant ASC
";
$res = mysqli_query($conn, $sql);
$total_patients = mysqli_num_rows($res);

// Stats rapides
$stat_retards = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT v.id_enfant) c FROM vaccination v
     JOIN rendez_vous r ON r.id_enfant=v.id_enfant
     WHERE r.id_docteur=$id_doc AND v.statut='Prévu' AND v.date_prevue < CURDATE()"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dossiers Patients — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="admin-vaccines.css">
  <style>
    :root {
      --teal:    #0d9488;
      --teal-lt: hsla(174,72%,42%,.1);
      --blue:    #2563eb;
      --blue-lt: hsla(217,91%,60%,.1);
      --red:     #dc2626;
      --red-lt:  hsla(0,84%,60%,.1);
      --orange:  #ea580c;
      --org-lt:  hsla(25,95%,53%,.1);
      --purple:  #7c3aed;
      --pur-lt:  hsla(262,83%,58%,.1);
      --text:    #1a1a2e;
      --muted:   #6b7280;
      --border:  #eef0f5;
      --card-bg: #ffffff;
      --radius:  14px;
    }

    /* ── Layout ── */
    .page-header { margin-bottom: 1.5rem; }
    .page-header h1 { font-family:'Nunito',sans-serif; font-size:1.5rem; font-weight:900; color:var(--text); margin:0 0 .25rem; }
    .page-header p  { color:var(--muted); font-size:.9rem; margin:0; }

    /* ── Stats strip ── */
    .stats-strip {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 1rem; margin-bottom: 1.8rem;
    }
    .stat-pill {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1rem 1.2rem;
      display: flex; align-items: center; gap: .9rem;
    }
    .stat-pill-icon {
      width: 44px; height: 44px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .stat-pill-num  { font-family:'Nunito',sans-serif; font-size: 1.6rem; font-weight: 900; line-height:1; color:var(--text); }
    .stat-pill-lbl  { font-size: .78rem; color: var(--muted); font-weight: 600; margin-top:2px; }

    /* ── Toolbar ── */
    .toolbar {
      display: flex; justify-content: space-between; align-items: center;
      gap: 1rem; flex-wrap: wrap; margin-bottom: 1.2rem;
    }
    .search-box { position: relative; min-width: 260px; }
    .search-box input {
      width: 100%; padding: 10px 14px 10px 38px;
      border: 1px solid var(--border); border-radius: 20px;
      font-size: .87rem; font-family:'Quicksand',sans-serif;
      outline: none; transition: border .2s; background:#fff;
    }
    .search-box input:focus { border-color: var(--blue); }
    .search-box .s-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); font-size:.85rem; }

    .filter-chips { display:flex; gap:.5rem; flex-wrap:wrap; }
    .chip {
      padding: 6px 16px; border-radius: 20px; border: 1px solid var(--border);
      background: #fff; color: var(--muted); font-weight: 700; font-size: .8rem;
      cursor: pointer; transition: .2s; font-family:'Quicksand',sans-serif;
    }
    .chip.active, .chip:hover { background: var(--teal); color:#fff; border-color:var(--teal); }

    /* ── Grid patients ── */
    .patients-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.1rem;
    }

    /* ── Patient card ── */
    .patient-card {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: var(--radius); overflow: hidden;
      transition: box-shadow .2s, transform .2s;
    }
    .patient-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }

    /* Bandeau top coloré selon genre */
    .card-band { height: 5px; }
    .band-m { background: linear-gradient(90deg, #2563eb, #60a5fa); }
    .band-f { background: linear-gradient(90deg, #db2777, #f472b6); }
    .band-u { background: linear-gradient(90deg, #6d28d9, #a78bfa); }

    .card-body { padding: 1.1rem 1.2rem; }

    /* Avatar + nom */
    .card-header-row {
      display: flex; align-items: center; gap: .85rem; margin-bottom: .9rem;
    }
    .child-avatar {
      width: 48px; height: 48px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem; flex-shrink: 0;
    }
    .child-name  { font-family:'Nunito',sans-serif; font-weight:800; font-size:1rem; color:var(--text); }
    .child-sub   { font-size:.78rem; color:var(--muted); margin-top:2px; }

    /* Indicateur retard */
    .retard-dot {
      margin-left:auto; width:10px; height:10px; border-radius:50%;
      background:var(--red); flex-shrink:0;
      box-shadow:0 0 0 3px var(--red-lt);
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse { 0%,100%{box-shadow:0 0 0 3px var(--red-lt)} 50%{box-shadow:0 0 0 6px var(--red-lt)} }

    /* Info grid */
    .info-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: .5rem .8rem; margin-bottom: .9rem;
    }
    .info-item label {
      font-size: .7rem; font-weight: 800; color: var(--muted);
      text-transform: uppercase; letter-spacing:.05em; display:block; margin-bottom:1px;
    }
    .info-item span { font-size: .85rem; font-weight: 600; color: var(--text); }

    /* Parent strip */
    .parent-strip {
      background: var(--blue-lt); border-radius: 10px;
      padding: .6rem .85rem; display:flex; align-items:center; gap:.6rem;
      margin-bottom: .85rem;
    }
    .parent-strip .parent-icon { font-size: 1rem; }
    .parent-strip .parent-name { font-size:.83rem; font-weight:700; color:var(--blue); }
    .parent-strip .parent-contact { font-size:.75rem; color:var(--muted); }

    /* Vaccin progress */
    .vaccin-row {
      display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem;
    }
    .vaccin-row span { font-size:.8rem; color:var(--muted); font-weight:600; }
    .progress-bar { flex:1; height:7px; background:#f1f5f9; border-radius:10px; margin:0 .8rem; overflow:hidden; }
    .progress-fill { height:100%; border-radius:10px; background:linear-gradient(90deg, var(--teal), #34d399); transition:width .5s; }

    /* Card footer */
    .card-footer {
      border-top: 1px solid var(--border); padding: .75rem 1.2rem;
      display:flex; gap:.5rem;
    }
    .btn-card {
      flex:1; padding: 8px 0; border-radius: 10px; font-size:.82rem;
      font-weight:700; cursor:pointer; border:none; font-family:'Quicksand',sans-serif;
      transition:.2s; text-align:center; text-decoration:none; display:block;
    }
    .btn-dossier { background:var(--teal-lt); color:var(--teal); }
    .btn-dossier:hover { background:var(--teal); color:#fff; }
    .btn-rdv { background:var(--blue-lt); color:var(--blue); }
    .btn-rdv:hover { background:var(--blue); color:#fff; }

    /* Alert retard tag */
    .tag-retard {
      background:var(--red-lt); color:var(--red);
      font-size:.72rem; font-weight:800; padding:2px 8px;
      border-radius:6px; display:inline-block;
    }

    /* Empty */
    .empty-state {
      text-align:center; padding:4rem; color:var(--muted); grid-column:1/-1;
    }
    .empty-state .e-icon { font-size:3rem; display:block; margin-bottom:.8rem; }

    /* Badge count sidebar */
    .badge-count {
      background:#ff0000; color:#fff; font-size:10px; font-weight:800;
      padding:2px 6px; border-radius:50px; margin-left:auto;
      display:inline-block; line-height:1;
    }

    /* count result */
    #resultCount { font-size:.82rem; color:var(--muted); }
  </style>
</head>
<body class="admin-blue">
<div class="app-wrapper">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <div class="logo-icon">💉</div>
      <span class="logo-text">Talqih<span class="text-blue">Sghiri</span></span>
    </div>
    <nav class="sidebar-nav">
      <a href="admin.php">📊 Tableau de bord</a>
      <a href="dossiers-patients.php" class="active">👶 Liste des Enfants</a>
      <a href="calendrier-vaccins.php">📅 Liste rendez-vous</a>
      <a href="historique.php">📋 Historique</a>
      <a href="index.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">

    <div class="admin-topbar">
      <div class="page-header">
        <h1>👶 Dossiers Patients</h1>
        <p>Consultez et gérez les dossiers de vos enfants suivis</p>
      </div>
      <div class="admin-user">
        <div>
          <div class="admin-name"><?= htmlspecialchars($prenom_doc . ' ' . $nom_doc) ?></div>
          <div class="admin-role">Médecin</div>
        </div>
        <div class="admin-avatar">👨‍⚕️</div>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="stats-strip">
      <div class="stat-pill">
        <div class="stat-pill-icon" style="background:var(--teal-lt);">👶</div>
        <div>
          <div class="stat-pill-num"><?= $total_patients ?></div>
          <div class="stat-pill-lbl">Total patients</div>
        </div>
      </div>
      <div class="stat-pill">
        <div class="stat-pill-icon" style="background:var(--red-lt);">⚠️</div>
        <div>
          <div class="stat-pill-num" style="<?= $stat_retards > 0 ? 'color:var(--red)' : '' ?>"><?= $stat_retards ?></div>
          <div class="stat-pill-lbl">Avec retard vaccinal</div>
        </div>
      </div>
      <div class="stat-pill">
        <div class="stat-pill-icon" style="background:var(--blue-lt);">✅</div>
        <div>
          <div class="stat-pill-num" style="color:var(--teal)"><?= $total_patients - $stat_retards ?></div>
          <div class="stat-pill-lbl">Vaccination à jour</div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
        <div class="search-box">
          <span class="s-icon">🔍</span>
          <input type="text" id="searchInput" placeholder="Chercher un enfant, parent...">
        </div>
        <div class="filter-chips">
          <button class="chip active" data-filter="all">Tous</button>
          <button class="chip" data-filter="retard">⚠️ En retard</button>
          <button class="chip" data-filter="ok">✅ À jour</button>
          <button class="chip" data-filter="M">Garçons</button>
          <button class="chip" data-filter="F">Filles</button>
        </div>
      </div>
      <span id="resultCount"><?= $total_patients ?> patient(s)</span>
    </div>

    <!-- Grid -->
    <div class="patients-grid" id="patientsGrid">
      <?php
      mysqli_data_seek($res, 0);
      if ($total_patients > 0):
        while ($p = mysqli_fetch_assoc($res)):
          $genre    = strtoupper(substr($p['genre'] ?? 'U', 0, 1));
          $band     = match($genre) { 'M' => 'band-m', 'F' => 'band-f', default => 'band-u' };
          $avatar   = match($genre) { 'M' => '👦', 'F' => '👧', default => '🧒' };
          $av_bg    = match($genre) { 'M' => 'var(--blue-lt)', 'F' => 'hsla(330,80%,60%,.1)', default => 'var(--pur-lt)' };
          $age_j    = (new DateTime($p['date_naissance']))->diff(new DateTime())->days;
          $age_txt  = $age_j < 30 ? $age_j . 'j'
                    : ($age_j < 365 ? round($age_j/30) . ' mois'
                    : round($age_j/365, 1) . ' ans');
          $has_retard = $p['vaccins_retard'] > 0;
          $dernier  = $p['dernier_rdv'] ? date('d/m/Y', strtotime($p['dernier_rdv'])) : '—';
      ?>
      <div class="patient-card"
           data-genre="<?= $genre ?>"
           data-retard="<?= $has_retard ? '1' : '0' ?>"
           data-search="<?= strtolower(htmlspecialchars($p['prenom_enfant'].' '.$p['nom_enfant'].' '.$p['prenom_parent'].' '.$p['nom_parent'].' '.$p['email'])) ?>">

        <div class="card-band <?= $band ?>"></div>
        <div class="card-body">

          <!-- Header -->
          <div class="card-header-row">
            <div class="child-avatar" style="background:<?= $av_bg ?>"><?= $avatar ?></div>
            <div>
              <div class="child-name"><?= htmlspecialchars($p['prenom_enfant'] . ' ' . $p['nom_enfant']) ?></div>
              <div class="child-sub">🎂 <?= $age_txt ?> &nbsp;·&nbsp; <?= $p['groupe_sanguin'] ?: '—' ?></div>
            </div>
            <?php if ($has_retard): ?><div class="retard-dot" title="Vaccin en retard"></div><?php endif; ?>
          </div>

          <!-- Info grid -->
          <div class="info-grid">
            <div class="info-item">
              <label>Naissance</label>
              <span><?= date('d/m/Y', strtotime($p['date_naissance'])) ?></span>
            </div>
            <div class="info-item">
              <label>Genre</label>
              <span><?= $genre === 'M' ? 'Masculin' : ($genre === 'F' ? 'Féminin' : '—') ?></span>
            </div>
            <div class="info-item">
              <label>Poids</label>
              <span><?= $p['poids'] ? $p['poids'] . ' kg' : '—' ?></span>
            </div>
            <div class="info-item">
              <label>Taille</label>
              <span><?= $p['taille'] ? $p['taille'] . ' cm' : '—' ?></span>
            </div>
            <div class="info-item" style="grid-column:span 2;">
              <label>Allergie(s)</label>
              <span><?= $p['allergie'] ?: 'Aucune connue' ?></span>
            </div>
          </div>

          <!-- Parent -->
          <div class="parent-strip">
            <span class="parent-icon">👤</span>
            <div>
              <div class="parent-name"><?= htmlspecialchars($p['prenom_parent'] . ' ' . $p['nom_parent']) ?></div>
              <div class="parent-contact"><?= htmlspecialchars($p['telephone'] ?: $p['email']) ?></div>
            </div>
          </div>

          <!-- Vaccin progress -->
          <?php
          $total_vac = $p['vaccins_faits'] + $p['vaccins_retard'];
          $pct = $total_vac > 0 ? round(($p['vaccins_faits'] / $total_vac) * 100) : 0;
          ?>
          <div class="vaccin-row">
            <span>💉 Vaccinations</span>
            <span><?= $p['vaccins_faits'] ?>/<?= $total_vac ?></span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= $pct ?>%"></div>
          </div>

          <?php if ($has_retard): ?>
            <div style="margin-top:.55rem;">
              <span class="tag-retard">⚠️ <?= $p['vaccins_retard'] ?> vaccin(s) en retard</span>
            </div>
          <?php endif; ?>

          <div style="margin-top:.5rem;font-size:.75rem;color:var(--muted);">
            Dernier RDV : <?= $dernier ?>
          </div>

        </div><!-- /card-body -->

        <div class="card-footer">
          <a href="dossier-enfant.php?id=<?= $p['id_enfant'] ?>" class="btn-card btn-dossier">📄 Dossier complet</a>
        </div>

      </div><!-- /patient-card -->
      <?php endwhile;
      else: ?>
      <div class="empty-state">
        <span class="e-icon">📭</span>
        <p>Aucun patient trouvé.<br>Les patients apparaissent après un premier rendez-vous.</p>
      </div>
      <?php endif; ?>
    </div><!-- /patients-grid -->

    <div id="noResult" style="display:none;text-align:center;padding:3rem;color:var(--muted);">
      Aucun patient correspondant à votre recherche.
    </div>

  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const search  = document.getElementById('searchInput');
  const chips   = document.querySelectorAll('.chip');
  const cards   = document.querySelectorAll('.patient-card');
  const count   = document.getElementById('resultCount');
  const noRes   = document.getElementById('noResult');
  let activeFilter = 'all', searchText = '';

  function filter() {
    let v = 0;
    cards.forEach(c => {
      const genre  = c.dataset.genre;
      const retard = c.dataset.retard;
      const s      = c.dataset.search;
      let mf = false;
      if (activeFilter === 'all')    mf = true;
      else if (activeFilter === 'retard') mf = retard === '1';
      else if (activeFilter === 'ok')    mf = retard === '0';
      else mf = genre === activeFilter;
      const ms = !searchText || s.includes(searchText);
      if (mf && ms) { c.style.display=''; v++; } else c.style.display='none';
    });
    count.textContent = v + ' patient(s)';
    noRes.style.display = v === 0 ? 'block' : 'none';
  }

  chips.forEach(ch => ch.addEventListener('click', function() {
    chips.forEach(c => c.classList.remove('active'));
    this.classList.add('active');
    activeFilter = this.dataset.filter;
    filter();
  }));
  search.addEventListener('input', function() { searchText = this.value.toLowerCase().trim(); filter(); });
  filter();
});
</script>
</body>
</html>