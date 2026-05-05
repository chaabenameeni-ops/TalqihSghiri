<?php
session_start();
include('db.php');

if (empty($_SESSION['email'])) {
    header("Location: loginp.php"); exit();
}

$email     = $_SESSION['email'];
$nom_pa    = $_SESSION['nom']    ?? '';
$prenom_pa = $_SESSION['prenom'] ?? '';

if (empty($_SESSION['id_parent'])) {
    $rp = mysqli_query($conn, "SELECT id_parent FROM parent WHERE email='$email'");
    $up = $rp ? mysqli_fetch_assoc($rp) : null;
    if ($up) $_SESSION['id_parent'] = $up['id_parent'];
    else { header("Location: loginp.php"); exit(); }
}
$id_parent = intval($_SESSION['id_parent']);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reserver'])) {

    $id_enfant  = intval($_POST['id_enfant']);
    $id_vaccin  = intval($_POST['id_vaccin']);
    $id_docteur = intval($_POST['id_docteur']);
    $date_rdv   = mysqli_real_escape_string($conn, $_POST['date_rdv']);
    $heure_rdv  = mysqli_real_escape_string($conn, $_POST['heure_rdv']);
    $notes      = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $statut     = in_array($_POST['statut'] ?? '', ['En attente','Confirmé'])
                  ? $_POST['statut'] : 'En attente';

    // ✅ VALIDATION BACKEND : dimanche interdit
    $jour_semaine = date('N', strtotime($date_rdv)); // 7 = dimanche
    if ($jour_semaine == 7) {
        $message = "<div class='msg error'>❌ Les rendez-vous ne sont pas disponibles le dimanche.</div>";
    }
    // ✅ VALIDATION BACKEND : heure entre 08:00 et 18:00
    elseif ($heure_rdv < '08:00' || $heure_rdv > '18:00') {
        $message = "<div class='msg error'>❌ L'heure doit être entre 08:00 et 18:00.</div>";
    }
    elseif ($id_enfant > 0 && $id_vaccin > 0 && $id_docteur > 0 && $date_rdv && $heure_rdv) {

        $chk = mysqli_query($conn,
            "SELECT id_enfant FROM enfant WHERE id_enfant=$id_enfant AND id_parent=$id_parent");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            $message = "<div class='msg error'>❌ Enfant non trouvé.</div>";
        } else {
            $sql_insert = "INSERT INTO rendez_vous
                (id_enfant, id_parent, id_docteur, id_vaccin, date_rdv, heure_rdv, statut, notes)
                VALUES
                ($id_enfant, $id_parent, $id_docteur, $id_vaccin, '$date_rdv', '$heure_rdv',
                  '$statut', '$notes')";

            if (mysqli_query($conn, $sql_insert)) {
                $vac = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT nom_complet FROM vaccin WHERE id_vaccin=$id_vaccin"));
                $enf = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT prenom_enfant, nom_enfant FROM enfant WHERE id_enfant=$id_enfant"));
                $nom_vac = mysqli_real_escape_string($conn, $vac['nom_complet'] ?? 'Vaccin');
                $nom_enf = mysqli_real_escape_string($conn,
                    ($enf['prenom_enfant'] ?? '') . ' ' . ($enf['nom_enfant'] ?? ''));
                $em = mysqli_real_escape_string($conn, $email);
                $msg_notif = "✅ Rendez-vous confirmé pour $nom_enf — Vaccin : $nom_vac, le "
                           . date('d/m/Y', strtotime($date_rdv)) . " à " . substr($heure_rdv,0,5) . ".";
                mysqli_query($conn,
                    "INSERT INTO notification (email, type, message, statut, date_notification)
                     VALUES ('$em','Rappel','$msg_notif','Non lu',NOW())");

                $message = "<div class='msg success'>✅ Rendez-vous ajouté avec succès !</div>";
            } else {
                $message = "<div class='msg error'>❌ Erreur SQL : " . mysqli_error($conn) . "</div>";
            }
        }
    } else {
        $message = "<div class='msg warning'>⚠️ Veuillez remplir tous les champs obligatoires.</div>";
    }
}

