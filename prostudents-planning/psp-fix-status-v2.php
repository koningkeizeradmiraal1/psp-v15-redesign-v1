<?php
/**
 * PSP eenmalig fix-script — status van de zojuist geïmporteerde diensten corrigeren.
 *
 * Oorzaak: de import gebruikte CURDATE() van de server om vervuld/open te bepalen,
 * maar de systeemklok van de server komt niet overeen met de datums in de planning
 * (2026), waardoor alles op "open" kwam te staan. Dit script zet handmatig de diensten
 * van vóór 2026-09-15 terug op "vervuld".
 *
 * URL: https://psplanning.nl/wp-content/plugins/prostudents-planning/psp-fix-status-v2.php?sleutel=pspfixstatus2026
 */

define('PSP_FIX_SLEUTEL', 'pspfixstatus2026');

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
if ($sleutel !== PSP_FIX_SLEUTEL) {
    http_response_code(403);
    exit('Geen toegang. Voeg ?sleutel=' . PSP_FIX_SLEUTEL . ' toe aan de URL.');
}

global $wpdb;
$tabel = $wpdb->prefix . 'ps_diensten';
$run   = isset($_GET['run']) && $_GET['run'] === '1';

header('Content-Type: text/html; charset=utf-8');
echo '<h1>PSP – status excel-import diensten corrigeren</h1>';

$cutoff = '2026-09-15';

$aantal = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM `{$tabel}` WHERE (omschrijving = 'excel-import' OR omschrijving LIKE '[excel-import]%%') AND datum < %s AND status != 'vervuld'",
    $cutoff
));

echo "<p>Diensten die van 'open' naar 'vervuld' gezet worden (datum &lt; {$cutoff}): <strong>{$aantal}</strong></p>";

if (!$run) {
    echo '<form method="get"><input type="hidden" name="sleutel" value="' . esc_attr(PSP_FIX_SLEUTEL) . '">
          <input type="hidden" name="run" value="1">
          <button type="submit" style="background:#d31775;color:#fff;border:none;padding:10px 20px;border-radius:6px;font-size:1rem;cursor:pointer">▶ Corrigeren</button></form>';
} else {
    $result = $wpdb->query($wpdb->prepare(
        "UPDATE `{$tabel}` SET status = 'vervuld' WHERE (omschrijving = 'excel-import' OR omschrijving LIKE '[excel-import]%%') AND datum < %s AND status != 'vervuld'",
        $cutoff
    ));
    if ($result === false) {
        echo '<p style="color:#c0392b"><strong>FOUT:</strong> ' . esc_html($wpdb->last_error) . '</p>';
    } else {
        echo '<p style="color:#27ae60"><strong>✅ ' . (int)$result . ' dienst(en) bijgewerkt naar status "vervuld".</strong></p>';
        echo '<p>Verwijder dit bestand nu uit de repository.</p>';
    }
}
