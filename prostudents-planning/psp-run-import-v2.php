<?php
/**
 * PSP eenmalig import-script — Lopende Planning 2026 (v2, Excel-upload sept 2026)
 * Zet dit bestand via git in de plugin-map, bezoek de URL, verwijder het daarna.
 *
 * URL: https://psplanning.nl/wp-content/plugins/prostudents-planning/psp-run-import-v2.php
 *
 * Nieuwe sleutel (afwijkend van de oude psp-run-import.php, die stond nog open in de repo).
 */

define('PSP_IMPORT_SLEUTEL', 'psp2026import-v2-8f2k4q');   // ← pas dit aan vóór je pusht indien gewenst

// ── Bootstrap WordPress ───────────────────────────────────────────────
$wp_load = dirname(__FILE__);
for ($i = 0; $i < 6; $i++) {
    $try = $wp_load . '/wp-load.php';
    if (file_exists($try)) { require_once $try; break; }
    $wp_load = dirname($wp_load);
}
if (!defined('ABSPATH')) {
    die('WordPress niet gevonden. Controleer de plugin-locatie.');
}

// ── Beveiliging ───────────────────────────────────────────────────────
$sleutel = isset($_GET['sleutel']) ? $_GET['sleutel'] : '';
?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<title>PSP Import v2 — Lopende Planning 2026</title>
<style>
  body  { font-family: -apple-system, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; color: #333; }
  h1    { color: #d31775; }
  pre   { background: #f4f4f4; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: .82rem; max-height: 500px; }
  .ok   { color: #27ae60; font-weight: bold; }
  .err  { color: #c0392b; font-weight: bold; }
  .info { color: #555; }
  form  { margin: 20px 0; }
  input[type=password] { padding: 8px 12px; font-size: 1rem; border: 1px solid #ccc; border-radius: 6px; margin-right: 8px; }
  button { background: #d31775; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; font-size: 1rem; cursor: pointer; }
  .warn { background: #fff3cd; border: 1px solid #ffc107; padding: 12px 16px; border-radius: 6px; margin: 16px 0; }
</style>
</head>
<body>
<h1>PSP Import v2 — Lopende Planning 2026 (Excel-upload)</h1>

<?php if ($sleutel !== PSP_IMPORT_SLEUTEL) : ?>
<p class="info">Voer de importsleutel in om door te gaan.</p>
<form method="get">
  <input type="password" name="sleutel" placeholder="Importsleutel" autofocus>
  <?php if (isset($_GET['run'])) : ?><input type="hidden" name="run" value="1"><?php endif; ?>
  <button type="submit">Inloggen</button>
</form>

<?php else : ?>

<div class="warn">
  ⚠ Dit vervangt alle eerder via Excel geïmporteerde diensten door de nieuwe dataset
  (herkenbaar aan <code>[excel-import]</code> in de omschrijving, en de oude marker <code>excel-import</code>).
  Handmatig aangemaakte diensten worden <strong>niet</strong> aangeraakt.<br>
  <strong>Verwijder dit bestand en het .sql-bestand daarna uit de repository!</strong>
</div>

<?php
$sql_file = dirname(__FILE__) . '/lopende_planning_2026_import_v2.sql';

if (!file_exists($sql_file)) {
    echo '<p class="err">❌ SQL-bestand niet gevonden: ' . esc_html($sql_file) . '</p>';
    echo '<p class="info">Zorg dat <code>lopende_planning_2026_import_v2.sql</code> in dezelfde map staat als dit script.</p>';
} elseif (!isset($_GET['run'])) {
    $size = round(filesize($sql_file) / 1024);
    echo "<p class=\"info\">SQL-bestand gevonden ({$size} KB).</p>";
    echo '<form method="get"><input type="hidden" name="sleutel" value="' . esc_attr(PSP_IMPORT_SLEUTEL) . '">
          <input type="hidden" name="run" value="1">
          <button type="submit">▶ Import uitvoeren</button></form>';
} else {
    global $wpdb;
    $wpdb->show_errors();

    echo '<h2>Import bezig…</h2><pre>';
    flush();

    $sql_raw = file_get_contents($sql_file);
    if ($sql_raw === false) {
        echo '<span class="err">Kon SQL-bestand niet lezen.</span>';
        die();
    }

    // Vervang het {PREFIX}-token door het echte WordPress-tabelprefix
    $sql_raw = str_replace('{PREFIX}', $wpdb->prefix, $sql_raw);

    // Splits op statements (regels eindigend op ;)
    $statements = [];
    $buffer     = '';
    foreach (explode("\n", $sql_raw) as $line) {
        $trimmed = trim($line);
        if (substr($trimmed, 0, 2) === '--') continue;
        if ($trimmed === '') continue;
        $buffer .= $line . "\n";
        if (substr(rtrim($line), -1) === ';') {
            $stmt = trim($buffer);
            if ($stmt && $stmt !== ';') $statements[] = $stmt;
            $buffer = '';
        }
    }
    if (trim($buffer)) $statements[] = trim($buffer);

    $ok = 0; $errors = 0;
    foreach ($statements as $stmt) {
        $upper = strtoupper(substr($stmt, 0, 10));
        $result = $wpdb->query($stmt);
        if ($result === false) {
            echo '<span class="err">❌ FOUT: ' . esc_html($wpdb->last_error) . "</span>\n";
            echo '   SQL: ' . esc_html(substr($stmt, 0, 150)) . "…\n";
            $errors++;
        } else {
            $ok++;
            if (strpos($upper, 'DELETE') !== false || strpos($upper, 'INSERT') !== false) {
                echo '<span class="ok">✓ ' . esc_html(substr($stmt, 0, 70)) . '… (' . (int)$result . " rijen)</span>\n";
                flush();
            }
        }
    }

    echo "\n";
    if ($errors === 0) {
        echo '<span class="ok">✅ Import geslaagd! ' . $ok . ' statements uitgevoerd.</span>' . "\n";
        echo "\n<span class=\"info\">Verwijder nu psp-run-import-v2.php en lopende_planning_2026_import_v2.sql uit de repository.</span>";
    } else {
        echo '<span class="err">⚠ ' . $errors . ' fout(en) opgetreden. ' . $ok . ' statements OK.</span>' . "\n";
    }
    echo '</pre>';
}
?>

<hr>
<p class="info" style="font-size:.85rem">
  <strong>Na de import:</strong> verwijder <code>psp-run-import-v2.php</code> en <code>lopende_planning_2026_import_v2.sql</code>
  uit de repository en push opnieuw.
</p>

<?php endif; ?>
</body>
</html>
