<?php
/**
 * PSP Cleanup — verwijder @psplanning.nl nep-accounts
 * Gebruik: ?sleutel=pspcleanup2026          → preview
 *          ?sleutel=pspcleanup2026&run=1    → uitvoeren
 */
define('ABSPATH_CHECK', true);
$pad = dirname(__FILE__);
while (!file_exists($pad . '/wp-load.php') && $pad !== dirname($pad)) $pad = dirname($pad);
require_once $pad . '/wp-load.php';

if (!isset($_GET['sleutel']) || $_GET['sleutel'] !== 'pspcleanup2026') {
    http_response_code(403); exit('Geen toegang.');
}

$run = isset($_GET['run']) && $_GET['run'] === '1';

echo '<style>body{font-family:sans-serif;padding:20px;max-width:800px}
.ok{color:#15803d}.err{color:#dc2626}.info{color:#2563eb}pre{background:#f3f4f6;padding:12px;border-radius:6px}
</style>';
echo '<h2>PSP Cleanup — nep-accounts (@psplanning.nl)</h2>';

// Haal alle WP-users met @psplanning.nl email op
$nep_users = get_users(array(
    'search'         => '*@psplanning.nl',
    'search_columns' => array('user_email'),
    'number'         => -1,
    'fields'         => array('ID', 'user_login', 'user_email', 'display_name'),
));

echo '<p>Gevonden: <strong>' . count($nep_users) . '</strong> accounts met @psplanning.nl e-mailadres.</p>';

if (!$nep_users) {
    echo '<p class="ok">✅ Niets te doen.</p>';
    exit;
}

if (!$run) {
    echo '<h3>Preview (niet verwijderd):</h3><pre>';
    foreach ($nep_users as $u) {
        echo esc_html("#{$u->ID}  {$u->display_name}  <{$u->user_email}>") . "\n";
    }
    echo '</pre>';
    echo '<p><a href="?sleutel=pspcleanup2026&run=1" style="background:#dc2626;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none">⚠️ Verwijder alle ' . count($nep_users) . ' nep-accounts</a></p>';
    exit;
}

// Uitvoeren
require_once ABSPATH . 'wp-admin/includes/user.php';
$verwijderd = 0;
$fouten     = 0;
foreach ($nep_users as $u) {
    // Verwijder ook beschikbaarheid-rijen voor dit nepaccount
    // (de beschikbaarheid is al via de echte naam gekoppeld, niet via email)
    // We laten beschikbaarheid staan — alleen het WP-account weg
    $ok = wp_delete_user($u->ID);
    if ($ok) {
        echo '<span class="ok">✓ Verwijderd: ' . esc_html($u->display_name) . ' (' . esc_html($u->user_email) . ')</span><br>';
        $verwijderd++;
    } else {
        echo '<span class="err">✗ Mislukt: ' . esc_html($u->display_name) . '</span><br>';
        $fouten++;
    }
}
echo "<hr><p class='ok'><strong>Klaar. Verwijderd: {$verwijderd}, fouten: {$fouten}</strong></p>";
echo '<p class="info">Verwijder dit bestand na gebruik: <code>psp-cleanup-nep-accounts.php</code></p>';
