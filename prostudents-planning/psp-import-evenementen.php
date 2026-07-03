<?php
/**
 * PSP Evenementen import — eenmalig gebruiken, daarna verwijderen.
 * Preview : https://psplanning.nl/psp-import-evenementen.php?sleutel=pspev2026
 * Uitvoeren: https://psplanning.nl/psp-import-evenementen.php?sleutel=pspev2026&run=1
 */
if (($_GET['sleutel'] ?? '') !== 'pspev2026') { http_response_code(403); exit('Verboden.'); }

require_once dirname(dirname(dirname(__DIR__))) . '/wp-load.php';
global $wpdb;
$tbl = $wpdb->prefix . 'ps_evenementen';

// Tabel aanmaken als die nog niet bestaat
$wpdb->query("CREATE TABLE IF NOT EXISTS $tbl (
    id              bigint(20)   NOT NULL AUTO_INCREMENT,
    datum           date         NOT NULL,
    opdrachtgever   varchar(255) NOT NULL DEFAULT '',
    medewerker      varchar(255) NOT NULL DEFAULT '',
    dienst_info     text         NOT NULL,
    notities        text         DEFAULT '',
    aangemaakt_op   datetime     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY datum (datum),
    KEY opdrachtgever (opdrachtgever(100))
) " . $wpdb->get_charset_collate());

// Records: [datum, opdrachtgever, medewerker, dienst_info, notities]
$records = [
    ['2026-07-04','Beijk Catering','Nog in te vullen','Catering dienst',''],
    ['2026-07-05','Croptimal','Nog in te vullen','Veldwerk',''],
    ['2026-07-06','Cuisinerie Mensinge','Nog in te vullen','Bediening',''],
    ['2026-07-07','Gang van Zaken / TKP','Nog in te vullen','Congres ondersteuning',''],
    ['2026-07-08','Heusinkveld','Nog in te vullen','Evenement support',''],
    ['2026-07-09','WRK4 - Hullabaloo','Nog in te vullen','Festival crew',''],
    ['2026-07-11','Beijk Catering','Nog in te vullen','Catering dienst',''],
    ['2026-07-12','Croptimal','Nog in te vullen','Veldwerk',''],
    ['2026-07-13','Cuisinerie Mensinge','Nog in te vullen','Bediening',''],
    ['2026-07-14','Gang van Zaken / TKP','Nog in te vullen','Congres ondersteuning',''],
    ['2026-07-15','Heusinkveld','Nog in te vullen','Evenement support',''],
    ['2026-07-16','WRK4 - Hullabaloo','Nog in te vullen','Festival crew',''],
    ['2026-07-18','Beijk Catering','Nog in te vullen','Catering dienst',''],
    ['2026-07-19','Croptimal','Nog in te vullen','Veldwerk',''],
    ['2026-07-20','Cuisinerie Mensinge','Nog in te vullen','Bediening',''],
    ['2026-07-21','Gang van Zaken / TKP','Nog in te vullen','Congres ondersteuning',''],
    ['2026-07-22','Heusinkveld','Nog in te vullen','Evenement support',''],
    ['2026-07-23','WRK4 - Hullabaloo','Nog in te vullen','Festival crew',''],
    ['2026-07-25','Beijk Catering','Nog in te vullen','Catering dienst',''],
    ['2026-07-26','Croptimal','Nog in te vullen','Veldwerk',''],
    ['2026-07-27','Cuisinerie Mensinge','Nog in te vullen','Bediening',''],
    ['2026-07-28','Gang van Zaken / TKP','Nog in te vullen','Congres ondersteuning',''],
    ['2026-07-29','Heusinkveld','Nog in te vullen','Evenement support',''],
    ['2026-07-30','WRK4 - Hullabaloo','Nog in te vullen','Festival crew',''],
    ['2026-08-01','Beijk Catering','Nog in te vullen','Catering dienst',''],
    ['2026-08-02','Croptimal','Nog in te vullen','Veldwerk',''],
    ['2026-08-03','Cuisinerie Mensinge','Nog in te vullen','Bediening',''],
    ['2026-08-04','Gang van Zaken / TKP','Nog in te vullen','Congres ondersteuning',''],
    ['2026-08-05','Heusinkveld','Nog in te vullen','Evenement support',''],
    ['2026-08-06','WRK4 - Hullabaloo','Nog in te vullen','Festival crew',''],
];

if (($_GET['run'] ?? '') !== '1') {
    echo '<h2>Preview — ' . count($records) . ' evenementen</h2><table border="1" cellpadding="4">';
    echo '<tr><th>Datum</th><th>Opdrachtgever</th><th>Medewerker</th><th>Dienst</th></tr>';
    foreach ($records as $r) {
        echo '<tr><td>' . htmlspecialchars($r[0]) . '</td><td>' . htmlspecialchars($r[1]) . '</td>'
           . '<td>' . htmlspecialchars($r[2]) . '</td><td>' . htmlspecialchars($r[3]) . '</td></tr>';
    }
    echo '</table>';
    echo '<br><a href="?sleutel=pspev2026&run=1"><strong>&#9654; Importeer nu</strong></a>';
    exit;
}

// Verwijder toekomstige records en herlaad
$wpdb->query("DELETE FROM $tbl WHERE datum >= CURDATE()");
$ok = 0;
foreach ($records as $r) {
    $wpdb->insert($tbl, [
        'datum'         => $r[0],
        'opdrachtgever' => $r[1],
        'medewerker'    => $r[2],
        'dienst_info'   => $r[3],
        'notities'      => $r[4],
    ]);
    if ($wpdb->insert_id) $ok++;
}
echo "<p style='font-size:1.2rem'>&#10003; <strong>$ok evenementen geïmporteerd.</strong></p>";
echo "<p style='color:#c00'><strong>Verwijder dit script na gebruik!</strong></p>";
