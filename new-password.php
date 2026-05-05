<?php
$conn = new mysqli("localhost", "root", "", "vaccin");
$token = $_GET['token'] ?? '';


$stmt = $conn->prepare("SELECT email FROM users WHERE reset_token = ? AND token_expire > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("Lien invalide ou expiré.");
}

$user = $res->fetch_assoc();

if (isset($_POST['update'])) {
    $new_pass = password_hash($_POST['motdepasse'], PASSWORD_DEFAULT); 
    
    $upd = $conn->prepare("UPDATE utilisateur  SET motdepasse = ?, reset_token = NULL, token_expire = NULL WHERE email = ?");
    $upd->bind_param("ss", $new_pass, $user['email']);
    $upd->execute();
    
    echo "Mot de passe modifié avec succès ! <a href='login.php'>Connexion</a>";
    exit;
}
?>

<form method="POST" style="text-align:center; padding: 50px;">
    <h3>Nouveau mot de passe</h3>
    <input type="password" name="motdepasse" placeholder="Mot de passe" required style="border-radius:20px; padding:10px;"><br><br>
    <button type="submit" name="update" class="btn btn-primary btn-round">Enregistrer</button>
</form>
?>
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