// ─── Enfants du parent ────────────────────────────────────
$res_enfants = mysqli_query($conn,
    "SELECT id_enfant, prenom_enfant, nom_enfant, date_naissance
     FROM enfant WHERE id_parent=$id_parent ORDER BY prenom_enfant");

// ─── Tous les vaccins (on filtrera par JS) ─────────────────
$res_vaccins = mysqli_query($conn,
    "SELECT id_vaccin, nom_complet, age_recommande, type_vacs
     FROM vaccin ORDER BY age_recommande, nom_complet");

// ─── Médecins ─────────────────────────────────────────────
$res_docteurs = mysqli_query($conn,
    "SELECT id_docteur, nom_docteur, prenom_docteur FROM docteur ORDER BY nom_docteur");

// ─── Vaccins en retard ────────────────────────────────────
$res_retard = mysqli_query($conn,
    "SELECT e.id_enfant, e.prenom_enfant, e.nom_enfant,
            v.nom_complet AS nom_vaccin, va.date_prevue
     FROM vaccination va
     JOIN enfant e ON e.id_enfant = va.id_enfant
     JOIN vaccin v ON v.id_vaccin = va.id_vaccin
     WHERE e.id_parent = $id_parent
       AND va.statut IN ('Prévu','En retard')
       AND va.date_prevue <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY va.date_prevue ASC LIMIT 5");

// ─── Préparer données vaccins pour JS ─────────────────────
$vaccins_data = [];
if ($res_vaccins) {
    while ($v = mysqli_fetch_assoc($res_vaccins)) {
        $vaccins_data[] = $v;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nouveau Rendez-vous — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="new-appointment.css">
  <style>
    .new-apt-layout {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 1.5rem;
      align-items: start;
    }
    @media (max-width: 900px) {
      .new-apt-layout { grid-template-columns: 1fr; }
    }
    .form-card {
      background: #fff;
      border: 1px solid #eef0f5;
      border-radius: 16px;
      padding: 1.6rem 1.8rem;
    }
    .form-card h2 {
      font-family: 'Nunito', sans-serif;
      font-size: 1.05rem; font-weight: 800;
      color: #1a1a2e; margin: 0 0 1.3rem;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .form-group { display: flex; flex-direction: column; gap: .35rem; }
    .form-group.full-width { grid-column: span 2; }
    .form-group label {
      font-size: .8rem; font-weight: 800; color: #6b7280;
      text-transform: uppercase; letter-spacing: .05em;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 10px 12px;
      border: 1px solid #eef0f5;
      border-radius: 10px;
      font-size: .9rem;
      font-family: 'Quicksand', sans-serif;
      outline: none;
      transition: border .2s;
      background: #fafbff;
      width: 100%;
      box-sizing: border-box;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { border-color: var(--pink, #d63384); background: #fff; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .status-options { display: flex; gap: .75rem; }
    .status-option  { display: flex; align-items: center; gap: .35rem; font-size: .9rem; }
    .status-option input[type="radio"] { accent-color: var(--pink, #d63384); }
    .form-actions {
      display: flex; gap: .75rem; justify-content: flex-end;
      margin-top: 1.4rem; padding-top: 1.2rem;
      border-top: 1px solid #eef0f5;
    }
    .info-sidebar { display: flex; flex-direction: column; gap: 1rem; }
    .info-card {
      background: #fff; border: 1px solid #eef0f5;
      border-radius: 14px; padding: 1.1rem 1.2rem;
    }
    .info-card h3 {
      font-family: 'Nunito', sans-serif; font-size: .88rem;
      font-weight: 800; color: #1a1a2e; margin: 0 0 .8rem;
    }
    .info-item {
      display: flex; align-items: flex-start; gap: .7rem;
      padding: .45rem 0; border-bottom: 1px solid #f1f5f9;
    }
    .info-item:last-child { border-bottom: none; }
    .info-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
    .info-value { font-size: .85rem; font-weight: 700; color: #1a1a2e; }
    .info-label { font-size: .75rem; color: #6b7280; margin-top: 1px; }
    .retard-card {
      background: #fff5f5;
      border: 1px solid #feb2b2;
      border-left: 4px solid #e53e3e;
      border-radius: 14px; padding: 1rem 1.1rem;
    }
    .retard-card h3 { color: #c53030; }
    .retard-item {
      display: flex; flex-direction: column; gap: 1px;
      padding: .4rem 0; border-bottom: 1px solid #fed7d7;
      font-size: .83rem;
    }
    .retard-item:last-child { border-bottom: none; }
    .retard-item strong { color: #742a2a; }
    .retard-item span   { color: #e53e3e; font-size: .75rem; font-weight: 700; }
    .msg { padding:14px 18px; border-radius:10px; font-weight:600;
           text-align:center; margin-bottom:1rem; font-size:.9rem; }
    .success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .warning { background:#fff3cd; color:#856404; border:1px solid #fde68a; }
    .vaccin-hint { font-size:.72rem; color:#6b7280; margin-top:2px; }
    .req { color: #e53e3e; margin-left: 2px; }

    /* ✅ Style heure invalide */
    input[type="time"].invalid-time {
      border-color: #e53e3e !important;
      background: #fff5f5 !important;
    }
    .time-warning {
      font-size: .75rem; color: #e53e3e; font-weight: 700;
      display: none; margin-top: 3px;
    }

    /* ✅ Style vaccin non disponible */
    #no-vaccin-msg {
      display: none;
      padding: 10px 12px;
      background: #fff3cd;
      border: 1px solid #fde68a;
      border-radius: 10px;
      font-size: .85rem;
      color: #856404;
      font-weight: 600;
    }
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
      <a href="children.php">👶 Mes enfants</a>
      <a href="appointments.php" class="active">📅 Rendez-vous</a>
      <a href="notifications.php">🔔 Notifications</a>
      <a href="index.php" id="logoutBtn">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">

    <h1 class="page-title">Nouveau Rendez-vous 📅</h1>
    <p class="page-sub" style="margin-bottom:1.4rem;">Planifiez un rendez-vous de vaccination</p>

    <a href="appointments.php" class="btn btn-outline btn-round"
       style="margin-bottom:1.4rem;display:inline-block;">← Retour</a>

    <div class="new-apt-layout">

      <div class="form-card">
        <h2>📋 Informations du rendez-vous</h2>

        <?= $message ?>

        <form method="POST" action="" id="rdvForm">
          <div class="form-grid">

            <!-- Enfant -->
            <div class="form-group">
              <label>👶 Enfant <span class="req">*</span></label>
              <select name="id_enfant" id="sel-enfant" required>
                <option value="">— Choisir un enfant —</option>
                <?php if ($res_enfants && mysqli_num_rows($res_enfants) > 0):
                  while ($enf = mysqli_fetch_assoc($res_enfants)):
                    $age_j = (new DateTime($enf['date_naissance']))->diff(new DateTime())->days;
                    $age_s = $age_j < 365 ? round($age_j/30).' mois' : round($age_j/365,1).' ans';
                ?>
                <option value="<?= $enf['id_enfant'] ?>"
                        data-dob="<?= $enf['date_naissance'] ?>"
                        data-age-days="<?= $age_j ?>">
                  <?= htmlspecialchars($enf['prenom_enfant'].' '.$enf['nom_enfant']) ?>
                  (<?= $age_s ?>)
                </option>
                <?php endwhile; else: ?>
                <option value="" disabled>Aucun enfant enregistré</option>
                <?php endif; ?>
              </select>
              <?php if (!$res_enfants || mysqli_num_rows($res_enfants) === 0): ?>
                <span class="vaccin-hint">
                  <a href="ajouterchildren.php" style="color:var(--pink);">+ Ajouter un enfant d'abord</a>
                </span>
              <?php endif; ?>
            </div>

            <!-- Vaccin — filtré par âge -->
            <div class="form-group">
              <label>💉 Vaccin <span class="req">*</span></label>
              <select name="id_vaccin" id="sel-vaccin" required>
                <option value="">— Choisissez d'abord un enfant —</option>
              </select>
              <div id="no-vaccin-msg">⚠️ Aucun vaccin disponible pour cet âge.</div>
              <span class="vaccin-hint" id="vaccin-age-hint"></span>
            </div>

            <!-- Médecin -->
            <div class="form-group">
              <label>👨‍⚕️ Médecin <span class="req">*</span></label>
              <select name="id_docteur" required>
                <option value="">— Choisir un médecin —</option>
                <?php if ($res_docteurs):
                  while ($d = mysqli_fetch_assoc($res_docteurs)):
                ?>
                <option value="<?= $d['id_docteur'] ?>">
                  Dr. <?= htmlspecialchars($d['prenom_docteur'].' '.$d['nom_docteur']) ?>
                </option>
                <?php endwhile; endif; ?>
              </select>
            </div>

            <!-- ✅ Date — dimanche désactivé -->
            <div class="form-group">
              <label>📅 Date du RDV <span class="req">*</span></label>
              <input type="date" name="date_rdv" id="date_rdv"
                     min="<?= date('Y-m-d') ?>"
                     value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                     required>
              <span class="time-warning" id="date-warning" style="display:none;">
                ❌ Les rendez-vous ne sont pas disponibles le dimanche.
              </span>
            </div>

            <!-- ✅ Heure — limité 08:00 à 18:00 -->
            <div class="form-group">
              <label>🕐 Heure <span class="req">*</span></label>
              <input type="time" name="heure_rdv" id="heure_rdv"
                     min="08:00" max="18:00"
                     value="09:00" required>
              <span class="time-warning" id="time-warning">
                ❌ L'heure doit être entre 08:00 et 18:00.
              </span>
            </div>

            <!-- Statut -->
            <div class="form-group full-width">
              <label>📌 Statut</label>
              <div class="status-options">
                <label class="status-option">
                  <input type="radio" name="statut" value="En attente" checked>
                  <span>⏳ En attente</span>
                </label>
                <label class="status-option">
                  <input type="radio" name="statut" value="Confirmé">
                  <span>✅ Confirmé</span>
                </label>
              </div>
            </div>

            <!-- Notes -->
            <div class="form-group full-width">
              <label>📝 Notes / Remarques</label>
              <textarea name="notes"
                placeholder="Informations importantes pour le médecin..."></textarea>
            </div>

          </div>

          <div class="form-actions">
            <a href="appointments.php" class="btn btn-outline btn-round">Annuler</a>
            <button type="submit" name="reserver" class="btn btn-primary btn-round">
               Réserver le rendez-vous
            </button>
          </div>
        </form>
      </div>

      <!-- Sidebar -->
      <div class="info-sidebar">

        <?php if ($res_retard && mysqli_num_rows($res_retard) > 0): ?>
        <div class="retard-card">
          <h3>⚠️ Vaccins à planifier</h3>
          <?php while ($rd = mysqli_fetch_assoc($res_retard)):
            $d_fmt = date('d/m/Y', strtotime($rd['date_prevue']));
            $is_late = $rd['date_prevue'] < date('Y-m-d');
          ?>
          <div class="retard-item">
            <strong>
              <?= htmlspecialchars($rd['prenom_enfant'].' '.$rd['nom_enfant']) ?>
              — <?= htmlspecialchars($rd['nom_vaccin']) ?>
            </strong>
            <span><?= $is_late ? '⚠️ En retard depuis' : '📅 Prévu le' ?>
              <?= $d_fmt ?></span>
          </div>
          <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <div class="info-card">
          <h3>💡 Conseils</h3>
          <div class="info-item">
            <span class="info-icon">🕐</span>
            <div>
              <div class="info-value">Arrivez 15 min à l'avance</div>
              <div class="info-label">Pour la préparation</div>
            </div>
          </div>
          <div class="info-item">
            <span class="info-icon">📋</span>
            <div>
              <div class="info-value">Apportez le carnet de santé</div>
              <div class="info-label">Document obligatoire</div>
            </div>
          </div>
          <div class="info-item">
            <span class="info-icon">🍼</span>
            <div>
              <div class="info-value">Allaitez avant la visite</div>
              <div class="info-label">Pour calmer bébé</div>
            </div>
          </div>
          <div class="info-item">
            <span class="info-icon">🌡️</span>
            <div>
              <div class="info-value">Vérifiez la température</div>
              <div class="info-label">Pas de vaccin si fièvre</div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </main>
</div>

<script>
const allVaccins = <?= json_encode($vaccins_data) ?>;

function getAgeLimits(ageLabel) {
  const map = {
    'Naissance' : [0,   30],
    '2 mois'    : [31,  75],
    '3 mois'    : [76,  105],
    '4 mois'    : [106, 150],
    '6 mois'    : [151, 240],
    '9 mois'    : [241, 330],
    '11 mois'   : [331, 364],
    '12 mois'   : [365, 545],
    '18 mois'   : [546, 99999], // 18 mois et plus
  };
  for (const [key, val] of Object.entries(map)) {
    if (ageLabel && ageLabel.toLowerCase().trim() === key.toLowerCase().trim()) {
      return val;
    }
  }
  return null;
}

function filterVaccins(ageDays) {
  const sel   = document.getElementById('sel-vaccin');
  const noMsg = document.getElementById('no-vaccin-msg');
  const hint  = document.getElementById('vaccin-age-hint');

  sel.innerHTML = '<option value="">— Choisir un vaccin —</option>';

  const filtered = allVaccins.filter(v => {
    if (!v.age_recommande || v.age_recommande.trim() === '') return true;
    const limits = getAgeLimits(v.age_recommande);
    if (!limits) return true;
    return ageDays >= limits[0] && ageDays <= limits[1];
  });

  // Trier : Obligatoire en premier
  filtered.sort((a, b) => {
    const aObl = (a.type_vacs === 'Obligatoire') ? 0 : 1;
    const bObl = (b.type_vacs === 'Obligatoire') ? 0 : 1;
    return aObl - bObl;
  });

  if (filtered.length === 0) {
    noMsg.style.display = 'block';
    sel.style.display   = 'none';
    hint.innerHTML = '';
  } else {
    noMsg.style.display = 'none';
    sel.style.display   = 'block';

    // Groupes optgroup
    let grpOblig = null;
    let grpRecom = null;

    filtered.forEach(v => {
      const opt = document.createElement('option');
      opt.value       = v.id_vaccin;
      opt.dataset.age = v.age_recommande || '';
      opt.textContent = v.nom_complet + (v.age_recommande ? ' — ' + v.age_recommande : '');

      if (v.type_vacs === 'Obligatoire') {
        if (!grpOblig) {
          grpOblig = document.createElement('optgroup');
          grpOblig.label = '⭐ Obligatoires';
          sel.appendChild(grpOblig);
        }
        opt.style.fontWeight = 'bold';
        grpOblig.appendChild(opt);
      } else {
        if (!grpRecom) {
          grpRecom = document.createElement('optgroup');
          grpRecom.label = '💡 Recommandés';
          sel.appendChild(grpRecom);
        }
        grpRecom.appendChild(opt);
      }
    });

    const obligCount = filtered.filter(v => v.type_vacs === 'Obligatoire').length;
    const recomCount = filtered.length - obligCount;
    hint.innerHTML = `✅ ${filtered.length} vaccin(s) : <strong style="color:#166534">${obligCount} obligatoire(s)</strong>, ${recomCount} recommandé(s)`;
  }
}

// Changer enfant → filtrer vaccins
document.getElementById('sel-enfant').addEventListener('change', function () {
  const selected = this.options[this.selectedIndex];
  const ageDays  = parseInt(selected.dataset.ageDays || '0');
  if (this.value) {
    filterVaccins(ageDays);
  } else {
    const sel = document.getElementById('sel-vaccin');
    sel.innerHTML = '<option value="">— Choisissez d\'abord un enfant —</option>';
    sel.style.display = 'block';
    document.getElementById('no-vaccin-msg').style.display = 'none';
    document.getElementById('vaccin-age-hint').innerHTML = '';
  }
});

// Afficher âge recommandé vaccin sélectionné
document.getElementById('sel-vaccin').addEventListener('change', function () {
  const age = this.options[this.selectedIndex]?.dataset.age || '';
  if (age) document.getElementById('vaccin-age-hint').textContent = '📌 Âge recommandé : ' + age;
});

// Bloquer dimanche
document.getElementById('date_rdv').addEventListener('change', function () {
  const warning   = document.getElementById('date-warning');
  const submitBtn = document.querySelector('button[name="reserver"]');
  const date = new Date(this.value + 'T00:00:00');

  if (date.getDay() === 0) {
    warning.style.display  = 'block';
    this.style.borderColor = '#e53e3e';
    this.style.background  = '#fff5f5';
    submitBtn.disabled     = true;
    submitBtn.style.opacity = '0.5';
    const lundi = new Date(date);
    lundi.setDate(lundi.getDate() + 1);
    setTimeout(() => {
      this.value = lundi.toISOString().split('T')[0];
      warning.style.display  = 'none';
      this.style.borderColor = '';
      this.style.background  = '';
      submitBtn.disabled     = false;
      submitBtn.style.opacity = '1';
    }, 1500);
  } else {
    warning.style.display  = 'none';
    this.style.borderColor = '';
    this.style.background  = '';
    submitBtn.disabled     = false;
    submitBtn.style.opacity = '1';
  }
});

// Bloquer heure hors 08:00-18:00
document.getElementById('heure_rdv').addEventListener('change', function () {
  const warning   = document.getElementById('time-warning');
  const submitBtn = document.querySelector('button[name="reserver"]');
  if (this.value < '08:00' || this.value > '18:00') {
    warning.style.display  = 'block';
    this.classList.add('invalid-time');
    submitBtn.disabled     = true;
    submitBtn.style.opacity = '0.5';
  } else {
    warning.style.display  = 'none';
    this.classList.remove('invalid-time');
    submitBtn.disabled     = false;
    submitBtn.style.opacity = '1';
  }
});

// Validation finale
document.getElementById('rdvForm').addEventListener('submit', function (e) {
  const date  = new Date(document.getElementById('date_rdv').value + 'T00:00:00');
  const heure = document.getElementById('heure_rdv').value;
  if (date.getDay() === 0) {
    e.preventDefault();
    alert('❌ Les rendez-vous ne sont pas disponibles le dimanche.');
    return;
  }
  if (heure < '08:00' || heure > '18:00') {
    e.preventDefault();
    alert('❌ L\'heure doit être entre 08:00 et 18:00.');
    return;
  }
});
</script>
</body>
</html>