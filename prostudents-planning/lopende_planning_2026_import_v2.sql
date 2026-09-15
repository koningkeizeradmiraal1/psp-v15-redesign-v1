-- ================================================================
-- ProStudents Planning — Import lopende planning (Excel-upload)
-- Gegenereerd: 2026-09-15 09:51
-- 76 diensten, 14 unieke opdrachtgevers
-- Marker in omschrijving: [excel-import]  (oude marker "excel-import" wordt ook opgeruimd)
-- ================================================================
SET NAMES utf8mb4;

-- Stap 1a: koppelingen naar eerder geimporteerde diensten opruimen
DELETE FROM `{PREFIX}ps_koppelingen` WHERE dienst_id IN (SELECT id FROM `{PREFIX}ps_diensten` WHERE omschrijving = 'excel-import' OR omschrijving LIKE '[excel-import]%');

-- Stap 1b: werkbevestigingen die verwijzen naar eerder geimporteerde diensten opruimen
DELETE FROM `{PREFIX}ps_werkbevestigingen` WHERE dienst_id IN (SELECT id FROM `{PREFIX}ps_diensten` WHERE omschrijving = 'excel-import' OR omschrijving LIKE '[excel-import]%');

-- Stap 2: eerder geimporteerde diensten verwijderen (zowel oude als nieuwe importmarker)
DELETE FROM `{PREFIX}ps_diensten` WHERE omschrijving = 'excel-import' OR omschrijving LIKE '[excel-import]%';

-- Stap 3: 14 opdrachtgevers toevoegen als ze nog niet bestaan (unique key op naam)
INSERT IGNORE INTO `{PREFIX}ps_opdrachtgevers` (naam) VALUES
  ('Alsema Zuidlaren'),
  ('Bakker Schoonmaakbedrijf'),
  ('Beijk catering service'),
  ('Bonte Wever, de - housekeeping'),
  ('CleanEnergy'),
  ('FysioSupplies'),
  ('GCH BV'),
  ('Gang van Zaken Business Catering B.V'),
  ('Heusinkveld'),
  ('Innovum (GemBOXX)'),
  ('LINE UP boek & media'),
  ('Magnum Opus (Koops)'),
  ('Stijlzinnig'),
  ('Werk Horeca - Cafe Hammingh');

