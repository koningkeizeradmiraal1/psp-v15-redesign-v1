<?php
defined('ABSPATH') || exit;

class PSP_Admin {

    public static function init(): void {
        add_action('admin_menu',                   [self::class, 'menu']);
        add_action('wp_ajax_psp_koppel_student',   [self::class, 'ajax_koppel']);
        add_action('wp_ajax_psp_verwijder_koppeling', [self::class, 'ajax_verwijder_koppeling']);
        add_action('admin_post_psp_save_dienst',   [self::class, 'save_dienst']);
    }

    public static function menu(): void {
        add_menu_page('Planning', 'Planning', 'manage_options', 'psp-weekoverzicht', [self::class, 'page_weekoverzicht'], 'dashicons-calendar-alt', 26);
        add_submenu_page('psp-weekoverzicht', 'Weekoverzicht', 'Weekoverzicht', 'manage_options', 'psp-weekoverzicht', [self::class, 'page_weekoverzicht']);
        add_submenu_page('psp-weekoverzicht', 'Diensten',      'Diensten',      'manage_options', 'psp-diensten',      [self::class, 'page_diensten']);
        add_submenu_page('psp-weekoverzicht', 'Beschikbaarheid', 'Beschikbaarheid', 'manage_options', 'psp-beschikbaarheid', [self::class, 'page_beschikbaarheid']);
    }

