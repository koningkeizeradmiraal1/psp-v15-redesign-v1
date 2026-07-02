<?php
/**
 * PSP – Studenten importeren vanuit Excel (Poule)
 *
 * Preview:   https://psplanning.nl/psp-import-studenten.php?sleutel=pspimport2026
 * Uitvoeren: https://psplanning.nl/psp-import-studenten.php?sleutel=pspimport2026&run=1
 *
 * - Maakt alleen NIEUWE accounts aan (bestaande emailadressen worden overgeslagen)
 * - Stuurt GEEN emails
 * - Geeft de rol psp_student
 * - Slaat telefoonnummer op in user_meta (psp_telefoon)
 * - Verwijder dit bestand na gebruik
 */

define('SLEUTEL', 'pspimport2026');

if (($_GET['sleutel'] ?? '') !== SLEUTEL) {
    http_response_code(403); exit('Geen toegang.');
}

$wp_load = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load)) exit('wp-load.php niet gevonden op: ' . $wp_load);
require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/user.php';

$run = isset($_GET['run']) && $_GET['run'] === '1';

// ─── STUDENTENLIJST ────────────────────────────────────────────────────────
$studenten = [
    [ 'email' => 'adaab3033@gmail.com', 'display_name' => 'Ada Bordean', 'first_name' => 'Ada', 'last_name' => 'Bordean', 'telefoon' => '+40 773963721' ],
    [ 'email' => 'adisirbu2006@gmail.com', 'display_name' => 'Adrian Sirbu', 'first_name' => 'Adrian', 'last_name' => 'Sirbu', 'telefoon' => '06-26284376' ],
    [ 'email' => 'formanickisadrians@gmail.com', 'display_name' => 'Adrians Formanickis', 'first_name' => 'Adrians', 'last_name' => 'Formanickis', 'telefoon' => '06-19276890' ],
    [ 'email' => 'alexpousplotnikov@gmail.com', 'display_name' => 'Alex Plotnikov', 'first_name' => 'Alex', 'last_name' => 'Plotnikov', 'telefoon' => '+34 657358297' ],
    [ 'email' => 'ikbenaliciayeah@outlook.com', 'display_name' => 'Alicia Kruidhof Cervantes', 'first_name' => 'Alicia', 'last_name' => 'Kruidhof Cervantes', 'telefoon' => '06-33306364' ],
    [ 'email' => 'mallu_leitte@outlook.com', 'display_name' => 'Amanda Leite', 'first_name' => 'Amanda', 'last_name' => 'Leite', 'telefoon' => '06-40260780' ],
    [ 'email' => 'amber.burbach@icloud.com', 'display_name' => 'Amber Burbach', 'first_name' => 'Amber', 'last_name' => 'Burbach', 'telefoon' => '06-30199659' ],
    [ 'email' => 'annexvis@gmail.com', 'display_name' => 'Anne Vis', 'first_name' => 'Anne', 'last_name' => 'Vis', 'telefoon' => '06-57740837' ],
    [ 'email' => 'asvanherwijnen@live.nl', 'display_name' => 'Anne-Sophie Herwijnen van', 'first_name' => 'Anne-Sophie', 'last_name' => 'Herwijnen van', 'telefoon' => '06-23874338' ],
    [ 'email' => 'anneliesophiebos@gmail.com', 'display_name' => 'Annelie Bos', 'first_name' => 'Annelie', 'last_name' => 'Bos', 'telefoon' => '06-83859369' ],
    [ 'email' => 'anniek@bultje.nl', 'display_name' => 'Anniek Bultjes', 'first_name' => 'Anniek', 'last_name' => 'Bultjes', 'telefoon' => '06-80136540' ],
    [ 'email' => 'astridj.jager@gmail.com', 'display_name' => 'Astrid Jager', 'first_name' => 'Astrid', 'last_name' => 'Jager', 'telefoon' => '06-37297618' ],
    [ 'email' => 'axel.warbroek@gmail.com', 'display_name' => 'Axel Warbroek', 'first_name' => 'Axel', 'last_name' => 'Warbroek', 'telefoon' => '06-13334168' ],
    [ 'email' => 'bastervoort0@gmail.com', 'display_name' => 'Bas Tervoort', 'first_name' => 'Bas', 'last_name' => 'Tervoort', 'telefoon' => '06-11805615' ],
    [ 'email' => 'brecht.dikkerboom@gmail.com', 'display_name' => 'Brecht Dikkerboom', 'first_name' => 'Brecht', 'last_name' => 'Dikkerboom', 'telefoon' => '06-51372765' ],
    [ 'email' => 'caitlinluntz@gmail.com', 'display_name' => 'Caitlin Luntz', 'first_name' => 'Caitlin', 'last_name' => 'Luntz', 'telefoon' => '06-12276067' ],
    [ 'email' => 'casper.kamstra@icloud.com', 'display_name' => 'Casper Kamstra', 'first_name' => 'Casper', 'last_name' => 'Kamstra', 'telefoon' => '06-23612534' ],
    [ 'email' => 'caspervansanten@gmail.com', 'display_name' => 'Casper Santen van', 'first_name' => 'Casper', 'last_name' => 'Santen van', 'telefoon' => '06-38446302' ],
    [ 'email' => 'cayavandermei@icloud.com', 'display_name' => 'Caya Mei van der', 'first_name' => 'Caya', 'last_name' => 'Mei van der', 'telefoon' => '06 – 15524234' ],
    [ 'email' => 'cecile.hoving@gmail.com', 'display_name' => 'Cecile Hoving', 'first_name' => 'Cecile', 'last_name' => 'Hoving', 'telefoon' => '06-40775585' ],
    [ 'email' => 'chrisvdmeulen04@gmail.com', 'display_name' => 'Chris Meulen van der', 'first_name' => 'Chris', 'last_name' => 'Meulen van der', 'telefoon' => '06-44641380' ],
    [ 'email' => 'pdcorzaan@gmail.com', 'display_name' => 'Daan Corzaan', 'first_name' => 'Daan', 'last_name' => 'Corzaan', 'telefoon' => '06-39679514' ],
    [ 'email' => 'daantjeriddersma@hotmail.com', 'display_name' => 'Daantje Riddersma', 'first_name' => 'Daantje', 'last_name' => 'Riddersma', 'telefoon' => '06-15292848' ],
    [ 'email' => 'dagmar.pluim@gmail.com', 'display_name' => 'Dagmar Pluim', 'first_name' => 'Dagmar', 'last_name' => 'Pluim', 'telefoon' => '06-83309323' ],
    [ 'email' => 'drtipping2007@gmail.com', 'display_name' => 'Daniel Tipping', 'first_name' => 'Daniel', 'last_name' => 'Tipping', 'telefoon' => '06-28162869' ],
    [ 'email' => 'davidtuinroos@icloud.com', 'display_name' => 'David Roos', 'first_name' => 'David', 'last_name' => 'Roos', 'telefoon' => '06-46614099' ],
    [ 'email' => 'david_schaub@web.de', 'display_name' => 'David Schaub', 'first_name' => 'David', 'last_name' => 'Schaub', 'telefoon' => '06-45476121' ],
    [ 'email' => 'dendriel@icloud.com', 'display_name' => 'Dennis van Driel', 'first_name' => 'Dennis van', 'last_name' => 'Driel', 'telefoon' => '06-3924005' ],
    [ 'email' => 'dianakorendijk@proton.me', 'display_name' => 'Diana Korendijk', 'first_name' => 'Diana', 'last_name' => 'Korendijk', 'telefoon' => '06-83152585' ],
    [ 'email' => 'dibareints@gmail.com', 'display_name' => 'Diba Reints', 'first_name' => 'Diba', 'last_name' => 'Reints', 'telefoon' => '06-26455115' ],
    [ 'email' => 'dominique24.vandam@ziggo.nl', 'display_name' => 'Dominique Dam de', 'first_name' => 'Dominique', 'last_name' => 'Dam de', 'telefoon' => '06-36433863' ],
    [ 'email' => 'dnaftchi@gmail.com', 'display_name' => 'Donya Naftchi', 'first_name' => 'Donya', 'last_name' => 'Naftchi', 'telefoon' => '06-68223888' ],
    [ 'email' => 'eggebuijink@gmail.com', 'display_name' => 'Egge Buijink', 'first_name' => 'Egge', 'last_name' => 'Buijink', 'telefoon' => '06-82005142' ],
    [ 'email' => 'emily.r.bernard@icloud.com', 'display_name' => 'Emily Bernard', 'first_name' => 'Emily', 'last_name' => 'Bernard', 'telefoon' => '06-21961267' ],
    [ 'email' => 'eva.fee.mesu@gmail.com', 'display_name' => 'Eva Mesu', 'first_name' => 'Eva', 'last_name' => 'Mesu', 'telefoon' => '06-23985676' ],
    [ 'email' => 'e.vita26@hotmail.com', 'display_name' => 'Ezekiel Vita', 'first_name' => 'Ezekiel', 'last_name' => 'Vita', 'telefoon' => '06-31981014' ],
    [ 'email' => 'fatimahnoureddine06@outlook.com', 'display_name' => 'Fatimah Noureddine', 'first_name' => 'Fatimah', 'last_name' => 'Noureddine', 'telefoon' => '06-43789157' ],
    [ 'email' => 'febe.hellinga@gmail.com', 'display_name' => 'Febe Hellinga', 'first_name' => 'Febe', 'last_name' => 'Hellinga', 'telefoon' => '06-25538212' ],
    [ 'email' => 'f.c.wijnen@gmail.com', 'display_name' => 'Femke Wijnen', 'first_name' => 'Femke', 'last_name' => 'Wijnen', 'telefoon' => '06-29899173' ],
    [ 'email' => 'fenne.poel@yahoo.com', 'display_name' => 'Fenne Poel', 'first_name' => 'Fenne', 'last_name' => 'Poel', 'telefoon' => '06-23945585' ],
    [ 'email' => 'finnbrug12@gmail.com', 'display_name' => 'Finn Brug van der', 'first_name' => 'Finn', 'last_name' => 'Brug van der', 'telefoon' => '06-39532150' ],
    [ 'email' => 'stormartworks.design@gmail.com', 'display_name' => 'Finn Feringa', 'first_name' => 'Finn', 'last_name' => 'Feringa', 'telefoon' => '06-45644295' ],
    [ 'email' => 'floorlvandijk@gmail.com', 'display_name' => 'Floor Dijk van', 'first_name' => 'Floor', 'last_name' => 'Dijk van', 'telefoon' => '06-18713759' ],
    [ 'email' => 'frederiquedenhof@outlook.com', 'display_name' => 'Fréderique Oldenhof', 'first_name' => 'Fréderique', 'last_name' => 'Oldenhof', 'telefoon' => '06-15204927' ],
    [ 'email' => 'glysdi25_flores@yahoo.com', 'display_name' => 'Glydsi Flores', 'first_name' => 'Glydsi', 'last_name' => 'Flores', 'telefoon' => '06-43157729' ],
    [ 'email' => 'gusvanvegchel@gmail.com', 'display_name' => 'Gus Vegchel Van', 'first_name' => 'Gus', 'last_name' => 'Vegchel Van', 'telefoon' => '06-23096067' ],
    [ 'email' => 'hannealgra@gmail.com', 'display_name' => 'Hanne Algra', 'first_name' => 'Hanne', 'last_name' => 'Algra', 'telefoon' => '06 30616332' ],
    [ 'email' => 'harnetbeyene21@outlook.com', 'display_name' => 'Harnet Beyene', 'first_name' => 'Harnet', 'last_name' => 'Beyene', 'telefoon' => 'Bellen: 0686232525 / WhatsApp:06-48131979' ],
    [ 'email' => 'hidde@willebrandts.nl', 'display_name' => 'Hidde Willebrandts', 'first_name' => 'Hidde', 'last_name' => 'Willebrandts', 'telefoon' => '06- 81871548' ],
    [ 'email' => 'hugo.beckers06@gmail.com', 'display_name' => 'Hugo Beckers', 'first_name' => 'Hugo', 'last_name' => 'Beckers', 'telefoon' => '06-49936676' ],
    [ 'email' => 'ilse.hoekstra07@gmail.com', 'display_name' => 'Ilse Hoekstra', 'first_name' => 'Ilse', 'last_name' => 'Hoekstra', 'telefoon' => '06-57522339' ],
    [ 'email' => 'ils1312@hotmail.nl', 'display_name' => 'Ilse Wal van der', 'first_name' => 'Ilse', 'last_name' => 'Wal van der', 'telefoon' => '06-40636890' ],
    [ 'email' => 'indiracarlabruni@gmail.com', 'display_name' => 'Indira Bruni', 'first_name' => 'Indira', 'last_name' => 'Bruni', 'telefoon' => '0039-3703213095' ],
    [ 'email' => 'irisdehaan2007@gmail.com', 'display_name' => 'Iris Haan de', 'first_name' => 'Iris', 'last_name' => 'Haan de', 'telefoon' => '06-10942107' ],
    [ 'email' => 'jacemollema09@gmail.com', 'display_name' => 'Jace Mollenaar', 'first_name' => 'Jace', 'last_name' => 'Mollenaar', 'telefoon' => '06-29428067' ],
    [ 'email' => 'jjrkruithof@gmail.com', 'display_name' => 'Jan Kruithof', 'first_name' => 'Jan', 'last_name' => 'Kruithof', 'telefoon' => '06-40488611' ],
    [ 'email' => 'jasmijn.vannieuwkoop@gmail.com', 'display_name' => 'Jasmijn Nieuwkoop van', 'first_name' => 'Jasmijn', 'last_name' => 'Nieuwkoop van', 'telefoon' => '06-83705094' ],
    [ 'email' => 'jasmijnwagenaar@gmail.com', 'display_name' => 'Jasmijn Wagenaar', 'first_name' => 'Jasmijn', 'last_name' => 'Wagenaar', 'telefoon' => '06-14796032' ],
    [ 'email' => 'jeltefolkertsma06@gmail.com', 'display_name' => 'Jelte Folkertsma', 'first_name' => 'Jelte', 'last_name' => 'Folkertsma', 'telefoon' => '06-15203678' ],
    [ 'email' => 'uhljardzsenifer@gmail.com', 'display_name' => 'Jennifer Uhljar', 'first_name' => 'Jennifer', 'last_name' => 'Uhljar', 'telefoon' => '36705796243' ],
    [ 'email' => 'jenteschirm@gmail.com', 'display_name' => 'Jente Schirm', 'first_name' => 'Jente', 'last_name' => 'Schirm', 'telefoon' => '06-57374009' ],
    [ 'email' => 'jes.rob11@gmail.com', 'display_name' => 'Jessica Robaard', 'first_name' => 'Jessica', 'last_name' => 'Robaard', 'telefoon' => '06-11492771' ],
    [ 'email' => 'jitsdej7@gmail.com', 'display_name' => 'Jits Jong de', 'first_name' => 'Jits', 'last_name' => 'Jong de', 'telefoon' => '06-20956813' ],
    [ 'email' => 'johannes.lagerweij@live.nl', 'display_name' => 'Johannes Lagerweij', 'first_name' => 'Johannes', 'last_name' => 'Lagerweij', 'telefoon' => '06-14308856' ],
    [ 'email' => 'jonasknolbruins@gmail.com', 'display_name' => 'Jonas Knol-Bruins', 'first_name' => 'Jonas', 'last_name' => 'Knol-Bruins', 'telefoon' => '06-30830250' ],
    [ 'email' => 'bakkerjorick07@gmail.com', 'display_name' => 'Jorick Bakker', 'first_name' => 'Jorick', 'last_name' => 'Bakker', 'telefoon' => '06-18609114' ],
    [ 'email' => 'jordriel@icloud.com', 'display_name' => 'Joris van Driel', 'first_name' => 'Joris van', 'last_name' => 'Driel', 'telefoon' => '06-30507757' ],
    [ 'email' => 'jldejonge7@gmail.com', 'display_name' => 'Judith Jonge de', 'first_name' => 'Judith', 'last_name' => 'Jonge de', 'telefoon' => '06-10307570' ],
    [ 'email' => 'hoevenaarjulia@gmail.com', 'display_name' => 'Julia Hoevenaar', 'first_name' => 'Julia', 'last_name' => 'Hoevenaar', 'telefoon' => '06-83897804' ],
    [ 'email' => 'julian.moek@gmail.com', 'display_name' => 'Julian Moek', 'first_name' => 'Julian', 'last_name' => 'Moek', 'telefoon' => '06-41312772' ],
    [ 'email' => 'julianoverhorst@outlook.com', 'display_name' => 'Julian Overhorst', 'first_name' => 'Julian', 'last_name' => 'Overhorst', 'telefoon' => '06-10413611' ],
    [ 'email' => 'kdbakker07@gmail.com', 'display_name' => 'Karsten Bakker', 'first_name' => 'Karsten', 'last_name' => 'Bakker', 'telefoon' => '06-13984175' ],
    [ 'email' => 'karstenbosch@hotmail.com', 'display_name' => 'Karsten Bosch', 'first_name' => 'Karsten', 'last_name' => 'Bosch', 'telefoon' => '06-36284460' ],
    [ 'email' => 'katie.devries2008@gmail.com', 'display_name' => 'Katie Vries de', 'first_name' => 'Katie', 'last_name' => 'Vries de', 'telefoon' => '06-31380245' ],
    [ 'email' => 'kimvandenbelt4@icloud.com', 'display_name' => 'Kim van den Belt', 'first_name' => 'Kim van den', 'last_name' => 'Belt', 'telefoon' => '06-24671080' ],
    [ 'email' => 'kirstenkuperus@gmail.com', 'display_name' => 'Kirsten Kuperus', 'first_name' => 'Kirsten', 'last_name' => 'Kuperus', 'telefoon' => '06-14119070' ],
    [ 'email' => 'kirsten-olinga@hotmail.com', 'display_name' => 'Kirsten Olinga', 'first_name' => 'Kirsten', 'last_name' => 'Olinga', 'telefoon' => '06-12959372' ],
    [ 'email' => 'kirstenvliem@gmail.com', 'display_name' => 'Kirsten Vliem', 'first_name' => 'Kirsten', 'last_name' => 'Vliem', 'telefoon' => '06-22339318' ],
    [ 'email' => 'laradeg05@gmail.com', 'display_name' => 'Lara Goede de', 'first_name' => 'Lara', 'last_name' => 'Goede de', 'telefoon' => '06-11839088' ],
    [ 'email' => 'larsvanmaanen@icloud.com', 'display_name' => 'Lars Maanen van', 'first_name' => 'Lars', 'last_name' => 'Maanen van', 'telefoon' => '06-48151509' ],
    [ 'email' => 'lassevanbokhoven@gmail.com', 'display_name' => 'Lasse van Bokhoven', 'first_name' => 'Lasse van', 'last_name' => 'Bokhoven', 'telefoon' => '06-22322103' ],
    [ 'email' => 'laureveerman@icloud.com', 'display_name' => 'Laure Veerman', 'first_name' => 'Laure', 'last_name' => 'Veerman', 'telefoon' => '06-11151783' ],
    [ 'email' => 'leavanwijk@gmail.com', 'display_name' => 'Lea van Wijk', 'first_name' => 'Lea van', 'last_name' => 'Wijk', 'telefoon' => '06-16599762' ],
    [ 'email' => 'leonsmit.ls@gmail.com', 'display_name' => 'Leon Smit', 'first_name' => 'Leon', 'last_name' => 'Smit', 'telefoon' => '06-81194077' ],
    [ 'email' => 'timmerlevi@hotmail.com', 'display_name' => 'Levi Timmer', 'first_name' => 'Levi', 'last_name' => 'Timmer', 'telefoon' => '06-12055521' ],
    [ 'email' => 'lilianarnoldus@gmail.com', 'display_name' => 'Lilian Arnoldus', 'first_name' => 'Lilian', 'last_name' => 'Arnoldus', 'telefoon' => '06-37408873' ],
    [ 'email' => 'lotte.staring@icloud.com', 'display_name' => 'Lotte Staring', 'first_name' => 'Lotte', 'last_name' => 'Staring', 'telefoon' => '06-48068794' ],
    [ 'email' => 'lotte.dewagt@gmail.com', 'display_name' => 'Lotte de Wagt', 'first_name' => 'Lotte de', 'last_name' => 'Wagt', 'telefoon' => '06-44224015' ],
    [ 'email' => 'l.hoeffnagel@gmail.com', 'display_name' => 'Lucia Hoeffnagel', 'first_name' => 'Lucia', 'last_name' => 'Hoeffnagel', 'telefoon' => '06-23397948' ],
    [ 'email' => 'luuk.van.spil@kpnmail.nl', 'display_name' => 'Luuk van Spil', 'first_name' => 'Luuk van', 'last_name' => 'Spil', 'telefoon' => '06-30931633' ],
    [ 'email' => 'luzperezbatista55@gmail.com', 'display_name' => 'Luz Perez Batista', 'first_name' => 'Luz Perez', 'last_name' => 'Batista', 'telefoon' => '06-10182687' ],
    [ 'email' => 'lysannestoop@gmail.com', 'display_name' => 'Lysanne Stoop', 'first_name' => 'Lysanne', 'last_name' => 'Stoop', 'telefoon' => '06-57783254' ],
    [ 'email' => 'lbdspa@gmail.com', 'display_name' => 'Léon Spa', 'first_name' => 'Léon', 'last_name' => 'Spa', 'telefoon' => '06-12657099' ],
    [ 'email' => 'maartensimonroos@icloud.com', 'display_name' => 'Maarten Roos', 'first_name' => 'Maarten', 'last_name' => 'Roos', 'telefoon' => '06-49168026' ],
    [ 'email' => 'mahmoudkk55m@gmail.com', 'display_name' => 'Mahmoud Hassan', 'first_name' => 'Mahmoud', 'last_name' => 'Hassan', 'telefoon' => '06-39539280' ],
    [ 'email' => 'mahniiik@gmail.com', 'display_name' => 'Mahour Nikpanjeh', 'first_name' => 'Mahour', 'last_name' => 'Nikpanjeh', 'telefoon' => '06-633842961' ],
    [ 'email' => 'manderhuiskes@gmail.com', 'display_name' => 'Mander Huiskes', 'first_name' => 'Mander', 'last_name' => 'Huiskes', 'telefoon' => '06-46305819' ],
    [ 'email' => 'manon.schuurs2003@gmail.com', 'display_name' => 'Manon Schuurs', 'first_name' => 'Manon', 'last_name' => 'Schuurs', 'telefoon' => '06-18169180' ],
    [ 'email' => 'marcovandijk989@gmail.com', 'display_name' => 'Marco Dijk van', 'first_name' => 'Marco', 'last_name' => 'Dijk van', 'telefoon' => '06-47668363' ],
    [ 'email' => 'mjpputters@gmail.com', 'display_name' => 'Maria Putters', 'first_name' => 'Maria', 'last_name' => 'Putters', 'telefoon' => '06-57198671' ],
    [ 'email' => 'mariawww2006@gmail.com', 'display_name' => 'Maria Wallicka', 'first_name' => 'Maria', 'last_name' => 'Wallicka', 'telefoon' => '+48 509 070 705' ],
    [ 'email' => 'mariame.bofaya@yahoo.com', 'display_name' => 'Mariame Bofaya', 'first_name' => 'Mariame', 'last_name' => 'Bofaya', 'telefoon' => '06-19849798' ],
    [ 'email' => 'mfumarola89@gmail.com', 'display_name' => 'Mariantonietta Fumarola', 'first_name' => 'Mariantonietta', 'last_name' => 'Fumarola', 'telefoon' => '00-324883558' ],
    [ 'email' => 'markmous@icloud.com', 'display_name' => 'Mark Mous', 'first_name' => 'Mark', 'last_name' => 'Mous', 'telefoon' => '06-26449535' ],
    [ 'email' => 'marritvanderveer@ziggo.nl', 'display_name' => 'Marrit Veer van der', 'first_name' => 'Marrit', 'last_name' => 'Veer van der', 'telefoon' => '06-11279491' ],
    [ 'email' => 'maud.boschker@gmail.com', 'display_name' => 'Maud Boschker', 'first_name' => 'Maud', 'last_name' => 'Boschker', 'telefoon' => '06-18203191' ],
    [ 'email' => 'melba.chilaule@gmail.com', 'display_name' => 'Melba Chilaúle', 'first_name' => 'Melba', 'last_name' => 'Chilaúle', 'telefoon' => '06 -84601080' ],
    [ 'email' => 'mennovandervelde@live.nl', 'display_name' => 'Menno Velde van der', 'first_name' => 'Menno', 'last_name' => 'Velde van der', 'telefoon' => '06- 38745874' ],
    [ 'email' => 'merelvgelder@hotmail.com', 'display_name' => 'Merel Gelder Van', 'first_name' => 'Merel', 'last_name' => 'Gelder Van', 'telefoon' => '06-83112578' ],
    [ 'email' => 'michel.semturis@gmail.com', 'display_name' => 'Michel Semturis', 'first_name' => 'Michel', 'last_name' => 'Semturis', 'telefoon' => '0049-15732366331' ],
    [ 'email' => 'mignonwithaar@gmail.com', 'display_name' => 'Mignon Withaar', 'first_name' => 'Mignon', 'last_name' => 'Withaar', 'telefoon' => '06-55941124' ],
    [ 'email' => 'milaruchti@hotmail.com', 'display_name' => 'Mila Ruchti', 'first_name' => 'Mila', 'last_name' => 'Ruchti', 'telefoon' => '06-57235150' ],
    [ 'email' => 'milanvrijgrn2009@gmail.com', 'display_name' => 'Milan Vrij', 'first_name' => 'Milan', 'last_name' => 'Vrij', 'telefoon' => '06-29432825' ],
    [ 'email' => 'mirthejanssen10@gmail.com', 'display_name' => 'Mirthe Janssen', 'first_name' => 'Mirthe', 'last_name' => 'Janssen', 'telefoon' => '06-11667329' ],
    [ 'email' => 'natinega23@gmail.com', 'display_name' => 'Natalia Neaga', 'first_name' => 'Natalia', 'last_name' => 'Neaga', 'telefoon' => '+40 720 026 902' ],
    [ 'email' => 'natineaga23@gmail.com', 'display_name' => 'Natalia Neaga', 'first_name' => 'Natalia', 'last_name' => 'Neaga', 'telefoon' => '+40 720 026 902' ],
    [ 'email' => 'nick0728@live.nl', 'display_name' => 'Nick Anbergen', 'first_name' => 'Nick', 'last_name' => 'Anbergen', 'telefoon' => '06-29859298' ],
    [ 'email' => 'n.a.koenderink@gmail.com', 'display_name' => 'Nick Koenderink', 'first_name' => 'Nick', 'last_name' => 'Koenderink', 'telefoon' => '06-51694347' ],
    [ 'email' => 'nickschraa123@gmail.com', 'display_name' => 'Nick Schraa', 'first_name' => 'Nick', 'last_name' => 'Schraa', 'telefoon' => '06-16854716' ],
    [ 'email' => 'niekieerdmans@gmail.com', 'display_name' => 'Nieki Eerdmans', 'first_name' => 'Nieki', 'last_name' => 'Eerdmans', 'telefoon' => '06-83409857' ],
    [ 'email' => 'nienke@moreservices.nl', 'display_name' => 'Nienke Berg van den', 'first_name' => 'Nienke', 'last_name' => 'Berg van den', 'telefoon' => '06-33568226' ],
    [ 'email' => 'ninatyl@hotmail.com', 'display_name' => 'Nina Tyler', 'first_name' => 'Nina', 'last_name' => 'Tyler', 'telefoon' => '06-39373709' ],
    [ 'email' => 'noorkorts@gmail.com', 'display_name' => 'Noor Korts', 'first_name' => 'Noor', 'last_name' => 'Korts', 'telefoon' => '06-27336564' ],
    [ 'email' => 'nynkeleverman@outlook.com', 'display_name' => 'Nynke Leverman', 'first_name' => 'Nynke', 'last_name' => 'Leverman', 'telefoon' => '06-28201187' ],
    [ 'email' => 'brittoldenburg11@gmail.com', 'display_name' => 'Oldenburg Britt', 'first_name' => 'Oldenburg', 'last_name' => 'Britt', 'telefoon' => 'brittoldenburg11@gmail.com' ],
    [ 'email' => 'olepepijnvtn@gmail.com', 'display_name' => 'Ole-Pepijn - van \'t Noordende', 'first_name' => 'Ole-Pepijn - van \'t', 'last_name' => 'Noordende', 'telefoon' => '06-51018878' ],
    [ 'email' => 'oleksii.stolyar@gmail.com', 'display_name' => 'Oleksii Stolyar', 'first_name' => 'Oleksii', 'last_name' => 'Stolyar', 'telefoon' => '06-29749038' ],
    [ 'email' => 'olivier.huisers@gmail.com', 'display_name' => 'Olivier Huisers', 'first_name' => 'Olivier', 'last_name' => 'Huisers', 'telefoon' => '06-12671706' ],
    [ 'email' => 'pimheijckmann@gmail.com', 'display_name' => 'Pim Heijckmann', 'first_name' => 'Pim', 'last_name' => 'Heijckmann', 'telefoon' => '06-15443434' ],
    [ 'email' => 'rcmfunke@gmx.de', 'display_name' => 'Rafael Funke', 'first_name' => 'Rafael', 'last_name' => 'Funke', 'telefoon' => '0049 176 568 570 39' ],
    [ 'email' => 'ravi.j.maas@gmail.com', 'display_name' => 'Ravi Maas', 'first_name' => 'Ravi', 'last_name' => 'Maas', 'telefoon' => '06-38586371' ],
    [ 'email' => 'reinout@pukka.com', 'display_name' => 'Reinout Fock', 'first_name' => 'Reinout', 'last_name' => 'Fock', 'telefoon' => '06-57964114' ],
    [ 'email' => 'remmelt04@gmail.com', 'display_name' => 'Remmelt Huizinga', 'first_name' => 'Remmelt', 'last_name' => 'Huizinga', 'telefoon' => '06 2352 0382' ],
    [ 'email' => 'robbinveen@hotmail.com', 'display_name' => 'Robbin Veen', 'first_name' => 'Robbin', 'last_name' => 'Veen', 'telefoon' => '06-14568520' ],
    [ 'email' => 'rochelle.mooij@live.com', 'display_name' => 'Rochelle Mooij', 'first_name' => 'Rochelle', 'last_name' => 'Mooij', 'telefoon' => '06-22667317' ],
    [ 'email' => 'rozel2008@gmail.com', 'display_name' => 'Roos Jong de', 'first_name' => 'Roos', 'last_name' => 'Jong de', 'telefoon' => '06-83663002' ],
    [ 'email' => 'roosmarijnvanstrien@gmail.com', 'display_name' => 'Roosmarijn van Strien', 'first_name' => 'Roosmarijn van', 'last_name' => 'Strien', 'telefoon' => '06-57336971' ],
    [ 'email' => 'rosaliekleissen@gmail.com', 'display_name' => 'Rosalie Kleissen', 'first_name' => 'Rosalie', 'last_name' => 'Kleissen', 'telefoon' => '06-20020316' ],
    [ 'email' => 'ruben.daan.van.dijk@gmail.com', 'display_name' => 'Ruben Dijk van', 'first_name' => 'Ruben', 'last_name' => 'Dijk van', 'telefoon' => '06-46031779' ],
    [ 'email' => 'rumagerritsen@gmail.com', 'display_name' => 'Ruma Gerritsen', 'first_name' => 'Ruma', 'last_name' => 'Gerritsen', 'telefoon' => '06-25381153' ],
    [ 'email' => 'sam.devroede@hotmail.com', 'display_name' => 'Sam Devroede', 'first_name' => 'Sam', 'last_name' => 'Devroede', 'telefoon' => '06-17035116' ],
    [ 'email' => 'sjdh2004@gmail.com', 'display_name' => 'Sam Haan de', 'first_name' => 'Sam', 'last_name' => 'Haan de', 'telefoon' => '06-30339976' ],
    [ 'email' => 'samvorsteveld@icloud.com', 'display_name' => 'Sam Vorsteveld', 'first_name' => 'Sam', 'last_name' => 'Vorsteveld', 'telefoon' => '06-30648650' ],
    [ 'email' => 'sanderwijnia@gmail.com', 'display_name' => 'Sander Wijnia', 'first_name' => 'Sander', 'last_name' => 'Wijnia', 'telefoon' => '06-27651971' ],
    [ 'email' => 'shantydc@hotmail.com', 'display_name' => 'Shantelly Cecilia', 'first_name' => 'Shantelly', 'last_name' => 'Cecilia', 'telefoon' => '06-82810443' ],
    [ 'email' => 'sharde.bregitha@gmail.com', 'display_name' => 'Shardé Bregitha', 'first_name' => 'Shardé', 'last_name' => 'Bregitha', 'telefoon' => '06-634448369' ],
    [ 'email' => 'sherriecefleming@hotmail.com', 'display_name' => 'Sherriece Fleming', 'first_name' => 'Sherriece', 'last_name' => 'Fleming', 'telefoon' => '06-45149203' ],
    [ 'email' => 'siegerjanvanklaarbergen@gmail.com', 'display_name' => 'Sieger Jan Klaarbergen van', 'first_name' => 'Sieger Jan', 'last_name' => 'Klaarbergen van', 'telefoon' => '06-15681845' ],
    [ 'email' => 'simone.stagnitto@gmail.com', 'display_name' => 'Simone Stagnitto', 'first_name' => 'Simone', 'last_name' => 'Stagnitto', 'telefoon' => '0039-3298014598' ],
    [ 'email' => 'sophievdp05@gmail.com', 'display_name' => 'Sophie Pol van der', 'first_name' => 'Sophie', 'last_name' => 'Pol van der', 'telefoon' => '06-53632680' ],
    [ 'email' => 'stefkn@outlook.com', 'display_name' => 'Stef Knijnenburg', 'first_name' => 'Stef', 'last_name' => 'Knijnenburg', 'telefoon' => '06-83649809' ],
    [ 'email' => 'stijnbrinksma@gmail.com', 'display_name' => 'Stijn Brinksma', 'first_name' => 'Stijn', 'last_name' => 'Brinksma', 'telefoon' => '06-22821221' ],
    [ 'email' => 'shatimmermans@gmail.com', 'display_name' => 'Stijn Timmermans', 'first_name' => 'Stijn', 'last_name' => 'Timmermans', 'telefoon' => '06-27302983' ],
    [ 'email' => 'tanyasassu@outlook.com', 'display_name' => 'Tanya Sassu', 'first_name' => 'Tanya', 'last_name' => 'Sassu', 'telefoon' => '06-41477301' ],
    [ 'email' => 'tess.pouderoyen@hotmail.com', 'display_name' => 'Tess Pouderoyen', 'first_name' => 'Tess', 'last_name' => 'Pouderoyen', 'telefoon' => '06-14218327' ],
    [ 'email' => 'tartatyana23@gmail.com', 'display_name' => 'Tetiana Tarasenko', 'first_name' => 'Tetiana', 'last_name' => 'Tarasenko', 'telefoon' => '06-27897907' ],
    [ 'email' => 'thomjp2004@gmail.com', 'display_name' => 'Thom Jonge Poerink', 'first_name' => 'Thom', 'last_name' => 'Jonge Poerink', 'telefoon' => '06-51151224' ],
    [ 'email' => 't.boonman@hotmail.com', 'display_name' => 'Tijs Boonman', 'first_name' => 'Tijs', 'last_name' => 'Boonman', 'telefoon' => '06-30409856' ],
    [ 'email' => 'tirzahdejong24@gmail.com', 'display_name' => 'Tirzah de Jong', 'first_name' => 'Tirzah de', 'last_name' => 'Jong', 'telefoon' => '06-23153122' ],
    [ 'email' => 't9dekker@gmail.com', 'display_name' => 'Treffer Dekker', 'first_name' => 'Treffer', 'last_name' => 'Dekker', 'telefoon' => '06-24605385' ],
    [ 'email' => 'froger.tea1@gmail.com', 'display_name' => 'Téa Froger', 'first_name' => 'Téa', 'last_name' => 'Froger', 'telefoon' => '06-16170712' ],
    [ 'email' => 'rodriguescfvasco@gmail.com', 'display_name' => 'Vasco Rodrigues', 'first_name' => 'Vasco', 'last_name' => 'Rodrigues', 'telefoon' => '0035-1966888099' ],
    [ 'email' => 'vickie.tuvi@gmail.com', 'display_name' => 'Viktoria Tuvi', 'first_name' => 'Viktoria', 'last_name' => 'Tuvi', 'telefoon' => '0037-25073428' ],
    [ 'email' => 'vbrouwer@live.nl', 'display_name' => 'Vincent Brouwer', 'first_name' => 'Vincent', 'last_name' => 'Brouwer', 'telefoon' => '06-30587447' ],
    [ 'email' => 'vosse.bruinsma@gmail.com', 'display_name' => 'Vosse Bruinsma', 'first_name' => 'Vosse', 'last_name' => 'Bruinsma', 'telefoon' => '615304342' ],
    [ 'email' => 'aaledw@gmail.com', 'display_name' => 'Walid Alkodmani', 'first_name' => 'Walid', 'last_name' => 'Alkodmani', 'telefoon' => '06-38868668' ],
    [ 'email' => 'akwalidnl@gmail.com', 'display_name' => 'Walid Alkodmani', 'first_name' => 'Walid', 'last_name' => 'Alkodmani', 'telefoon' => '06-38868668' ],
    [ 'email' => 'wittederijk@hotmail.com', 'display_name' => 'Wiite de Rijk', 'first_name' => 'Wiite de', 'last_name' => 'Rijk', 'telefoon' => '06 2212 8630' ],
    [ 'email' => 'wout-terlingen@hotmail.com', 'display_name' => 'Wout Terlingen', 'first_name' => 'Wout', 'last_name' => 'Terlingen', 'telefoon' => '06-21651131' ],
    [ 'email' => 'yanfraanje@gmail.com', 'display_name' => 'Yannick Fraanje', 'first_name' => 'Yannick', 'last_name' => 'Fraanje', 'telefoon' => '06-17077803' ],
    [ 'email' => 'yaro@ziggo.nl', 'display_name' => 'Yaro Bult', 'first_name' => 'Yaro', 'last_name' => 'Bult', 'telefoon' => '06-83107110' ],
    [ 'email' => 'y.a.van.rouendal@student.rug.nl', 'display_name' => 'Yasmine Rouendal van', 'first_name' => 'Yasmine', 'last_name' => 'Rouendal van', 'telefoon' => '06-82690563' ],
    [ 'email' => 'plahotna.evaa@gmail.com', 'display_name' => 'Yeva Plakhotna', 'first_name' => 'Yeva', 'last_name' => 'Plakhotna', 'telefoon' => '06-57338282' ],
    [ 'email' => 'y.a.reuver@student.rug.nl', 'display_name' => 'Yrsa Reuver', 'first_name' => 'Yrsa', 'last_name' => 'Reuver', 'telefoon' => '06-31281213' ],
    [ 'email' => 'yvanpacobel@gmail.com', 'display_name' => 'Yvan Bell', 'first_name' => 'Yvan', 'last_name' => 'Bell', 'telefoon' => '06-83558997' ],
    [ 'email' => 'zahraheydari2005@gmail.com', 'display_name' => 'Zahra Heydari', 'first_name' => 'Zahra', 'last_name' => 'Heydari', 'telefoon' => '06-84026992' ],
    [ 'email' => 'zoe.van.kuijck@gmail.com', 'display_name' => 'Zoë Kuijck van', 'first_name' => 'Zoë', 'last_name' => 'Kuijck van', 'telefoon' => '06-81708076' ],
    [ 'email' => 'a.ionas@student.rug.nl', 'display_name' => 'arianna Ionas', 'first_name' => 'arianna', 'last_name' => 'Ionas', 'telefoon' => '+31 620556833' ],
];
// ───────────────────────────────────────────────────────────────────────────

