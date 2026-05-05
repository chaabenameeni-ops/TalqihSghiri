<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

include('db.php');

$age_mois = intval($_GET['age'] ?? 0);

if ($age_mois < 0) {
    echo json_encode(['error' => 'Âge invalide']);
    exit();
}

// ✅ Correspondance âge en mois → label
function getAgeLabel(int $mois): string {
    if ($mois == 0)               return 'Naissance';
    if ($mois >= 1  && $mois < 3) return '2 mois';
    if ($mois >= 3  && $mois < 4) return '3 mois';
    if ($mois >= 4  && $mois < 6) return '4 mois';
    if ($mois >= 6  && $mois < 11) return '6 mois';
    if ($mois >= 11 && $mois < 12) return '11 mois';
    if ($mois >= 12 && $mois < 18) return '12 mois';
    if ($mois >= 18 && $mois < 72) return '18 mois';
    if ($mois >= 72 && $mois < 144) return '6 ans';
    if ($mois >= 144 && $mois < 216) return '12 ans';
    if ($mois >= 216) return '18 ans';
    return '';
}

$age_label = getAgeLabel($age_mois);

if (empty($age_label)) {
    echo json_encode([
        'age_mois'  => $age_mois,
        'age_label' => 'Inconnu',
        'vaccins'   => [],
        'message'   => 'Aucun vaccin trouvé pour cet âge.'
    ]);
    exit();
}

$age_esc = mysqli_real_escape_string($conn, $age_label);
$res = mysqli_query($conn,
    "SELECT id_vaccin, nom_complet, maladie, age_recommande, type_vacs, voie_administration, site_injection
     FROM vaccin
     WHERE age_recommande = '$age_esc'
     ORDER BY type_vacs DESC, nom_complet ASC");

$vaccins = [];
while ($v = mysqli_fetch_assoc($res)) {
    $vaccins[] = $v;
}

echo json_encode([
    'age_mois'  => $age_mois,
    'age_label' => $age_label,
    'count'     => count($vaccins),
    'vaccins'   => $vaccins
], JSON_UNESCAPED_UNICODE);