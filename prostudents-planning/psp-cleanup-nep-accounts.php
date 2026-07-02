<?php
/**
 * PSP Cleanup — verwijder @psplanning.nl nep-accounts + beschikbaarheid-rijen
 * Gebruik: ?sleutel=pspcleanup2026          → preview
 *          ?sleutel=pspcleanup2026&run=1    → uitvoeren
 */
$pad = dirname(__FILE__);
while (!file_exists($pad . '/wp-load.php') && $pad !== dirname($pad)) $pad = dirname($pad);
require_once $pad . '/wp-load.php';

if (!isset($_GET['sleutel']) || $_GET['sleutel'] !== 'pspcleanup2026') {
    http_response_code(403); exit('Geen toegang.');
}

global $wpdb;
$run = isset($_GET['run']) && $_GET['run'] === '1';

$nep_users = get_users([
    'search'         => '*@psplanning.nl',
    'search_columns' => ['user_email'],
    'number'         => -1,
    'fields'         => ['ID', 'user_login', 'user_email', 'display_name'],
]);

$nep_besch = $wpdb->get_results(
    "SELECT id, naam, email FROM " . $wpdb->prefix . "ps_beschikbaarheid WHERE email LIKE '%@psplanning.nl'"
);

echo '<style>body{font-family:sans-serif;padding:20px;max-width:800px}
.ok{color:#15803d}.err{color:#dc2626}pre{background:#f3f4f6;padding:12px;border-radius:6px;font-size:.85rem}
h2{color:#d31775}</style>';
echo '<h2>PSP Cleanup — @psplanning.nl verwijderen</h2>';
echo '<p><strong>' . count($nep_users) . '</strong> nep WP-accounts &nbsp;|&nbsp; <strong>' . count($nep_besch) . '</strong> beschikbaarheid-rijen met @psplanning.nl</p>';

if (!$run) {
    if ($nep_users) {
        echo '<h3>WP accounts (worden verwijderd):</h3><pre>';
        foreach ($nep_users as $u) echo esc_html("#{$u->ID}  {$u->display_name}  <{$u->user_email}>") . "\n";
        echo '</pre>';
    }
    if ($nep_besch) {
        echo '<h3>Beschikbaarheid-rijen (worden verwijderd):</h3><pre>';
        foreach ($nep_besch as $b) echo esc_html("#{$b->id}  {$b->naam}  <{$b->email}>") . "\n";
        echo '</pre>';
    }
    if (!$nep_users && !$nep_besch) {
        echo '<p class="ok">✅ Niets te verwijderen.</p>'; exit;
    }
    echo '<p><a href="?sleutel=pspcleanup2026&run=1" style="background:#dc2626;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;margin-top:12px">⚠️ Nu alles verwijderen</a></p>';
    exit;
}

// Uitvoeren
require_once ABSPATH . 'wp-admin/includes/user.php';
$ok_users = 0; $ok_besch = 0;

foreach ($nep_users as $u) {
    if (wp_delete_user($u->ID)) {
        echo '<span class="ok">✓ Account verwijderd: ' . esc_html($u->display_name) . ' (' . esc_html($u->user_email) . ')</span><br>';
        $ok_users++;
    } else {
        echo '<span class="err">✗ Account mislukt: ' . esc_html($u->display_name) . '</span><br>';
    }
}

if ($nep_besch) {
    $ids = implode(',', array_map(fn($b) => (int)$b->id, $nep_besch));
    $del = $wpdb->query("DELETE FROM " . $wpdb->prefix . "ps_beschikbaarheid WHERE id IN ({$ids})");
    $ok_besch = (int)$del;
    echo '<span class="ok">✓ ' . $ok_besch . ' beschikbaarheid-rijen verwijderd.</span><br>';
}

echo "<hr><p class='ok'><strong>Klaar. WP-accounts: {$ok_users}, beschikbaarheid-rijen: {$ok_besch}</strong></p>";
