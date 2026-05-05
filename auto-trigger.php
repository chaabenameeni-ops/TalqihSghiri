<?php
/**
 * auto-trigger.php
 * Inclure ce fichier dans parent.php, admin.php, etc.
 * Il déclenche le cron une fois par jour maximum (stocké en BDD ou fichier).
 */

$flag_file = __DIR__ . '/storage/cron_last_run.txt';
$today     = date('Y-m-d');

// Créer le dossier storage si absent
if (!is_dir(__DIR__ . '/storage')) {
    mkdir(__DIR__ . '/storage', 0755, true);
}

$last_run = file_exists($flag_file) ? trim(file_get_contents($flag_file)) : '';

if ($last_run !== $today) {
    // Déclencher le cron en arrière-plan (non bloquant)
    $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
         . dirname($_SERVER['REQUEST_URI']) . '/cron-notifications.php';

    // Tentative via file_get_contents (si allow_url_fopen activé)
    @file_get_contents($url);

    // Fallback : inclusion directe
    if (!file_exists($flag_file) || trim(file_get_contents($flag_file)) !== $today) {
        include_once __DIR__ . '/cron-notifications.php';
    }

    file_put_contents($flag_file, $today);
}