<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
$conn = new mysqli("localhost", "root", "", "vaccin");
if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(32));
    
    $stmt = $conn->prepare("UPDATE utilisateur SET reset_token = ?, token_expire = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE email = ?");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $link = "http://localhost/your_project/new-password.php?token=" . $token;
    
        echo "Un lien de réinitialisation a été envoyé à votre adresse e-mail.";
    } else {
        echo "Cette adresse e-mail n'existe pas dans notre base de données.";
    }
}
?>

<div class="reset-container">
    <h2>Mot de passe oublié</h2>
    <p style="color: #888; font-size: 14px;">Entrez votre email pour recevoir le lien.</p>
    
    <form action="send-link.php" method="POST">
        <input type="email" name="email" class="reset-input" placeholder="Votre Email" required>
        <button type="submit" class="btn btn-primary btn-round">Envoyer le lien</button>
    </form>
    
    <div style="margin-top: 20px;">
        <a href="loginp.php" style="color: #d87093; text-decoration: none; font-size: 13px;">← Retour à la connexion</a>
    </div>
</div>
</body>
</html>
<style>
  
.reset-container {
    max-width: 400px;
    margin: 100px auto;
    background: #fff;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    text-align: center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}


.reset-container h2, .reset-container h3 {
    color: #444;
    margin-bottom: 20px;
    font-weight: 600;
}

.reset-input {
    width: 100%;
    padding: 12px 20px;
    margin: 10px 0;
    border: 1px solid #eee;
    border-radius: 25px; 
    background: #fdfdfd;
    outline: none;
    transition: 0.3s;
    box-sizing: border-box;
}

.reset-input:focus {
    border-color: #d87093; 
    background: #fff;
}


.btn-primary.btn-round {
    background-color: #d87093; 
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: 0.3s;
    width: 100%;
    margin-top: 15px;
}

.btn-primary.btn-round:hover {
    background-color: #c05d7e;
    transform: translateY(-2px);
}


.alert {
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}
.alert-success { background: #e7f5ea; color: #2e7d32; }
.alert-error { background: #fdeaea; color: #c62828; }
</style>