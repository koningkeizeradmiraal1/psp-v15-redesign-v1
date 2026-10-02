<?php
defined('ABSPATH') || exit;

class PSP_Dashboard {

    public static function init() {
        add_shortcode('psp_dashboard',              [self::class, 'render']);
        add_action('wp_ajax_psp_week_data',          [self::class, 'ajax_week_data']);
        add_action('wp_ajax_psp_save_dienst',        [self::class, 'ajax_save_dienst']);
        add_action('wp_ajax_psp_delete_dienst',      [self::class, 'ajax_delete_dienst']);
        add_action('wp_ajax_psp_koppel',             [self::class, 'ajax_koppel']);
        add_action('wp_ajax_psp_ontkoppel',          [self::class, 'ajax_ontkoppel']);
        add_action('wp_ajax_psp_delete_beschikbaarheid',        [self::class, 'ajax_delete_beschikbaarheid']);
        add_action('wp_ajax_psp_save_beschikbaarheid_admin',    [self::class, 'ajax_save_beschikbaarheid_admin']);
        add_action('wp_ajax_psp_get_aanmeldingen',              [self::class, 'ajax_get_aanmeldingen']);
        add_action('wp_ajax_psp_goedkeur_aanmelding',           [self::class, 'ajax_goedkeur_aanmelding']);
        add_action('wp_ajax_psp_wijs_af_aanmelding',            [self::class, 'ajax_wijs_af_aanmelding']);
        add_action('wp_ajax_psp_save_tarief',            [self::class, 'ajax_save_tarief']);
        add_action('wp_ajax_psp_tarieven_todo',          [self::class, 'ajax_tarieven_todo']);
        add_action('wp_ajax_psp_get_studenten',          [self::class, 'ajax_get_studenten']);
        add_action('wp_ajax_psp_maak_student_account',   [self::class, 'ajax_maak_student_account']);
        add_action('wp_ajax_psp_save_student_vaardigheden', [self::class, 'ajax_save_student_vaardigheden']);
        add_action('wp_ajax_psp_wb_laad',      [self::class, 'ajax_wb_laad']);
        add_action('wp_ajax_psp_wb_opslaan',   [self::class, 'ajax_wb_opslaan']);
        add_action('wp_ajax_psp_wb_verwijder', [self::class, 'ajax_wb_verwijder']);
        add_action('wp_ajax_psp_wb_templates_voor_og',  [self::class, 'ajax_wb_templates_voor_og']);
        add_action('wp_ajax_psp_wb_stuur',              [self::class, 'ajax_wb_stuur']);
        add_action('wp_ajax_psp_wb_bevestigingen',      [self::class, 'ajax_wb_bevestigingen']);
        add_action('wp_ajax_psp_urenoverzicht',          [self::class, 'ajax_urenoverzicht']);
        add_action('wp_ajax_psp_stuur_welkomstmail',     [self::class, 'ajax_stuur_welkomstmail']);
        add_action('wp_ajax_psp_get_opdrachtgevers',     [self::class, 'ajax_get_opdrachtgevers']);
        add_action('wp_ajax_psp_save_opdrachtgever',     [self::class, 'ajax_save_opdrachtgever']);
        add_action('wp_ajax_psp_delete_opdrachtgever',   [self::class, 'ajax_delete_opdrachtgever']);
        add_action('wp_ajax_psp_get_evenementen',         [self::class, 'ajax_get_evenementen']);
        add_action('wp_ajax_psp_save_evenement',          [self::class, 'ajax_save_evenement']);
        add_action('wp_ajax_psp_delete_evenement',        [self::class, 'ajax_delete_evenement']);
        add_action('wp_ajax_psp_week_evenementen',        [self::class, 'ajax_week_evenementen']);
    }

