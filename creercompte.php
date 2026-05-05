<?php
include('db.php');

$message = ""; 
$messageClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom_utilisateur'] ?? '');
    $nom    = mysqli_real_escape_string($conn, $_POST['nom_utilisateur'] ?? '');
    $email  = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone  = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $role   = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
    $reset_token  = "";
    $token_expire = NULL;
    $motdepasse = $_POST['motdepasse'] ?? '';
    $confirmermotdepasse = $_POST['confirmermotdepasse'] ?? '';

    if (empty($email) || empty($motdepasse) || empty($nom) || empty($prenom)) {
        $message = "Veuillez remplir tous les champs.";
        $messageClass = "error";
    } else {
        if ($motdepasse !== $confirmermotdepasse) {
            $message = "Les mots de passe ne correspondent pas !";
            $messageClass = "error";
        } else {
            $passwordHash = password_hash($motdepasse, PASSWORD_DEFAULT);
            $check = mysqli_query($conn, "SELECT * FROM utilisateur WHERE email='$email'");
        
            if (mysqli_num_rows($check) > 0) {
                $message = "Cet email est déjà utilisé !";
                $messageClass = "error";
            } else {
                $sql = "INSERT INTO utilisateur 
                        (nom_utilisateur, prenom_utilisateur, email, telephone, motdepasse, role, reset_token, token_expire, statut) 
                        VALUES 
                        ('$nom', '$prenom', '$email', '$phone', '$passwordHash', '$role', '$reset_token', NULL, 'en_attente')";

                if (mysqli_query($conn, $sql)) {
                    $last_id = mysqli_insert_id($conn);

                    if ($role == "parent") {
                        $sql_parent = "INSERT INTO parent 
                                       (id_parent, nom_parent, prenom_parent, email, telephone, motdepasse) 
                                       VALUES 
                                       ('$last_id', '$nom', '$prenom', '$email', '$phone', '$passwordHash')";
                        mysqli_query($conn, $sql_parent);
                        $sql_notifp = "INSERT INTO notification 
                                       (message, type, date_notification, statut, email) 
                                       VALUES 
                                       ('Parent inscrit', 'Info', NOW(), 'Non lu', '$email')";
                        mysqli_query($conn, $sql_notifp);
                    }

                    if ($role == "docteur") {
                        $sql_docteur = "INSERT INTO docteur 
                                        (id_docteur, nom_docteur, prenom_docteur, email, telephone, motdepasse) 
                                        VALUES 
                                        ('$last_id', '$nom', '$prenom', '$email', '$phone', '$passwordHash')";
                        mysqli_query($conn, $sql_docteur);
                        $sql_notifd = "INSERT INTO notification 
                                       (message, type, date_notification, statut, email) 
                                       VALUES 
                                       ('Docteur inscrit', 'Info', NOW(), 'Non lu', '$email')";
                        mysqli_query($conn, $sql_notifd);
                    }

                    header("Location: attente.php?email=" . urlencode($email));
                    exit();
                } else {
                    $message = "Erreur lors de l'inscription : " . mysqli_error($conn);
                    $messageClass = "error";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — TalqihSghiri</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="creercompte.css">

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
    .register-wrapper {
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

    /* Particules */
    .register-particles {
        position: fixed;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
        z-index: 0;
    }
    .register-particle {
        position: absolute;
        border-radius: 50%;
        background: hsla(340,60%,70%,.15);
        animation: particleFloat var(--dur,5s) ease-in-out infinite;
        animation-delay: var(--del,0s);
    }

    /* ════════════════════════════════════════════
       CARD
    ════════════════════════════════════════════ */
    .register-card {
        background: #fff;
        border-radius: 24px;
        padding: 2.2rem 2rem;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 20px 60px rgba(219,39,119,.15), 0 4px 20px rgba(0,0,0,.08);
        animation: scaleIn .6s ease both;
        position: relative;
        z-index: 1;
        border: 1px solid rgba(219,39,119,.1);
    }

    /* Ligne déco shimmer en haut */
    .register-card::before {
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
    .register-logo {
        text-align: center;
        margin-bottom: 1.5rem;
        animation: fadeInDown .6s .1s ease both;
    }
    .register-logo .logo-icon {
        font-size: 2.5rem;
        display: inline-block;
        animation: float 3s ease-in-out infinite;
        margin-bottom: .3rem;
    }
    .register-logo h1 {
        font-family: 'Nunito', sans-serif;
        font-size: 1.7rem;
        font-weight: 800;
        color: #1a1a2e;
        margin: 0;
    }
    .register-logo .text-pink {
        background: linear-gradient(90deg, #db2777, #f472b6, #db2777);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: shimmer 3s linear infinite;
    }
    .register-logo p {
        color: #6b7280;
        font-size: .85rem;
        margin-top: .3rem;
    }

    /* ════════════════════════════════════════════
       MESSAGES ERREUR/SUCCÈS
    ════════════════════════════════════════════ */
    .msg {
        padding: 12px 16px;
        margin: 12px 0;
        border-radius: 10px;
        text-align: center;
        font-weight: 600;
        font-size: .87rem;
        animation: bounceIn .5s ease both;
        border-left: 4px solid transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .msg.error {
        background: #fce4e4;
        border-color: #ef4444;
        color: #cc0033;
        animation: bounceIn .5s ease both, shake .4s .5s ease;
    }
    .msg.success {
        background: #d4edda;
        border-color: #22c55e;
        color: #155724;
        animation: bounceIn .5s ease both;
    }

    /* ════════════════════════════════════════════
       FORM GROUPS — cascade
    ════════════════════════════════════════════ */
    .form-row-double { animation: fadeInUp .5s .2s ease both; }
    #mail    { animation: fadeInUp .5s .3s ease both; }
    #phon    { animation: fadeInUp .5s .37s ease both; }
    .form-group:not([id]) { animation: fadeInUp .5s .44s ease both; }
    #motdepasse  { animation: fadeInUp .5s .51s ease both; }
    #confirmer   { animation: fadeInUp .5s .58s ease both; }

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
        transition: all .3s ease !important;
        border: 2px solid #f3f4f6 !important;
        border-radius: 12px !important;
        background: #fafbff !important;
    }
    .form-input:focus {
        border-color: #db2777 !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(219,39,119,.1) !important;
        transform: scale(1.01);
        outline: none !important;
    }
    .form-input:hover:not(:focus) {
        border-color: #f9a8d4 !important;
    }
    .form-input.invalid, input.invalid, select.invalid {
        border: 2px solid #ef4444 !important;
        background: #fff5f5 !important;
        animation: shake .35s ease !important;
    }

    /* Phone prefix */
    .phone-input-wrapper {
        display: flex;
        align-items: center;
        border: 2px solid #f3f4f6;
        border-radius: 12px;
        overflow: hidden;
        background: #fafbff;
        transition: all .3s ease;
    }
    .phone-input-wrapper:focus-within {
        border-color: #db2777;
        box-shadow: 0 0 0 4px rgba(219,39,119,.1);
    }
    .phone-prefix {
        padding: 0 12px;
        font-weight: 700;
        color: #db2777;
        background: #fce7f3;
        border-right: 1px solid #f9a8d4;
        height: 100%;
        display: flex;
        align-items: center;
        font-size: .9rem;
    }
    .phone-input {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: transparent !important;
    }
    .phone-input:focus {
        box-shadow: none !important;
        transform: none !important;
    }

    /* Toggle password */
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

    /* Select */
    select.form-input {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23db2777' d='M6 8L1 3h10z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 14px center !important;
        padding-right: 36px !important;
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
       BOUTON INSCRIPTION
    ════════════════════════════════════════════ */
    .btn-register {
        width: 100%;
        padding: 13px !important;
        margin-top: 1rem;
        border-radius: 50px !important;
        border: none !important;
        background: linear-gradient(135deg, #db2777, #f472b6) !important;
        color: #fff !important;
        font-family: 'Quicksand', sans-serif !important;
        font-size: 1rem !important;
        font-weight: 700 !important;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all .3s ease !important;
        animation: fadeInUp .5s .65s ease both;
    }
    .btn-register::before {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.25), transparent);
        transition: left .5s;
    }
    .btn-register:hover::before { left: 100%; }
    .btn-register:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 10px 25px rgba(219,39,119,.4) !important;
        animation: pulseRing .7s ease !important;
    }
    .btn-register:active { transform: scale(.97) !important; }
    .btn-register:disabled {
        opacity: .75;
        cursor: not-allowed;
        transform: none !important;
    }

    /* ════════════════════════════════════════════
       FOOTER
    ════════════════════════════════════════════ */
    .register-footer {
        text-align: center;
        font-size: .88rem;
        color: #6b7280;
        margin-top: 1.2rem;
        animation: fadeIn .5s .75s ease both;
    }
    .register-footer a {
        color: #db2777;
        font-weight: 700;
        text-decoration: none;
        position: relative;
        transition: color .2s;
    }
    .register-footer a::after {
        content: '';
        position: absolute;
        bottom: -2px; left: 0;
        width: 0; height: 2px;
        background: #db2777;
        transition: width .3s ease;
        border-radius: 2px;
    }
    .register-footer a:hover::after { width: 100%; }
    .register-footer a:hover { color: #be185d; }
    </style>
</head>
<body>

<!-- Particules décoratives -->
<div class="register-particles">
    <div class="register-particle" style="width:80px;height:80px;top:8%;left:5%;--dur:6s;--del:0s;"></div>
    <div class="register-particle" style="width:50px;height:50px;top:18%;right:7%;--dur:4.5s;--del:.8s;"></div>
    <div class="register-particle" style="width:100px;height:100px;bottom:12%;left:8%;--dur:7s;--del:.3s;"></div>
    <div class="register-particle" style="width:60px;height:60px;bottom:20%;right:5%;--dur:5s;--del:1.2s;"></div>
    <div class="register-particle" style="width:35px;height:35px;top:55%;left:48%;--dur:4s;--del:.5s;"></div>
    <div class="register-particle" style="width:45px;height:45px;top:35%;right:22%;--dur:5.5s;--del:.9s;"></div>
</div>

<div class="register-wrapper">
    <div class="register-card">

        <div class="register-logo">
            <div class="logo-icon">💉</div>
            <h1>Talqih<span class="text-pink">Sghiri</span></h1>
            <p>Créez votre compte pour protéger votre bébé 🍼</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="msg <?php echo $messageClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="post" action="">

            <div class="form-row-double">
                <div class="form-group">
                    <label for="firstname">
                        <i class="fa-solid fa-user"></i> Prénom
                    </label>
                    <input type="text" id="firstname" class="form-input" placeholder="Prénom" name="prenom_utilisateur">
                    <span class="error-msg" id="error-firstname"></span>
                </div>
                <div class="form-group">
                    <label for="lastname">
                        <i class="fa-solid fa-user"></i> Nom
                    </label>
                    <input type="text" id="lastname" class="form-input" placeholder="Nom" name="nom_utilisateur">
                    <span class="error-msg" id="error-lastname"></span>
                </div>
            </div>

            <div class="form-group" id="mail">
                <label for="email">
                    <i class="fa-solid fa-envelope"></i> Adresse email
                </label>
                <input type="email" id="email" class="form-input" placeholder="Adresse email" name="email">
                <span class="error-msg" id="error-email"></span>
            </div>

            <div class="form-group" id="phon">
                <label for="phone">
                    <i class="fa-solid fa-phone"></i> Téléphone
                </label>
                <div class="phone-input-wrapper" id="num">
                    <span class="phone-prefix">+216</span>
                    <input type="text" id="phone" class="form-input phone-input" name="phone" placeholder="Numéro" maxlength="8">
                </div>
                <span class="error-msg" id="error-phone"></span>
            </div>

            <div class="form-group">
                <label for="role">
                    <i class="fa-solid fa-user-tag"></i> Je suis :
                </label>
                <select id="role" class="form-input" name="role">
                    <option value="">Choisir</option>
                    <option value="parent">👨‍👩‍👧 Parent</option>
                    <option value="docteur">👨‍⚕️ Docteur</option>
                </select>
                <span class="error-msg" id="error-role"></span>
            </div>

            <div class="form-group" id="motdepasse">
                <label for="password">
                    <i class="fa-solid fa-lock"></i> Mot de passe
                </label>
                <div class="input-with-icon">
                    <input type="password" id="password" class="form-input" placeholder="Mot de passe" name="motdepasse">
                    <i class="fa-solid fa-eye toggle-password" data-target="password"></i>
                </div>
                <span class="error-msg" id="error-password"></span>
            </div>

            <div class="form-group" id="confirmer">
                <label for="confirm-password">
                    <i class="fa-solid fa-lock"></i> Confirmer le mot de passe
                </label>
                <div class="input-with-icon">
                    <input type="password" id="confirm-password" class="form-input" placeholder="Confirmer mot de passe" name="confirmermotdepasse">
                    <i class="fa-solid fa-eye toggle-password" data-target="confirm-password"></i>
                </div>
                <span class="error-msg" id="error-confirm-password"></span>
            </div>

            <button type="submit" id="register-btn"
                    class="btn btn-primary btn-round btn-register">
                <i class="fa-solid fa-user-plus"></i> Créer mon compte
            </button>
        </form>

        <div class="register-footer">
            Déjà un compte ? <a href="loginp.php">Se connecter</a>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registerForm");
    const btn  = document.getElementById("register-btn");

    // ── Toggle password ──────────────────────────
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

    // ── Helpers ──────────────────────────────────
    function showError(inputId, message) {
        const input     = document.getElementById(inputId);
        const errorSpan = document.getElementById("error-" + inputId);
        if (errorSpan) {
            errorSpan.innerText = message;
            errorSpan.style.animation = 'none';
            requestAnimationFrame(() => { errorSpan.style.animation = ''; });
        }
        if (input) input.classList.add('invalid');
    }

    function clearErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = "");
        document.querySelectorAll('.form-input').forEach(el => el.classList.remove('invalid'));
    }

    // ── Animation focus labels ───────────────────
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            const group = this.closest('.form-group');
            if (group) {
                group.style.transform = 'scale(1.01)';
                group.style.transition = 'transform .2s ease';
            }
        });
        input.addEventListener('blur', function() {
            const group = this.closest('.form-group');
            if (group) group.style.transform = 'scale(1)';
        });
    });

    // ── Validation + Submit ───────────────────────
    form.addEventListener("submit", function (e) {
        clearErrors();
        let isValid = true;

        const firstname = document.getElementById("firstname").value.trim();
        const lastname  = document.getElementById("lastname").value.trim();
        const email     = document.getElementById("email").value.trim();
        const phone     = document.getElementById("phone").value.trim();
        const role      = document.getElementById("role").value;
        const password  = document.getElementById("password").value;
        const confirm   = document.getElementById("confirm-password").value;

        if (!firstname) { showError("firstname", "Prénom requis"); isValid = false; }
        if (!lastname)  { showError("lastname",  "Nom requis"); isValid = false; }
        if (!role)      { showError("role", "Veuillez choisir un rôle"); isValid = false; }

        const emailRegex = /^[^\s@]+@(gmail\.com|yahoo\.(com|fr)|outlook\.com)$/;
        if (!emailRegex.test(email)) {
            showError("email", "Email invalide (@gmail, @yahoo, @outlook)");
            isValid = false;
        }

        const tunisPhoneRegex = /^[2459]\d{7}$/;
        if (!tunisPhoneRegex.test(phone)) {
            showError("phone", "Numéro tunisien invalide (8 chiffres)");
            isValid = false;
        }

        if (password.length < 8) {
            showError("password", "Minimum 8 caractères");
            isValid = false;
        }

        if (!confirm) {
            showError("confirm-password", "Veuillez confirmer le mot de passe");
            isValid = false;
        } else if (password !== confirm) {
            showError("confirm-password", "Les mots de passe ne correspondent pas");
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Shake la card
            const card = document.querySelector('.register-card');
            card.style.animation = 'none';
            requestAnimationFrame(() => { card.style.animation = 'shake .4s ease'; });
        } else {
            // Loading bouton
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Création...';
            btn.disabled  = true;
            btn.style.opacity = '.75';
        }
    });

    // ── Auto-masquer message après 5s ────────────
    const msg = document.querySelector('.msg');
    if (msg) {
        setTimeout(() => {
            msg.style.transition = 'opacity .5s, transform .5s';
            msg.style.opacity    = '0';
            msg.style.transform  = 'translateY(-10px)';
            setTimeout(() => msg.remove(), 500);
        }, 5000);
    }
});
</script>

</body>
</html>