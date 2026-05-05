
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include('db.php');

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw = $_POST['motdepasse'];
    $role_choisi  = $_POST['role'] ?? '';

    $sql    = "SELECT * FROM utilisateur WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password_raw, $user['motdepasse'])) {

            $statut_db = strtolower(trim($user['statut']));

            if ($statut_db === 'en_attente') {
                header("Location: attente.php?email=" . urlencode($email));
                exit();
            }

            if ($statut_db === 'actif') {
                $role_db = strtolower(trim($user['role']));

                if ($role_choisi === $role_db) {
                    $_SESSION['nom']    = $user['nom_utilisateur'];
                    $_SESSION['prenom'] = $user['prenom_utilisateur'];
                    $_SESSION['email']  = $user['email'];
                    $_SESSION['role']   = $user['role'];

                    if ($role_db === 'parent') {
                        $row = mysqli_fetch_assoc(mysqli_query($conn,
                            "SELECT id_parent FROM parent WHERE email='$email'"));
                        if ($row) $_SESSION['id_parent'] = $row['id_parent'];
                        header("Location: parent.php");
                        exit();
                    } elseif ($role_db === 'docteur') {
                        $row = mysqli_fetch_assoc(mysqli_query($conn,
                            "SELECT id_docteur FROM docteur WHERE email='$email'"));
                        if ($row) $_SESSION['id_docteur'] = $row['id_docteur'];
                        header("Location: admin.php");
                        exit();
                    }
                } else {
                    $erreur = "⚠️ Vous devez choisir le même type de compte utilisé lors de l'inscription !";
                }
            } else {
                header("Location: attente.php?email=" . urlencode($email));
                exit();
            }
        } else {
            $erreur = "❌ Mot de passe incorrect !";
        }
    } else {
        $erreur = "❌ Aucun compte trouvé avec cet email.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — TalqihSghiri</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="login.css">

  <style>
  /* ════════════════════════════════════════════
     KEYFRAMES
  ════════════════════════════════════════════ */
  @keyframes fadeInDown {
    from { opacity:0; transform:translateY(-30px); }
    to   { opacity:1; transform:translateY(0); }
  }
  @keyframes fadeInUp {
    from { opacity:0; transform:translateY(30px); }
    to   { opacity:1; transform:translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity:0; }
    to   { opacity:1; }
  }
  @keyframes scaleIn {
    from { opacity:0; transform:scale(.88); }
    to   { opacity:1; transform:scale(1); }
  }
  @keyframes float {
    0%,100% { transform:translateY(0); }
    50%     { transform:translateY(-10px); }
  }
  @keyframes shimmer {
    0%   { background-position:200% center; }
    100% { background-position:-200% center; }
  }
  @keyframes pulseRing {
    0%   { box-shadow:0 0 0 0 hsla(340,60%,65%,.5); }
    70%  { box-shadow:0 0 0 12px hsla(340,60%,65%,0); }
    100% { box-shadow:0 0 0 0 hsla(340,60%,65%,0); }
  }
  @keyframes shake {
    0%,100% { transform:translateX(0); }
    20%     { transform:translateX(-8px); }
    40%     { transform:translateX(8px); }
    60%     { transform:translateX(-5px); }
    80%     { transform:translateX(5px); }
  }
  @keyframes slideInLeft {
    from { opacity:0; transform:translateX(-20px); }
    to   { opacity:1; transform:translateX(0); }
  }
  @keyframes bounceIn {
    0%   { opacity:0; transform:scale(0.3); }
    50%  { opacity:1; transform:scale(1.05); }
    70%  { transform:scale(.95); }
    100% { transform:scale(1); }
  }
  @keyframes spin {
    from { transform:rotate(0deg); }
    to   { transform:rotate(360deg); }
  }
  @keyframes gradientBG {
    0%   { background-position:0% 50%; }
    50%  { background-position:100% 50%; }
    100% { background-position:0% 50%; }
  }
  @keyframes particleFloat {
    0%,100% { transform:translateY(0) rotate(0deg); opacity:.6; }
    50%     { transform:translateY(-20px) rotate(180deg); opacity:.3; }
  }

  /* ════════════════════════════════════════════
     BACKGROUND ANIMÉ
  ════════════════════════════════════════════ */
  .login-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(-45deg, #fce7f3, #fff0f6, #fdf2f8, #fce7f3);
    background-size: 400% 400%;
    animation: gradientBG 8s ease infinite;
    position: relative;
    overflow: hidden;
    padding: 2rem;
  }

  /* Particules décoratives */
  .login-particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
  }
  .login-particle {
    position: absolute;
    border-radius: 50%;
    background: hsla(340,60%,70%,.15);
    animation: particleFloat var(--dur,5s) ease-in-out infinite;
    animation-delay: var(--del,0s);
  }

  /* ════════════════════════════════════════════
     CARD
  ════════════════════════════════════════════ */
  .login-card {
    background: #fff;
    border-radius: 24px;
    padding: 2.5rem 2.2rem;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(219,39,119,.15), 0 4px 20px rgba(0,0,0,.08);
    animation: scaleIn .6s ease both;
    position: relative;
    z-index: 1;
    border: 1px solid rgba(219,39,119,.1);
  }

  /* Ligne déco en haut de la card */
  .login-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #db2777, #f472b6, #db2777);
    background-size: 200% auto;
    border-radius: 24px 24px 0 0;
    animation: shimmer 2.5s linear infinite;
  }

  /* ════════════════════════════════════════════
     LOGO
  ════════════════════════════════════════════ */
  .login-logo {
    text-align: center;
    margin-bottom: 1.8rem;
    animation: fadeInDown .6s .1s ease both;
  }
  .login-logo .logo-icon {
    font-size: 2.5rem;
    display: inline-block;
    animation: float 3s ease-in-out infinite;
    margin-bottom: .4rem;
  }
  .login-logo h1 {
    font-family: 'Nunito', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    color: #1a1a2e;
    margin: 0;
  }
  .login-logo .text-pink {
    background: linear-gradient(90deg, #db2777, #f472b6, #db2777);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: shimmer 3s linear infinite;
  }
  .login-logo p {
    color: #6b7280;
    font-size: .88rem;
    margin-top: .4rem;
  }

  /* ════════════════════════════════════════════
     ALERT ERREUR ANIMÉE
  ════════════════════════════════════════════ */
  .alert-error {
    background: #fce4e4;
    border: 1px solid #fcc2c3;
    color: #cc0033;
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 10px;
    font-size: .87rem;
    font-weight: 600;
    text-align: center;
    animation: bounceIn .5s ease both, shake .4s .5s ease;
    border-left: 4px solid #ef4444;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
  }

  /* ════════════════════════════════════════════
     FORM GROUPS — apparition en cascade
  ════════════════════════════════════════════ */
  .form-group:nth-child(1) { animation: fadeInUp .5s .2s ease both; }
  .form-group:nth-child(2) { animation: fadeInUp .5s .32s ease both; }
  .form-group:nth-child(3) { animation: fadeInUp .5s .44s ease both; }
  .form-row               { animation: fadeIn .5s .55s ease both; }

  .form-group label {
    font-size: .82rem;
    font-weight: 700;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
    transition: color .2s;
  }
  .form-group:focus-within label { color: #db2777; }

  /* ════════════════════════════════════════════
     INPUTS
  ════════════════════════════════════════════ */
  .form-input {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid #f3f4f6;
    border-radius: 12px;
    font-size: .92rem;
    font-family: 'Quicksand', sans-serif;
    outline: none;
    background: #fafbff;
    transition: all .3s ease;
    box-sizing: border-box;
  }
  .form-input:focus {
    border-color: #db2777;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(219,39,119,.1);
    transform: scale(1.01);
  }
  .form-input:hover:not(:focus) {
    border-color: #f9a8d4;
  }

  /* Input invalide */
  .form-input.invalid, input.invalid, select.invalid {
    border: 2px solid #ef4444 !important;
    background: #fff5f5 !important;
    animation: shake .35s ease;
  }

  /* ════════════════════════════════════════════
     TOGGLE PASSWORD
  ════════════════════════════════════════════ */
  .input-with-icon { position: relative; width: 100%; }
  .toggle-password {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #aaa;
    transition: color .2s, transform .2s;
  }
  .toggle-password:hover {
    color: #db2777;
    transform: translateY(-50%) scale(1.2);
  }

  /* ════════════════════════════════════════════
     ERROR MESSAGES
  ════════════════════════════════════════════ */
  .error-msg {
    color: #ef4444 !important;
    font-weight: 700 !important;
    font-size: .78rem;
    display: block;
    margin-top: 4px;
    animation: slideInLeft .3s ease;
  }

  /* ════════════════════════════════════════════
     BOUTON CONNEXION
  ════════════════════════════════════════════ */
  .btn-login {
    width: 100%;
    padding: 13px;
    margin-top: 1.2rem;
    border-radius: 50px;
    border: none;
    background: linear-gradient(135deg, #db2777, #f472b6);
    color: #fff;
    font-family: 'Quicksand', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all .3s ease;
    animation: fadeInUp .5s .6s ease both;
  }
  .btn-login::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent);
    transition: left .5s;
  }
  .btn-login:hover::before { left: 100%; }
  .btn-login:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(219,39,119,.4);
    animation: pulseRing .7s ease;
  }
  .btn-login:active { transform: scale(.97); }
  .btn-login:disabled {
    opacity: .75;
    cursor: not-allowed;
    transform: none;
  }

  /* Loading spinner dans le bouton */
  .btn-login.loading::after {
    content: '';
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,.5);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin-left: 8px;
    vertical-align: middle;
  }

  /* ════════════════════════════════════════════
     DIVIDER & FOOTER
  ════════════════════════════════════════════ */
  .login-divider {
    text-align: center;
    margin: 1.2rem 0;
    color: #d1d5db;
    font-size: .85rem;
    position: relative;
    animation: fadeIn .5s .7s ease both;
  }
  .login-divider::before, .login-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 42%;
    height: 1px;
    background: #f3f4f6;
  }
  .login-divider::before { left: 0; }
  .login-divider::after  { right: 0; }

  .login-footer {
    text-align: center;
    font-size: .88rem;
    color: #6b7280;
    animation: fadeInUp .5s .8s ease both;
  }
  .login-footer a {
    color: #db2777;
    font-weight: 700;
    text-decoration: none;
    position: relative;
    transition: color .2s;
  }
  .login-footer a::after {
    content: '';
    position: absolute;
    bottom: -2px; left: 0;
    width: 0; height: 2px;
    background: #db2777;
    transition: width .3s ease;
    border-radius: 2px;
  }
  .login-footer a:hover::after { width: 100%; }
  .login-footer a:hover { color: #be185d; }

  /* Mot de passe oublié */
  .form-row a {
    color: #db2777;
    font-size: .82rem;
    text-decoration: none;
    transition: color .2s, letter-spacing .2s;
  }
  .form-row a:hover {
    color: #be185d;
    letter-spacing: .02em;
  }

  /* ════════════════════════════════════════════
     ROLE SELECT — icône animée
  ════════════════════════════════════════════ */
  select.form-input {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23db2777' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
  }
  </style>
</head>
<body>

<!-- Particules décoratives -->
<div class="login-particles">
  <div class="login-particle" style="width:80px;height:80px;top:10%;left:5%;--dur:6s;--del:0s;"></div>
  <div class="login-particle" style="width:50px;height:50px;top:20%;right:8%;--dur:4.5s;--del:.8s;"></div>
  <div class="login-particle" style="width:100px;height:100px;bottom:15%;left:10%;--dur:7s;--del:.3s;"></div>
  <div class="login-particle" style="width:60px;height:60px;bottom:25%;right:6%;--dur:5s;--del:1.2s;"></div>
  <div class="login-particle" style="width:35px;height:35px;top:60%;left:50%;--dur:4s;--del:.5s;"></div>
  <div class="login-particle" style="width:45px;height:45px;top:40%;right:25%;--dur:5.5s;--del:.9s;"></div>
</div>

<div class="login-wrapper">
  <div class="login-card">

    <div class="login-logo">
      <div class="logo-icon">💉</div>
      <h1>Talqih<span class="text-pink">Sghiri</span></h1>
      <p>Connectez-vous pour protéger votre bébé 💕</p>
    </div>

    <?php if (!empty($erreur)): ?>
      <div class="alert-error"><?= $erreur ?></div>
    <?php endif; ?>

    <form id="login-form" action="" method="POST">

      <div class="form-group">
        <label for="email">
          <i class="fa-solid fa-envelope"></i> Adresse email
        </label>
        <input type="email" id="email" class="form-input"
               placeholder="Adresse email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <span class="error-msg" id="error-email"></span>
      </div>

      <div class="form-group">
        <label for="password">
          <i class="fa-solid fa-lock"></i> Mot de passe
        </label>
        <div class="input-with-icon">
          <input type="password" id="password" class="form-input"
                 placeholder="Mot de passe" name="motdepasse">
          <i class="fa-solid fa-eye toggle-password" data-target="password"></i>
        </div>
        <span class="error-msg" id="error-password"></span>
      </div>

      <div class="form-group">
        <label for="role">
          <i class="fa-solid fa-user-tag"></i> Je suis :
        </label>
        <select id="role" class="form-input" name="role">
          <option value="">-- Choisir --</option>
          <option value="parent"  <?= (($_POST['role'] ?? '') === 'parent')  ? 'selected' : '' ?>>👨‍👩‍👧 Parent</option>
          <option value="docteur" <?= (($_POST['role'] ?? '') === 'docteur') ? 'selected' : '' ?>>👨‍⚕️ Docteur</option>
        </select>
        <span class="error-msg" id="error-role"></span>
      </div>

      <div class="form-row" style="text-align:right;margin-top:-5px;margin-bottom:10px;">
        <a href="forgot-password.php">
          <i class="fa-solid fa-key" style="font-size:.75rem;"></i> Mot de passe oublié ?
        </a>
      </div>

      <button type="submit" class="btn btn-login" id="login-btn">
        <i class="fa-solid fa-right-to-bracket"></i> Se connecter
      </button>

    </form>

    <div class="login-divider"><span>ou</span></div>

    <div class="login-footer">
      Pas encore de compte ? <a href="creercompte.php">S'inscrire</a>
    </div>

  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("login-form");
  const btn  = document.getElementById("login-btn");

  // ── Toggle password ──────────────────────────────
  document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', () => {
      const input = document.getElementById(icon.getAttribute('data-target'));
      if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
      }
    });
  });

  // ── Helpers ──────────────────────────────────────
  function showError(id, msg) {
    const input = document.getElementById(id);
    const span  = document.getElementById("error-" + id);
    if (span)  { span.innerText = msg; span.style.animation = 'none'; requestAnimationFrame(() => { span.style.animation = ''; }); }
    if (input) { input.classList.add('invalid'); }
  }

  function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(el => el.innerText = "");
    document.querySelectorAll('.form-input').forEach(el => el.classList.remove('invalid'));
  }

  // ── Animation focus sur les inputs ───────────────
  document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', function() {
      this.parentElement.style.transform = 'scale(1.01)';
      this.parentElement.style.transition = 'transform .2s ease';
    });
    input.addEventListener('blur', function() {
      this.parentElement.style.transform = 'scale(1)';
    });
  });

  // ── Validation + Submit ───────────────────────────
  form.addEventListener("submit", function (e) {
    clearErrors();
    let valid = true;

    const email    = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const role     = document.getElementById("role").value;

    if (!email) {
      showError("email", "L'email est requis"); valid = false;
    } else {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showError("email", "Format email invalide"); valid = false;
      }
    }

    if (!role) {
      showError("role", "Veuillez choisir votre rôle"); valid = false;
    }

    if (!password) {
      showError("password", "Le mot de passe est requis"); valid = false;
    }

    if (!valid) {
      e.preventDefault();
      // Shake la card si erreur
      const card = document.querySelector('.login-card');
      card.style.animation = 'none';
      requestAnimationFrame(() => {
        card.style.animation = 'shake .4s ease';
      });
    } else {
      // Animation loading sur le bouton
      btn.classList.add('loading');
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Connexion...';
      btn.disabled  = true;
    }
  });

  // ── Auto-masquer alert après 5s ──────────────────
  const alert = document.querySelector('.alert-error');
  if (alert) {
    setTimeout(() => {
      alert.style.transition = 'opacity .5s, transform .5s';
      alert.style.opacity    = '0';
      alert.style.transform  = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  }
});
</script>

</body>
</html>