    /* ─────────────── Shortcode ─────────────── */
    public static function render() {
        if ( ! is_user_logged_in() ) {
            return '<p class="psp-dash-login">Je moet <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">inloggen</a> om dit dashboard te bekijken.</p>';
        }
        if ( ! current_user_can('edit_posts') ) {
            return '<p class="psp-dash-login">Je hebt geen toegang tot dit dashboard.</p>';
        }
        // Opdrachtgever namen ophalen voor datalist autocomplete
        global $wpdb;
        $og_namen = [];
        $tabel_ok = $wpdb->get_var( "SHOW TABLES LIKE '" . PSP_TABLE_OPDRACHTGEVERS . "'" );
        if ( $tabel_ok ) {
            $og_namen = $wpdb->get_col( "SELECT naam FROM " . PSP_TABLE_OPDRACHTGEVERS . " ORDER BY naam ASC" );
        }
        if ( empty( $og_namen ) ) {
            $og_namen = $wpdb->get_col(
                "SELECT DISTINCT opdrachtgever FROM " . PSP_TABLE_DIENSTEN
                . " WHERE opdrachtgever != '' ORDER BY opdrachtgever ASC"
            );
        }

        ob_start(); ?>
<div id="psp-dashboard" class="psp-dash">

<datalist id="psp-og-datalist">
<?php foreach ( $og_namen as $nm ) : ?>
  <option value="<?php echo esc_attr( $nm ); ?>">
<?php endforeach; ?>
</datalist>

  <!-- Header -->
  <div class="psp-dash-header">
    <div class="psp-dash-logo">📅 Pro Students Planning</div>
    <div class="psp-dash-tabs">
      <button class="psp-tab active" data-tab="rooster">Weekrooster</button>
      <button class="psp-tab" data-tab="diensten">Diensten</button>
      <button class="psp-tab" data-tab="studenten">Beschikbaarheid</button>
      <button class="psp-tab" data-tab="inplannen">Inplannen</button>
      <button class="psp-tab" data-tab="beheer">&#9881; Beheer <span id="psp-tarieven-badge" style="display:none;background:#e53935;color:#fff;border-radius:10px;font-size:.65rem;font-weight:700;padding:1px 6px;margin-left:2px;vertical-align:middle"></span></button>
      <button class="psp-tab" data-tab="evenementen">&#127965; Evenementen</button>
    </div>
    <div class="psp-dash-week-nav">
      <button class="psp-btn-icon" id="psp-prev-week">◀</button>
      <span id="psp-week-label" class="psp-week-label">—</span>
      <button class="psp-btn-icon" id="psp-next-week">▶</button>
    </div>
  </div>

  <!-- Filter bar -->
  <div class="psp-filter-bar">
    <label class="psp-filter-label">Opdrachtgever:</label>
    <select id="psp-filter-opdrachtgever" class="psp-filter-select">
      <option value="">— Alle —</option>
    </select>
    <label class="psp-filter-label">Ervaring:</label>
    <select id="psp-filter-vaardigheid" class="psp-filter-select">
      <option value="">— Alle —</option>
      <?php foreach ( PSP_Frontend::vaardigheden_lijst() as $key => $label ): ?>
      <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
      <?php endforeach; ?>
    </select>
    <button class="psp-filter-clear" id="psp-filter-clear" style="display:none">✕ Wis filters</button>
    <span class="psp-filter-result" id="psp-filter-result"></span>
  </div>

  <!-- Loader -->
  <div id="psp-loader" class="psp-loader"><div class="psp-spinner-lg"></div></div>

  <!-- TAB: Rooster -->
  <div id="psp-tab-rooster" class="psp-tab-panel">
    <div class="psp-rooster-wrap">
      <div class="psp-sidebar" id="psp-diensten-sidebar">
        <div class="psp-sidebar-header">
          <h3>Diensten <span id="psp-sidebar-open-count" style="display:none"></span></h3>
          <button class="psp-btn-primary" id="psp-nieuw-dienst-btn">+ Nieuw</button>
        </div>
        <div id="psp-diensten-lijst" class="psp-diensten-lijst"><p class="psp-empty-msg">Geen diensten.</p></div>
      </div>
      <div class="psp-grid-area">
        <div id="psp-week-ev-strip"></div>
        <div id="psp-grid-wrap" class="psp-grid-wrap">
          <p class="psp-empty-msg" style="padding:40px">Geen beschikbaarheid ingediend voor deze week.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB: Diensten -->
  <div id="psp-tab-diensten" class="psp-tab-panel" style="display:none">
    <div class="psp-panel-header">
      <h3>Alle diensten</h3>
      <button class="psp-btn-primary" id="psp-nieuw-dienst-btn2">+ Nieuwe dienst</button>
    </div>
    <div id="psp-diensten-tabel-wrap"><p class="psp-empty-msg">Laden…</p></div>
  </div>

  <!-- TAB: Studenten -->
  <div id="psp-tab-studenten" class="psp-tab-panel" style="display:none">
    <div class="psp-panel-header">
      <h3>Ingediende beschikbaarheid</h3>
      <button class="psp-btn-primary psp-btn-sm" id="psp-beschikbaar-toevoegen-btn">+ Toevoegen</button>
    </div>
    <div id="psp-studenten-tabel-wrap"><p class="psp-empty-msg">Laden…</p></div>
  </div>

  <!-- MODAL: Beschikbaarheid toevoegen/bewerken (medewerker) -->
  <div id="psp-beschikbaar-modal" class="psp-modal" style="display:none">
    <div class="psp-modal-box">
      <div class="psp-modal-header">
        <h2 id="psp-beschikbaar-modal-titel">Beschikbaarheid toevoegen</h2>
        <button class="psp-modal-close" data-modal="psp-beschikbaar-modal">✕</button>
      </div>
      <div class="psp-modal-body">
        <input type="hidden" id="psp-besch-id" value="0">

        <div class="psp-form-row2">
          <div class="psp-field">
            <label>Student</label>
            <select id="psp-besch-student-select">
              <option value="">— Selecteer student —</option>
            </select>
          </div>
          <div class="psp-field">
            <label>Week (maandag)</label>
            <input type="date" id="psp-besch-week">
          </div>
        </div>

        <div class="psp-form-row2" id="psp-besch-handmatig-wrap" style="display:none">
          <div class="psp-field">
            <label>Naam</label>
            <input type="text" id="psp-besch-naam" placeholder="Voor- en achternaam">
          </div>
          <div class="psp-field">
            <label>E-mailadres</label>
            <input type="email" id="psp-besch-email" placeholder="student@email.nl">
          </div>
        </div>

        <div class="psp-field">
          <label>Telefoonnummer</label>
          <input type="text" id="psp-besch-telefoon" placeholder="06 — xx xx xx xx" style="max-width:220px">
        </div>

        <div class="psp-field">
          <label>Beschikbaarheid per dag</label>
          <div id="psp-besch-dagen-wrap">
            <?php foreach ( ['ma'=>'Maandag','di'=>'Dinsdag','wo'=>'Woensdag','do'=>'Donderdag','vr'=>'Vrijdag','za'=>'Zaterdag'] as $dk => $label ): ?>
            <div class="psp-besch-dag-rij" style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f5f5f5;">
              <label style="display:flex;align-items:center;gap:6px;min-width:110px;font-size:.88rem;font-weight:600;cursor:pointer">
                <input type="checkbox" class="psp-besch-dag-check" data-dag="<?php echo $dk; ?>" style="accent-color:#d31775;width:16px;height:16px">
                <?php echo $label; ?>
              </label>
              <input type="time" class="psp-besch-van" data-dag="<?php echo $dk; ?>" value="08:00" disabled
                style="border:1px solid #e8e8e8;border-radius:6px;padding:5px 8px;font-size:.85rem;width:100px;opacity:.4">
              <span style="color:#aaa;font-size:.8rem">t/m</span>
              <input type="time" class="psp-besch-tot" data-dag="<?php echo $dk; ?>" value="17:00" disabled
                style="border:1px solid #e8e8e8;border-radius:6px;padding:5px 8px;font-size:.85rem;width:100px;opacity:.4">
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="psp-field" style="margin-top:12px">
          <label>Opmerkingen / voorkeur</label>
          <textarea id="psp-besch-voorkeur" rows="2" style="width:100%;border:1px solid #e8e8e8;border-radius:7px;padding:8px 10px;font-size:.875rem;resize:vertical"></textarea>
        </div>
      </div>
      <div class="psp-modal-footer">
        <button class="psp-btn-primary" id="psp-besch-opslaan-btn">Opslaan</button>
        <button class="psp-btn-ghost" data-modal="psp-beschikbaar-modal">Annuleren</button>
      </div>
    </div>
  </div>

  <!-- TAB: Inplannen (studenten links, diensten rechts) -->
  <div id="psp-tab-inplannen" class="psp-tab-panel" style="display:none">
    <div class="psp-inplannen-toolbar">
      <div class="psp-inplannen-legenda">
        <span><span class="psp-ip-legenda-dot beschikbaar"></span> Beschikbaar</span>
        <span><span class="psp-ip-legenda-dot ingepland"></span> Ingepland</span>
        <span><span class="psp-ip-legenda-dot niet"></span> Niet beschikbaar</span>
      </div>
      <span id="psp-inplannen-summary" class="psp-inplannen-count"></span>
      <button class="psp-btn-primary psp-btn-sm" id="psp-nieuw-dienst-btn3" style="margin-left:auto">+ Nieuwe dienst</button>
    </div>
    <div id="psp-inplannen-grid-wrap" class="psp-inplannen-grid-wrap">
      <p class="psp-empty-msg">Laden…</p>
    </div>
    <div class="psp-inplannen-open-sectie">
      <div class="psp-inplannen-col-header"><span>Nog volledig open (niemand beschikbaar of ingepland)</span></div>
      <div id="psp-inplannen-open-lijst" class="psp-inplannen-open-lijst"></div>
    </div>
  </div>


  <!-- TAB: Beheer -->
  <div id="psp-tab-beheer" class="psp-tab-panel" style="display:none">
    <div class="psp-beheer-subtabs">
      <button class="psp-stab active" data-stab="tarieven">&#128466; Tarieven flexexpert</button>
      <button class="psp-stab" data-stab="aanmeldingen">&#128221; Aanmeldingen <span id="psp-aanmeldingen-badge" style="display:none;background:#d31775;color:#fff;border-radius:10px;font-size:.65rem;font-weight:700;padding:1px 6px;margin-left:2px;vertical-align:middle"></span></button>
      <button class="psp-stab" data-stab="studenten">&#128101; Student accounts</button>
      <button class="psp-stab" data-stab="werkbevestiging">&#128196; Werkbevestiging</button>
      <button class="psp-stab" data-stab="bevestigingen">&#10003; Bevestigingen</button>
      <button class="psp-stab" data-stab="rapportage">&#128200; Uren</button>
      <button class="psp-stab" data-stab="opdrachtgevers">&#127968; Opdrachtgevers</button>
    </div>

    <!-- Subtab: Tarieven -->
    <div id="psp-stab-tarieven" class="psp-stab-panel">
      <div class="psp-panel-body">
        <p style="color:#666;font-size:.87rem;margin:0 0 16px">
          Eerste-keer koppelingen waarvoor het uurtarief en loon nog niet zijn doorgegeven aan flexexpert.
        </p>
        <div id="psp-tarieven-todo-lijst"><p class="psp-empty-msg">Laden&#8230;</p></div>
      </div>
    </div>

    <!-- Subtab: Aanmeldingen -->
    <div id="psp-stab-aanmeldingen" class="psp-stab-panel" style="display:none">
      <div class="psp-panel-body">
        <p style="color:#666;font-size:.87rem;margin:0 0 16px">
          Nieuwe uitzendkrachten die zich hebben aangemeld via de website. Keur goed of wijs af.
        </p>
        <div id="psp-aanmeldingen-lijst"><p class="psp-empty-msg">Laden&#8230;</p></div>
      </div>
    </div>

    <!-- Subtab: Student accounts -->
    <div id="psp-stab-studenten" class="psp-stab-panel" style="display:none">
      <div class="psp-panel-body">
        <div class="psp-panel-header" style="margin-bottom:16px">
          <p style="color:#666;font-size:.87rem;margin:0">
            Maak hier WordPress-accounts aan voor uitzendkrachten. Er worden <strong>geen welkomsmails</strong> verstuurd &mdash; deel de inloggegevens zelf mee.
          </p>
          <button class="psp-btn-primary psp-btn-sm" id="psp-student-nieuw-btn">+ Student toevoegen</button>
        </div>
        <div id="psp-studenten-accounts-lijst"><p class="psp-empty-msg">Laden&#8230;</p></div>
      </div>
    </div>

    <!-- MODAL: Nieuwe student toevoegen -->
    <div id="psp-modal-nieuwe-student" class="psp-modal" style="display:none">
      <div class="psp-modal-box psp-modal-sm">
        <div class="psp-modal-header">
          <h2>Nieuwe student toevoegen</h2>
          <button class="psp-modal-close" data-modal="psp-modal-nieuwe-student">&#10005;</button>
        </div>
        <form id="psp-student-nieuw-form">
          <div class="psp-modal-body">
            <div class="psp-field"><label>Naam</label><input type="text" id="psp-student-nieuw-naam" required></div>
            <div class="psp-field"><label>E-mailadres</label><input type="email" id="psp-student-nieuw-email" required></div>
          </div>
          <div class="psp-modal-footer">
            <button type="submit" class="psp-btn-primary" id="psp-student-nieuw-opslaan-btn">Account aanmaken</button>
            <button type="button" class="psp-btn-ghost psp-modal-close" data-modal="psp-modal-nieuwe-student">Annuleren</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Subtab: Werkbevestiging -->
    <div id="psp-stab-werkbevestiging" class="psp-stab-panel" style="display:none">
      <div class="psp-panel-body">
        <div class="psp-panel-header" style="margin-bottom:16px">
          <p style="color:#666;font-size:.87rem;margin:0">
            Beheer e-mailtemplates voor werkbevestigingen per opdrachtgever.<br>
            Gebruik <code>{naam}</code>, <code>{datum}</code>, <code>{van}</code>, <code>{tot}</code>, <code>{opdrachtgever}</code>, <code>{locatie}</code>, <code>{type_werk}</code> als variabelen.
          </p>
          <button class="psp-btn-primary psp-btn-sm" id="psp-wb-nieuw-btn">+ Nieuwe template</button>
        </div>
        <div class="psp-wb-filter" style="margin-bottom:12px">
          <label style="font-size:.85rem;color:#555">Filter op opdrachtgever:</label>
          <select id="psp-wb-og-filter" style="margin-left:8px;padding:4px 8px;border:1px solid #ddd;border-radius:6px;font-size:.85rem">
            <option value="">— Alle —</option>
          </select>
        </div>
        <div id="psp-wb-lijst"><p class="psp-empty-msg">Laden&#8230;</p></div>
      </div>
    </div>

    <!-- Subtab: Rapportage -->
    <div id="psp-stab-rapportage" class="psp-stab-panel" style="display:none">
      <div class="psp-panel-body">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;flex-wrap:wrap">
          <p style="color:#666;font-size:.87rem;margin:0">Ingeplande uren op basis van diensten met een koppeling.</p>
          <div style="display:flex;gap:8px;align-items:center;margin-left:auto">
            <label style="font-size:.85rem;color:#555">Jaar:</label>
            <select id="psp-rap-jaar" style="padding:5px 10px;border:1px solid #ddd;border-radius:6px;font-size:.85rem">
              <?php
              $huidig_jaar = (int) date('Y');
              for ($y = $huidig_jaar; $y >= $huidig_jaar - 2; $y--) {
                  echo "<option value=\"{$y}\"" . ($y === $huidig_jaar ? ' selected' : '') . ">{$y}</option>";
              }
              ?>
            </select>
            <button class="psp-btn-primary psp-btn-sm" id="psp-rap-laad-btn">Laden</button>
          </div>
        </div>
        <div id="psp-rapportage-wrap">
          <p class="psp-empty-msg">Kies een jaar en klik op Laden.</p>
        </div>
      </div>
    </div>

    <!-- Subtab: Bevestigingen -->
    <div id="psp-stab-bevestigingen" class="psp-stab-panel" style="display:none">
      <div class="psp-panel-body">
        <p style="color:#666;font-size:.87rem;margin:0 0 16px">
          Overzicht van werkbevestigingen die door studenten zijn bevestigd.
        </p>
        <div id="psp-wb-bevestigingen-lijst"><p class="psp-empty-msg">Laden&#8230;</p></div>
      </div>
    </div>

    <!-- Subtab: Opdrachtgevers -->
    <div id="psp-stab-opdrachtgevers" class="psp-stab-panel" style="display:none">
      <div class="psp-panel-body">
        <div class="psp-panel-header" style="margin-bottom:16px">
          <p style="color:#666;font-size:.87rem;margin:0">Beheer opdrachtgevers: naam, contactpersoon, e-mail en telefoon.</p>
          <button class="psp-btn-primary psp-btn-sm" id="psp-og-nieuw-btn">+ Nieuwe opdrachtgever</button>
        </div>
        <div id="psp-opdrachtgevers-lijst"><p class="psp-empty-msg">Laden&#8230;</p></div>
      </div>
    </div>
  </div>

</div><!-- #psp-dashboard -->

<!-- MODAL: Dienst aanmaken/bewerken -->
<div id="psp-modal-dienst" class="psp-modal" style="display:none">
  <div class="psp-modal-box">
    <div class="psp-modal-header">
      <h2 id="psp-modal-dienst-title">Nieuwe dienst</h2>
      <button class="psp-modal-close" data-modal="psp-modal-dienst">✕</button>
    </div>
    <form id="psp-dienst-form">
      <input type="hidden" name="dienst_id" id="psp-dienst-id" value="">
      <div class="psp-modal-body">
        <div class="psp-form-row2">
          <div class="psp-field"><label>Naam dienst *</label><input type="text" name="titel" required placeholder="bijv. Bediening diner"></div>
          <div class="psp-field"><label>Opdrachtgever *</label><input type="text" name="opdrachtgever" list="psp-og-datalist" required placeholder="Kies of typ een opdrachtgever"></div>
        </div>
        <div class="psp-form-row3">
          <div class="psp-field"><label>Datum *</label><input type="date" name="datum" required></div>
          <div class="psp-field"><label>Van</label><input type="time" name="tijdstip_van" value="09:00"></div>
          <div class="psp-field"><label>Tot</label><input type="time" name="tijdstip_tot" value="17:00"></div>
        </div>
        <div class="psp-form-row2">
          <div class="psp-field"><label>Locatie</label><input type="text" name="locatie" placeholder="Adres of afdeling"></div>
          <div class="psp-field"><label>Type werk</label><input type="text" name="type_werk" placeholder="Bediening, Productie…"></div>
        </div>
        <div class="psp-field"><label>Omschrijving</label><textarea name="omschrijving" rows="3"></textarea></div>
      </div>
      <div class="psp-modal-footer">
        <button type="submit" class="psp-btn-primary" id="psp-dienst-save-btn">Opslaan</button>
        <button type="button" class="psp-btn-ghost" data-modal="psp-modal-dienst">Annuleren</button>
        <button type="button" class="psp-btn-danger" id="psp-dienst-delete-btn" style="display:none">Verwijderen</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Koppelen / Vervanger -->
<div id="psp-modal-koppel" class="psp-modal" style="display:none">
  <div class="psp-modal-box psp-modal-sm">
    <div class="psp-modal-header">
      <h2 id="psp-koppel-title">Student inplannen</h2>
      <button class="psp-modal-close" data-modal="psp-modal-koppel">✕</button>
    </div>
    <div class="psp-modal-body">
      <p id="psp-koppel-info" class="psp-koppel-info"></p>
      <div id="psp-koppel-opties" class="psp-koppel-opties"></div>
    </div>
  </div>
</div>

<!-- MODAL: Vervanger zoeken -->
<div id="psp-modal-vervanger" class="psp-modal" style="display:none">
  <div class="psp-modal-box psp-modal-sm">
    <div class="psp-modal-header">
      <h2>🔄 Vervanger zoeken</h2>
      <button class="psp-modal-close" data-modal="psp-modal-vervanger">✕</button>
    </div>
    <div class="psp-modal-body">
      <div id="psp-vervanger-info" class="psp-vervanger-info"></div>
      <div id="psp-vervanger-lijst" class="psp-koppel-opties"></div>
    </div>
  </div>
</div>

<!-- MODAL: Tariefmelding flexexpert -->
<div id="psp-modal-tarief" class="psp-modal" style="display:none">
  <div class="psp-modal-box psp-modal-sm">
    <div class="psp-modal-header">
      <h2>&#128246; Doorgeven aan flexexpert</h2>
    </div>
    <div class="psp-modal-body">
      <p id="psp-tarief-tekst" style="font-weight:600;margin:0 0 6px"></p>
      <p style="color:#666;font-size:.85rem;margin:0 0 18px">
        Dit is de eerste keer dat deze student bij deze opdrachtgever werkt.
        Vul het uurtarief en loon in en bevestig zodra dit is doorgegeven aan flexexpert.
      </p>
      <div class="psp-form-row2">
        <div class="psp-field">
          <label>Uurtarief klant (&euro;/uur)</label>
          <input type="number" id="psp-tarief-uurtarief" step="0.01" min="0" placeholder="bijv. 14.50">
        </div>
        <div class="psp-field">
          <label>Loon student (&euro;/uur)</label>
          <input type="number" id="psp-tarief-loon" step="0.01" min="0" placeholder="bijv. 12.00">
        </div>
      </div>
    </div>
    <div class="psp-modal-footer">
      <button class="psp-btn-primary" id="psp-tarief-save-btn">&#10003; Bevestigen &#8212; doorgegeven aan flexexpert</button>
      <button class="psp-btn-ghost" id="psp-tarief-skip-btn">Later invullen</button>
    </div>
  </div>
</div>

<!-- MODAL: Account aangemaakt -->
<div id="psp-modal-account" class="psp-modal" style="display:none">
  <div class="psp-modal-box psp-modal-sm">
    <div class="psp-modal-header">
      <h2>&#10003; Account aangemaakt</h2>
    </div>
    <div class="psp-modal-body">
      <p style="color:#666;font-size:.87rem;margin:0 0 16px">
        Deel de onderstaande gegevens zelf met de uitzendkracht. Het wachtwoord is maar <strong>eenmalig</strong> zichtbaar.
      </p>
      <div class="psp-account-gegevens">
        <div class="psp-account-rij"><span>Gebruikersnaam</span><strong id="psp-acc-login"></strong></div>
        <div class="psp-account-rij"><span>E-mail</span><strong id="psp-acc-email"></strong></div>
        <div class="psp-account-rij"><span>Wachtwoord</span><strong id="psp-acc-ww" style="font-family:monospace;background:#fff3cd;padding:2px 8px;border-radius:4px"></strong></div>
        <div class="psp-account-rij"><span>Inlog-URL</span><a id="psp-acc-url" href="#" target="_blank"></a></div>
      </div>
    </div>
    <div class="psp-modal-footer">
      <button class="psp-btn-primary" id="psp-acc-sluit-btn">Begrepen</button>
    </div>
  </div>
</div>


  <!-- TAB: Evenementen -->
  <div id="psp-tab-evenementen" class="psp-tab-panel" style="display:none">
    <div class="psp-ev-toolbar">
      <div style="display:flex;align-items:center;gap:8px;">
        <label class="psp-filter-label">Opdrachtgever:</label>
        <select id="psp-ev-filter-og" class="psp-filter-select"><option value="">— Alle —</option></select>
        <button class="psp-btn-primary psp-btn-sm" id="psp-ev-nieuw-btn">+ Nieuw evenement</button>
      </div>
      <span id="psp-ev-count" style="font-size:.78rem;color:#9ca3af;"></span>
    </div>
    <div id="psp-ev-lijst" class="psp-ev-lijst"><p class="psp-empty-msg">Laden...</p></div>
  </div>

  <!-- MODAL: Evenement bewerken -->
  <div id="psp-modal-evenement" class="psp-modal" style="display:none">
    <div class="psp-modal-box psp-modal-sm">
      <div class="psp-modal-header">
        <h2 id="psp-modal-ev-title">Evenement</h2>
        <button class="psp-modal-close" data-modal="psp-modal-evenement">&#10005;</button>
      </div>
      <div class="psp-modal-body">
        <input type="hidden" id="psp-ev-id" value="">
        <div class="psp-form-row2">
          <div class="psp-field"><label>Datum</label><input type="date" id="psp-ev-datum"></div>
          <div class="psp-field"><label>Opdrachtgever</label><input type="text" id="psp-ev-og" list="psp-og-list" placeholder="Naam opdrachtgever"></div>
        </div>
        <div class="psp-field"><label>Medewerker</label><input type="text" id="psp-ev-medewerker" placeholder="Naam of 'Nog in te vullen'"></div>
        <div class="psp-field"><label>Dienst / tijden</label><input type="text" id="psp-ev-info" placeholder="bijv. 09:00–17:00 Catering"></div>
        <div class="psp-field"><label>Notities</label><textarea id="psp-ev-notities" rows="2" style="resize:vertical"></textarea></div>
      </div>
      <div class="psp-modal-footer">
        <button class="psp-btn-primary" id="psp-ev-opslaan-btn">Opslaan</button>
        <button class="psp-btn-ghost psp-modal-close" data-modal="psp-modal-evenement">Annuleren</button>
        <button class="psp-btn-danger" id="psp-ev-verwijder-btn" style="display:none">Verwijderen</button>
      </div>
    </div>
  </div>

<!-- MODAL: Opdrachtgever aanmaken/bewerken -->
<div id="psp-modal-og" class="psp-modal" style="display:none">
  <div class="psp-modal-box">
    <div class="psp-modal-header">
      <h2 id="psp-modal-og-title">Nieuwe opdrachtgever</h2>
      <button class="psp-modal-close" data-modal="psp-modal-og">&#10005;</button>
    </div>
    <form id="psp-og-form">
      <input type="hidden" id="psp-og-id" name="id" value="">
      <div class="psp-modal-body">
        <div class="psp-form-row2">
          <div class="psp-field">
            <label>Naam opdrachtgever *</label>
            <input type="text" name="naam" id="psp-og-naam" required placeholder="Bedrijfsnaam">
          </div>
          <div class="psp-field">
            <label>Contactpersoon</label>
            <input type="text" name="contactpersoon" id="psp-og-contact" placeholder="Voornaam achternaam">
          </div>
        </div>
        <div class="psp-form-row2">
          <div class="psp-field">
            <label>E-mailadres</label>
            <input type="email" name="email" id="psp-og-email" placeholder="contact@bedrijf.nl">
          </div>
          <div class="psp-field">
            <label>Telefoonnummer</label>
            <input type="text" name="telefoon" id="psp-og-telefoon" placeholder="06-12345678">
          </div>
        </div>
        <div class="psp-field">
          <label>Adres</label>
          <input type="text" name="adres" id="psp-og-adres" placeholder="Straat 1, 1234 AB Stad">
        </div>
        <div class="psp-field">
          <label>Notities</label>
          <textarea name="notities" id="psp-og-notities" rows="3" placeholder="Interne notities..."></textarea>
        </div>
      </div>
      <div class="psp-modal-footer">
        <button type="button" class="psp-btn-ghost" data-modal="psp-modal-og">Annuleren</button>
        <button type="submit" class="psp-btn-primary" id="psp-og-opslaan-btn">Opslaan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Vaardigheden bewerken -->
<div id="psp-modal-vaardigheden" class="psp-modal" style="display:none">
  <div class="psp-modal-box psp-modal-vaardigheden">
    <div class="psp-modal-header">
      <h2>&#9998; Vaardigheden — <span id="psp-vaard-modal-naam"></span></h2>
    </div>
    <div class="psp-modal-body" style="overflow-y:auto;max-height:65vh">
      <form id="psp-vaard-modal-form" data-user-id="0">
        <div class="psp-vaard-velden"></div>
      </form>
    </div>
    <div class="psp-modal-footer" style="display:flex;gap:8px;justify-content:flex-end">
      <button class="psp-btn-ghost" id="psp-vaard-modal-sluit">Annuleren</button>
      <button class="psp-btn-primary" id="psp-vaard-modal-opslaan">Opslaan</button>
    </div>
  </div>
</div>

<!-- MODAL: Werkbevestiging template -->
<div id="psp-modal-wb" class="psp-modal" style="display:none">
  <div class="psp-modal-box">
    <div class="psp-modal-header">
      <h2 id="psp-modal-wb-title">Nieuwe template</h2>
      <button class="psp-modal-close" data-modal="psp-modal-wb">&#10005;</button>
    </div>
    <form id="psp-wb-form">
      <input type="hidden" id="psp-wb-id" name="wb_id" value="">
      <div class="psp-modal-body">
        <div class="psp-form-row2">
          <div class="psp-field">
            <label>Opdrachtgever *</label>
            <input type="text" name="opdrachtgever" id="psp-wb-opdrachtgever" list="psp-og-datalist" required placeholder="Kies of typ een opdrachtgever">
          </div>
          <div class="psp-field">
            <label>Template naam *</label>
            <input type="text" name="naam" id="psp-wb-naam" required placeholder="bijv. Standaard bevestiging">
          </div>
        </div>
        <div class="psp-field">
          <label>Onderwerp (e-mail)</label>
          <input type="text" name="onderwerp" id="psp-wb-onderwerp" placeholder="Onderwerp van de e-mail">
        </div>
        <div class="psp-field">
          <label>Inhoud</label>
          <p class="psp-field-hint">De tekst met {variabelen} staat al klaar als voorbeeld — pas aan wat je wilt, de rest blijft gewoon staan.</p>
          <textarea name="inhoud" id="psp-wb-inhoud" rows="8" style="font-family:monospace;font-size:.84rem"></textarea>
        </div>
      </div>
      <div class="psp-modal-footer">
        <button type="submit" class="psp-btn-primary">Opslaan</button>
        <button type="button" class="psp-btn-ghost" data-modal="psp-modal-wb">Annuleren</button>
        <button type="button" class="psp-btn-danger" id="psp-wb-delete-btn" style="display:none">Verwijderen</button>
      </div>
    </form>
  </div>
</div>


<!-- MODAL: Werkbevestiging versturen -->
<div id="psp-modal-wb-stuur" class="psp-modal" style="display:none">
  <div class="psp-modal-box">
    <div class="psp-modal-header">
      <h2 id="psp-modal-wb-stuur-title">Werkbevestiging versturen</h2>
      <button class="psp-modal-close" data-modal="psp-modal-wb-stuur">&#10005;</button>
    </div>
    <div class="psp-modal-body">
      <input type="hidden" id="psp-wbs-dienst-id" value="">
      <input type="hidden" id="psp-wbs-student-email" value="">
      <input type="hidden" id="psp-wbs-opdrachtgever" value="">
      <div id="psp-wbs-template-keuze" style="margin-bottom:14px;display:none">
        <label style="font-size:.85rem;font-weight:600;color:#444;display:block;margin-bottom:6px">Template laden:</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap" id="psp-wbs-template-btns"></div>
      </div>
      <div class="psp-field" style="margin-bottom:10px">
        <label>Aan (e-mailadres student)</label>
        <input type="text" id="psp-wbs-aan" readonly style="background:#f8f9fa;color:#666">
      </div>
      <div class="psp-field" style="margin-bottom:10px">
        <label>Onderwerp</label>
        <input type="text" id="psp-wbs-onderwerp" placeholder="Onderwerp van de werkbevestiging">
      </div>
      <div class="psp-field">
        <label>Inhoud <span style="color:#aaa;font-size:.78rem">(gebruik {naam}, {datum}, {van}, {tot}, {opdrachtgever}, {locatie}, {type_werk})</span></label>
        <textarea id="psp-wbs-inhoud" rows="10" style="font-family:monospace;font-size:.83rem;white-space:pre"></textarea>
      </div>
      <div id="psp-wbs-status-info" style="display:none;margin-top:10px;padding:8px 12px;border-radius:6px;font-size:.84rem"></div>
    </div>
    <div class="psp-modal-footer">
      <button class="psp-btn-primary" id="psp-wbs-stuur-btn">&#128231; Versturen</button>
      <button class="psp-btn-ghost" data-modal="psp-modal-wb-stuur">Annuleren</button>
    </div>
  </div>
</div>

<?php wp_nonce_field('psp_dashboard', 'psp_dash_nonce'); ?>
        <?php
        return ob_get_clean();
    }

