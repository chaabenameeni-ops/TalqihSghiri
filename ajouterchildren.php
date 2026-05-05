<?php
session_start();
include('db.php');

if (empty($_SESSION['email'])) { header("Location: loginp.php"); exit(); }

$nom_pa    = $_SESSION['nom']    ?? '';
$prenom_pa = $_SESSION['prenom'] ?? '';
$email     = $_SESSION['email'];

if (empty($_SESSION['id_parent'])) {
    $rp = mysqli_query($conn, "SELECT id_parent FROM parent WHERE email='$email'");
    $up = $rp ? mysqli_fetch_assoc($rp) : null;
    if ($up) $_SESSION['id_parent'] = $up['id_parent'];
}
$id_parent = $_SESSION['id_parent'];

$erreur = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $prenom_enfant  = mysqli_real_escape_string($conn, $_POST['prenomenfant']);
    $nom_enfant     = mysqli_real_escape_string($conn, $_POST['nomenfant']);
    $date_naiss     = mysqli_real_escape_string($conn, $_POST['datenaissance']);
    $genre          = mysqli_real_escape_string($conn, $_POST['genre']);
    $groupe_sanguin = mysqli_real_escape_string($conn, $_POST['groupesanguin']);
    $poids          = (int)($_POST['poids'] ?? 0);
    $taille         = (int)($_POST['taille'] ?? 0);
    $allergie       = mysqli_real_escape_string($conn, $_POST['allergies']);
    $notes          = mysqli_real_escape_string($conn, $_POST['notes']);
    $sql = "INSERT INTO enfant (prenom_enfant, nom_enfant, date_naissance, genre,
            groupe_sanguin, poids, taille, allergie, notes, id_parent)
            VALUES ('$prenom_enfant','$nom_enfant','$date_naiss','$genre',
            '$groupe_sanguin',$poids,$taille,'$allergie','$notes',$id_parent)";
$r4 = mysqli_query($conn, "SELECT COUNT(*) total FROM notification WHERE email='$email' AND statut='Non lu'");
$total_notifs = $r4 ? mysqli_fetch_assoc($r4)['total'] : 0;

    if (mysqli_query($conn, $sql)) {
        $id_enfant_new = mysqli_insert_id($conn);
        $today = date('Y-m-d');
        $dob   = new DateTime($date_naiss);

        // ── Générer automatiquement le calendrier vaccinal ──
        $calendrier = [
            'Naissance' => 0,
            '2 mois'    => 60,
            '3 mois'    => 90,
            '4 mois'    => 120,
            '6 mois'    => 180,
            '9 mois'    => 270,
            '11 mois'   => 330,
            '12 mois'   => 365,
            '18 mois'   => 540,
        ];
        $vac_inserts = 0;
        foreach ($calendrier as $label => $jours) {
            $label_esc   = mysqli_real_escape_string($conn, $label);
            $res_vacs    = mysqli_query($conn, "SELECT id_vaccin FROM vaccin WHERE age_recommande='$label_esc'");
            if (!$res_vacs) continue;
            while ($v = mysqli_fetch_assoc($res_vacs)) {
                $dp  = (clone $dob)->modify("+$jours days")->format('Y-m-d');
                $sta = $dp < $today ? 'En retard' : 'Prévu';
                mysqli_query($conn,
                    "INSERT INTO vaccination (date_prevue, statut, id_enfant, id_vaccin)
                     VALUES ('$dp','$sta',$id_enfant_new,{$v['id_vaccin']})");
                $vac_inserts++;
            }
        }

        $success = "Enfant ajouté avec succès ! $vac_inserts vaccination(s) planifiée(s) automatiquement.";
    } else {
        $erreur = "Erreur lors de l'ajout : " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajouter un enfant — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="ajouterchildren.css">
  <style>
    .msg-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:12px 16px; border-radius:10px; font-weight:600; margin-bottom:1.2rem; }
   .notif-badge { background:#e53e3e; color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:50px; margin-left:4px; display:inline-block; line-height:1; vertical-align:middle; }

    .msg-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; padding:12px 16px; border-radius:10px; font-weight:600; margin-bottom:1.2rem; }
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
      <a href="children.php" class="active">👶 Mes enfants</a>
      <a href="appointments.php">📅 Rendez-vous</a>
        <a href="notifications.php">🔔 Notifications
        <?php if ($total_notifs > 0): ?>
          <span class="notif-badge"><?= $total_notifs ?></span>
        <?php endif; ?>
      </a>
      <a href="index.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main-content">
    <h1 class="page-title">Bonjour, <?= htmlspecialchars($prenom_pa . ' ' . $nom_pa) ?> ! 👋</h1>
    <div class="page-header">
      <a href="children.php" style="color:var(--pink);text-decoration:none;font-weight:600;">← Retour à mes enfants</a>
    </div>

    <div class="form-container">
      <div class="form-header">
        <div class="form-header-icon">👶</div>
        <h1>Ajouter un enfant</h1>
        <p>Les vaccinations seront planifiées <strong>automatiquement</strong> selon l'âge de l'enfant.</p>
      </div>

      <?php if ($success): ?>
        <div class="msg-success">✅ <?= htmlspecialchars($success) ?> <a href="children.php" style="color:var(--green);font-weight:700;">→ Voir mes enfants</a></div>
      <?php endif; ?>
      <?php if ($erreur): ?>
        <div class="msg-error">❌ <?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>

      <form id="add-child-form" class="child-form" method="POST" action="">

        <div class="form-group">
          <label>Prénom de l'enfant *</label>
          <input type="text" name="prenomenfant" placeholder="Prénom" required>
        </div>
        <div class="form-group">
          <label>Nom de l'enfant *</label>
          <input type="text" name="nomenfant" placeholder="Nom" required>
        </div>
        <div class="form-group">
          <label>Date de naissance *</label>
          <input type="date" name="datenaissance" required max="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Genre *</label>
          <div class="gender-options">
            <label class="gender-option"><input type="radio" name="genre" value="fille" checked><span class="gender-btn"><span class="gender-icon">👧</span>Fille</span></label>
            <label class="gender-option"><input type="radio" name="genre" value="garcon"><span class="gender-btn"><span class="gender-icon">👦</span>Garçon</span></label>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Groupe sanguin</label>
            <select name="groupesanguin">
              <option value="">— Sélectionner —</option>
              <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $gs): ?>
                <option value="<?= $gs ?>"><?= $gs ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Poids (kg)</label>
            <input type="number" name="poids" placeholder="Ex: 3" step="0.1" min="0.5" max="50">
          </div>
          <div class="form-group">
            <label>Taille (cm)</label>
            <input type="number" name="taille" placeholder="Ex: 50">
          </div>
        </div>
        <div class="form-group">
          <label>Allergies connues</label>
          <textarea name="allergies" rows="2" placeholder="Ex: Allergie aux œufs..."></textarea>
        </div>
        <div class="form-group">
          <label>Notes médicales</label>
          <textarea name="notes" rows="2" placeholder="Informations importantes..."></textarea>
        </div>
        <div class="form-actions">
          <a href="children.php" class="btn btn-outline">Annuler</a>
          <button type="submit" class="btn btn-primary">💾 Enregistrer l'enfant</button>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>