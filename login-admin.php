<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['motdepasse'];

    if (empty($email) || empty($password) || !str_ends_with($email, "@talqih.tn")) {
        header("Location: login-admin.php?error=invalid");
        exit();
    }
    
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin — TalqihSghiri</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --admin-blue: #70b8d8;
            --admin-blue-light: #e3f2fd;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Nunito', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo-icon-admin {
            background: var(--admin-blue-light);
            color: var(--admin-blue);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
        }

        .login-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .login-header h2 span {
            color: var(--admin-blue);
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .form-input:focus {
            border-color: var(--admin-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(112, 184, 216, 0.2);
        }

        .btn-login-admin {
            background: var(--admin-blue);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-login-admin:hover {
            background: #5da6c5;
            transform: translateY(-2px);
        }

        .input-with-icon { position: relative; }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }

        .error-msg {
            color: #dc3545;
            font-size: 0.8rem;
            font-weight: bold;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <div class="login-header">
            <div class="logo-icon-admin">💉</div>
            <h2>Talqih<span>Sghiri</span></h2>
            <p style="color: #888; font-size: 0.9rem; margin-top: 5px;">Espace Administrateur</p>
        </div>

        <form id="login-form-admin" action="admin-vaccines.php" method="post" style="margin-top: 30px;">
            <div class="form-group">
                <label><i class="fa-solid fa-envelope"></i> Adresse email</label>
                <input type="email" id="email" class="form-input" placeholder="admin@talqih.tn" name="email">
                <span class="error-msg" id="error-email"></span>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Mot de passe</label>
                <div class="input-with-icon">
                    <input type="password" id="password" class="form-input" placeholder="••••••••" name="motdepasse">
                    <i class="fa-solid fa-eye toggle-password" data-target="password"></i>
                </div>
                <span class="error-msg" id="error-password"></span>
            </div>

            <button type="submit" class="btn-login-admin">Se connecter</button>
        </form>

    </div>

   <script>
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = document.getElementById(icon.getAttribute('data-target'));
            input.type = input.type === "password" ? "text" : "password";
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });

    document.getElementById('login-form-admin').addEventListener('submit', function(e) {
        let email = document.getElementById('email').value;
        let password = document.getElementById('password').value;
        let errorEmail = document.getElementById('error-email');
        let errorPassword = document.getElementById('error-password');
        let isValid = true;

        errorEmail.innerText = "";
        errorPassword.innerText = "";

        if (email === "") {
            errorEmail.innerText = "L'email est requis";
            isValid = false;
        }
        if (password === "") {
            errorPassword.innerText = "Le mot de passe est requis";
            isValid = false;
        }

        if (email !== "" && !email.endsWith("@talqih.tn")) {
            errorEmail.innerText = "Domaine @talqih.tn";
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>