    /* ─────────────── AJAX: weekdata ─────────────── */
    public static function ajax_week_data() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $week_start = sanitize_text_field( $_POST['week_start'] ?? '' );
        if ( ! $week_start ) wp_send_json_error( array( 'message' => 'Geen week' ) );

        $beschikbaarheid = PSP_DB::get_beschikbaarheid_by_week( $week_start );
        $diensten        = PSP_DB::get_diensten_voor_week( $week_start );

        $diensten_data = array();
        foreach ( $diensten as $d ) {
            $kop = PSP_DB::get_koppeling_voor_dienst( $d->id );
            $diensten_data[] = array(
                'id'            => (int) $d->id,
                'titel'         => $d->titel,
                'opdrachtgever' => $d->opdrachtgever,
                'datum'         => $d->datum,
                'tijdstip_van'  => $d->tijdstip_van,
                'tijdstip_tot'  => $d->tijdstip_tot,
                'locatie'       => $d->locatie,
                'type_werk'     => $d->type_werk,
                'omschrijving'  => $d->omschrijving,
                'status'        => $d->status,
                'koppeling'     => $kop ? array(
                    'id'                  => (int) $kop->id,
                    'beschikbaarheid_id'  => (int) $kop->beschikbaarheid_id,
                    'naam'                => $kop->naam,
                    'email'               => $kop->email,
                    'notificatie'         => (bool) $kop->notificatie_verzonden,
                ) : null,
            );
            // Werkbevestiging status toevoegen
            $wb = PSP_DB::get_wb_voor_dienst( $d->id );
            $diensten_data[ count($diensten_data) - 1 ]['wb_status'] = $wb ? $wb->status : null;
        }

