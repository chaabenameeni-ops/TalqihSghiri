<?php
// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "vaccins");

if(isset($_POST['age'])) {
    $age = intval($_POST['age']);
    
    // Jointure entre la table 'age' et 'vaccin'
    $sql = "SELECT v.nom_vaccin 
            FROM age a 
            JOIN vaccin v ON a.id_vaccin = v.id_vaccin 
            WHERE a.nb_age = $age";
            
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<p style='color: #db2777; font-weight: bold;'>✅ " . $row['nom_vaccin'] . "</p>";
        }
    } else {
        echo "<p>Aucun vaccin prévu pour cet âge.</p>";
    }
}
?>