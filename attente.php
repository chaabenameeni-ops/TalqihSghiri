<?php
include('db.php');

$email = isset($_GET['email']) ? mysqli_real_escape_string($conn, $_GET['email']) : '';

if (empty($email)) {
    header("Location: creercompte.php");
    exit();
}

$query  = "SELECT nom_utilisateur, prenom_utilisateur, statut, role FROM utilisateur WHERE email='$email'";
$result = mysqli_query($conn, $query);
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: creercompte.php");
    exit();
}

$statut = strtolower(trim($user['statut'])); // 'en_attente' ou 'actif'
$nom    = htmlspecialchars($user['nom_utilisateur']);
$role   = strtolower(trim($user['role']));

// ─── Si le compte est déjà actif → rediriger directement ──
// (cas où l'utilisateur revient sur cette page après activation)
// On ne redirige PAS ici car la session n'est pas encore créée.
// La redirection se fait côté JS après détection du changement de statut.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TalqihSghiri — Validation du compte</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: linear-gradient(135deg, #fdf2f8 0%, #f0f4ff 100%);
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Quicksand', sans-serif;
    }
    .card {
      background: #fff; border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0,0,0,.08);
      padding: 3rem 2.5rem; text-align: center;
      width: 100%; max-width: 460px;
    }
    .logo-row {
      display: flex; align-items: center; justify-content: center;
      gap: .5rem; margin-bottom: 1.8rem;
    }
    .logo-icon { font-size: 2rem; }
    .logo-text { font-family: 'Nunito', sans-serif; font-size: 1.4rem; font-weight: 900; color: #1a1a2e; }
    .logo-text .pink { color: #d87093; }

    /* ── En attente ── */
    .icon-wrap {
      width: 90px; height: 90px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2.6rem; margin: 0 auto 1.3rem;
    }
    .icon-pending { background: hsla(38,100%,50%,.1); }
    .icon-success { background: hsla(142,72%,29%,.1); }

    h2 { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 1.4rem; margin-bottom: .7rem; }
    p  { color: #6b7280; font-size: .95rem; line-height: 1.6; margin-bottom: 1.2rem; }

    /* Loader */
    .loader-wrap { margin: 1rem auto; display: flex; align-items: center; justify-content: center; gap: .6rem; }
    .dot {
      width: 10px; height: 10px; border-radius: 50%;
      background: #d87093; animation: bounce .9s infinite ease-in-out;
    }
    .dot:nth-child(2) { animation-delay: .15s; }
    .dot:nth-child(3) { animation-delay: .30s; }
    @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }

    .status-msg { font-size: .8rem; color: #aaa; margin-top: .5rem; }

    /* Bouton */
    .btn-go {
      display: inline-block; padding: 13px 36px;
      background: linear-gradient(135deg, #d87093, #c05070);
      color: #fff; border-radius: 14px; font-weight: 800;
      font-family: 'Nunito', sans-serif; font-size: 1rem;
      text-decoration: none; transition: .25s; margin-top: .5rem;
    }
    .btn-go:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(216,112,147,.35); }
    .btn-go.green {
      background: linear-gradient(135deg, #16a34a, #0d9488);
    }
    .btn-go.green:hover { box-shadow: 0 8px 20px rgba(22,163,74,.35); }

    /* Info email */
    .email-chip {
      display: inline-block; background: #f8fafc; border: 1px solid #eef0f5;
      border-radius: 20px; padding: 4px 14px; font-size: .82rem;
      color: #475569; font-weight: 700; margin-bottom: 1.2rem;
    }

    /* Steps */
    .steps { display: flex; flex-direction: column; gap: .6rem; margin: 1.2rem 0; text-align: left; }
    .step  { display: flex; align-items: flex-start; gap: .65rem; font-size: .87rem; color: #555; }
    .step-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }

    /* Retry link */
    .retry-link { display: block; margin-top: 1.2rem; font-size: .83rem; color: #aaa; }
    .retry-link a { color: #d87093; text-decoration: underline; }
  </style>

  <?php if ($statut === 'en_attente'): ?>
  <script>
    // Vérification automatique toutes les 5 secondes via AJAX
    function checkStatus() {
      fetch('check-status.php?email=<?= urlencode($email) ?>')
        .then(r => r.json())
        .then(data => {
          if (data.statut === 'actif') {
            // Compte activé → rediriger vers la page de connexion avec message
            window.location.href = 'loginp.php?activated=1';
          }
        })
        .catch(() => {});
    }
    setInterval(checkStatus, 5000);
  </script>
  <?php endif; ?>
</head>
<body>

<div class="card">
  <div class="logo-row">
    <span class="logo-icon">💉</span>
    <span class="logo-text">Talqih<span class="pink">Sghiri</span></span>
  </div>

  <?php if ($statut === 'en_attente'): ?>
  <!-- ── Compte EN ATTENTE ── -->
  <div class="icon-wrap icon-pending">⏳</div>
  <h2 style="color:#d97706;">Inscription reçue, <?= $nom ?> !</h2>

  <span class="email-chip">📧 <?= htmlspecialchars($email) ?></span>

  <p>Votre demande d'inscription a bien été enregistrée.<br>L'administrateur examine votre dossier.</p>

  <div class="steps">
    <div class="step"><span class="step-icon">✅</span><span>Compte créé avec succès</span></div>
    <div class="step"><span class="step-icon">⏳</span><span>Validation par l'administrateur en cours…</span></div>
    <div class="step"><span class="step-icon">🔒</span><span>Accès à votre espace personnel</span></div>
  </div>

  <div class="loader-wrap">
    <div class="dot"></div>
    <div class="dot"></div>
    <div class="dot"></div>
  </div>
  <p class="status-msg">Vérification automatique en cours…</p>

  <div class="retry-link">
    Déjà activé ? <a href="loginp.php">Se connecter</a>
  </div>

  <?php else: ?>
  <!-- ── Compte ACTIVÉ ── -->
  <div class="icon-wrap icon-success">🎉</div>
  <h2 style="color:#16a34a;">Compte activé, <?= $nom ?> !</h2>

  <span class="email-chip">📧 <?= htmlspecialchars($email) ?></span>

  <p>
    Bonne nouvelle ! L'administrateur a <strong>accepté</strong> votre compte.<br>
    Vous pouvez maintenant accéder à votre espace personnel.
  </p>

  <a href="loginp.php" class="btn-go green">
    🚀 Accéder à mon compte
  </a>

  <?php endif; ?>
</div>

</body>
</html>