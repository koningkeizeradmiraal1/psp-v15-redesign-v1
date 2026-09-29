<?php
/**
 * PSP eenmalig mail-script — alle bekende studenten informeren over PSPlanning:
 * inloggen/account aanmaken + beschikbaarheid doorgeven.
 *
 * Werkwijze:
 *  1. Zet dit bestand via git in de plugin-map en push/pull naar de server.
 *  2. Bezoek de URL met ?sleutel=... → toont eerst een PREVIEW (lijst ontvangers, geen mail verstuurd).
 *  3. Test eerst met ?test=jouw@email.nl → stuurt de mail ALLEEN naar dat adres.
 *  4. Pas zo nodig het tekstblok hieronder aan (subject/body).
 *  5. Voeg &run=1 toe om de mail echt naar iedereen te sturen.
 *  6. Verwijder dit bestand daarna weer uit de repository.
 *
 * URL: https://psplanning.nl/wp-content/plugins/prostudents-planning/psp-mail-studenten-v1.php?sleutel=pspmail2026
 */

define('PSP_MAIL_SLEUTEL', 'pspmail2026');

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
if ($sleutel !== PSP_MAIL_SLEUTEL) {
    http_response_code(403);
    exit('Geen toegang. Voeg ?sleutel=' . PSP_MAIL_SLEUTEL . ' toe aan de URL.');
}

global $wpdb;
$T_BESCH = $wpdb->prefix . 'ps_beschikbaarheid';

/* ─────────────────────────────────────────────────────────
   TEKST VAN DE MAIL — pas hier aan wat je wilt
───────────────────────────────────────────────────────── */
function psp_mail_studenten_inhoud( string $naam, bool $heeft_account, string $login = '' ): array {
    $login_url      = wp_login_url( home_url('/mijn-rooster/') );
    $aanmelden_url  = home_url('/aanmelden/');
    $beschikbaar_url= home_url('/beschikbaarheid/');
    $rooster_url    = home_url('/mijn-rooster/');

    $subject = 'Belangrijk: vanaf nu plannen we via PSPlanning';

    $body  = "Beste {$naam},\n\n";
    $body .= "ProStudents is overgestapt op een nieuw planningssysteem: PSPlanning.\n";
    $body .= "Beschikbaarheid doorgeven en je rooster bekijken doe je vanaf nu niet meer via app, WhatsApp of mail, maar via dit portaal.\n\n";

    if ( $heeft_account ) {
        $body .= "Je hebt al een inlog. Gebruik onderstaande gegevens:\n\n";
        $body .= "Gebruikersnaam: {$login}\n";
        $body .= "Inloggen:       {$login_url}\n\n";
    } else {
        $body .= "Je hebt nog geen inlog. Maak die eerst aan:\n\n";
        $body .= "Aanmelden: {$aanmelden_url}\n\n";
        $body .= "Zodra je aanmelding is goedgekeurd, ontvang je een aparte mail met je inloggegevens.\n\n";
    }

    $body .= "Wat we van je vragen:\n";
    $body .= "1. Log in (of maak een account aan als je die nog niet hebt).\n";
    $body .= "2. Geef wekelijks je beschikbaarheid door via: {$beschikbaar_url}\n";
    $body .= "   Doe dit bij voorkeur uiterlijk donderdag voor de week erna.\n";
    $body .= "3. Bekijk je ingeplande diensten via: {$rooster_url}\n\n";

    $body .= "Heb je vragen of lukt het inloggen niet? Neem contact op:\n";
    $body .= "📞 050 – 311 23 22\n";
    $body .= "📧 info@prostudents.nl\n\n";
    $body .= "Met vriendelijke groet,\nProStudents Groningen";

    return [$subject, $body];
}
/* ───────────────────────────────────────────────────────── */

// ── Ontvangers ophalen (zelfde definitie als "Beheer → Student accounts") ──
function psp_mail_studenten_ontvangers(): array {
    global $wpdb, $T_BESCH;
    $T_BESCH = $wpdb->prefix . 'ps_beschikbaarheid';
    $ontvangers = [];
    $gezien = [];

    $wp_studenten = get_users([
        'role'   => 'psp_student',
        'fields' => ['ID', 'display_name', 'user_email', 'user_login'],
    ]);
    foreach ($wp_studenten as $u) {
        $email = $u->user_email;
        if (substr($email, -14) === '@psplanning.nl') continue;
        $gezien[strtolower($email)] = true;
        $ontvangers[] = [
            'naam' => $u->display_name ?: $u->user_login,
            'email' => $email,
            'heeft_account' => true,
            'login' => $u->user_login,
        ];
    }

    $emails_besch = $wpdb->get_col(
        "SELECT DISTINCT email FROM `{$T_BESCH}` WHERE email NOT LIKE '%@psplanning.nl' ORDER BY email ASC"
    );
    foreach ($emails_besch as $email) {
        if (isset($gezien[strtolower($email)])) continue;
        $wp_user = get_user_by('email', $email);
        $naam_row = $wpdb->get_var($wpdb->prepare(
            "SELECT naam FROM `{$T_BESCH}` WHERE email = %s ORDER BY created_at DESC LIMIT 1", $email
        ));
        $ontvangers[] = [
            'naam' => $naam_row ?: $email,
            'email' => $email,
            'heeft_account' => (bool) $wp_user,
            'login' => $wp_user ? $wp_user->user_login : '',
        ];
    }

    return $ontvangers;
}

