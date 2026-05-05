<?php
// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "vaccin");

// فحص الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(32)); 

    // 1. فحص وجود الإيميل
    $check = $conn->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
    if (!$check) {
        die("Erreur SQL (Check): " . $conn->error); // سيخبرك هنا إذا كان اسم الجدول أو العمود خطأ
    }
    
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // 2. تحديث الرمز ووقت الانتهاء
        $stmt = $conn->prepare("UPDATE utilisateur SET reset_token = ?, token_expire = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE email = ?");
        
        if (!$stmt) {
            die("Erreur SQL (Update): " . $conn->error); // سيظهر الخطأ هنا إذا لم تضفي الأعمدة الجديدة
        }

        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        // 3. إرسال الرابط (تعديل الرابط ليناسب مجلد مشروعك)
        $link = "http://localhost/pfe/new-password.php?token=" . $token;
        $subject = "Réinitialisation de mot de passe";
        $msg = "Bonjour, pour réinitialiser votre mot de passe, cliquez sur ce lien : " . $link;
        $headers = "From: no-reply@votre-domaine.com";
        
        // ملاحظة: دالة mail() تحتاج لإعدادات SMTP لتصل الرسالة فعلياً
        if(mail($email, $subject, $msg, $headers)) {
            echo "Un lien de réinitialisation a été envoyé à votre adresse e-mail.";
        } else {
            echo "Lien généré : " . $link . "<br>(Note: L'envoi d'e-mail a échoué, vérifiez votre config SMTP)";
        }
        
    } else {
        echo "Cette adresse e-mail n'existe pas.";
    }
}
?>