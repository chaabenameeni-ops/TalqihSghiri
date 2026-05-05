<?php
session_start();
include('db.php');

if (empty($_SESSION['email'])) { header("Location: loginp.php"); exit(); }

$nom_pa    = $_SESSION['nom']    ?? '';
$prenom_pa = $_SESSION['prenom'] ?? '';
$email     = $_SESSION['email'];
$role      = $_SESSION['role']   ?? 'parent';
$nom_user  = ($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '');

$id_enfant = intval($_GET['id'] ?? 0);
if ($id_enfant === 0) { header("Location: " . ($role === 'docteur' ? 'admin.php' : 'children.php')); exit(); }

$res_e = mysqli_query($conn,
    "SELECT e.*, p.nom_parent, p.prenom_parent, p.email AS email_parent, p.telephone
     FROM enfant e JOIN parent p ON p.id_parent = e.id_parent
     WHERE e.id_enfant = $id_enfant");
$enfant = $res_e ? mysqli_fetch_assoc($res_e) : null;
if (!$enfant) { header("Location: " . ($role === 'docteur' ? 'admin.php' : 'children.php')); exit(); }

if (isset($_POST['action']) && $_POST['action'] === 'realiser' && $role === 'docteur') {
    $id_vac = intval($_POST['id_vaccination']);
    mysqli_query($conn, "UPDATE vaccination SET statut='Réalisé', date_effectuee=CURDATE() WHERE id_vaccination=$id_vac");
    $email_p = mysqli_real_escape_string($conn, $enfant['email_parent']);
    $nom_vac = mysqli_real_escape_string($conn, $_POST['nom_vaccin'] ?? 'vaccin');
    $msg_v   = "✅ Vaccination réalisée : {$enfant['prenom_enfant']} {$enfant['nom_enfant']} a reçu le vaccin « $nom_vac » le " . date('d/m/Y') . ".";
    mysqli_query($conn, "INSERT INTO notification (email, type, message, statut, date_notification) VALUES ('$email_p', 'Info', '$msg_v', 'Non lu', NOW())");
    header("Location: dossier-enfant.php?id=$id_enfant&msg=ok");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'retard' && $role === 'docteur') {
    $id_vac = intval($_POST['id_vaccination']);
    mysqli_query($conn, "UPDATE vaccination SET statut='En retard' WHERE id_vaccination=$id_vac");
    $email_p = mysqli_real_escape_string($conn, $enfant['email_parent']);
    $nom_vac = mysqli_real_escape_string($conn, $_POST['nom_vaccin'] ?? 'vaccin');
    $msg_v   = "⚠️ Vaccin en retard : {$enfant['prenom_enfant']} {$enfant['nom_enfant']} n'a pas encore reçu le vaccin « $nom_vac ». Veuillez prendre rendez-vous.";
    mysqli_query($conn, "INSERT INTO notification (email, type, message, statut, date_notification) VALUES ('$email_p', 'Alerte', '$msg_v', 'Non lu', NOW())");
    header("Location: dossier-enfant.php?id=$id_enfant&msg=retard");
    exit();
}

$res_vac = mysqli_query($conn,
    "SELECT v.*, vac.nom_complet, vac.type_vacs, vac.age_recommande, vac.voie_administration
     FROM vaccination v
     JOIN vaccin vac ON vac.id_vaccin = v.id_vaccin
     WHERE v.id_enfant = $id_enfant
     ORDER BY v.date_prevue ASC");
$vaccinations = [];
if ($res_vac) { while ($r = mysqli_fetch_assoc($res_vac)) $vaccinations[] = $r; }

$nb_total   = count($vaccinations);
$nb_realise = count(array_filter($vaccinations, fn($v) => $v['statut'] === 'Réalisé'));
$nb_retard  = count(array_filter($vaccinations, fn($v) => $v['statut'] === 'En retard'));
$nb_prevu   = count(array_filter($vaccinations, fn($v) => $v['statut'] === 'Prévu'));
$pct        = $nb_total > 0 ? round(($nb_realise / $nb_total) * 100) : 0;
// ✅ CORRECTION : r.id_vaccin sans s
$res_rdv = mysqli_query($conn,
    "SELECT r.*, d.nom_docteur, d.prenom_docteur, vac.nom_complet AS nom_vaccin
     FROM rendez_vous r
     JOIN docteur d ON d.id_docteur = r.id_docteur
     LEFT JOIN vaccin vac ON vac.id_vaccin = r.id_vaccin
     WHERE r.id_enfant = $id_enfant
     ORDER BY r.date_rdv DESC");
$r4 = mysqli_query($conn, "SELECT COUNT(*) total FROM notification WHERE email='$email' AND statut='Non lu'");
$total_notifs = $r4 ? mysqli_fetch_assoc($r4)['total'] : 0;

$dob     = new DateTime($enfant['date_naissance']);
$now     = new DateTime();
$age_int = $dob->diff($now);
$age_txt = $age_int->y > 0
    ? $age_int->y . ' an(s) et ' . $age_int->m . ' mois'
    : ($age_int->m > 0 ? $age_int->m . ' mois' : $age_int->days . ' jours');

$back_url = ($role === 'docteur') ? 'dossiers-patients.php' : 'children.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dossier — <?= htmlspecialchars($enfant['prenom_enfant'] . ' ' . $enfant['nom_enfant']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="admin-vaccines.css">
  <style>
    :root {
      --pink:#d63384; --pink-lt:hsla(330,70%,55%,.1);
      --teal:#0d9488; --teal-lt:hsla(174,72%,42%,.1);
      --blue:#2563eb; --blue-lt:hsla(217,91%,60%,.1);
      --green:#16a34a; --grn-lt:hsla(142,72%,29%,.1);
      --red:#dc2626; --red-lt:hsla(0,84%,60%,.1);
      --orange:#ea580c; --org-lt:hsla(25,95%,53%,.1);
      --text:#1a1a2e; --muted:#6b7280; --border:#eef0f5; --radius:14px;
    }
    .flash { padding:12px 18px; border-radius:12px; margin-bottom:1.2rem; display:flex; align-items:center; gap:10px; font-size:.9rem; font-weight:600; animation:slideDown .4s ease; }
    .flash.ok     { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
    .flash.retard { background:#fff3cd; border:1px solid #fde68a; color:#92400e; }
    @keyframes slideDown { from{transform:translateY(-10px);opacity:0} to{transform:translateY(0);opacity:1} }
    .child-hero { background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; display:flex; align-items:center; gap:1.3rem; margin-bottom:1.5rem; }
    .child-hero-avatar { width:72px; height:72px; border-radius:50%; background:var(--pink-lt); display:flex; align-items:center; justify-content:center; font-size:2.2rem; flex-shrink:0; }
    .child-hero-name { font-family:'Nunito',sans-serif; font-size:1.4rem; font-weight:900; color:var(--text); margin:0 0 .2rem; }
    .child-hero-sub  { font-size:.85rem; color:var(--muted); }
    .child-hero-stats { display:flex; gap:1rem; margin-left:auto; flex-wrap:wrap; }
    .hero-stat { text-align:center; }
    .hero-stat-num { font-family:'Nunito',sans-serif; font-size:1.5rem; font-weight:900; line-height:1; }
    .hero-stat-lbl { font-size:.72rem; color:var(--muted); font-weight:600; }
    .info-section { background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.2rem 1.4rem; margin-bottom:1.2rem; }
    .info-section h3 { font-family:'Nunito',sans-serif; font-size:.9rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; margin:0 0 .9rem; }
    .info-grid2 { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px,1fr)); gap:.7rem 1.2rem; }
    .info-field label { font-size:.72rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:2px; }
    .info-field span  { font-size:.9rem; font-weight:600; color:var(--text); }
    .pbar-wrap { margin:1rem 0; }
    .pbar-row  { display:flex; justify-content:space-between; align-items:center; margin-bottom:.35rem; font-size:.83rem; font-weight:600; color:var(--muted); }
    .pbar      { height:9px; background:#f1f5f9; border-radius:10px; overflow:hidden; }
    .pbar-fill { height:100%; border-radius:10px; background:linear-gradient(90deg, var(--pink), var(--teal)); transition:width .6s; }
    .vac-table { width:100%; border-collapse:collapse; font-size:.87rem; }
    .vac-table thead tr { background:#f8fafc; border-bottom:2px solid var(--border); }
    .vac-table thead th { padding:.75rem 1rem; text-align:left; font-size:.72rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
    .vac-table tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
    .vac-table tbody tr:last-child { border-bottom:none; }
    .vac-table tbody tr:hover { background:#f8fafc; }
    .vac-table tbody td { padding:.75rem 1rem; vertical-align:middle; }
    .badge-realise { background:var(--grn-lt); color:var(--green); padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .badge-retard  { background:var(--red-lt);  color:var(--red);   padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .badge-prevu   { background:var(--blue-lt); color:var(--blue);  padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .badge-conf    { background:var(--grn-lt);  color:var(--green); padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .badge-att     { background:#fff3cd; color:#92400e; padding:4px 10px; border-radius:20px; font-size:.75rem; font-weight:700; }
    .btn-realiser { background:var(--grn-lt); color:var(--green); border:none; padding:5px 12px; border-radius:8px; font-size:.78rem; font-weight:700; cursor:pointer; transition:.2s; font-family:'Quicksand',sans-serif; }
    .btn-realiser:hover { background:var(--green); color:#fff; }
    .btn-retard-m { background:var(--red-lt); color:var(--red); border:none; padding:5px 12px; border-radius:8px; font-size:.78rem; font-weight:700; cursor:pointer; transition:.2s; font-family:'Quicksand',sans-serif; }
    .btn-retard-m:hover { background:var(--red); color:#fff; }
    .section-tabs { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:1.2rem; }
    .stab { padding:10px 20px; border:none; background:none; font-family:'Quicksand',sans-serif; font-weight:700; font-size:.9rem; color:var(--muted); cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:.2s; }
    .stab.active { color:var(--pink); border-bottom-color:var(--pink); }
    .tab-pane { display:none; }
    .tab-pane.active { display:block; }
    .rdv-hist-item { display:flex; align-items:center; gap:.9rem; padding:.75rem 0; border-bottom:1px solid var(--border); }
    .rdv-hist-item:last-child { border-bottom:none; }
    .rdv-date-col { min-width:80px; text-align:center; background:#f8fafc; border-radius:10px; padding:.4rem .5rem; }
    .rdv-date-d { font-family:'Nunito',sans-serif; font-size:1.2rem; font-weight:900; color:var(--text); line-height:1; }
    .rdv-date-m { font-size:.7rem; color:var(--muted); font-weight:700; text-transform:uppercase; }
    .sidebar-nav a { color:inherit; text-decoration:none; }
    .btn-back { padding:7px 18px; border-radius:20px; border:1px solid var(--border); background:#fff; font-size:.85rem; font-weight:700; cursor:pointer; font-family:'Quicksand',sans-serif; text-decoration:none; color:var(--text); display:inline-flex; align-items:center; gap:.4rem; transition:.2s; }
    .btn-back:hover { background:var(--text); color:#fff; }
    .notif-badge { background:#e53e3e; color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:50px; margin-left:4px; display:inline-block; line-height:1; vertical-align:middle; }

  </style>
</head>
<body class="<?= $role === 'docteur' ? 'admin-blue' : '' ?>">
<div class="app-wrapper">

  <aside class="sidebar">
    <div class="logo">
      <div class="logo-icon">💉</div>
      <span class="logo-text">Talqih<span class="<?= $role === 'docteur' ? 'text-blue' : 'text-pink' ?>">Sghiri</span></span>
    </div>
    <nav class="sidebar-nav">
      <?php if ($role === 'docteur'): ?>
        <a href="admin.php">📊 Tableau de bord</a>
        <a href="dossiers-patients.php" class="active">👶 Liste des Enfants</a>
        <a href="calendrier-vaccins.php">📅 Liste rendez-vous</a>
        <a href="historique.php">📋 Historique</a>
        <a href="index.php">🚪 Déconnexion</a>
      <?php else: ?>
        <a href="parent.php">📊 Tableau de bord</a>
        <a href="children.php" class="active">👶 Mes enfants</a>
        <a href="appointments.php">📅 Rendez-vous</a>
          <a href="notifications.php">🔔 Notifications
        <?php if ($total_notifs > 0): ?>
          <span class="notif-badge"><?= $total_notifs ?></span>
        <?php endif; ?>
      </a>
        <a href="index.php">🚪 Déconnexion</a>
      <?php endif; ?>
    </nav>
  </aside>

  <main class="main-content">

    <div class="admin-topbar" style="margin-bottom:1.2rem;">
      <div>
        <h1>📄 Dossier de <?= htmlspecialchars($enfant['prenom_enfant'] . ' ' . $enfant['nom_enfant']) ?></h1>
        <p>Suivi vaccinal complet</p>
      </div>
      <div class="admin-user">
        <div><div class="admin-role"><?= htmlspecialchars($nom_pa . ' ' . $prenom_pa) ?></div></div>
        <div class="admin-avatar"><?= $role === 'docteur' ? '👨‍⚕️' : '👤' ?></div>
      </div>
    </div>

    <a href="<?= $back_url ?>" class="btn-back" style="margin-bottom:1.2rem;">← Retour</a>

    <?php if (isset($_GET['msg'])): ?>
      <?php if ($_GET['msg'] === 'ok'): ?>
        <div class="flash ok">✅ Vaccination marquée comme réalisée. Le parent a été notifié.</div>
      <?php elseif ($_GET['msg'] === 'retard'): ?>
        <div class="flash retard">⚠️ Vaccin marqué en retard. Le parent a été notifié.</div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="child-hero">
      <div class="child-hero-avatar">
        <?= ($enfant['genre'] === 'garcon') ? '👦' : '👧' ?>
      </div>
      <div>
        <h2 class="child-hero-name"><?= htmlspecialchars($enfant['prenom_enfant'] . ' ' . $enfant['nom_enfant']) ?></h2>
        <div class="child-hero-sub">
          🎂 <?= $age_txt ?> &nbsp;·&nbsp;
          🩸 <?= htmlspecialchars($enfant['groupe_sanguin'] ?: '—') ?> &nbsp;·&nbsp;
          ⚖️ <?= $enfant['poids'] ? $enfant['poids'] . ' kg' : '—' ?> &nbsp;·&nbsp;
          📏 <?= $enfant['taille'] ? $enfant['taille'] . ' cm' : '—' ?>
        </div>
      </div>
      <div class="child-hero-stats">
        <div class="hero-stat">
          <div class="hero-stat-num" style="color:var(--green)"><?= $nb_realise ?></div>
          <div class="hero-stat-lbl">Réalisés</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-num" style="color:var(--blue)"><?= $nb_prevu ?></div>
          <div class="hero-stat-lbl">Prévus</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-num" style="color:var(--red)"><?= $nb_retard ?></div>
          <div class="hero-stat-lbl">En retard</div>
        </div>
      </div>
    </div>

    <div class="info-section">
      <h3>Progression vaccinale</h3>
      <div class="pbar-wrap">
        <div class="pbar-row">
          <span><?= $nb_realise ?> / <?= $nb_total ?> vaccins administrés</span>
          <span style="font-weight:800;color:var(--pink)"><?= $pct ?>%</span>
        </div>
        <div class="pbar"><div class="pbar-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
    </div>

    <div class="info-section">
      <h3>Informations médicales</h3>
      <div class="info-grid2">
        <div class="info-field"><label>Date de naissance</label><span><?= date('d/m/Y', strtotime($enfant['date_naissance'])) ?></span></div>
        <div class="info-field"><label>Genre</label><span><?= ucfirst($enfant['genre'] ?: '—') ?></span></div>
        <div class="info-field"><label>Groupe sanguin</label><span><?= htmlspecialchars($enfant['groupe_sanguin'] ?: '—') ?></span></div>
        <div class="info-field"><label>Poids</label><span><?= $enfant['poids'] ? $enfant['poids'] . ' kg' : '—' ?></span></div>
        <div class="info-field"><label>Taille</label><span><?= $enfant['taille'] ? $enfant['taille'] . ' cm' : '—' ?></span></div>
        <div class="info-field"><label>Allergie(s)</label><span><?= htmlspecialchars($enfant['allergie'] ?: 'Aucune connue') ?></span></div>
        <div class="info-field" style="grid-column:span 2;"><label>Notes</label><span><?= htmlspecialchars($enfant['notes'] ?: '—') ?></span></div>
      </div>
    </div>

    <div class="info-section">
      <h3>Parent / Tuteur</h3>
      <div class="info-grid2">
        <div class="info-field"><label>Nom</label><span><?= htmlspecialchars($enfant['prenom_parent'] . ' ' . $enfant['nom_parent']) ?></span></div>
        <div class="info-field"><label>Email</label><span><?= htmlspecialchars($enfant['email_parent']) ?></span></div>
        <div class="info-field"><label>Téléphone</label><span><?= htmlspecialchars($enfant['telephone'] ?: '—') ?></span></div>
      </div>
    </div>

    <div class="info-section">
      <div class="section-tabs">
        <button class="stab active" data-tab="vaccinations">💉 Vaccinations</button>
        <button class="stab" data-tab="rdv">📅 Historique vaccins</button>
      </div>

      <div class="tab-pane active" id="tab-vaccinations">
        <?php if (!empty($vaccinations)): ?>
        <table class="vac-table">
          <thead>
            <tr>
              <th>Vaccin</th>
              <th>Âge recommandé</th>
              <th>Date prévue</th>
              <th>Date réalisée</th>
              <th>Statut</th>
              <?php if ($role === 'docteur'): ?><th>Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($vaccinations as $vac):
              $badge_vac = match($vac['statut']) {
                'Réalisé'   => 'badge-realise',
                'En retard' => 'badge-retard',
                default     => 'badge-prevu',
              };
            ?>
            <tr>
              <td>
                <strong style="font-size:.9rem;"><?= htmlspecialchars($vac['nom_complet']) ?></strong>
                <?php if ($vac['type_vacs']): ?>
                  <br><span style="font-size:.72rem;color:var(--muted);"><?= htmlspecialchars($vac['type_vacs']) ?></span>
                <?php endif; ?>
              </td>
              <td><span style="font-size:.83rem;color:var(--muted);"><?= htmlspecialchars($vac['age_recommande'] ?? '—') ?></span></td>
              <td style="font-size:.85rem;"><?= $vac['date_prevue'] ? date('d/m/Y', strtotime($vac['date_prevue'])) : '—' ?></td>
              <td style="font-size:.85rem;color:var(--green);"><?= $vac['date_effectuee'] ? '✅ ' . date('d/m/Y', strtotime($vac['date_effectuee'])) : '—' ?></td>
              <td><span class="badge <?= $badge_vac ?>"><?= $vac['statut'] ?></span></td>
              <?php if ($role === 'docteur' && $vac['statut'] !== 'Réalisé'): ?>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="realiser">
                  <input type="hidden" name="id_vaccination" value="<?= $vac['id_vaccination'] ?>">
                  <input type="hidden" name="nom_vaccin" value="<?= htmlspecialchars($vac['nom_complet']) ?>">
                  <button type="submit" class="btn-realiser" onclick="return confirm('Confirmer la réalisation ?')">✅ Réalisé</button>
                </form>
                <?php if ($vac['statut'] !== 'En retard'): ?>
                <form method="POST" style="display:inline;margin-left:.3rem;">
                  <input type="hidden" name="action" value="retard">
                  <input type="hidden" name="id_vaccination" value="<?= $vac['id_vaccination'] ?>">
                  <input type="hidden" name="nom_vaccin" value="<?= htmlspecialchars($vac['nom_complet']) ?>">
                  <button type="submit" class="btn-retard-m">⚠️ Retard</button>
                </form>
                <?php endif; ?>
              </td>
              <?php elseif ($role === 'docteur'): ?>
              <td><span style="color:var(--green);font-size:.8rem;font-weight:700;">✅ Complété</span></td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center;padding:2.5rem;color:var(--muted);">
          <span style="font-size:2.5rem;display:block;margin-bottom:.8rem;">💉</span>
          <p>Aucune vaccination enregistrée.<br>Le calendrier vaccinal sera généré automatiquement.</p>
        </div>
        <?php endif; ?>
      </div>

      <div class="tab-pane" id="tab-rdv">
        <?php if ($res_rdv && mysqli_num_rows($res_rdv) > 0):
          mysqli_data_seek($res_rdv, 0);
          while ($rdv = mysqli_fetch_assoc($res_rdv)):
            $mois_court = ['','Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
            $m = (int)date('n', strtotime($rdv['date_rdv']));
            $badge_rdv = match(strtolower($rdv['statut'])) {
              'terminé'  => 'badge-realise',
              'confirmé' => 'badge-conf',
              default    => 'badge-att',
            };
        ?>
        <div class="rdv-hist-item">
          <div class="rdv-date-col">
            <div class="rdv-date-d"><?= date('d', strtotime($rdv['date_rdv'])) ?></div>
            <div class="rdv-date-m"><?= $mois_court[$m] ?> <?= date('Y', strtotime($rdv['date_rdv'])) ?></div>
          </div>
          <div style="flex:1;">
            <div style="font-weight:700;font-size:.92rem;">
              💉 <?= htmlspecialchars($rdv['nom_vaccin'] ?? 'Vaccin non précisé') ?>
            </div>
            <div style="font-size:.8rem;color:var(--muted);margin-top:2px;">
              👨‍⚕️ Dr. <?= htmlspecialchars($rdv['prenom_docteur'] . ' ' . $rdv['nom_docteur']) ?>
              &nbsp;·&nbsp; 🕐 <?= substr($rdv['heure_rdv'], 0, 5) ?>
            </div>
          </div>
          <span class="badge <?= $badge_rdv ?>"><?= $rdv['statut'] ?></span>
        </div>
        <?php endwhile; else: ?>
        <div style="text-align:center;padding:2.5rem;color:var(--muted);">
          <p>Aucun rendez-vous enregistré pour cet enfant.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<script>
document.querySelectorAll('.stab').forEach(tab => {
  tab.addEventListener('click', function () {
    document.querySelectorAll('.stab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
  });
});
setTimeout(() => {
  document.querySelectorAll('.flash').forEach(a => a.style.display = 'none');
}, 5000);
</script>
</body>
</html>