$ontvangers = psp_mail_studenten_ontvangers();
$test_email = isset($_GET['test']) ? sanitize_email($_GET['test']) : '';
$run        = isset($_GET['run']) && $_GET['run'] === '1';

header('Content-Type: text/html; charset=utf-8');
echo '<html><head><meta charset="utf-8"><title>PSP – studenten mailen</title>
<style>body{font-family:-apple-system,sans-serif;max-width:820px;margin:40px auto;padding:0 20px;color:#333}
h1{color:#d31775}table{width:100%;border-collapse:collapse;font-size:.85rem}
th,td{padding:6px 10px;border-bottom:1px solid #eee;text-align:left}
.btn{display:inline-block;background:#d31775;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;margin-top:14px}
pre{background:#f4f4f4;padding:14px;border-radius:8px;white-space:pre-wrap}
.warn{background:#fff3cd;border:1px solid #ffc107;padding:10px 16px;border-radius:6px;margin:16px 0}
.ok{color:#27ae60}.err{color:#c0392b}</style></head><body>';
echo '<h1>PSP – studenten mailen</h1>';
echo '<p>' . count($ontvangers) . ' bekende student(en) gevonden (op basis van dezelfde lijst als Beheer → Student accounts).</p>';

if ($test_email) {
    echo '<h2>Testmail</h2>';
    [$subject, $body] = psp_mail_studenten_inhoud('Test Student', true, 'test.student');
    echo '<p>Verstuurd naar: <strong>' . esc_html($test_email) . '</strong></p>';
    echo '<pre><strong>Onderwerp:</strong> ' . esc_html($subject) . "\n\n" . esc_html($body) . '</pre>';
    $ok = wp_mail($test_email, $subject, $body, ['Content-Type: text/plain; charset=UTF-8', 'From: ProStudents <info@prostudents.nl>']);
    echo $ok ? '<p class="ok">✅ Testmail verstuurd.</p>' : '<p class="err">❌ Versturen mislukt.</p>';
    echo '<p><a href="?sleutel=' . esc_attr(PSP_MAIL_SLEUTEL) . '">← Terug naar overzicht</a></p>';
    exit;
}

if (!$run) {
    echo '<h2>Voorbeeld van de mail</h2>';
    [$subject, $body] = psp_mail_studenten_inhoud('Voornaam Achternaam', true, 'voornaam.achternaam');
    echo '<pre><strong>Onderwerp:</strong> ' . esc_html($subject) . "\n\n" . esc_html($body) . '</pre>';

    echo '<div class="warn">Test dit eerst naar jezelf: voeg <code>&test=jouw@email.nl</code> toe aan de URL.</div>';

    echo '<h2>Ontvangers (' . count($ontvangers) . ')</h2>';
    echo '<table><thead><tr><th>Naam</th><th>E-mail</th><th>Heeft al account</th></tr></thead><tbody>';
    foreach ($ontvangers as $o) {
        echo '<tr><td>' . esc_html($o['naam']) . '</td><td>' . esc_html($o['email']) . '</td><td>' . ($o['heeft_account'] ? '✅ Ja' : '— Nee, moet aanmelden') . '</td></tr>';
    }
    echo '</tbody></table>';

    $url = '?sleutel=' . esc_attr(PSP_MAIL_SLEUTEL) . '&run=1';
    echo '<a class="btn" href="' . esc_attr($url) . '" onclick="return confirm(\'Mail nu echt versturen naar alle ' . count($ontvangers) . ' studenten?\')">▶ Verstuur naar alle ' . count($ontvangers) . ' studenten</a>';
    echo '</body></html>';
    exit;
}

// ── Echt versturen ──
echo '<h2>Versturen…</h2><pre>';
$verzonden = 0; $mislukt = 0;
foreach ($ontvangers as $o) {
    [$subject, $body] = psp_mail_studenten_inhoud($o['naam'], $o['heeft_account'], $o['login']);
    $ok = wp_mail($o['email'], $subject, $body, ['Content-Type: text/plain; charset=UTF-8', 'From: ProStudents <info@prostudents.nl>']);
    if ($ok) {
        echo "<span class=\"ok\">✓ " . esc_html($o['email']) . "</span>\n";
        $verzonden++;
    } else {
        echo "<span class=\"err\">❌ mislukt: " . esc_html($o['email']) . "</span>\n";
        $mislukt++;
    }
    if (ob_get_level()) { @flush(); }
}
echo "\n✅ {$verzonden} verzonden, {$mislukt} mislukt.\n";
echo "\nVerwijder nu psp-mail-studenten-v1.php uit de repository.\n";
echo '</pre></body></html>';