    /* ═══ WEEKOVERZICHT ═══ */
    public static function page_weekoverzicht(): void {
        $week_start = sanitize_text_field($_GET['week'] ?? '');
        if (!$week_start) {
            $dt  = new DateTime();
            $dow = (int)$dt->format('N');
            $dt->modify('-' . ($dow - 1) . ' days');
            $week_start = $dt->format('Y-m-d');
        }
        $week_dt   = new DateTime($week_start);
        $prev_week = (clone $week_dt)->modify('-7 days')->format('Y-m-d');
        $next_week = (clone $week_dt)->modify('+7 days')->format('Y-m-d');

        $dag_keys = ['ma','di','wo','do','vr','za'];
        $dag_names= ['ma'=>'Ma','di'=>'Di','wo'=>'Wo','do'=>'Do','vr'=>'Vr','za'=>'Za'];
        $dates    = [];
        $wk       = clone $week_dt;
        foreach ($dag_keys as $dk) {
            $dates[$dk] = $wk->format('Y-m-d');
            $wk->modify('+1 day');
        }

        $rows    = PSP_DB::get_beschikbaarheid_by_week($week_start);
        $diensten= PSP_DB::get_diensten();

        // Bouw lookup: datum → dienst
        $dienst_per_datum = [];
        foreach ($diensten as $d) { $dienst_per_datum[$d->datum][] = $d; }

        // Bouw lookup: beschikbaarheid_id → koppelingen
        $gekoppeld = [];
        foreach ($rows as $row) {
            $kops = PSP_DB::get_koppelingen_voor_beschikbaarheid($row->id);
            foreach ($kops as $k) { $gekoppeld[$row->id][$k->dienst_id] = $k; }
        }

        ?>
        <div class="wrap psp-wrap">
          <h1 class="psp-page-title">📅 Weekoverzicht</h1>

          <div class="psp-week-nav">
            <a class="button" href="?page=psp-weekoverzicht&week=<?= esc_attr($prev_week) ?>">← Vorige week</a>
            <span class="psp-week-label">Week van <?= date_i18n('j F Y', strtotime($week_start)) ?></span>
            <a class="button" href="?page=psp-weekoverzicht&week=<?= esc_attr($next_week) ?>">Volgende week →</a>
            <a class="button button-primary" href="<?= admin_url('admin.php?page=psp-diensten&actie=nieuw') ?>">+ Nieuwe dienst</a>
          </div>

          <?php if (empty($rows)): ?>
            <div class="psp-empty">Geen beschikbaarheid ingediend voor deze week.</div>
          <?php else: ?>
          <div class="psp-grid-wrap">
            <table class="psp-grid">
              <thead>
                <tr>
                  <th class="psp-col-naam">Student</th>
                  <?php foreach ($dag_keys as $dk): ?>
                  <th class="psp-col-dag">
                    <?= esc_html($dag_names[$dk]) ?><br>
                    <small><?= date_i18n('j M', strtotime($dates[$dk])) ?></small>
                    <?php if (!empty($dienst_per_datum[$dates[$dk]])): ?>
                      <div class="psp-dag-diensten">
                        <?php foreach ($dienst_per_datum[$dates[$dk]] as $d): ?>
                          <span class="psp-dienst-chip <?= $d->status === 'vervuld' ? 'vervuld' : '' ?>"
                                title="<?= esc_attr("{$d->opdrachtgever} {$d->tijdstip_van}–{$d->tijdstip_tot}") ?>">
                            <?= esc_html(mb_strimwidth($d->titel, 0, 14, '…')) ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </th>
                  <?php endforeach; ?>
                  <th>Acties</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                <?php $dagen = json_decode($row->dagen, true) ?: []; ?>
                <tr class="psp-student-row">
                  <td class="psp-col-naam">
                    <strong><?= esc_html($row->naam) ?></strong><br>
                    <small><?= esc_html($row->email) ?></small>
                    <?php if ($row->telefoon): ?><br><small>📞 <?= esc_html($row->telefoon) ?></small><?php endif; ?>
                    <?php if ($row->voorkeur): ?><br><small class="psp-voorkeur" title="<?= esc_attr($row->voorkeur) ?>">💬 Voorkeur</small><?php endif; ?>
                  </td>
                  <?php foreach ($dag_keys as $dk):
                    $dag   = $dagen[$dk] ?? null;
                    $datum = $dates[$dk];
                    $kop   = null;
                    if (!empty($gekoppeld[$row->id])) {
                        foreach ($gekoppeld[$row->id] as $did => $k) {
                            $d = PSP_DB::get_dienst_by_id($did);
                            if ($d && $d->datum === $datum) { $kop = ['dienst'=>$d,'koppeling'=>$k]; break; }
                        }
                    }
                  ?>
                  <td class="psp-cell <?= $dag ? ($kop ? 'psp-cell-ingepland' : 'psp-cell-beschikbaar') : 'psp-cell-leeg' ?>">
                    <?php if ($kop): ?>
                      <div class="psp-ingepland">
                        🟢 <?= esc_html($kop['dienst']->tijdstip_van . '–' . $kop['dienst']->tijdstip_tot) ?><br>
                        <small><?= esc_html($kop['dienst']->opdrachtgever) ?></small>
                        <button class="psp-btn-tiny psp-verwijder-koppeling"
                                data-dienst-id="<?= $kop['dienst']->id ?>"
                                title="Koppeling verwijderen">✕</button>
                      </div>
                    <?php elseif ($dag): ?>
                      <div class="psp-beschikbaar">
                        <?= esc_html($dag['van'] . '–' . $dag['tot']) ?><br>
                        <?php
                        // Toon open diensten op deze dag
                        $open_diensten = array_filter( isset($dienst_per_datum[$datum]) ? $dienst_per_datum[$datum] : [], function($d) { return $d->status === 'open'; });
                        foreach ($open_diensten as $od): ?>
                          <button class="psp-btn-tiny psp-koppel-btn"
                                  data-beschikbaarheid-id="<?= $row->id ?>"
                                  data-dienst-id="<?= $od->id ?>"
                                  data-naam="<?= esc_attr($row->naam) ?>"
                                  data-dienst="<?= esc_attr($od->titel . ' ' . $od->tijdstip_van . '–' . $od->tijdstip_tot) ?>">
                            📌 <?= esc_html(mb_strimwidth($od->titel, 0, 12, '…')) ?>
                          </button>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <span class="psp-nvt">—</span>
                    <?php endif; ?>
                  </td>
                  <?php endforeach; ?>
                  <td>
                    <a href="mailto:<?= esc_attr($row->email) ?>" class="button button-small">✉</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

          <div id="psp-koppel-bevestiging" class="psp-overlay" style="display:none">
            <div class="psp-overlay-box">
              <p id="psp-koppel-vraag"></p>
              <button class="button button-primary" id="psp-koppel-ok">Bevestigen + mail sturen</button>
              <button class="button" id="psp-koppel-annuleer">Annuleren</button>
            </div>
          </div>
        </div>
        <?php
    }

