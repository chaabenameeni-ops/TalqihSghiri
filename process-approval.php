<?php
include('db.php');

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'accept') {
        $check_sql = "SELECT * FROM utilisateur WHERE id_utilisateur = $id";
        $check_res = mysqli_query($conn, $check_sql);
        $user_data = mysqli_fetch_assoc($check_res);

        if ($user_data['statut'] === 'Accepté') {
            header("Location: gestion-utilisateur.php?status=already_done");
            exit();
        }

        // 2. تحديث الحالة في جدول utilisateur (id بالصغير)
        $update_sql = "UPDATE utilisateur SET statut = 'Accepté' WHERE id_utilisateur = $id";
        
        if (mysqli_query($conn, $update_sql)) {
            $nom = mysqli_real_escape_string($conn, $user_data['nom_utilisateur']);
            $email = mysqli_real_escape_string($conn, $user_data['email']);
            $role = $user_data['role'];

            // 3. الصب في الجدول المناسب حسب الـ Role
            if ($role == 'Parent') {
                $check_p = mysqli_query($conn, "SELECT * FROM parent WHERE id_utilisateur = $id");
                if (mysqli_num_rows($check_p) == 0) {
                    // صب في جدول parent
                    mysqli_query($conn, "INSERT INTO parent (id_utilisateur, nom_parent, email) VALUES ($id, '$nom', '$email')");
                }
            } elseif ($role == 'Docteur') {
                $check_d = mysqli_query($conn, "SELECT * FROM docteur WHERE id_utilisateur = $id");
                if (mysqli_num_rows($check_d) == 0) {
                    // صب في جدول docteur
                    mysqli_query($conn, "INSERT INTO docteur (id_utilisateur, nom_docteur, email) VALUES ($id, '$nom', '$email')");
                }
            }

            // 4. إرسال التنبيه
            $msg = "Félicitations ! Votre compte TalqihSghiri a été accepté.";
            mysqli_query($conn, "INSERT INTO notification (id_utilisateur, message, date_notification, lu) VALUES ($id, '$msg', NOW(), 0)");

            header("Location: gestion-utilisateur.php?status=success");
            exit();
        }
    } 
    elseif ($action == 'refuse') {
        // 5. الفسخ النهائي (id بالصغير)
        $delete_sql = "DELETE FROM utilisateur WHERE id_utilisateur = $id";
        if (mysqli_query($conn, $delete_sql)) {
            header("Location: gestion-utilisateur.php?status=deleted");
            exit();
        }
    }
}
?>