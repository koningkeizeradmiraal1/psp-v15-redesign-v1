<?php
/**
 * PSP – Alle diensten, koppelingen en werkbevestigingen verwijderen.
 *
 * Preview:   https://psplanning.nl/psp-leeg-rooster.php?sleutel=pspleeg2026
 * Uitvoeren: https://psplanning.nl/psp-leeg-rooster.php?sleutel=pspleeg2026&run=1
 *
 * Verwijder dit bestand NA gebruik.
 */

define('SLEUTEL', 'pspleeg2026');

if (($_GET['sleutel'] ?? '') !== SLEUTEL) {
    http_response_code(403);
    exit('Geen toegang.');
}

$wp_load = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load)) exit('wp-load.php niet gevonden.');
require_once $wp_load;

global $wpdb;
$t_diensten    = $wpdb->prefix . 'ps_diensten';
$t_koppelingen = $wpdb->prefix . 'ps_koppelingen';
$t_wb          = $wpdb->prefix . 'ps_werkbevestigingen';

$aantallen = [
    'diensten'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$t_diensten}`"),
    'koppelingen'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$t_koppelingen}`"),
    'werkbevestigingen' => (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$t_wb}`"),
];

$run = isset($_GET['run']) && $_GET['run'] === '1';

?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>PSP – Rooster leegmaken</title>
<style>
  body { font-family: sans-serif; max-width: 600px; margin: 60px auto; background: #f8f8f8; color: #222; }
  h1   { color: #d31775; }
  .ok  { color: #2e7d32; font-weight: bold; }
  .num { font-size: 1.4rem; font-weight: bold; color: #d31775; }
  .box { background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px 28px; margin:20px 0; }
  .btn { display:inline-block; margin-top:20px; padding:11px 24px;
         background:#d31775; color:#fff; border-radius:6px;
         text-decoration:none; font-weight:bold; font-size:1rem; }
  .btn-cancel { background:#888; margin-left:10px; }
</style>
</head>
<body>
<h1>PSP – Rooster leegmaken</h1>

<?php if ($run): ?>

<?php
$wpdb->query("DELETE FROM `{$t_wb}`");
$wpdb->query("DELETE FROM `{$t_koppelingen}`");
$wpdb->query("DELETE FROM `{$t_diensten}`");

// Auto-increment resetten zodat ID's netjes bij 1 beginnen
$wpdb->query("ALTER TABLE `{$t_diensten}` AUTO_INCREMENT = 1");
$wpdb->query("ALTER TABLE `{$t_koppelingen}` AUTO_INCREMENT = 1");
$wpdb->query("ALTER TABLE `{$t_wb}` AUTO_INCREMENT = 1");
?>

<p class="ok" style="font-size:1.1rem">✔ Rooster is leeggemaakt. Alle diensten, koppelingen en werkbevestigingen zijn verwijderd.</p>
<p>Vanaf volgende week kun je diensten aanmaken met de juiste opdrachtgevers.</p>

<?php else: ?>

<div class="box">
  <p>Dit verwijdert <strong>alles</strong> uit het rooster:</p>
  <ul>
    <li><span class="num"><?php echo $aantallen['diensten']; ?></span> diensten</li>
    <li><span class="num"><?php echo $aantallen['koppelingen']; ?></span> koppelingen (student ↔ dienst)</li>
    <li><span class="num"><?php echo $aantallen['werkbevestigingen']; ?></span> werkbevestigingen</li>
  </ul>
  <p>Beschikbaarheid van studenten blijft bewaard.</p>
</div>

<a class="btn" href="?sleutel=<?php echo SLEUTEL; ?>&run=1">▶ Ja, maak rooster leeg</a>
<a class="btn btn-cancel" href="javascript:history.back()">Annuleren</a>

<?php endif; ?>

<p style="margin-top:40px;color:#aaa;font-size:.8rem">Verwijder dit bestand na gebruik.</p>
</body>
</html>
