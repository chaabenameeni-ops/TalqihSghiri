<?php

session_start();
include('db.php');
$role=$_POST['role'];
$erreur="";
if (isset($_POST['role'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw = $_POST['motdepasse'];
    $sql = "SELECT * FROM utilisateur WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password_raw, $user['motdepasse'])) {
           $_SESSION['prenom'] = $user['prenom_utilisateur'];
           $_SESSION['nom'] = $user['nom_utilisateur'];
            $_SESSION['email'] = $user['email']; 
            $_SESSION['role'] = $user['role'];

            if( ($role == $user['role'])&&($user['role']=='docteur')) {
                header("Location: admin.php");
            } else {
                $erreur="⚠️ Vous devez choisir le même type de compte utilisé lors de l’inscription ! ";
                 //echo "⚠️ Vous devez choisir le même type de compte utilisé lors de l’inscription ! ";
            }
            exit();
        } else {
             echo "<script>alert('Mot de passe incorrect ❌'); window.location.href='loginp.php';</script>";
        }
    } else {
        $erreur="Utilisateur non trouvé ❌";
    }
} 
?>