-- Stap 4: 76 diensten invoegen
INSERT INTO `{PREFIX}ps_diensten` (titel, opdrachtgever, datum, tijdstip_van, tijdstip_tot, type_werk, omschrijving, status) VALUES
  ('werktijden iom OG ma-vrij', 'Alsema Zuidlaren', '2026-09-14', '00:00:00', '00:00:00', 'Renee Nienhuis', '[excel-import] werktijden iom OG ma-vrij', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('werktijden iom OG ma-vrij', 'Alsema Zuidlaren', '2026-09-15', '00:00:00', '00:00:00', 'Renee Nienhuis', '[excel-import] werktijden iom OG ma-vrij', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('werktijden iom OG ma-vrij', 'Alsema Zuidlaren', '2026-09-16', '00:00:00', '00:00:00', 'Renee Nienhuis', '[excel-import] werktijden iom OG ma-vrij', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('werktijden iom OG ma-vrij', 'Alsema Zuidlaren', '2026-09-17', '00:00:00', '00:00:00', 'Renee Nienhuis', '[excel-import] werktijden iom OG ma-vrij', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('werktijden iom OG ma-vrij', 'Alsema Zuidlaren', '2026-09-18', '00:00:00', '00:00:00', 'Renee Nienhuis', '[excel-import] werktijden iom OG ma-vrij', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('2 uur per dag- Schoonmaak synspec', 'Bakker Schoonmaakbedrijf', '2026-09-14', '00:00:00', '00:00:00', 'Fayah Kruize', '[excel-import] 2 uur per dag- Schoonmaak synspec', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('2 uur per dag- Schoonmaak synspec', 'Bakker Schoonmaakbedrijf', '2026-09-15', '00:00:00', '00:00:00', 'Fayah Kruize', '[excel-import] 2 uur per dag- Schoonmaak synspec', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('2 uur per dag- Schoonmaak synspec', 'Bakker Schoonmaakbedrijf', '2026-09-16', '00:00:00', '00:00:00', 'Fayah Kruize', '[excel-import] 2 uur per dag- Schoonmaak synspec', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('2 uur per dag- Schoonmaak synspec', 'Bakker Schoonmaakbedrijf', '2026-09-17', '00:00:00', '00:00:00', 'Fayah Kruize', '[excel-import] 2 uur per dag- Schoonmaak synspec', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('2 uur per dag- Schoonmaak synspec', 'Bakker Schoonmaakbedrijf', '2026-09-18', '00:00:00', '00:00:00', 'Fayah Kruize', '[excel-import] 2 uur per dag- Schoonmaak synspec', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('Integron', 'Bakker Schoonmaakbedrijf', '2026-09-15', '17:00:00', '19:00:00', 'Oleksii Stolyar', '[excel-import] 17.00 -ca 19.00 Integron', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('Integron', 'Bakker Schoonmaakbedrijf', '2026-09-17', '17:00:00', '19:00:00', 'Oleksii Stolyar', '[excel-import] 17.00 -ca 19.00 Integron', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Integron', 'Bakker Schoonmaakbedrijf', '2026-09-18', '17:00:00', '18:00:00', 'Oleksii Stolyar', '[excel-import] 17.00 -ca 18.00 Integron', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('Catering M&G Assen', 'Beijk catering service', '2026-09-14', '08:45:00', '13:30:00', 'Lisa Kiewiet', '[excel-import] 08.45-13.30-Catering M&G Assen', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Catering Photonis Roden', 'Beijk catering service', '2026-09-15', '07:30:00', '14:30:00', 'Lisa Kiewiet', '[excel-import] 07.30-14.30-Catering Photonis Roden', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('dienst aangepast check app Ruben', 'Beijk catering service', '2026-09-14', '00:00:00', '00:00:00', 'Ruben van Dijk', '[excel-import] dienst aangepast check app Ruben', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Catering M&G Assen', 'Beijk catering service', '2026-09-15', '08:45:00', '13:30:00', 'Ruben van Dijk', '[excel-import] 08.45-13.30-Catering M&G Assen', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('Catering M&G Assen', 'Beijk catering service', '2026-09-16', '08:45:00', '13:30:00', 'Casper van Santen', '[excel-import] 08.45-13.30-Catering M&G Assen', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('Catering M&G Assen', 'Beijk catering service', '2026-09-17', '08:45:00', '13:30:00', 'Casper van Santen', '[excel-import] 08.45-13.30-Catering M&G Assen', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Catering Photonis Roden', 'Beijk catering service', '2026-09-16', '07:00:00', '14:30:00', 'Sieger Jan Klaarbergen', '[excel-import] 07.00-14.30-Catering Photonis Roden', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('Catering M&G Assen', 'Beijk catering service', '2026-09-18', '08:30:00', '13:30:00', 'Sieger Jan Klaarbergen', '[excel-import] 08.30-13.30-Catering M&G Assen', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('Catering Photonis Roden', 'Beijk catering service', '2026-09-16', '07:00:00', '14:30:00', 'Lars van Maanen', '[excel-import] 07.00-14.30-Catering Photonis Roden', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('Catering Photonis Roden', 'Beijk catering service', '2026-09-17', '07:30:00', '14:30:00', 'Lily Nader Wilk?? Kan zij werken?', '[excel-import] 07.30-14.30-Catering Photonis Roden', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Catering Photonis Roden', 'Beijk catering service', '2026-09-19', '09:00:00', '12:00:00', 'Lily Nader Wilk', '[excel-import] 09.00-12.00-Catering Photonis Roden', IF('2026-09-19' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-17', '09:30:00', '15:00:00', 'Ada Bordean', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-19', '10:00:00', '16:00:00', 'Ada Bordean', '[excel-import] 10.00-ca.16.00-Housekeeping', IF('2026-09-19' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-19', '10:00:00', '16:00:00', 'Lars van Maanen', '[excel-import] 10.00-ca.16.00-Housekeeping', IF('2026-09-19' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-20', '10:00:00', '16:00:00', 'Adrians Formanicks', '[excel-import] 10.00-ca.16.00-Housekeeping', IF('2026-09-20' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-14', '09:30:00', '15:00:00', 'Chantida Oosterwijk-Paphol', '[excel-import] reserve: 09.30-ca.15.00-Housekeeping', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-15', '09:30:00', '15:00:00', 'Chantida Oosterwijk-Paphol', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-17', '09:30:00', '15:00:00', 'Chantida Oosterwijk-Paphol', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-19', '10:00:00', '16:00:00', 'Chantida Oosterwijk-Paphol', '[excel-import] reserve: 10.00-16.00-Housekeeping', IF('2026-09-19' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-20', '10:00:00', '16:00:00', 'Chantida Oosterwijk-Paphol', '[excel-import] 10.00-ca.16.00-Housekeeping', IF('2026-09-20' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-20', '10:00:00', '16:00:00', 'Mahmoud Hassan', '[excel-import] 10.00-ca.16.00-Housekeeping', IF('2026-09-20' < CURDATE(), 'vervuld', 'open')),
  ('weet niet dat dit reserve is', 'Bonte Wever, de - housekeeping', '2026-09-14', '00:00:00', '00:00:00', 'Lars van Maanen', '[excel-import] weet niet dat dit reserve is', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-19', '10:00:00', '16:00:00', 'Lars van Maanen', '[excel-import] reserve: 10.00-16.00-Housekeeping', IF('2026-09-19' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-17', '09:30:00', '15:00:00', 'Mik Claessens', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-18', '09:30:00', '15:00:00', 'Mik Claessens', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-15', '09:30:00', '15:00:00', 'Emre Serrtas', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-17', '09:30:00', '15:00:00', 'Emre Serrtas', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-18', '09:30:00', '15:00:00', 'Emre Serrtas', '[excel-import] 09.30-ca.15.00-Housekeeping', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-16', '09:30:00', '15:00:00', 'Manon Schuurs', '[excel-import] reserve: 09.30-ca.15.00-Housekeeping', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-20', '10:00:00', '16:00:00', 'Manon Schuurs', '[excel-import] 10.00-ca.16.00-Housekeeping', IF('2026-09-20' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-17', '09:30:00', '15:00:00', 'Nog in te vullen', '[excel-import] reserve: 09.30-ca.15.00-Housekeeping', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-18', '09:30:00', '15:00:00', 'Nog in te vullen', '[excel-import] reserve: 09.30-ca.15.00-Housekeeping', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-20', '10:00:00', '16:00:00', 'Nog in te vullen', '[excel-import] reserve: 10.00-16.00-Housekeeping', IF('2026-09-20' < CURDATE(), 'vervuld', 'open')),
  ('reserve:  -Housekeeping', 'Bonte Wever, de - housekeeping', '2026-09-20', '10:00:00', '16:00:00', 'Nog in te vullen', '[excel-import] reserve: 10.00-16.00-Housekeeping', IF('2026-09-20' < CURDATE(), 'vervuld', 'open')),
  ('werktijden   uur', 'CleanEnergy', '2026-09-14', '08:30:00', '17:00:00', 'Kyra Plaatje uren i.o. OG 2 / 3 dagen', '[excel-import] werktijden 08.30-17.00 uur', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('werktijden   uur', 'CleanEnergy', '2026-09-14', '08:30:00', '17:00:00', 'Mariame Bofaya uren i.o.', '[excel-import] werktijden 08.30-17.00 uur', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('werktijden   uur', 'CleanEnergy', '2026-09-14', '08:30:00', '17:00:00', 'Nog in te vullen VAST voorkeur 32 uur', '[excel-import] werktijden 08.30-17.00 uur', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('FysioSupplies', 'FysioSupplies', '2026-09-15', '08:30:00', '17:00:00', 'Jorick Bakker', '[excel-import] 08.30-17.00', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('FysioSupplies', 'FysioSupplies', '2026-09-16', '08:30:00', '17:00:00', 'Jorick Bakker', '[excel-import] 08.30-17.00', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('FysioSupplies', 'FysioSupplies', '2026-09-17', '08:30:00', '17:00:00', 'Jorick Bakker', '[excel-import] 08.30-17.00', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('FysioSupplies', 'FysioSupplies', '2026-09-18', '08:30:00', '17:00:00', 'Jorick Bakker', '[excel-import] 08.30-17.00', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('catering Duinkerkenstraat', 'Gang van Zaken Business Catering B.V', '2026-09-14', '09:30:00', '14:00:00', 'Harnet Beyene', '[excel-import] 9:30-14:00 catering Duinkerkenstraat', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('catering Duinkerkenstraat', 'Gang van Zaken Business Catering B.V', '2026-09-15', '09:30:00', '14:00:00', 'Harnet Beyene', '[excel-import] 9:30-14:00 catering Duinkerkenstraat', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('werkdagen en tijden i.o.', 'GCH BV', '2026-09-14', '00:00:00', '00:00:00', 'Martin Dekker, vast kok netwerkmdw.', '[excel-import] werkdagen en tijden i.o.', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Assemblage medewerker', 'Heusinkveld', '2026-09-14', '08:30:00', '17:00:00', 'Hatim Lamoumni', '[excel-import] 8:30 - 17:00 uur Assemblage medewerker', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Assemblage medewerker', 'Heusinkveld', '2026-09-15', '08:30:00', '17:00:00', 'Hatim Lamoumni', '[excel-import] 8:30 - 17:00 uur Assemblage medewerker', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('Assemblage medewerker', 'Heusinkveld', '2026-09-16', '08:30:00', '17:00:00', 'Hatim Lamoumni', '[excel-import] 8:30 - 17:00 uur Assemblage medewerker', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('Assemblage medewerker', 'Heusinkveld', '2026-09-17', '08:30:00', '17:00:00', 'Hatim Lamoumni', '[excel-import] 8:30 - 17:00 uur Assemblage medewerker', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Assemblage medewerker', 'Heusinkveld', '2026-09-18', '08:30:00', '15:00:00', 'Hatim Lamoumni', '[excel-import] 8:30 - 15:00 uur Assemblage medewerker', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('uren gaan i.o.', 'Innovum (GemBOXX)', '2026-09-14', '00:00:00', '00:00:00', 'Jasper Kruizinga', '[excel-import] uren gaan i.o.', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('uren gaan i.o.', 'Innovum (GemBOXX)', '2026-09-14', '00:00:00', '00:00:00', 'Yaro Bult', '[excel-import] uren gaan i.o.', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('uren i.o. OG', 'LINE UP boek & media', '2026-09-14', '00:00:00', '00:00:00', 'Jannis Bartelds - Netwerkmdw. Vast FS B 1 tot 200925 FS B tot 200926', '[excel-import] uren i.o. OG', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Jannis heeft een ander contract, werkt door tot 20 september', 'LINE UP boek & media', '2026-09-15', '00:00:00', '00:00:00', 'Jannis Bartelds - Netwerkmdw. Vast FS B 1 tot 200925 FS B tot 200926', '[excel-import] Jannis heeft een ander contract, werkt door tot 20 september', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('Magnum Opus (Koops)', 'Magnum Opus (Koops)', '2026-09-14', '07:30:00', '16:00:00', 'Tetiana Tarasenko vast Productiemedw. voor langere periode', '[excel-import] 07.30 - 16.00 uur', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Magnum Opus (Koops)', 'Magnum Opus (Koops)', '2026-09-15', '07:30:00', '16:00:00', 'Tetiana Tarasenko vast Productiemedw. voor langere periode', '[excel-import] 07.30 - 16.00 uur', IF('2026-09-15' < CURDATE(), 'vervuld', 'open')),
  ('Magnum Opus (Koops)', 'Magnum Opus (Koops)', '2026-09-16', '07:30:00', '16:00:00', 'Tetiana Tarasenko vast Productiemedw. voor langere periode', '[excel-import] 07.30 - 16.00 uur', IF('2026-09-16' < CURDATE(), 'vervuld', 'open')),
  ('Magnum Opus (Koops)', 'Magnum Opus (Koops)', '2026-09-17', '07:30:00', '16:00:00', 'Tetiana Tarasenko vast Productiemedw. voor langere periode', '[excel-import] 07.30 - 16.00 uur', IF('2026-09-17' < CURDATE(), 'vervuld', 'open')),
  ('Magnum Opus (Koops)', 'Magnum Opus (Koops)', '2026-09-18', '07:30:00', '16:00:00', 'Tetiana Tarasenko vast Productiemedw. voor langere periode', '[excel-import] 07.30 - 16.00 uur', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('Stijlzinnig', 'Stijlzinnig', '2026-09-18', '09:00:00', '15:00:00', 'Wout Terlingen', '[excel-import] 09.00-15.00', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('Stijlzinnig', 'Stijlzinnig', '2026-09-18', '09:00:00', '15:00:00', 'Yannick Fraanje', '[excel-import] 09.00-15.00', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('weet Manon dit al??', 'Werk Horeca - Cafe Hammingh', '2026-09-14', '00:00:00', '00:00:00', 'Nog in te vullen t.v.v Manon Schuurs', '[excel-import] weet Manon dit al??', IF('2026-09-14' < CURDATE(), 'vervuld', 'open')),
  ('Werk Horeca - Cafe Hammingh', 'Werk Horeca - Cafe Hammingh', '2026-09-18', '11:00:00', '16:00:00', 'Nog in te vullen t.v.v Manon Schuurs', '[excel-import] 11.00 uur -ca 16.00 uur', IF('2026-09-18' < CURDATE(), 'vervuld', 'open')),
  ('geannuleerd door opdrachtgever', 'Werk Horeca - Cafe Hammingh', '2026-09-15', '12:00:00', '18:00:00', 'Nog in te vullen', '[excel-import] 12.00 - ca. 18.00 uur geannuleerd door opdrachtgever', IF('2026-09-15' < CURDATE(), 'vervuld', 'open'));