    /* ═══ DIENSTEN ═══ */
    public static function page_diensten(): void {
        $actie = sanitize_key($_GET['actie'] ?? 'lijst');
        if ($actie === 'nieuw' || $actie === 'bewerk') {
            self::form_dienst();
        } else {
            self::lijst_diensten();
        }
    }

    private static function lijst_diensten(): void {
        $diensten = PSP_DB::get_diensten();
        ?>
        <div class="wrap psp-wrap">
          <h1 class="psp-page-title">🗓 Diensten
            <a href="<?= admin_url('admin.php?page=psp-diensten&actie=nieuw') ?>" class="page-title-action">+ Nieuw</a>
          </h1>
          <?php if (isset($_GET['opgeslagen'])): ?>
            <div class="notice notice-success is-dismissible"><p>✓ Dienst opgeslagen.</p></div>
          <?php endif; ?>
          <?php if (isset($_GET['verwijderd'])): ?>
            <div class="notice notice-success is-dismissible"><p>✓ Koppeling verwijderd.</p></div>
          <?php endif; ?>

          <?php if (empty($diensten)): ?>
            <div class="psp-empty">Nog geen diensten. <a href="?page=psp-diensten&actie=nieuw">Maak de eerste aan →</a></div>
          <?php else: ?>
          <table class="wp-list-table widefat striped">
            <thead>
              <tr>
                <th>Datum</th><th>Dienst</th><th>Opdrachtgever</th><th>Tijd</th><th>Locatie</th><th>Status</th><th>Ingepland</th><th>Acties</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($diensten as $d):
                $koppeling = PSP_DB::get_koppeling_voor_dienst($d->id); ?>
              <tr>
                <td><?= date_i18n('j M Y', strtotime($d->datum)) ?></td>
                <td><strong><?= esc_html($d->titel) ?></strong>
                  <?php if ($d->type_werk): ?><br><small><?= esc_html($d->type_werk) ?></small><?php endif; ?></td>
                <td><?= esc_html($d->opdrachtgever) ?></td>
                <td><?= esc_html($d->tijdstip_van . ' – ' . $d->tijdstip_tot) ?></td>
                <td><?= esc_html($d->locatie) ?></td>
                <td><span class="psp-status-badge psp-status-<?= esc_attr($d->status) ?>"><?= esc_html($d->status) ?></span></td>
                <td>
                  <?php if ($koppeling): ?>
                    <strong><?= esc_html($koppeling->naam) ?></strong><br>
                    <small><?= esc_html($koppeling->email) ?></small>
                    <?php if ($koppeling->notificatie_verzonden): ?><span title="Mail verzonden" style="color:green">✓ Mail</span><?php endif; ?>
                  <?php else: ?>
                    <em style="color:#999">—</em>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="?page=psp-diensten&actie=bewerk&id=<?= $d->id ?>" class="button button-small">Bewerk</a>
                  <a href="?page=psp-weekoverzicht&week=<?= PSP_DB::week_start_for_datum($d->datum) ?>" class="button button-small">In rooster</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
        <?php
    }