        $studenten_data = array();
        foreach ( $beschikbaarheid as $b ) {
            $kops     = PSP_DB::get_koppelingen_voor_beschikbaarheid( $b->id );
            $kops_arr = array();
            foreach ( $kops as $k ) {
                $kops_arr[ $k->dienst_id ] = array(
                    'dienst_id'    => (int) $k->dienst_id,
                    'koppeling_id' => (int) $k->id,
                );
            }
            $studenten_data[] = array(
                'id'          => (int) $b->id,
                'naam'        => $b->naam,
                'email'       => $b->email,
                'telefoon'    => $b->telefoon,
                'voorkeur'    => $b->voorkeur,
                'dagen'       => json_decode( $b->dagen, true ) ?: array(),
                'vaardigheden'=> json_decode( $b->vaardigheden, true ) ?: array(),
                'koppelingen' => $kops_arr,
            );
        }

        wp_send_json_success( array(
            'beschikbaarheid' => $studenten_data,
            'diensten'        => $diensten_data,
        ) );
    }

    /* ─────────────── AJAX: dienst opslaan ─────────────── */
    public static function ajax_save_dienst() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $id   = (int) ( $_POST['dienst_id'] ?? 0 );
        $data = array(
            'titel'         => sanitize_text_field( $_POST['titel']         ?? '' ),
            'opdrachtgever' => sanitize_text_field( $_POST['opdrachtgever'] ?? '' ),
            'datum'         => sanitize_text_field( $_POST['datum']          ?? '' ),
            'tijdstip_van'  => sanitize_text_field( $_POST['tijdstip_van']  ?? '09:00' ),
            'tijdstip_tot'  => sanitize_text_field( $_POST['tijdstip_tot']  ?? '17:00' ),
            'locatie'       => sanitize_text_field( $_POST['locatie']        ?? '' ),
            'type_werk'     => sanitize_text_field( $_POST['type_werk']      ?? '' ),
            'omschrijving'  => sanitize_textarea_field( $_POST['omschrijving'] ?? '' ),
        );

        if ( ! $data['titel'] || ! $data['opdrachtgever'] || ! $data['datum'] ) {
            wp_send_json_error( array( 'message' => 'Vul alle verplichte velden in.' ) );
        }

        if ( $id ) {
            PSP_DB::update_dienst( $id, $data );
        } else {
            $id = PSP_DB::insert_dienst( $data );
        }
        wp_send_json_success( array( 'dienst_id' => $id, 'message' => 'Dienst opgeslagen.' ) );
    }

    /* ─────────────── AJAX: dienst verwijderen ─────────────── */
    public static function ajax_delete_dienst() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $id = (int) ( $_POST['dienst_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();
        PSP_DB::delete_dienst( $id );
        wp_send_json_success( array( 'message' => 'Dienst verwijderd.' ) );
    }

    /* ─────────────── AJAX: beschikbaarheid opslaan door medewerker ─────────────── */
    public static function ajax_save_beschikbaarheid_admin() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error( array('message'=>'Geen toegang.') );

        $id         = (int) ( $_POST['id'] ?? 0 );
        $naam       = sanitize_text_field( $_POST['naam'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );
        $telefoon   = sanitize_text_field( $_POST['telefoon'] ?? '' );
        $week_start = sanitize_text_field( $_POST['week_start'] ?? '' );
        $dagen_raw  = $_POST['dagen'] ?? '{}';
        $voorkeur   = sanitize_textarea_field( $_POST['voorkeur'] ?? '' );

        if ( ! $naam || ! $email || ! $week_start ) {
            wp_send_json_error( array( 'message' => 'Naam, e-mail en week zijn verplicht.' ) );
        }

        $dagen = json_decode( wp_unslash( $dagen_raw ), true ) ?: array();
        $dagen_clean = array();
        foreach ( $dagen as $dag => $tijden ) {
            if ( ! empty( $tijden['van'] ) && ! empty( $tijden['tot'] ) ) {
                $dagen_clean[ sanitize_key($dag) ] = array(
                    'van' => substr( preg_replace('/[^0-9:]/', '', $tijden['van'] ), 0, 5 ),
                    'tot' => substr( preg_replace('/[^0-9:]/', '', $tijden['tot'] ), 0, 5 ),
                );
            }
        }

        $data = array(
            'naam'         => $naam,
            'email'        => $email,
            'telefoon'     => $telefoon,
            'week_start'   => $week_start,
            'dagen'        => $dagen_clean,
            'vaardigheden' => array(),
            'voorkeur'     => $voorkeur,
        );

        if ( $id > 0 ) {
            PSP_DB::update_beschikbaarheid( $id, $data );
            wp_send_json_success( array( 'message' => 'Beschikbaarheid bijgewerkt.' ) );
        } else {
            $new_id = PSP_DB::insert_beschikbaarheid( $data );
            $new_id ? wp_send_json_success( array( 'message' => 'Beschikbaarheid toegevoegd.', 'id' => $new_id ) )
                    : wp_send_json_error( array( 'message' => 'Opslaan mislukt.' ) );
        }
    }

    /* ─────────────── AJAX: beschikbaarheid verwijderen ─────────────── */
    public static function ajax_delete_beschikbaarheid() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $id = (int) ( $_POST['beschikbaarheid_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();
        PSP_DB::delete_beschikbaarheid( $id );
        wp_send_json_success( array( 'message' => 'Beschikbaarheid verwijderd.' ) );
    }

    /* ─────────────── AJAX: koppelen ─────────────── */
    public static function ajax_koppel() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $bid = (int) ( $_POST['beschikbaarheid_id'] ?? 0 );
        $did = (int) ( $_POST['dienst_id']          ?? 0 );
        if ( ! $bid || ! $did ) wp_send_json_error( array( 'message' => 'Ongeldige parameters' ) );

        PSP_DB::verwijder_koppeling( $did ); // Verwijder eventuele bestaande koppeling
        $koppeling_id = PSP_DB::insert_koppeling( $bid, $did );
        if ( ! $koppeling_id ) wp_send_json_error( array( 'message' => 'Koppeling mislukt.' ) );

        PSP_DB::update_dienst( $did, array( 'status' => 'vervuld' ) );

        $beschikbaarheid = PSP_DB::get_beschikbaarheid_by_id( $bid );
        $dienst          = PSP_DB::get_dienst_by_id( $did );

        // Geen automatische mail — werkbevestiging verstuurt de recruiter via de WB-modal.

        // Eerste-keer check voor flexexpert-tarief
        $eerste_keer = false;
        if ( $beschikbaarheid && $dienst ) {
            $eerste_keer = PSP_DB::is_eerste_keer( $beschikbaarheid->email, $dienst->opdrachtgever );
            if ( $eerste_keer ) {
                PSP_DB::registreer_eerste_keer( $beschikbaarheid->email, $dienst->opdrachtgever );
            }
        }

        wp_send_json_success( array(
            'message'        => '\u2713 Ingepland.',
            'koppeling_id'   => $koppeling_id,
            'naam'           => $beschikbaarheid ? $beschikbaarheid->naam  : '',
            'email'          => $beschikbaarheid ? $beschikbaarheid->email : '',
            'eerste_keer'    => $eerste_keer,
            'opdrachtgever'  => $dienst ? $dienst->opdrachtgever : '',
            'student_email'  => $beschikbaarheid ? $beschikbaarheid->email : '',
            'student_naam'   => $beschikbaarheid ? $beschikbaarheid->naam  : '',
        ) );
    }

    /* ─────────────── AJAX: ontkoppelen ─────────────── */
    public static function ajax_ontkoppel() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $did = (int) ( $_POST['dienst_id'] ?? 0 );
        if ( ! $did ) wp_send_json_error();
        PSP_DB::verwijder_koppeling( $did );
        wp_send_json_success( array( 'message' => 'Koppeling verwijderd.' ) );
    }

    /* ─────────────── AJAX: tarief opslaan ─────────────── */
    public static function ajax_save_tarief() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $email         = sanitize_email( $_POST['student_email']    ?? '' );
        $opdrachtgever = sanitize_text_field( $_POST['opdrachtgever'] ?? '' );
        $uurtarief     = floatval( $_POST['uurtarief'] ?? 0 );
        $loon          = floatval( $_POST['loon']      ?? 0 );
        if ( ! $email || ! $opdrachtgever ) {
            wp_send_json_error( array( 'message' => 'Velden ontbreken.' ) );
        }
        PSP_DB::save_tarief( $email, $opdrachtgever, $uurtarief, $loon );
        wp_send_json_success( array( 'message' => '\u2713 Doorgegeven aan flexexpert.' ) );
    }

    /* ─────────────── AJAX: openstaande tarieven ─────────────── */
    public static function ajax_tarieven_todo() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $rows = PSP_DB::get_onbevestigde_tarieven();
        $out  = array();
        foreach ( $rows as $r ) {
            $out[] = array(
                'student_email' => $r->student_email,
                'student_naam'  => $r->student_naam ?: $r->student_email,
                'opdrachtgever' => $r->opdrachtgever,
                'datum'         => $r->aangemaakt_op ? substr( $r->aangemaakt_op, 0, 10 ) : '',
            );
        }
        wp_send_json_success( $out );
    }

    /* ─────────────── AJAX: studenten ophalen ─────────────── */
    public static function ajax_get_studenten() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        global $wpdb;
        $studenten = array();
        $gezien    = array(); // bijhouden welke emails al verwerkt zijn

        // 1. WP-gebruikers met psp_student rol (échte accounts, geen @psplanning.nl)
        $wp_studenten = get_users( array(
            'role'    => 'psp_student',
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'fields'  => array('ID','display_name','user_email'),
        ) );
        foreach ( $wp_studenten as $u ) {
            $email = $u->user_email;
            // Sla nep-emails over
            if ( substr( $email, -14 ) === '@psplanning.nl' ) continue;
            $gezien[ strtolower($email) ] = true;
            $naam_row = $wpdb->get_var( $wpdb->prepare(
                "SELECT naam FROM " . PSP_TABLE_BESCHIKBAARHEID . " WHERE email = %s ORDER BY created_at DESC LIMIT 1",
                $email
            ) );
            $vaardigheden = get_user_meta( $u->ID, 'psp_vaardigheden', true );
            $studenten[] = array(
                'email'        => $email,
                'naam'         => $naam_row ?: $u->display_name,
                'has_account'  => true,
                'user_id'      => $u->ID,
                'vaardigheden' => is_array($vaardigheden) ? $vaardigheden : array(),
            );
        }

        // 2. Emails uit beschikbaarheid zonder WP-account (nog geen login),
        //    ook geen @psplanning.nl
        $emails_besch = $wpdb->get_col(
            "SELECT DISTINCT email FROM " . PSP_TABLE_BESCHIKBAARHEID
            . " WHERE email NOT LIKE '%@psplanning.nl' ORDER BY email ASC"
        );
        foreach ( $emails_besch as $email ) {
            if ( isset( $gezien[ strtolower($email) ] ) ) continue; // al verwerkt via WP-users
            $wp_user = get_user_by( 'email', $email );
            if ( $wp_user ) {
                // account bestaat maar heeft misschien andere rol - toon toch
                $gezien[ strtolower($email) ] = true;
            }
            $naam_row = $wpdb->get_var( $wpdb->prepare(
                "SELECT naam FROM " . PSP_TABLE_BESCHIKBAARHEID . " WHERE email = %s ORDER BY created_at DESC LIMIT 1",
                $email
            ) );
            $vaardigheden = $wp_user ? get_user_meta( $wp_user->ID, 'psp_vaardigheden', true ) : array();
            $studenten[] = array(
                'email'        => $email,
                'naam'         => $naam_row ?: $email,
                'has_account'  => $wp_user ? true : false,
                'user_id'      => $wp_user ? $wp_user->ID : 0,
                'vaardigheden' => is_array($vaardigheden) ? $vaardigheden : array(),
            );
        }

        // Sorteer op naam
        usort( $studenten, function($a, $b) {
            return strcasecmp( $a['naam'], $b['naam'] );
        });

        wp_send_json_success( $studenten );
    }

    /* ─────────────── AJAX: account aanmaken (GEEN welkomsmail) ─────────────── */
    public static function ajax_maak_student_account() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $email = sanitize_email( $_POST['email'] ?? '' );
        $naam  = sanitize_text_field( $_POST['naam'] ?? '' );
        if ( ! $email ) wp_send_json_error( array( 'message' => 'Geen e-mailadres opgegeven.' ) );
        if ( get_user_by( 'email', $email ) ) {
            wp_send_json_error( array( 'message' => 'Er bestaat al een account voor dit e-mailadres.' ) );
        }

        // Gebruikersnaam genereren
        $base = sanitize_user( strtolower( preg_replace('/\s+/', '.', $naam ?: $email) ), true );
        $base = preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'student';
        $login = $base; $i = 1;
        while ( username_exists($login) ) { $login = $base . $i++; }

        $wachtwoord = wp_generate_password( 12, false );

        // Welkomsmails volledig uitschakelen
        add_filter( 'wp_new_user_notification_email',       '__return_false' );
        add_filter( 'wp_new_user_notification_email_admin', '__return_false' );
        remove_all_actions( 'user_register' );

        $user_id = wp_insert_user( array(
            'user_login'   => $login,
            'user_email'   => $email,
            'display_name' => $naam ?: $email,
            'first_name'   => $naam ?: '',
            'role'         => 'psp_student',
            'user_pass'    => $wachtwoord,
        ) );

        if ( is_wp_error($user_id) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        wp_send_json_success( array(
            'login'     => $login,
            'email'     => $email,
            'wachtwoord'=> $wachtwoord,
            'login_url' => wp_login_url(),
        ) );
    }

    /* ─────────────── AJAX: vaardigheden student opslaan ─────────────── */
    public static function ajax_save_student_vaardigheden() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $user_id = (int) ( $_POST['user_id'] ?? 0 );
        if ( ! $user_id ) wp_send_json_error( array( 'message' => 'Geen gebruiker opgegeven.' ) );

        $definitie    = PSP_Frontend::vaardigheden_definitie();
        $vaardigheden = array();

        if ( isset($_POST['vaardigheden']) && is_array($_POST['vaardigheden']) ) {
            foreach ( $_POST['vaardigheden'] as $k => $v ) {
                $k = sanitize_key($k);
                if ( ! isset($definitie[$k]) ) continue;
                $type = $definitie[$k]['type'];
                if ( $type === 'checkbox' ) {
                    $vaardigheden[$k] = ($v === '1' || $v === 'ja') ? '1' : '0';
                } elseif ( $type === 'textarea' ) {
                    $vaardigheden[$k] = sanitize_textarea_field($v);
                } else {
                    $vaardigheden[$k] = sanitize_text_field($v);
                }
            }
        }

        update_user_meta( $user_id, 'psp_vaardigheden', $vaardigheden );
        wp_send_json_success( array( 'message' => '\u2713 Vaardigheden opgeslagen.' ) );
    }

    /* ────
    /* ─────────────── AJAX: WB template laden ─────────────── */
    public static function ajax_wb_laad() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $og = sanitize_text_field( $_POST['og'] ?? '' );
        wp_send_json_success( array(
            'rows' => PSP_DB::get_wb_templates( $og ),
            'ogs'  => PSP_DB::get_wb_ogs(),
        ) );
    }

    /* ─────────────── AJAX: WB template opslaan ─────────────── */
    public static function ajax_wb_opslaan() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $id            = (int)   ( $_POST['wb_id']        ?? 0 );
        $opdrachtgever = sanitize_text_field( $_POST['opdrachtgever'] ?? '' );
        $naam          = sanitize_text_field( $_POST['naam']          ?? '' );
        $onderwerp     = sanitize_text_field( $_POST['onderwerp']     ?? '' );
        $inhoud        = sanitize_textarea_field( $_POST['inhoud']    ?? '' );

        if ( ! $opdrachtgever || ! $naam ) wp_send_json_error( array( 'message' => 'Opdrachtgever en naam zijn verplicht.' ) );

        $data = compact( 'opdrachtgever', 'naam', 'onderwerp', 'inhoud' );
        if ( $id ) $data['wb_id'] = $id;
        $ok = PSP_DB::save_wb_template( $data );
        if ( ! $ok ) wp_send_json_error( array( 'message' => 'Opslaan mislukt.' ) );
        wp_send_json_success( array( 'message' => "✓ Template opgeslagen.", 'templates' => PSP_DB::get_wb_templates() ) );
    }

    /* ─────────────── AJAX: WB template verwijderen ─────────────── */
    public static function ajax_wb_verwijder() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $id = (int) ( $_POST['wb_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();
        PSP_DB::delete_wb_template( $id );
        wp_send_json_success( array( 'message' => 'Template verwijderd.', 'templates' => PSP_DB::get_wb_templates() ) );
    }

    /* ─────────────── AJAX: WB templates voor opdrachtgever ─────────────── */
    public static function ajax_wb_templates_voor_og() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $og       = sanitize_text_field( $_POST['opdrachtgever'] ?? '' );
        $dienst_id = (int) ( $_POST['dienst_id'] ?? 0 );

        $templates = PSP_DB::get_wb_templates( $og );
        $bestaande = $dienst_id ? PSP_DB::get_wb_voor_dienst( $dienst_id ) : null;

        $bestaande_data = null;
        if ( $bestaande ) {
            $dienst = PSP_DB::get_dienst_by_id( $dienst_id );
            $bestaande_data = array(
                'onderwerp'    => $bestaande->onderwerp,
                'inhoud'       => $bestaande->inhoud,
                'status'       => $bestaande->status,
                'verzonden_op' => $bestaande->verzonden_op,
                'bevestigd_op' => $bestaande->bevestigd_op,
            );
            // Vervang placeholders
            if ( $dienst ) {
                $datum_nl = date_i18n( 'l j F Y', strtotime( $dienst->datum ) );
                $replace  = array(
                    '{naam}'          => '',
                    '{datum}'         => $datum_nl,
                    '{van}'           => substr( $dienst->tijdstip_van, 0, 5 ),
                    '{tot}'           => substr( $dienst->tijdstip_tot, 0, 5 ),
                    '{opdrachtgever}' => $dienst->opdrachtgever,
                    '{locatie}'       => $dienst->locatie   ?: '',
                    '{type_werk}'     => $dienst->type_werk ?: '',
                    '{bevestig_link}' => '',
                );
                $bestaande_data['inhoud']   = str_replace( array_keys($replace), array_values($replace), $bestaande_data['inhoud'] );
                $bestaande_data['onderwerp'] = str_replace( array_keys($replace), array_values($replace), $bestaande_data['onderwerp'] );
            }
        }

        wp_send_json_success( array(
            'templates' => $templates,
            'bestaande' => $bestaande_data,
        ) );
    }

    /* ─────────────── AJAX: werkbevestiging versturen ─────────────── */
    public static function ajax_wb_stuur() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $dienst_id     = (int)   ( $_POST['dienst_id']     ?? 0 );
        $student_email = sanitize_email( $_POST['student_email'] ?? '' );
        $opdrachtgever = sanitize_text_field( $_POST['opdrachtgever'] ?? '' );
        $onderwerp     = sanitize_text_field( $_POST['onderwerp']     ?? '' );
        $inhoud        = sanitize_textarea_field( $_POST['inhoud']    ?? '' );

        if ( ! $dienst_id || ! $student_email ) wp_send_json_error( array( 'message' => 'Ontbrekende gegevens.' ) );

        $dienst = PSP_DB::get_dienst_by_id( $dienst_id );
        if ( ! $dienst ) wp_send_json_error( array( 'message' => 'Dienst niet gevonden.' ) );

        // Haal studentnaam op via beschikbaarheid
        global $wpdb;
        $student_naam = $wpdb->get_var( $wpdb->prepare(
            "SELECT naam FROM " . PSP_TABLE_BESCHIKBAARHEID . " WHERE email = %s LIMIT 1", $student_email
        ) );
        if ( ! $student_naam ) {
            $u = get_user_by( 'email', $student_email );
            $student_naam = $u ? $u->display_name : $student_email;
        }

        $datum_nl = date_i18n( 'l j F Y', strtotime( $dienst->datum ) );

        // Bevestigingslink genereren
        $bevestig_token = wp_generate_password( 32, false );
        $bevestig_link  = home_url( '/mijn-rooster/?wb_bevestig=' . $bevestig_token );

        $replace = array(
            '{naam}'          => $student_naam,
            '{datum}'         => $datum_nl,
            '{van}'           => substr( $dienst->tijdstip_van, 0, 5 ),
            '{tot}'           => substr( $dienst->tijdstip_tot, 0, 5 ),
            '{opdrachtgever}' => $dienst->opdrachtgever,
            '{locatie}'       => $dienst->locatie   ?: '',
            '{type_werk}'     => $dienst->type_werk ?: '',
            '{bevestig_link}' => $bevestig_link,
        );
        $inhoud_filled   = str_replace( array_keys($replace), array_values($replace), $inhoud );
        $onderwerp_filled = str_replace( array_keys($replace), array_values($replace), $onderwerp );

        // WB opslaan in DB
        $wb_id = PSP_DB::save_werkbevestiging( array(
            'dienst_id'     => $dienst_id,
            'student_email' => $student_email,
            'opdrachtgever' => $dienst->opdrachtgever,
            'onderwerp'     => $onderwerp_filled,
            'inhoud'        => $inhoud_filled,
            'status'        => 'verzonden',
        ) );

        // Mail versturen
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ProStudents <info@prostudents.nl>',
        );
        $ok = wp_mail( $student_email, $onderwerp_filled, $inhoud_filled, $headers );

        if ( ! $ok ) wp_send_json_error( array( 'message' => 'Mail versturen mislukt.' ) );

        wp_send_json_success( array( 'message' => "✓ Werkbevestiging verstuurd naar {$student_naam}." ) );
    }

    /* ─────────────── AJAX: WB bevestigingen overzicht ─────────────── */
    public static function ajax_wb_bevestigingen() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT wb.*, b.naam as student_naam
             FROM " . PSP_TABLE_WERKBEVESTIGINGEN . " wb
             LEFT JOIN " . PSP_TABLE_BESCHIKBAARHEID . " b ON b.email = wb.student_email
             ORDER BY wb.verzonden_op DESC
             LIMIT 100"
        );
        $out = array();
        foreach ( $rows as $r ) {
            $out[] = array(
                'id'            => (int) $r->id,
                'dienst_id'     => (int) $r->dienst_id,
                'student_email' => $r->student_email,
                'student_naam'  => $r->student_naam ?: $r->student_email,
                'opdrachtgever' => $r->opdrachtgever,
                'datum'         => $r->datum,
                'status'        => $r->status,
                'verzonden_op'  => $r->verzonden_op,
                'bevestigd_op'  => $r->bevestigd_op,
            );
        }
        wp_send_json_success( $out );
    }

    /* ─────────────── AJAX: urenoverzicht ─────────────── */
    public static function ajax_urenoverzicht() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $van = sanitize_text_field( $_POST['van'] ?? date('Y-m-01') );
        $tot = sanitize_text_field( $_POST['tot'] ?? date('Y-m-t') );

        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.naam, b.email, d.opdrachtgever, d.datum,
                    d.tijdstip_van, d.tijdstip_tot, d.type_werk
             FROM " . PSP_TABLE_KOPPELINGEN   . " k
             JOIN " . PSP_TABLE_DIENSTEN       . " d ON d.id = k.dienst_id
             JOIN " . PSP_TABLE_BESCHIKBAARHEID . " b ON b.id = k.beschikbaarheid_id
             WHERE d.datum BETWEEN %s AND %s
             ORDER BY b.naam ASC, d.datum ASC",
            $van, $tot
        ) );

        $per_student = array();
        foreach ( $rows as $r ) {
            $key = $r->email;
            if ( ! isset($per_student[$key]) ) {
                $per_student[$key] = array(
                    'naam'          => $r->naam,
                    'email'         => $r->email,
                    'totaal_uren'   => 0,
                    'diensten'      => array(),
                );
            }
            // Bereken uren
            $van_ts = strtotime( $r->datum . ' ' . $r->tijdstip_van );
            $tot_ts = strtotime( $r->datum . ' ' . $r->tijdstip_tot );
            $uren   = $tot_ts > $van_ts ? round( ( $tot_ts - $van_ts ) / 3600, 2 ) : 0;
            $per_student[$key]['totaal_uren'] += $uren;
            $per_student[$key]['diensten'][]   = array(
                'datum'         => $r->datum,
                'opdrachtgever' => $r->opdrachtgever,
                'van'           => substr( $r->tijdstip_van, 0, 5 ),
                'tot'           => substr( $r->tijdstip_tot, 0, 5 ),
                'uren'          => $uren,
                'type_werk'     => $r->type_werk,
            );
        }
        wp_send_json_success( array_values( $per_student ) );
    }

    /* ─────────────── AJAX: welkomstmail sturen ─────────────── */
    public static function ajax_stuur_welkomstmail() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $user_id = (int) ( $_POST['user_id'] ?? 0 );
        if ( ! $user_id ) wp_send_json_error( array( 'message' => 'Geen gebruiker opgegeven.' ) );
        $ok = PSP_Mail::stuur_welkomstmail( $user_id );
        if ( ! $ok ) wp_send_json_error( array( 'message' => 'Mail versturen mislukt.' ) );
        wp_send_json_success( array( 'message' => "✓ Welkomstmail verstuurd." ) );
    }

    /* ─────────────── AJAX: aanmeldingen ophalen ─────────────── */
    public static function ajax_get_aanmeldingen() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $users = get_users( array( 'role' => 'psp_aanvraag', 'number' => -1 ) );
        $out   = array();
        foreach ( $users as $u ) {
            $out[] = array(
                'user_id'  => $u->ID,
                'naam'     => $u->display_name,
                'email'    => $u->user_email,
                'datum'    => get_user_meta( $u->ID, 'psp_aanmelddatum', true ) ?: substr( $u->user_registered, 0, 10 ),
                'telefoon' => get_user_meta( $u->ID, 'psp_telefoon', true ) ?: '',
            );
        }
        wp_send_json_success( $out );
    }

    /* ─────────────── AJAX: aanmelding goedkeuren ─────────────── */
    public static function ajax_goedkeur_aanmelding() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $user_id = (int) ( $_POST['user_id'] ?? 0 );
        $user    = get_user_by( 'id', $user_id );
        if ( ! $user ) wp_send_json_error( array( 'message' => 'Gebruiker niet gevonden.' ) );

        $user->set_role( 'psp_student' );
        delete_user_meta( $user_id, 'psp_status' );
        PSP_Mail::stuur_welkomstmail( $user_id );

        wp_send_json_success( array( 'message' => "✓ {$user->display_name} goedgekeurd. Welkomstmail verstuurd." ) );
    }

    /* ─────────────── AJAX: aanmelding afwijzen ─────────────── */
    public static function ajax_wijs_af_aanmelding() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $user_id = (int) ( $_POST['user_id'] ?? 0 );
        $user    = get_user_by( 'id', $user_id );
        if ( ! $user ) wp_send_json_error( array( 'message' => 'Gebruiker niet gevonden.' ) );

        $naam = $user->display_name;
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $user_id );

        wp_send_json_success( array( 'message' => "{$naam} afgewezen en verwijderd." ) );
    }

    /* ─────────────── AJAX: opdrachtgevers ophalen ─────────────── */
    public static function ajax_get_opdrachtgevers() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        wp_send_json_success( PSP_DB::get_opdrachtgevers() );
    }

    /* ─────────────── AJAX: opdrachtgever opslaan ─────────────── */
    public static function ajax_save_opdrachtgever() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();

        $id   = (int) ( $_POST['id'] ?? 0 );
        $data = array(
            'naam'          => sanitize_text_field( $_POST['naam']          ?? '' ),
            'contactpersoon'=> sanitize_text_field( $_POST['contactpersoon']?? '' ),
            'email'         => sanitize_email(      $_POST['email']         ?? '' ),
            'telefoon'      => sanitize_text_field( $_POST['telefoon']      ?? '' ),
            'adres'         => sanitize_text_field( $_POST['adres']         ?? '' ),
            'notities'      => sanitize_textarea_field( $_POST['notities']  ?? '' ),
        );
        if ( ! $data['naam'] ) wp_send_json_error( array( 'message' => 'Naam is verplicht.' ) );
        if ( $id ) $data['id'] = $id;
        $ok = PSP_DB::save_opdrachtgever( $data );
        if ( $ok === false ) wp_send_json_error( array( 'message' => 'Opslaan mislukt.' ) );
        wp_send_json_success( array( 'message' => "✓ Opgeslagen.", 'lijst' => PSP_DB::get_opdrachtgevers() ) );
    }

    /* ─────────────── AJAX: opdrachtgever verwijderen ─────────────── */
    public static function ajax_delete_opdrachtgever() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        $id = (int) ( $_POST['id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();
        PSP_DB::delete_opdrachtgever( $id );
        wp_send_json_success( array( 'message' => 'Verwijderd.', 'lijst' => PSP_DB::get_opdrachtgevers() ) );
    }

    /* ─────────────── AJAX: evenementen ─────────────── */
    public static function ajax_get_evenementen() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        global $wpdb;
        $tbl = PSP_TABLE_EVENEMENTEN;
        $og  = sanitize_text_field( $_POST['og'] ?? '' );
        if ( $og ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $tbl WHERE datum >= CURDATE() AND opdrachtgever = %s ORDER BY datum ASC, medewerker ASC",
                $og
            ) );
        } else {
            $rows = $wpdb->get_results(
                "SELECT * FROM $tbl WHERE datum >= CURDATE() ORDER BY datum ASC, opdrachtgever ASC, medewerker ASC"
            );
        }
        $ogs = $wpdb->get_col( "SELECT DISTINCT opdrachtgever FROM $tbl WHERE datum >= CURDATE() ORDER BY opdrachtgever ASC" );
        wp_send_json_success( array( 'items' => $rows, 'opdrachtgevers' => $ogs ) );
    }

    public static function ajax_save_evenement() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        global $wpdb;
        $tbl = PSP_TABLE_EVENEMENTEN;
        $id  = (int) ( $_POST['id'] ?? 0 );
        $data = array(
            'datum'         => sanitize_text_field( $_POST['datum']         ?? '' ),
            'opdrachtgever' => sanitize_text_field( $_POST['opdrachtgever'] ?? '' ),
            'medewerker'    => sanitize_text_field( $_POST['medewerker']    ?? '' ),
            'dienst_info'   => sanitize_text_field( $_POST['dienst_info']   ?? '' ),
            'notities'      => sanitize_textarea_field( $_POST['notities']  ?? '' ),
        );
        if ( $id ) {
            $wpdb->update( $tbl, $data, array( 'id' => $id ) );
        } else {
            $wpdb->insert( $tbl, $data );
            $id = $wpdb->insert_id;
        }
        wp_send_json_success( array( 'id' => $id ) );
    }

    public static function ajax_delete_evenement() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        global $wpdb;
        $id = (int) ( $_POST['id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();
        $wpdb->delete( PSP_TABLE_EVENEMENTEN, array( 'id' => $id ) );
        wp_send_json_success();
    }

    public static function ajax_week_evenementen() {
        check_ajax_referer('psp_dashboard', 'nonce');
        if ( ! current_user_can('edit_posts') ) wp_send_json_error();
        global $wpdb;
        $tbl   = PSP_TABLE_EVENEMENTEN;
        $start = sanitize_text_field( $_POST['week_start'] ?? '' );
        if ( ! $start ) wp_send_json_error();
        $end   = date( 'Y-m-d', strtotime( $start . ' +6 days' ) );
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $tbl WHERE datum BETWEEN %s AND %s ORDER BY datum ASC, opdrachtgever ASC, medewerker ASC",
            $start, $end
        ) );
        wp_send_json_success( array( 'items' => $rows ) );
    }
}
