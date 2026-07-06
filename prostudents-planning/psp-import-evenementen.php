<?php
/**
 * PSP Evenementen import — eenmalig gebruiken, daarna verwijderen.
 * Preview : https://psplanning.nl/wp-content/plugins/prostudents-planning/psp-import-evenementen.php?sleutel=pspev2026
 * Uitvoeren: https://psplanning.nl/wp-content/plugins/prostudents-planning/psp-import-evenementen.php?sleutel=pspev2026&run=1
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
    ['2026-07-03','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 13:00 uur Catering TKP',''],
    ['2026-07-03','Heusinkveld','Leon Smit','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-03','Heusinkveld','Oleksii Stolyar','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-06','Beijk Catering','Dorien Neijenhuis Nieuw','09:00 - ca. 14:15 uur M&G Assen',''],
    ['2026-07-06','Beijk Catering','Nog in te vullen','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-06','Beijk Catering','Nog in te vullen','09:00 - ca. 14:15 uur M&G Assen',''],
    ['2026-07-06','Beijk Catering','Noor Korts','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-06','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-06','Heusinkveld','Casper van Santen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-07','Beijk Catering','Casper van Santen','09:15 - ca. 14:00 uur M&G Assen',''],
    ['2026-07-07','Beijk Catering','Nog in te vullen','08:15 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-07','Beijk Catering','Nog in te vullen','09:15 - ca. 14:00 uur M&G Assen',''],
    ['2026-07-07','Beijk Catering','Roosmarijn van Strien','08:15 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-07','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-07','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-07','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-08','Beijk Catering','Nog in te vullen','08:15 - ca. 14:00 uur M&G Assen',''],
    ['2026-07-08','Beijk Catering','Nog in te vullen','09:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-08','Beijk Catering','Noor Korts','09:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-08','Beijk Catering','Roosmarijn van Strien','08:15 - ca. 14:00 uur M&G Assen',''],
    ['2026-07-08','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-09','Beijk Catering','Nog in te vullen','08:15 - ca. 13:45 uur M&G Assen',''],
    ['2026-07-09','Beijk Catering','Nog in te vullen','09:15 - ca. 13:45 uur M&G Assen',''],
    ['2026-07-09','Beijk Catering','Noor Korts','09:15 - ca. 13:45 uur M&G Assen',''],
    ['2026-07-09','Beijk Catering','Roosmarijn van Strien','08:15 - ca. 13:45 uur M&G Assen',''],
    ['2026-07-09','Gang van Zaken / TKP','Astrid Jager','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-09','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-09','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-09','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-10','Beijk Catering','Dorien Neijenhuis Nieuw','10:15 - ca. 14:30 uur M&G Assen',''],
    ['2026-07-10','Beijk Catering','Nog in te vullen','08:30 - ca. 13:45 uur M&G Assen',''],
    ['2026-07-10','Beijk Catering','Nog in te vullen','10:15 - ca. 14:30 uur M&G Assen',''],
    ['2026-07-10','Beijk Catering','Roosmarijn van Strien','08:30 - ca. 13:45 uur M&G Assen',''],
    ['2026-07-10','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 13:00 uur Catering TKP',''],
    ['2026-07-10','Heusinkveld','Leon Smit','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-13','Beijk Catering','Dorien Neijenhuis Nieuw','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-13','Beijk Catering','Nog in te vullen','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-13','Heusinkveld','Casper van Santen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-13','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-14','Beijk Catering','Nog in te vullen','08:45 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-14','Beijk Catering','Roosmarijn van Strien','08:45 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-14','Gang van Zaken / TKP','Astrid Jager','8:00-14:30 uur Catering TKP',''],
    ['2026-07-14','Gang van Zaken / TKP','Nog in te vullen','8:00-14:30 uur Catering TKP',''],
    ['2026-07-14','Heusinkveld','Casper van Santen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-14','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-15','Beijk Catering','Nog in te vullen','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-15','Beijk Catering','Roosmarijn van Strien','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-15','Heusinkveld','Casper van Santen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-15','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-16','Beijk Catering','Nog in te vullen','08:45 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-16','Beijk Catering','Roosmarijn van Strien','08:45 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-16','Heusinkveld','Casper van Santen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-16','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-17','Beijk Catering','Nog in te vullen','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-17','Beijk Catering','Roosmarijn van Strien','08:00 - ca. 13:30 uur M&G Assen',''],
    ['2026-07-17','Gang van Zaken / TKP','Cecile Hoving','9:00- ca 13:00 uur Catering TKP',''],
    ['2026-07-17','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 13:00 uur Catering TKP',''],
    ['2026-07-17','Heusinkveld','Casper van Santen','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-17','Heusinkveld','Leon Smit','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-20','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-20','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-21','Gang van Zaken / TKP','Cecile Hoving','09.00- ca 15.00 uur Catering TKP Cecile appen als zij 21\\07 kan werken',''],
    ['2026-07-21','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-21','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-21','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-22','Gang van Zaken / TKP','Cecile Hoving','8:00-14:30 uur Catering TKP\\ is komen te vervallen. OG GM ip dan 21 juli',''],
    ['2026-07-22','Gang van Zaken / TKP','Nog in te vullen','8:00-14:30 uur Catering TKP',''],
    ['2026-07-22','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-22','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-23','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-23','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-24','Cuisinerie Mensinge','Nog in te vullen','ovb tegen die tijd even contact',''],
    ['2026-07-24','Gang van Zaken / TKP','Cecile Hoving','9:00- ca 13:00 uur Catering TKP',''],
    ['2026-07-24','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 13:00 uur Catering TKP',''],
    ['2026-07-24','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-24','Heusinkveld','Leon Smit','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-24','Heusinkveld','Oleksii Stolyar','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-27','Gang van Zaken / TKP','Nog in te vullen','09.00- ca 15.00 uur Catering TKP',''],
    ['2026-07-27','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-27','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-27','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-28','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-28','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-29','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-29','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-30','Gang van Zaken / TKP','Astrid Jager','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-30','Gang van Zaken / TKP','Nog in te vullen','9:00- ca 15:00 uur Catering TKP',''],
    ['2026-07-30','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-30','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-07-31','Heusinkveld','Leon Smit','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-07-31','Heusinkveld','Oleksii Stolyar','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-03','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-03','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-04','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-04','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-05','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-05','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-06','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-06','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-07','Heusinkveld','Leon Smit','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-07','Heusinkveld','Oleksii Stolyar','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-10','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-10','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-11','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-11','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-12','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-12','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-13','Gang van Zaken / TKP','Nog in te vullen','8:00- ca 15:00 uur Catering TKP',''],
    ['2026-08-13','Heusinkveld','Leon Smit','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-13','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-14','Heusinkveld','Leon Smit','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-14','Heusinkveld','Oleksii Stolyar','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-17','Gang van Zaken / TKP','Nog in te vullen','8:00- ca 15:00 uur Catering TKP',''],
    ['2026-08-17','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-17','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-18','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-18','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-19','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-19','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-20','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-20','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-21','Heusinkveld','Nog in te vullen','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-21','Heusinkveld','Oleksii Stolyar','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-24','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-24','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-25','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-25','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-26','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-26','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-27','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-27','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-28','Heusinkveld','Nog in te vullen','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-28','Heusinkveld','Oleksii Stolyar','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-08-31','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-08-31','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-09-01','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-09-01','Heusinkveld','Oleksii Stolyar','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-09-02','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-09-03','Heusinkveld','Nog in te vullen','8:30 - 17:00 uur inpakmedewerker',''],
    ['2026-09-04','Heusinkveld','Nog in te vullen','8:30 - 15:00 uur inpakmedewerker',''],
    ['2026-09-06','Cuisinerie Mensinge','Nog in te vullen','ovb tegen die tijd even contact',''],
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