    private static function form_dienst(): void {
        $id     = (int)($_GET['id'] ?? 0);
        $dienst = $id ? PSP_DB::get_dienst_by_id($id) : null;
        ?>
        <div class="wrap psp-wrap">
          <h1 class="psp-page-title"><?= $dienst ? 'Dienst bewerken' : 'Nieuwe dienst' ?></h1>
          <form method="post" action="<?= admin_url('admin-post.php') ?>" class="psp-admin-form">
            <?php wp_nonce_field('psp_save_dienst', 'psp_dienst_nonce'); ?>
            <input type="hidden" name="action" value="psp_save_dienst">
            <input type="hidden" name="dienst_id" value="<?= $id ?>">

            <table class="form-table">
              <tr><th><label for="psp-titel">Naam dienst *</label></th>
                <td><input type="text" id="psp-titel" name="titel" required class="regular-text" value="<?= esc_attr($dienst->titel ?? '') ?>" placeholder="bijv. Bediening diner"></td></tr>
              <tr><th><label>Opdrachtgever *</label></th>
                <td><input type="text" name="opdrachtgever" required class="regular-text" value="<?= esc_attr($dienst->opdrachtgever ?? '') ?>" placeholder="Restaurantnaam / Bedrijf"></td></tr>
              <tr><th><label>Datum *</label></th>
                <td><input type="date" name="datum" required value="<?= esc_attr($dienst->datum ?? '') ?>"></td></tr>
              <tr><th><label>Tijdstip</label></th>
                <td>
                  Van <input type="time" name="tijdstip_van" value="<?= esc_attr($dienst->tijdstip_van ?? '09:00') ?>">
                  Tot <input type="time" name="tijdstip_tot" value="<?= esc_attr($dienst->tijdstip_tot ?? '17:00') ?>">
                </td></tr>
              <tr><th><label>Locatie</label></th>
                <td><input type="text" name="locatie" class="regular-text" value="<?= esc_attr($dienst->locatie ?? '') ?>" placeholder="Adres of afdeling"></td></tr>
              <tr><th><label>Type werk</label></th>
                <td><input type="text" name="type_werk" class="regular-text" value="<?= esc_attr($dienst->type_werk ?? '') ?>" placeholder="bijv. Bediening, Productie"></td></tr>
              <tr><th><label>Omschrijving</label></th>
                <td><textarea name="omschrijving" rows="4" class="large-text"><?= esc_textarea($dienst->omschrijving ?? '') ?></textarea></td></tr>
            </table>
            <?php submit_button($dienst ? 'Opslaan' : 'Dienst aanmaken'); ?>
            <a href="<?= admin_url('admin.php?page=psp-diensten') ?>" class="button">Annuleren</a>
          </form>
        </div>
        <?php
    }

    public static function save_dienst(): void {
        check_admin_referer('psp_save_dienst', 'psp_dienst_nonce');
        if (!current_user_can('manage_options')) wp_die('Geen toegang');

        $id   = (int)($_POST['dienst_id'] ?? 0);
        $data = [
            'titel'         => sanitize_text_field($_POST['titel']         ?? ''),
            'opdrachtgever' => sanitize_text_field($_POST['opdrachtgever'] ?? ''),
            'datum'         => sanitize_text_field($_POST['datum']          ?? ''),
            'tijdstip_van'  => sanitize_text_field($_POST['tijdstip_van']  ?? '09:00'),
            'tijdstip_tot'  => sanitize_text_field($_POST['tijdstip_tot']  ?? '17:00'),
            'locatie'       => sanitize_text_field($_POST['locatie']        ?? ''),
            'type_werk'     => sanitize_text_field($_POST['type_werk']      ?? ''),
            'omschrijving'  => sanitize_textarea_field($_POST['omschrijving'] ?? ''),
        ];

        if ($id) { PSP_DB::update_dienst($id, $data); }
        else      { PSP_DB::insert_dienst($data); }

        wp_redirect(admin_url('admin.php?page=psp-diensten&opgeslagen=1'));
        exit;
    }

