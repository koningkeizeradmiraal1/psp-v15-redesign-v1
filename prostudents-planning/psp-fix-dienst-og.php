<?php
/**
 * PSP – Opdrachtgevernamen in ps_diensten herstellen.
 *
 * Stap 1 – overzicht:  https://psplanning.nl/psp-fix-dienst-og.php?sleutel=pspog2026
 * Stap 2 – uitvoeren:  https://psplanning.nl/psp-fix-dienst-og.php?sleutel=pspog2026&run=1
 *
 * Verwijder dit bestand NA gebruik.
 */

define('SLEUTEL', 'pspog2026');

if (($_GET['sleutel'] ?? '') !== SLEUTEL) {
    http_response_code(403);
    exit('Geen toegang.');
}

$wp_load = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load)) exit('wp-load.php niet gevonden op: ' . $wp_load);
require_once $wp_load;

global $wpdb;
$tabel_diensten = $wpdb->prefix . 'ps_diensten';
$tabel_og       = $wpdb->prefix . 'ps_opdrachtgevers';

// Correcte lijst (zelfde als reset script)
$correct = [
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

// Huidige unieke waarden in ps_diensten
$huidig = $wpdb->get_col(
    "SELECT DISTINCT opdrachtgever FROM `{$tabel_diensten}` WHERE opdrachtgever != '' ORDER BY opdrachtgever ASC"
);

// Bepaal welke al correct zijn en welke niet
$fout   = array_diff($huidig, $correct);
$goed   = array_intersect($huidig, $correct);

$run = isset($_GET['run']) && $_GET['run'] === '1';

?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>PSP – Dienst opdrachtgevers fixen</title>
<style>
  body  { font-family: sans-serif; max-width: 900px; margin: 40px auto; background: #f8f8f8; color: #222; }
  h1    { color: #d31775; }
  h2    { margin-top: 30px; }
  .ok   { color: #2e7d32; font-weight: bold; }
  .err  { color: #c62828; font-weight: bold; }
  .warn { color: #e65100; font-weight: bold; }
  table { width: 100%; border-collapse: collapse; background: #fff; }
  th,td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; font-size: .9rem; }
  th    { background: #f0f0f0; }
  select { width: 100%; padding: 4px; font-size: .9rem; }
  .btn  { display:inline-block; margin-top:20px; padding:10px 22px;
          background:#d31775; color:#fff; border-radius:6px;
          font-weight:bold; border:none; cursor:pointer; font-size:1rem; }
  .btn-sec { background: #555; }
  .note { background: #fff3cd; border: 1px solid #ffc107; padding: 10px 16px;
          border-radius: 6px; margin: 16px 0; }
</style>
</head>
<body>
<h1>PSP – Dienst opdrachtgevers fixen</h1>

<?php if ($run): ?>

<?php
// Lees mapping uit POST
$mapping = $_POST['mapping'] ?? [];
$totaal  = 0;
$fouten  = [];

foreach ($mapping as $oud => $nieuw) {
    $oud   = stripslashes($oud);
    $nieuw = stripslashes($nieuw);
    if ($nieuw === '' || $nieuw === '__skip__') continue;
    $res = $wpdb->update(
        $tabel_diensten,
        ['opdrachtgever' => $nieuw],
        ['opdrachtgever' => $oud],
        ['%s'], ['%s']
    );
    if ($res === false) {
        $fouten[] = esc_html($oud) . ' → ' . esc_html($nieuw) . ': ' . $wpdb->last_error;
    } else {
        $totaal += $res;
    }
}
?>

<?php if (empty($fouten)): ?>
  <p class="ok">✔ <?php echo $totaal; ?> dienst(en) bijgewerkt. Opdrachtgevernamen zijn gecorrigeerd.</p>
<?php else: ?>
  <p class="warn">Deels bijgewerkt (<?php echo $totaal; ?> rijen). Fouten:</p>
  <ul><?php foreach ($fouten as $f) echo '<li class="err">' . $f . '</li>'; ?></ul>
<?php endif; ?>

<p><a href="?sleutel=<?php echo SLEUTEL; ?>">← Terug naar overzicht</a></p>

<?php else: ?>

<p>Alle unieke opdrachtgevernamen in <code><?php echo esc_html($tabel_diensten); ?></code>:</p>

<div class="note">
  ✅ <strong><?php echo count($goed); ?></strong> namen zijn al correct &nbsp;|&nbsp;
  ⚠️ <strong><?php echo count($fout); ?></strong> namen komen niet voor in de officiële lijst
</div>

<?php if (empty($fout)): ?>
  <p class="ok">✔ Alle opdrachtgevernamen in de diensten zijn al correct. Niets te doen.</p>
<?php else: ?>

<form method="post" action="?sleutel=<?php echo SLEUTEL; ?>&run=1">
<table>
  <thead>
    <tr>
      <th>Huidige waarde in ps_diensten</th>
      <th># diensten</th>
      <th>Corrigeren naar →</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($fout as $naam):
      $aantal = (int) $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*) FROM `{$tabel_diensten}` WHERE opdrachtgever = %s", $naam
      ));
  ?>
    <tr>
      <td><?php echo esc_html($naam); ?></td>
      <td><?php echo $aantal; ?></td>
      <td>
        <select name="mapping[<?php echo esc_attr($naam); ?>]">
          <option value="__skip__">— niet wijzigen —</option>
          <?php foreach ($correct as $opt): ?>
            <option value="<?php echo esc_attr($opt); ?>"
              <?php
              // Selecteer de beste match op basis van overlap
              similar_text(strtolower($naam), strtolower($opt), $pct);
              if ($pct > 60) echo 'selected';
              ?>
            ><?php echo esc_html($opt); ?></option>
          <?php endforeach; ?>
        </select>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php if (!empty($goed)): ?>
<h2>Al correct (geen actie nodig)</h2>
<table>
  <thead><tr><th>Naam</th><th># diensten</th></tr></thead>
  <tbody>
  <?php foreach ($goed as $naam):
      $aantal = (int) $wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*) FROM `{$tabel_diensten}` WHERE opdrachtgever = %s", $naam
      ));
  ?>
    <tr><td><?php echo esc_html($naam); ?></td><td><?php echo $aantal; ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<br>
<button type="submit" class="btn">▶ Wijzigingen opslaan</button>
<a href="?sleutel=<?php echo SLEUTEL; ?>" class="btn btn-sec" style="text-decoration:none;margin-left:10px">↺ Opnieuw laden</a>
</form>

<?php endif; ?>
<?php endif; ?>

<p style="margin-top:40px;color:#888;font-size:.82rem">
  ⚠️ Verwijder dit bestand na gebruik: <code><?php echo esc_html(__FILE__); ?></code>
</p>
</body>
</html>
