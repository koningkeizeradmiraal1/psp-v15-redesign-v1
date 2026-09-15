<?php
/**
 * PSP eenmalig koppel-script — linkt de zojuist geïmporteerde diensten (week 38)
 * aan echte beschikbaarheid-records per student, zodat ze in het Weekrooster
 * verschijnen als "✓ Ingepland" met naam, in plaats van "Open".
 *
 * Vereist dat psp-run-import-v2.php al is uitgevoerd (de 76 diensten moeten al bestaan).
 *
 * URL: https://psplanning.nl/wp-content/plugins/prostudents-planning/psp-run-koppel-v2.php?sleutel=pspkoppel2026v2
 */

define('PSP_KOPPEL_SLEUTEL', 'pspkoppel2026v2');

$wp_load = dirname(__FILE__);
for ($i = 0; $i < 6; $i++) {
    $try = $wp_load . '/wp-load.php';
    if (file_exists($try)) { require_once $try; break; }
    $wp_load = dirname($wp_load);
}
if (!defined('ABSPATH')) {
    die('WordPress niet gevonden.');
}

$sleutel = $_GET['sleutel'] ?? '';
if ($sleutel !== PSP_KOPPEL_SLEUTEL) {
    http_response_code(403);
    exit('Geen toegang. Voeg ?sleutel=' . PSP_KOPPEL_SLEUTEL . ' toe aan de URL.');
}

$data_file = dirname(__FILE__) . '/psp_koppel_data.php';
if (!file_exists($data_file)) {
    exit('psp_koppel_data.php niet gevonden in de plugin-map.');
}
require $data_file; // definieert $PSP_STUDENTEN en $PSP_KOPPELINGEN

global $wpdb;
$T_BESCH  = $wpdb->prefix . 'ps_beschikbaarheid';
$T_DIENST = $wpdb->prefix . 'ps_diensten';
$T_KOPPEL = $wpdb->prefix . 'ps_koppelingen';
$WEEK_START = '2026-09-14';

$run = isset($_GET['run']) && $_GET['run'] === '1';

header('Content-Type: text/html; charset=utf-8');
echo '<h1>PSP – diensten koppelen aan studenten (week 38)</h1>';
echo '<p>' . count($PSP_STUDENTEN) . ' studenten, ' . count($PSP_KOPPELINGEN) . ' te koppelen diensten.</p>';

if (!$run) {
    echo '<form method="get"><input type="hidden" name="sleutel" value="' . esc_attr(PSP_KOPPEL_SLEUTEL) . '">
          <input type="hidden" name="run" value="1">
          <button type="submit" style="background:#d31775;color:#fff;border:none;padding:10px 20px;border-radius:6px;font-size:1rem;cursor:pointer">▶ Koppelen uitvoeren</button></form>';
    exit;
}

echo '<pre>';

// Stap 1: beschikbaarheid aanmaken (indien nog niet aanwezig) per student
$besch_id_per_naam = [];
$aangemaakt = 0; $bestond = 0;
foreach ($PSP_STUDENTEN as $naam => $info) {
    $bestaand = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM `{$T_BESCH}` WHERE email = %s AND week_start = %s LIMIT 1",
        $info['email'], $WEEK_START
    ));
    if ($bestaand) {
        $besch_id_per_naam[$naam] = (int) $bestaand;
        $bestond++;
        continue;
    }
    $ok = $wpdb->insert($T_BESCH, [
        'naam'         => $naam,
        'email'        => $info['email'],
        'telefoon'     => '',
        'week_start'   => $WEEK_START,
        'dagen'        => wp_json_encode($info['dagen']),
        'vaardigheden' => wp_json_encode([]),
        'voorkeur'     => '[excel-import] automatisch aangemaakt vanuit de weekplanning',
        'status'       => 'actief',
    ]);
    if ($ok) {
        $besch_id_per_naam[$naam] = (int) $wpdb->insert_id;
        $aangemaakt++;
        echo "✓ Beschikbaarheid aangemaakt voor {$naam} (id {$wpdb->insert_id})\n";
    } else {
        echo "❌ FOUT bij aanmaken beschikbaarheid voor {$naam}: {$wpdb->last_error}\n";
    }
}
echo "\n{$aangemaakt} nieuwe beschikbaarheid-records, {$bestond} bestonden al.\n\n";

// Stap 2: koppelingen leggen
$gekoppeld = 0; $niet_gevonden = 0; $fout = 0;
foreach ($PSP_KOPPELINGEN as $rij) {
    [$naam, $og, $datum, $omschrijving] = $rij;
    if (!isset($besch_id_per_naam[$naam])) {
        echo "⚠ Geen beschikbaarheid-id voor {$naam}, overgeslagen.\n";
        $niet_gevonden++;
        continue;
    }
    $dienst_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM `{$T_DIENST}` WHERE opdrachtgever = %s AND datum = %s AND omschrijving = %s LIMIT 1",
        $og, $datum, $omschrijving
    ));
    if (!$dienst_id) {
        echo "⚠ Dienst niet gevonden: {$naam} | {$og} | {$datum}\n";
        $niet_gevonden++;
        continue;
    }
    $ok = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO `{$T_KOPPEL}` (beschikbaarheid_id, dienst_id) VALUES (%d, %d)",
        $besch_id_per_naam[$naam], (int) $dienst_id
    ));
    if ($ok === false) {
        echo "❌ FOUT bij koppelen {$naam} <-> dienst {$dienst_id}: {$wpdb->last_error}\n";
        $fout++;
    } else {
        $gekoppeld++;
    }
}

echo "\n✅ {$gekoppeld} diensten gekoppeld aan een student.\n";
if ($niet_gevonden) echo "⚠ {$niet_gevonden} niet gekoppeld (dienst of student niet gevonden).\n";
if ($fout) echo "❌ {$fout} fout(en).\n";
echo "\nVerwijder nu psp-run-koppel-v2.php en psp_koppel_data.php uit de repository.\n";
echo '</pre>';
