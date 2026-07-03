<?php
/**
 * PSP Evenementen Import
 * Preview: /psp-import-evenementen.php?sleutel=pspev2026
 * Importeer: /psp-import-evenementen.php?sleutel=pspev2026&run=1
 * Verwijder dit bestand na gebruik!
 */

$sleutel = $_GET['sleutel'] ?? '';
if ($sleutel !== 'pspev2026') { http_response_code(403); die('Verboden.'); }

$wp_load = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load)) { die('wp-load.php niet gevonden op: ' . $wp_load); }
require_once $wp_load;

global $wpdb;
$tabel = $wpdb->prefix . 'ps_evenementen';

// Maak tabel aan als die niet bestaat
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
$charset = $wpdb->get_charset_collate();
$sql_create = "CREATE TABLE IF NOT EXISTS `$tabel` (
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
) $charset;";
dbDelta($sql_create);

$run = isset($_GET['run']) && $_GET['run'] === '1';

$data = [
  ['2026-07-03', 'Croptimal', 'Kevin Potgieter', '09.00-14.00'],
  ['2026-07-03', 'Gang van Zaken / TKP', 'Harnet  Beyene', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-03', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-03', 'Heusinkveld', 'Leon Smit', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-03', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-06', 'Beijk Catering', 'Dorien Neijenhuis Nieuw', '09:00 - ca. 14:15 uur M&G Assen'],
  ['2026-07-06', 'Beijk Catering', 'Noor Korts', '08:00 - ca. 13:30 uur M&G Assen'],
  ['2026-07-06', 'Croptimal', 'Anne Vis', '09.00. 13.00 uur'],
  ['2026-07-06', 'Croptimal', 'Kevin Potgieter', '09.00. 16.00 uur'],
  ['2026-07-06', 'Gang van Zaken / TKP', 'Harnet Beyene', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-06', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-06', 'Heusinkveld', 'Casper van Santen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-06', 'Heusinkveld', 'Leon Smit', 'xx/niet bsb'],
  ['2026-07-06', 'Heusinkveld', 'Oleksii Stolyar', 'xx/niet bsb'],
  ['2026-07-07', 'Beijk Catering', 'Casper van Santen', '09:15 - ca. 14:00 uur M&G Assen'],
  ['2026-07-07', 'Beijk Catering', 'Roosmarijn van Strien', '08:15 - ca. 13:30 uur M&G Assen'],
  ['2026-07-07', 'Croptimal', 'Kevin Potgieter', 'bsb 09.00. 16.00 uur'],
  ['2026-07-07', 'Croptimal', 'Olivier Huisers', '09.00 -15.00'],
  ['2026-07-07', 'Croptimal', 'Rochelle Mooij', '09.00 -15.00'],
  ['2026-07-07', 'Gang van Zaken / TKP', 'Harnet Beyene', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-07', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-07', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-07', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-08', 'Beijk Catering', 'Noor Korts', '09:00 - ca. 13:30 uur M&G Assen'],
  ['2026-07-08', 'Beijk Catering', 'Roosmarijn van Strien', '08:15 - ca. 14:00 uur M&G Assen'],
  ['2026-07-08', 'Croptimal', 'Rochelle Mooij', '09.00 -15.00'],
  ['2026-07-08', 'Croptimal', 'Yannick Fraanje', '12.00 tot -ca 17.00 uur'],
  ['2026-07-08', 'Heusinkveld', 'Casper van Santen', 'xx/niet bsb'],
  ['2026-07-08', 'Heusinkveld', 'Leon Smit', 'xx/niet bsb'],
  ['2026-07-08', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-09', 'Beijk Catering', 'Noor Korts', '09:15 - ca. 13:45 uur M&G Assen'],
  ['2026-07-09', 'Beijk Catering', 'Roosmarijn van Strien', '08:15 - ca. 13:45 uur M&G Assen'],
  ['2026-07-09', 'Croptimal', 'Kevin Potgieter', '09.00. 15.00 uur'],
  ['2026-07-09', 'Croptimal', 'Rochelle Mooij', '09.00 -15.00'],
  ['2026-07-09', 'Gang van Zaken / TKP', 'Astrid Jager', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-09', 'Gang van Zaken / TKP', 'Nog in te vullen', 'vakantie'],
  ['2026-07-09', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-09', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-09', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-10', 'Beijk Catering', 'Dorien Neijenhuis Nieuw', '10:15 - ca. 14:30 uur M&G Assen'],
  ['2026-07-10', 'Beijk Catering', 'Roosmarijn van Strien', '08:30 - ca. 13:45 uur M&G Assen'],
  ['2026-07-10', 'Croptimal', 'Nog in te vullen', '09.12 - 14.00?'],
  ['2026-07-10', 'Croptimal', 'Rochelle Mooij', '09.00 -15.00'],
  ['2026-07-10', 'Gang van Zaken / TKP', 'Nog in te vullen', 'vakantie'],
  ['2026-07-10', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-10', 'Gang van Zaken / TKP', 'ipv Cecile Hoving', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-10', 'Heusinkveld', 'Leon Smit', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-10', 'Heusinkveld', 'Oleksii Stolyar', 'xx/niet bsb'],
  ['2026-07-13', 'Beijk Catering', 'Dorien Neijenhuis Nieuw', '08:00 - ca. 13:30 uur M&G Assen'],
  ['2026-07-13', 'Croptimal', 'Nog in te vullen ?', '09.00 - 14.00?'],
  ['2026-07-13', 'Croptimal', 'Rochelle Mooij', '09.00 tot -ca 15.00'],
  ['2026-07-13', 'Heusinkveld', 'Casper van Santen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-13', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-13', 'Heusinkveld', 'Oleksii Stolyar', 'xx/niet bsb'],
  ['2026-07-14', 'Beijk Catering', 'Roosmarijn van Strien', '08:45 - ca. 13:30 uur M&G Assen'],
  ['2026-07-14', 'Croptimal', 'Kevin Potgieter', 'bsb 09.00-16.00'],
  ['2026-07-14', 'Croptimal', 'Rochelle Mooij', '09.00 tot -ca 15.00'],
  ['2026-07-14', 'Croptimal', 'Yannick Fraanje', '12.00-17.00'],
  ['2026-07-14', 'Gang van Zaken / TKP', 'Astrid Jager', '8:00-14:30 uur Catering TKP'],
  ['2026-07-14', 'Gang van Zaken / TKP', 'Nog in te vullen', '8:00-14:30 uur Catering TKP'],
  ['2026-07-14', 'Heusinkveld', 'Casper van Santen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-14', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-14', 'Heusinkveld', 'Oleksii Stolyar', 'xx/niet bsb'],
  ['2026-07-15', 'Beijk Catering', 'Roosmarijn van Strien', '08:00 - ca. 13:30 uur M&G Assen'],
  ['2026-07-15', 'Croptimal', 'Kevin Potgieter', '09.00-13.00'],
  ['2026-07-15', 'Croptimal', 'Rochelle Mooij', '09.00 tot -ca 15.00'],
  ['2026-07-15', 'Heusinkveld', 'Casper van Santen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-15', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-15', 'Heusinkveld', 'Oleksii Stolyar', 'xx/niet bsb'],
  ['2026-07-16', 'Beijk Catering', 'Roosmarijn van Strien', '08:45 - ca. 13:30 uur M&G Assen'],
  ['2026-07-16', 'Croptimal', 'Kevin Potgieter', '09.00-16.00'],
  ['2026-07-16', 'Croptimal', 'Rochelle Mooij', '09.00 tot -ca 15.00'],
  ['2026-07-16', 'Heusinkveld', 'Casper van Santen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-16', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-16', 'Heusinkveld', 'Oleksii Stolyar', 'xx/niet bsb'],
  ['2026-07-17', 'Beijk Catering', 'Roosmarijn van Strien', '08:00 - ca. 13:30 uur M&G Assen'],
  ['2026-07-17', 'Croptimal', 'Nog in te vullen ?', '09.00 - 14.00?'],
  ['2026-07-17', 'Croptimal', 'Rochelle Mooij', '09.00 tot -ca 15.00'],
  ['2026-07-17', 'Gang van Zaken / TKP', 'Cecile Hoving', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-17', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-17', 'Heusinkveld', 'Casper van Santen', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-17', 'Heusinkveld', 'Leon Smit', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-17', 'Heusinkveld', 'Oleksii Stolyar', 'xx/niet bsb'],
  ['2026-07-20', 'Croptimal', 'Laars van Maanen?', '09.00 - 14.00'],
  ['2026-07-20', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-20', 'Croptimal', 'Yannick Fraanje', '09.00 tot -14.00'],
  ['2026-07-20', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-20', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-21', 'Croptimal', 'Casper van Santen', '09.00 - 14.00  bsb'],
  ['2026-07-21', 'Croptimal', 'Kevin Potgieter', '09.00 - 16.00'],
  ['2026-07-21', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-21', 'Croptimal', 'Yannick Fraanje', '09.00 tot -14.00'],
  ['2026-07-21', 'Gang van Zaken / TKP', 'Cecile Hoving', '09.00- ca 15.00 uur Catering TKP Cecile appen als zij 21\07 kan werken'],
  ['2026-07-21', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-21', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-21', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-22', 'Croptimal', 'Casper van Santen', '09.00 -14.00'],
  ['2026-07-22', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-22', 'Croptimal', 'Yannick Fraanje', '09.00 tot -14.00'],
  ['2026-07-22', 'Gang van Zaken / TKP', 'Cecile Hoving', '8:00-14:30 uur Catering TKP\ is komen te vervallen. OG GM ip dan 21 juli'],
  ['2026-07-22', 'Gang van Zaken / TKP', 'Nog in te vullen', '8:00-14:30 uur Catering TKP'],
  ['2026-07-22', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-22', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-23', 'Croptimal', 'Kevin Potgieter', '09.00 - 16.00'],
  ['2026-07-23', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-23', 'Croptimal', 'Yannick Fraanje', '09.00 tot -14.00'],
  ['2026-07-23', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-23', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-24', 'Croptimal', 'Kevin Potgieter', '09.00 - 14.00'],
  ['2026-07-24', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-24', 'Croptimal', 'Yannick Fraanje', '09.00 tot -14.00'],
  ['2026-07-24', 'Cuisinerie Mensinge', 'Nog in te vullen', 'ovb tegen die tijd even contact'],
  ['2026-07-24', 'Gang van Zaken / TKP', 'Cecile Hoving', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-24', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 13:00 uur Catering TKP'],
  ['2026-07-24', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-24', 'Heusinkveld', 'Leon Smit', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-24', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-27', 'Croptimal', 'Kevin Potgieter', '09.00 -13.00'],
  ['2026-07-27', 'Croptimal', 'Laars van Maanen?', '09.00 - 16.00'],
  ['2026-07-27', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-27', 'Croptimal', 'Yannick Fraanje', 'vakantie'],
  ['2026-07-27', 'Gang van Zaken / TKP', 'Nog in te vullen', '09.00- ca 15.00 uur Catering TKP'],
  ['2026-07-27', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-27', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-27', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-28', 'Croptimal', 'Casper van Santen', '09.00 - 14.00'],
  ['2026-07-28', 'Croptimal', 'Kevin Potgieter', '09.00 - 16.00'],
  ['2026-07-28', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-28', 'Croptimal', 'Yannick Fraanje', 'vakantie'],
  ['2026-07-28', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-28', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-29', 'Croptimal', 'Casper van Santen', '09.00 - 14.00'],
  ['2026-07-29', 'Croptimal', 'Kevin Potgieter', '09.00 - 16.00?'],
  ['2026-07-29', 'Croptimal', 'Rochelle Mooij', 'xx/niet bsb'],
  ['2026-07-29', 'Croptimal', 'Yannick Fraanje', 'vakantie'],
  ['2026-07-29', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-29', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-30', 'Croptimal', 'Nog in te vullen ?', '09.00 - 16.00?'],
  ['2026-07-30', 'Croptimal', 'Rochelle Mooij', '09.00 tot -ca 13.00 uur'],
  ['2026-07-30', 'Croptimal', 'Yannick Fraanje', 'vakantie'],
  ['2026-07-30', 'Gang van Zaken / TKP', 'Astrid Jager', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-30', 'Gang van Zaken / TKP', 'Nog in te vullen', '9:00- ca 15:00 uur Catering TKP'],
  ['2026-07-30', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-30', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-07-31', 'Croptimal', 'Nog in te vullen ?', '09.00 - 16.00?'],
  ['2026-07-31', 'Croptimal', 'Rochelle Mooij', '09.00 tot -ca 13.00 uur'],
  ['2026-07-31', 'Croptimal', 'Yannick Fraanje', 'vakantie'],
  ['2026-07-31', 'Heusinkveld', 'Leon Smit', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-07-31', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-03', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-03', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-04', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-04', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-05', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-05', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-06', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-06', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-07', 'Heusinkveld', 'Leon Smit', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-07', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-10', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-10', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-11', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-11', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-12', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-12', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-13', 'Gang van Zaken / TKP', 'Harnet  Beyene', '8:00- ca 15:00 uur Catering TKP'],
  ['2026-08-13', 'Gang van Zaken / TKP', 'Nog in te vullen', '8:00- ca 15:00 uur Catering TKP'],
  ['2026-08-13', 'Heusinkveld', 'Leon Smit', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-13', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-14', 'Heusinkveld', 'Leon Smit', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-14', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-17', 'Gang van Zaken / TKP', 'Harnet  Beyene', '8:00- ca 15:00 uur Catering TKP'],
  ['2026-08-17', 'Gang van Zaken / TKP', 'Nog in te vullen', '8:00- ca 15:00 uur Catering TKP'],
  ['2026-08-17', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-17', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-18', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-18', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-19', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-19', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-20', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-20', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-21', 'Heusinkveld', 'Nog in te vullen', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-21', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-24', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-24', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-25', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-25', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-26', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-26', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-27', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-27', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-28', 'Heusinkveld', 'Nog in te vullen', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-28', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-08-31', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-08-31', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-09-01', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-09-01', 'Heusinkveld', 'Oleksii Stolyar', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-09-02', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-09-03', 'Heusinkveld', 'Nog in te vullen', '8:30 - 17:00 uur inpakmedewerker'],
  ['2026-09-04', 'Heusinkveld', 'Nog in te vullen', '8:30 - 15:00 uur inpakmedewerker'],
  ['2026-09-05', 'WRK4 - Hullabaloo', 'Nog in te vullen', 'tijden onbekend'],
  ['2026-09-06', 'Cuisinerie Mensinge', 'Nog in te vullen', 'ovb tegen die tijd even contact'],
  ['2026-09-06', 'WRK4 - Hullabaloo', 'Nog in te vullen', 'tijden onbekend']
];

$ingevoegd = 0; $overgeslagen = 0;

if ($run) {
    // Leeg de tabel eerst (alleen toekomstige data)
    $wpdb->query("DELETE FROM `$tabel` WHERE datum >= CURDATE()");
}

echo '<html><head><meta charset="utf-8"><style>
body{font-family:sans-serif;max-width:900px;margin:30px auto;padding:0 20px}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{background:#d31775;color:#fff;padding:8px 10px;text-align:left}
td{padding:6px 10px;border-bottom:1px solid #eee}
tr:nth-child(even)td{background:#fafafa}
.btn{display:inline-block;padding:10px 20px;background:#d31775;color:#fff;text-decoration:none;border-radius:6px;margin:10px 0}
h2{color:#d31775}
</style></head><body>';

echo '<h2>PSP Evenementen Import</h2>';
echo '<p><strong>' . count($data) . '</strong> toekomstige diensten gevonden (vanaf vandaag).</p>';

foreach ($data as $row) {
    [$datum, $og, $med, $info] = $row;
    if ($run) {
        $ok = $wpdb->insert($tabel, [
            'datum'         => $datum,
            'opdrachtgever' => $og,
            'medewerker'    => $med,
            'dienst_info'   => $info,
        ]);
        if ($ok) $ingevoegd++; else $overgeslagen++;
    }
}

if ($run) {
    echo '<div style="background:#dcfce7;padding:14px 18px;border-radius:8px;margin:16px 0">';
    echo '<strong>✓ Import klaar!</strong> ' . $ingevoegd . ' records ingevoegd, ' . $overgeslagen . ' mislukt.';
    echo '</div>';
} else {
    echo '<a class="btn" href="?sleutel=pspev2026&run=1">▶ Importeer nu ' . count($data) . ' records</a>';
}

echo '<table><tr><th>Datum</th><th>Opdrachtgever</th><th>Medewerker</th><th>Dienst info</th></tr>';
foreach ($data as $row) {
    [$datum,$og,$med,$info] = $row;
    echo '<tr><td>' . esc_html($datum) . '</td><td>' . esc_html($og) . '</td><td>' . esc_html($med) . '</td><td>' . esc_html($info) . '</td></tr>';
}
echo '</table></body></html>';
