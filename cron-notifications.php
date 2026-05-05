<?php
include('db.php');

$today    = date('Y-m-d');
$delais   = [5, 3, 1];
$inserted = 0;
$errors   = [];

foreach ($delais as $j) {
    $target_date = date('Y-m-d', strtotime("+$j days"));
    $sql = "
        SELECT r.id_rdv, r.date_rdv, r.heure_rdv,
               e.prenom_enfant, e.nom_enfant,
               p.email, p.nom_parent,
               v.nom_complet AS nom_vaccin
        FROM rendez_vous r
        JOIN enfant e ON e.id_enfant = r.id_enfant
        JOIN parent p ON p.id_parent = r.id_parent
        LEFT JOIN vaccin v ON v.id_vaccin = r.id_vaccin
        WHERE r.date_rdv = '$target_date'
          AND r.statut != 'Terminé'
    ";
    $res = mysqli_query($conn, $sql);
    if (!$res) { $errors[] = mysqli_error($conn); continue; }
    while ($rdv = mysqli_fetch_assoc($res)) {
        $email   = mysqli_real_escape_string($conn, $rdv['email']);
        $message = "Rappel : RDV de {$rdv['prenom_enfant']} {$rdv['nom_enfant']} dans $j jour(s) le " . date('d/m/Y', strtotime($rdv['date_rdv'])) . " à " . substr($rdv['heure_rdv'],0,5) . ". Vaccin : " . ($rdv['nom_vaccin'] ?? 'à préciser') . ".";
        $msg_esc = mysqli_real_escape_string($conn, $message);
        $chk = mysqli_query($conn, "SELECT id_notification FROM notification WHERE email='$email' AND type='Rappel' AND message LIKE '%{$rdv['prenom_enfant']}%' AND message LIKE '%dans $j jour%' AND DATE(date_notification)='$today'");
        if ($chk && mysqli_num_rows($chk) > 0) continue;
        $ins = mysqli_query($conn, "INSERT INTO notification (email, type, message, statut, date_notification) VALUES ('$email', 'Rappel', '$msg_esc', 'Non lu', NOW())");
        if ($ins) $inserted++;
        else $errors[] = mysqli_error($conn);
    }
}

$sql_retard = "
    SELECT v.id_vaccination, v.date_prevue,
           e.prenom_enfant, e.nom_enfant,
           p.email,
           vac.nom_complet AS nom_vaccin
    FROM vaccination v
    JOIN enfant e ON e.id_enfant = v.id_enfant
    JOIN parent p ON p.id_parent = e.id_parent
    LEFT JOIN vaccin vac ON vac.id_vaccin = v.id_vaccin
    WHERE v.statut = 'Prévu'
      AND v.date_prevue < '$today'
";
$res_retard = mysqli_query($conn, $sql_retard);
if ($res_retard) {
    while ($vr = mysqli_fetch_assoc($res_retard)) {
        mysqli_query($conn, "UPDATE vaccination SET statut='En retard' WHERE id_vaccination={$vr['id_vaccination']}");
        $email   = mysqli_real_escape_string($conn, $vr['email']);
        $message = "Vaccination en retard : {$vr['prenom_enfant']} {$vr['nom_enfant']} devait recevoir « " . ($vr['nom_vaccin'] ?? 'Vaccin') . " » le " . date('d/m/Y', strtotime($vr['date_prevue'])) . ".";
        $msg_esc = mysqli_real_escape_string($conn, $message);
        $chk = mysqli_query($conn, "SELECT id_notification FROM notification WHERE email='$email' AND type='Alerte' AND message LIKE '%{$vr['prenom_enfant']}%' AND message LIKE '%retard%' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) continue;
        $ins = mysqli_query($conn, "INSERT INTO notification (email, type, message, statut, date_notification) VALUES ('$email', 'Alerte', '$msg_esc', 'Non lu', NOW())");
        if ($ins) $inserted++;
    }
}

$res_enfants = mysqli_query($conn, "SELECT id_enfant, prenom_enfant, nom_enfant, date_naissance FROM enfant WHERE NOT EXISTS (SELECT 1 FROM vaccination v WHERE v.id_enfant = enfant.id_enfant)");
$calendrier = [
    ['label'=>'Naissance','jours'=>0], ['label'=>'2 mois','jours'=>60],
    ['label'=>'3 mois','jours'=>90],   ['label'=>'4 mois','jours'=>120],
    ['label'=>'6 mois','jours'=>180],  ['label'=>'9 mois','jours'=>270],
    ['label'=>'11 mois','jours'=>330], ['label'=>'12 mois','jours'=>365],
    ['label'=>'18 mois','jours'=>540],
];
if ($res_enfants) {
    while ($enf = mysqli_fetch_assoc($res_enfants)) {
        $dob = new DateTime($enf['date_naissance']);
        foreach ($calendrier as $cal) {
            $age_label = mysqli_real_escape_string($conn, $cal['label']);
            $res_vacs  = mysqli_query($conn, "SELECT id_vaccin FROM vaccin WHERE age_recommande='$age_label'");
            if (!$res_vacs) continue;
            while ($vac = mysqli_fetch_assoc($res_vacs)) {
                $d = clone $dob;
                $d->modify("+{$cal['jours']} days");
                $dp = $d->format('Y-m-d');
                $st = ($dp < $today) ? 'En retard' : 'Prévu';
                mysqli_query($conn, "INSERT INTO vaccination (date_prevue, statut, id_enfant, id_vaccin) VALUES ('$dp', '$st', {$enf['id_enfant']}, {$vac['id_vaccin']})");
            }
        }
    }
}

echo json_encode(['status' => empty($errors) ? 'ok' : 'partial', 'inserted' => $inserted, 'errors' => $errors, 'date' => $today]);