    /* ═══ BESCHIKBAARHEID LIJST ═══ */
    public static function page_beschikbaarheid(): void {
        $week_start = sanitize_text_field($_GET['week'] ?? date('Y-m-d', strtotime('monday this week')));
        $rows = PSP_DB::get_beschikbaarheid_by_week($week_start);
        ?>
        <div class="wrap psp-wrap">
          <h1 class="psp-page-title">👤 Ingediende beschikbaarheid</h1>
          <p>Week van <?= date_i18n('j F Y', strtotime($week_start)) ?> — <?= count($rows) ?> student(en)</p>
          <?php if (empty($rows)): ?>
            <div class="psp-empty">Geen beschikbaarheid voor deze week.</div>
          <?php else: ?>
          <table class="wp-list-table widefat striped">
            <thead><tr><th>Naam</th><th>E-mail</th><th>Tel</th><th>Ma</th><th>Di</th><th>Wo</th><th>Do</th><th>Vr</th><th>Za</th><th>Ingepland</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $row):
                $dagen = json_decode($row->dagen, true) ?: [];
                $kops  = PSP_DB::get_koppelingen_voor_beschikbaarheid($row->id);
              ?>
              <tr>
                <td><strong><?= esc_html($row->naam) ?></strong>
                  <?php if ($row->voorkeur): ?><br><small title="<?= esc_attr($row->voorkeur) ?>">💬 Voorkeur aanwezig</small><?php endif; ?></td>
                <td><a href="mailto:<?= esc_attr($row->email) ?>"><?= esc_html($row->email) ?></a></td>
                <td><?= esc_html($row->telefoon) ?></td>
                <?php foreach (['ma','di','wo','do','vr','za'] as $dk):
                  $d = $dagen[$dk] ?? null; ?>
                <td><?= $d ? esc_html("{$d['van']}–{$d['tot']}") : '<span style="color:#ccc">—</span>' ?></td>
                <?php endforeach; ?>
                <td><?php foreach ($kops as $k): ?>
                  <div><?= date_i18n('j M', strtotime($k->datum)) ?>: <?= esc_html($k->titel) ?></div>
                <?php endforeach; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
        <?php
    }

    /* ═══ AJAX ═══ */
    public static function ajax_koppel(): void {
        check_ajax_referer('psp_admin', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Geen toegang']);

        $bid = (int)($_POST['beschikbaarheid_id'] ?? 0);
        $did = (int)($_POST['dienst_id']          ?? 0);
        if (!$bid || !$did) wp_send_json_error(['message' => 'Ongeldige ID']);

        $beschikbaarheid = PSP_DB::get_beschikbaarheid_by_id($bid);
        $dienst          = PSP_DB::get_dienst_by_id($did);
        if (!$beschikbaarheid || !$dienst) wp_send_json_error(['message' => 'Niet gevonden']);

        $koppeling_id = PSP_DB::insert_koppeling($bid, $did);
        if (!$koppeling_id) wp_send_json_error(['message' => 'Koppeling al aanwezig of mislukt']);

        PSP_DB::update_dienst($did, ['status' => 'vervuld']);

        $mail_ok = PSP_Mail::stuur_koppeling_bevestiging($beschikbaarheid, $dienst, $koppeling_id);

        wp_send_json_success([
            'message'  => "✓ {$beschikbaarheid->naam} gekoppeld aan '{$dienst->titel}'." . ($mail_ok ? ' Mail verzonden.' : ' Mail mislukt.'),
            'mail_ok'  => $mail_ok,
            'naam'     => $beschikbaarheid->naam,
            'email'    => $beschikbaarheid->email,
        ]);
    }

    public static function ajax_verwijder_koppeling(): void {
        check_ajax_referer('psp_admin', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        $did = (int)($_POST['dienst_id'] ?? 0);
        if (!$did) wp_send_json_error();
        PSP_DB::verwijder_koppeling($did);
        wp_send_json_success(['message' => 'Koppeling verwijderd']);
    }
}
