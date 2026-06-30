<?php
/**
 * PSP eenmalig import-script — Lopende Planning 2026
 * Zet dit bestand via git in de plugin-map, bezoek de URL, verwijder het daarna.
 *
 * URL: https://psplanning.nl/wp-content/plugins/prostudents-planning/psp-run-import.php
 *
 * BEVEILIG MET EEN WACHTWOORD — verander PSP_IMPORT_SLEUTEL hieronder.
 */

define('PSP_IMPORT_SLEUTEL', 'psp2026import');   // ← pas dit aan vóór je pusht

// ── Bootstrap WordPress ───────────────────────────────────────────────
$wp_load = dirname(__FILE__);
// Loop omhoog tot we wp-load.php vinden (maximaal 6 niveaus)
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
<title>PSP Import — Lopende Planning 2026</title>
<style>
  body  { font-family: -apple-system, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; color: #333; }
  h1    { color: #d31775; }
  pre   { background: #f4f4f4; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: .85rem; }
  .ok   { color: #27ae60; font-weight: bold; }
  .err  { color: #c0392b; font-weight: bold; }
  .info { color: #555; }
  form  { margin: 20px 0; }
  input[type=password] { padding: 8px 12px; font-size: 1rem; border: 1px solid #ccc; border-radius: 6px; margin-right: 8px; }
  button { background: #d31775; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; font-size: 1rem; cursor: pointer; }
</style>
</head>
<body>
<h1>PSP Import — Lopende Planning 2026</h1>

<?php if ($sleutel !== PSP_IMPORT_SLEUTEL) : ?>
<p class="info">Voer de importsleutel in om door te gaan.</p>
<form method="get">
  <input type="password" name="sleutel" placeholder="Importsleutel" autofocus>
  <?php if (isset($_GET['run'])) : ?><input type="hidden" name="run" value="1"><?php endif; ?>
  <button type="submit">Inloggen</button>
</form>

<?php else : ?>

<p class="info">Ingelogd. Klik op de knop om de import te starten.<br>
<strong>⚠ Verwijder dit bestand daarna uit de repository!</strong></p>

<?php
// Zoek het SQL-bestand
$sql_file = dirname(__FILE__) . '/lopende_planning_2026_import.sql';

if (!file_exists($sql_file)) {
    echo '<p class="err">❌ SQL-bestand niet gevonden: ' . esc_html($sql_file) . '</p>';
    echo '<p class="info">Zorg dat <code>lopende_planning_2026_import.sql</code> in dezelfde map staat als dit script.</p>';
} elseif (!isset($_GET['run'])) {
    $size = round(filesize($sql_file) / 1024);
    echo "<p class=\"info\">SQL-bestand gevonden ({$size} KB).</p>";
    echo '<form method="get"><input type="hidden" name="sleutel" value="' . esc_attr(PSP_IMPORT_SLEUTEL) . '">
          <input type="hidden" name="run" value="1">
          <button type="submit">▶ Import uitvoeren</button></form>';
} else {
    // ── Import uitvoeren ──────────────────────────────────────────────
    global $wpdb;
    $wpdb->show_errors();

    echo '<h2>Import bezig…</h2><pre>';
    flush();

    $sql_raw = file_get_contents($sql_file);
    if ($sql_raw === false) {
        echo '<span class="err">Kon SQL-bestand niet lezen.</span>';
        die();
    }

    // Splits op puntkomma's, maar negeer die binnen strings (simpele aanpak: split op ";\n")
    $statements = [];
    $buffer     = '';
    foreach (explode("\n", $sql_raw) as $line) {
        $trimmed = trim($line);
        if (substr($trimmed, 0, 2) === '--') continue;  // comment-regels overslaan
        if ($trimmed === '') continue;
        $buffer .= $line . "\n";
        if (substr(rtrim($line), -1) === ';') {
            $stmt = trim($buffer);
            if ($stmt && $stmt !== ';') $statements[] = $stmt;
            $buffer = '';
        }
    }
    if (trim($buffer)) $statements[] = trim($buffer);

    $ok = 0; $errors = 0; $skipped = 0;
    foreach ($statements as $stmt) {
        $upper = strtoupper(substr($stmt, 0, 10));
        // SET en SELECT-statements apart behandelen
        if (strpos($upper, 'SELECT') !== false && strpos(strtolower($stmt), 'insert') === false) {
            $result = $wpdb->get_results($stmt);
            if ($result) {
                foreach ($result as $row) {
                    $vals = (array)$row;
                    echo '<span class="ok">✓ ' . esc_html(implode(' | ', $vals)) . "</span>\n";
                }
            }
            $ok++;
            continue;
        }
        $result = $wpdb->query($stmt);
        if ($result === false) {
            echo '<span class="err">❌ FOUT: ' . esc_html($wpdb->last_error) . "</span>\n";
            echo '   SQL: ' . esc_html(substr($stmt, 0, 150)) . "…\n";
            $errors++;
        } else {
            $ok++;
            // Alleen DELETE en INSERT tonen, niet elke SET
            if (strpos($upper, 'DELETE') !== false || strpos($upper, 'INSERT') !== false) {
                echo '<span class="ok">✓ ' . esc_html(substr($stmt, 0, 60)) . '… (' . (int)$result . " rijen)</span>\n";
                flush();
            }
        }
    }

    echo "\n";
    if ($errors === 0) {
        echo '<span class="ok">✅ Import geslaagd! ' . $ok . ' statements uitgevoerd.</span>' . "\n";
        echo "\n<span class=\"info\">Verwijder nu psp-run-import.php en lopende_planning_2026_import.sql uit de repository.</span>";
    } else {
        echo '<span class="err">⚠ ' . $errors . ' fout(en) opgetreden. ' . $ok . ' statements OK.</span>' . "\n";
    }
    echo '</pre>';
}
?>

<hr>
<p class="info" style="font-size:.85rem">
  <strong>Na de import:</strong> verwijder <code>psp-run-import.php</code> en <code>lopende_planning_2026_import.sql</code>
  uit de repository en push opnieuw. Laat deze bestanden nooit permanent online staan.
</p>

<?php endif; ?>
</body>
</html>
