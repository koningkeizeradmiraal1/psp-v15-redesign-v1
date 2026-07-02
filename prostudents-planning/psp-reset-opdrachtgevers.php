<?php
/**
 * PSP – Opdrachtgevers resetten naar de juiste lijst.
 *
 * GEBRUIK:
 *   Preview:  https://psplanning.nl/psp-reset-opdrachtgevers.php?sleutel=pspog2026
 *   Uitvoeren: https://psplanning.nl/psp-reset-opdrachtgevers.php?sleutel=pspog2026&run=1
 *
 * Verwijder dit bestand NA gebruik.
 */

define('SLEUTEL', 'pspog2026');

if (($_GET['sleutel'] ?? '') !== SLEUTEL) {
    http_response_code(403);
    exit('Geen toegang.');
}

// WordPress laden
$wp_load = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load)) {
    exit('wp-load.php niet gevonden op: ' . $wp_load);
}
require_once $wp_load;

global $wpdb;
$prefix = $wpdb->prefix;
$tabel  = $prefix . 'ps_opdrachtgevers';

// Correcte opdrachtgeverslijst
$opdrachtgevers = [
    'Alsema Zuidlaren',
    'Bakker Schoonmaakbedrijf',
    'Update GM 1906',
    'B&B Ambachtelijke bakkerij',
    'Beijk catering service',
    'PHOTONIS: ALLEEN NEDERLANDS SPREKENDEN',
    'Bonte Wever, de - horeca',
    'Bonte Wever, de - housekeeping',
    'og update GM 1906',
    'CleanEnergy',
    'klantenservicemedewerker',
    'Croptimal',
    'DVK Publiek Private Horeca',
    'FritsJurgens',
    'FysioSupplies',
    'Gang van Zaken Business Catering B.V',
    'GCH BV',
    'Heusinkveld',
    'Huize Maas',
    'Innovum (GemBOXX)',
    'Kaap Hoorn',
    'LINE UP boek & media',
    'tekstverwerkers educatieve boeken',
    'Magnum Opus (Koops)',
    'OnE Student',
    'Paradigm',
    'Score Production BV',
    'Stijlzinnig',
    'Werk Horeca - Cafe Hammingh',
];

$run = isset($_GET['run']) && $_GET['run'] === '1';

// Controleer of tabel bestaat
$tabel_bestaat = $wpdb->get_var("SHOW TABLES LIKE '{$tabel}'");

echo '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8">
<title>PSP – Opdrachtgevers reset</title>
<style>
  body { font-family: sans-serif; max-width: 700px; margin: 40px auto; background: #f8f8f8; color: #222; }
  h1   { color: #d31775; }
  .ok  { color: #2e7d32; font-weight: bold; }
  .err { color: #c62828; font-weight: bold; }
  ul   { background: #fff; border: 1px solid #ddd; padding: 12px 28px; border-radius: 6px; }
  li   { padding: 3px 0; }
  .btn { display:inline-block; margin-top:20px; padding:10px 22px;
         background:#d31775; color:#fff; border-radius:6px;
         text-decoration:none; font-weight:bold; }
</style></head><body>';

echo '<h1>PSP – Opdrachtgevers reset</h1>';

if (!$tabel_bestaat) {
    echo '<p class="err">Tabel <code>' . esc_html($tabel) . '</code> bestaat nog niet. Deploy eerst de plugin-update.</p>';
    echo '</body></html>';
    exit;
}

echo '<p>De volgende <strong>' . count($opdrachtgevers) . ' opdrachtgevers</strong> worden ingesteld:</p>';
echo '<ul>';
foreach ($opdrachtgevers as $naam) {
    echo '<li>' . esc_html($naam) . '</li>';
}
echo '</ul>';

if (!$run) {
    echo '<p>Dit is een <strong>preview</strong>. De tabel wordt nog NIET gewijzigd.</p>';
    echo '<a class="btn" href="?sleutel=' . SLEUTEL . '&run=1">▶ Nu uitvoeren (tabel legen + opnieuw vullen)</a>';
    echo '</body></html>';
    exit;
}

// Tabel leegmaken
$wpdb->query("TRUNCATE TABLE `{$tabel}`");

// Opdrachtgevers invoegen
$ingevoegd = 0;
$fouten    = [];

foreach ($opdrachtgevers as $naam) {
    $naam = trim($naam);
    if ($naam === '') continue;
    $res = $wpdb->insert($tabel, ['naam' => $naam], ['%s']);
    if ($res === false) {
        $fouten[] = $naam . ' → ' . $wpdb->last_error;
    } else {
        $ingevoegd++;
    }
}

echo '<hr>';
if (empty($fouten)) {
    echo '<p class="ok">✔ ' . $ingevoegd . ' opdrachtgevers ingevoegd. Tabel is klaar.</p>';
} else {
    echo '<p class="ok">✔ ' . $ingevoegd . ' ingevoegd.</p>';
    echo '<p class="err">Fouten:</p><ul>';
    foreach ($fouten as $f) echo '<li>' . esc_html($f) . '</li>';
    echo '</ul>';
}

echo '<p style="margin-top:30px;color:#888;font-size:.85rem">
  ⚠️ Verwijder dit bestand nu van de server:<br>
  <code>' . esc_html(__FILE__) . '</code>
</p>';
echo '</body></html>';