// E-mailnotificaties volledig uitschakelen
add_filter('wp_new_user_notification_email',       '__return_false');
add_filter('wp_new_user_notification_email_admin', '__return_false');
remove_all_actions('user_register');

$nieuw   = [];
$bestaand = [];
$fouten  = [];

foreach ($studenten as $s) {
    $email = strtolower(trim($s['email']));
    if (email_exists($email)) {
        $bestaand[] = $s['display_name'] . ' (' . $email . ')';
    } else {
        $nieuw[] = $s;
    }
}

?><!DOCTYPE html>
<html lang="nl">
<head><meta charset="UTF-8"><title>PSP – Studenten importeren</title>
<style>
body { font-family: sans-serif; max-width: 900px; margin: 40px auto; background: #f8f8f8; color: #222; }
h1   { color: #d31775; }
h2   { margin-top: 28px; color: #444; }
.ok  { color: #2e7d32; }
.err { color: #c62828; font-weight: bold; }
.num { font-size: 1.3rem; font-weight: bold; color: #d31775; }
.box { background:#fff; border:1px solid #ddd; border-radius:8px; padding:16px 24px; margin:16px 0; }
.btn { display:inline-block; margin-top:18px; padding:11px 26px;
       background:#d31775; color:#fff; border-radius:6px;
       text-decoration:none; font-weight:bold; font-size:1rem; }
.btn-sec { background:#666; margin-left:10px; }
pre  { background:#f3f4f6; padding:10px 14px; border-radius:6px; font-size:.82rem; max-height:300px; overflow:auto; }
</style></head>
<body>
<h1>PSP – Studenten importeren</h1>

<?php if ($run): ?>

<?php
// Uitvoeren
$ok = 0;
foreach ($nieuw as $s) {
    $email   = strtolower(trim($s['email']));
    $user_id = wp_insert_user([
        'user_login'   => $email,
        'user_email'   => $email,
        'first_name'   => $s['first_name'],
        'last_name'    => $s['last_name'],
        'display_name' => $s['display_name'],
        'role'         => 'psp_student',
        'user_pass'    => wp_generate_password(16, true, true),
    ]);

    if (is_wp_error($user_id)) {
        $fouten[] = $s['display_name'] . ' (' . $email . '): ' . $user_id->get_error_message();
    } else {
        update_user_meta($user_id, 'psp_telefoon', $s['telefoon']);
        delete_user_meta($user_id, 'psp_status');
        $ok++;
        echo '<span class="ok">✓ ' . esc_html($s['display_name']) . ' (' . esc_html($email) . ')</span><br>';
    }
}
?>

<hr>
<p class="ok" style="font-size:1.1rem"><strong>
    ✔ <?php echo $ok; ?> nieuwe account(s) aangemaakt.
    <?php if ($fouten): echo count($fouten) . ' fouten.'; endif; ?>
</strong></p>

<?php if ($fouten): ?>
<h2>Fouten</h2><pre><?php foreach ($fouten as $f) echo esc_html($f) . "\n"; ?></pre>
<?php endif; ?>

<?php if ($bestaand): ?>
<h2>Al bestaand (overgeslagen: <?php echo count($bestaand); ?>)</h2>
<pre><?php foreach ($bestaand as $b) echo esc_html($b) . "\n"; ?></pre>
<?php endif; ?>

<?php else: ?>

<div class="box">
  <p>Totaal in lijst: <span class="num"><?php echo count($studenten); ?></span></p>
  <p>Al bestaand (wordt overgeslagen): <span class="num"><?php echo count($bestaand); ?></span></p>
  <p>Nieuw aan te maken: <span class="num"><?php echo count($nieuw); ?></span></p>
</div>

<?php if ($nieuw): ?>
<h2>Nieuwe accounts (<?php echo count($nieuw); ?>)</h2>
<pre><?php foreach ($nieuw as $s) echo esc_html($s['display_name'] . ' <' . $s['email'] . '>') . "\n"; ?></pre>

<a class="btn" href="?sleutel=<?php echo SLEUTEL; ?>&run=1">▶ Nu importeren</a>
<a class="btn btn-sec" href="javascript:history.back()" style="text-decoration:none">Annuleren</a>
<?php else: ?>
<p class="ok">✔ Alle studenten uit de lijst bestaan al. Niets te importeren.</p>
<?php endif; ?>

<?php if ($bestaand): ?>
<h2>Al bestaand</h2>
<pre><?php foreach ($bestaand as $b) echo esc_html($b) . "\n"; ?></pre>
<?php endif; ?>

<?php endif; ?>

<p style="margin-top:40px;color:#aaa;font-size:.8rem">Verwijder dit bestand na gebruik.</p>
</body></html>
