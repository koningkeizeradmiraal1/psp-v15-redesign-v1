<?php
defined('ABSPATH') || exit;

class PSP_Frontend {

    public static function vaardigheden_lijst() {
        return array(
            'catering'           => 'Catering',
            'lopen_met_3_borden' => 'Lopen met 3 borden',
            'lopen_met_plateau'  => 'Lopen met plateau',
            'housekeeping'       => 'Housekeeping',
            'schoonmaak'         => 'Schoonmaak',
            'productiewerk'      => 'Productiewerk',
            'inpakwerkzaamheden' => 'Inpakwerkzaamheden',
            'bediening'          => 'Bediening',
            'bar'                => 'Bar',
        );
    }

    public static function init(): void {
        add_shortcode('ps_beschikbaarheid', [self::class, 'shortcode']);
        add_action('wp_ajax_nopriv_psp_submit_beschikbaarheid', [self::class, 'ajax_submit']);
        add_action('wp_ajax_psp_submit_beschikbaarheid',        [self::class, 'ajax_submit']);
    }

    public static function shortcode(): string {
        ob_start();
        // Bereken standaard week (aanstaande maandag)
        $dt  = new DateTime();
        $dow = (int)$dt->format('N');
        if ($dow === 1) { $dt->modify('+7 days'); }
        else            { $dt->modify('+' . (8 - $dow) . ' days'); }
        $default_week = $dt->format('Y-m-d');

        // Bouw 8 weken vooruit
        $weeks = [];
        $wk = new DateTime($default_week);
        for ($i = 0; $i < 8; $i++) {
            $label   = 'Week ' . $wk->format('W') . ' — ' . date_i18n('j M', $wk->getTimestamp()) . ' t/m ' . date_i18n('j M Y', $wk->getTimestamp() + 4 * 86400);
            $weeks[$wk->format('Y-m-d')] = $label;
            $wk->modify('+7 days');
        }

        $dagen = ['ma'=>'Maandag','di'=>'Dinsdag','wo'=>'Woensdag','do'=>'Donderdag','vr'=>'Vrijdag','za'=>'Zaterdag'];
        ?>
        <?php
        $is_student = is_user_logged_in() && current_user_can('psp_student');
        $wp_user    = $is_student ? wp_get_current_user() : null;
        $prefil_naam  = $wp_user ? $wp_user->display_name : '';
        $prefil_email = $wp_user ? $wp_user->user_email   : '';
        ?>
        <div class="psp-form-wrap" id="psp-beschikbaarheid">
            <h2 class="psp-form-title">Beschikbaarheid opgeven</h2>
            <?php if ( $is_student ): ?>
            <p class="psp-form-intro">Geef hieronder aan wanneer je beschikbaar bent. We plannen je zo snel mogelijk in.</p>
            <?php else: ?>
            <p class="psp-form-intro">Vul je naam, e-mail en beschikbaarheid in. Zodra je wordt ingepland ontvang je een bevestigingsmail.</p>
            <?php endif; ?>

            <form id="psp-form" novalidate>
                <?php wp_nonce_field('psp_frontend', 'psp_nonce'); ?>

                <div class="psp-row psp-row-2">
                    <div class="psp-field">
                        <label for="psp-naam">Naam *</label>
                        <input type="text" id="psp-naam" name="naam" required
                               placeholder="Jan de Vries"
                               value="<?php echo esc_attr( $prefil_naam ); ?>"
                               <?php echo $is_student ? 'readonly class="psp-field-readonly"' : ''; ?>>
                    </div>
                    <div class="psp-field">
                        <label for="psp-telefoon">Telefoonnummer</label>
                        <input type="tel" id="psp-telefoon" name="telefoon" placeholder="06-12345678">
                    </div>
                </div>

                <div class="psp-field">
                    <label for="psp-email">E-mailadres *</label>
                    <input type="email" id="psp-email" name="email" required
                           placeholder="jouw@email.nl"
                           value="<?php echo esc_attr( $prefil_email ); ?>"
                           <?php echo $is_student ? 'readonly class="psp-field-readonly"' : ''; ?>>
                </div>

                <div class="psp-field">
                    <label for="psp-week">Week *</label>
                    <select id="psp-week" name="week_start" required>
                        <?php foreach ($weeks as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="psp-field">
                    <label>Beschikbaarheid per dag *</label>
                    <p class="psp-field-hint">Vink de dagen aan waarop je beschikbaar bent en vul de tijden in.</p>
                    <div class="psp-dagen">
                        <div class="psp-dag-header">
                            <span></span><span>Van</span><span>Tot</span>
                        </div>
                        <?php foreach ($dagen as $key => $label): ?>
                        <div class="psp-dag-row" data-dag="<?php echo esc_attr($key); ?>">
                            <label class="psp-dag-check">
                                <input type="checkbox" class="psp-dag-checkbox" name="dag_<?php echo esc_attr($key); ?>" value="1">
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                            <input type="time" name="dag_<?php echo esc_attr($key); ?>_van" class="psp-time" value="09:00" disabled>
                            <input type="time" name="dag_<?php echo esc_attr($key); ?>_tot" class="psp-time" value="17:00" disabled>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ( ! $is_student ): ?>
                <div class="psp-field">
                    <label>Mijn ervaringen</label>
                    <p class="psp-field-hint">Vink aan waar je ervaring mee hebt.</p>
                    <div class="psp-vaardigheden">
                        <?php foreach (self::vaardigheden_lijst() as $key => $label): ?>
                        <label class="psp-vaardigheid-check">
                            <input type="checkbox" name="vaardigheden[]" value="<?php echo esc_attr($key); ?>">
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="psp-field">
                    <label for="psp-voorkeur">Voorkeur / opmerkingen</label>
                    <textarea id="psp-voorkeur" name="voorkeur" rows="3" placeholder="bijv. voorkeur horeca, liever geen vroege ochtenden…"></textarea>
                </div>

                <div id="psp-error" class="psp-notice psp-notice-error" style="display:none"></div>

                <button type="submit" class="psp-btn" id="psp-submit">
                    <span class="psp-btn-text">Beschikbaarheid doorgeven</span>
                    <span class="psp-btn-loading" style="display:none">Versturen…</span>
                </button>
            </form>

            <div id="psp-succes" class="psp-notice psp-notice-success" style="display:none">
                <strong>✓ Ontvangen!</strong> Bedankt, we nemen contact op zodra we je inplannen. Je ontvangt ook een bevestiging per e-mail.
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_submit(): void {
        check_ajax_referer('psp_frontend', 'psp_nonce');

        $naam  = sanitize_text_field($_POST['naam']  ?? '');
        $email = sanitize_email($_POST['email']      ?? '');
        $week  = sanitize_text_field($_POST['week_start'] ?? '');

        if (!$naam || !is_email($email) || !$week) {
            wp_send_json_error(['message' => 'Vul alle verplichte velden in.']);
        }

        $dag_keys = ['ma','di','wo','do','vr','za','zo'];
        $dagen    = [];
        $heeft_dag = false;
        foreach ($dag_keys as $dag) {
            if (!empty($_POST["dag_{$dag}"])) {
                $van = sanitize_text_field($_POST["dag_{$dag}_van"] ?? '09:00');
                $tot = sanitize_text_field($_POST["dag_{$dag}_tot"] ?? '17:00');
                if ($van && $tot && $van < $tot) {
                    $dagen[$dag]  = ['van' => $van, 'tot' => $tot];
                    $heeft_dag    = true;
                }
            }
        }

        if (!$heeft_dag) {
            wp_send_json_error(['message' => 'Vink minimaal één beschikbare dag aan.']);
        }

        $toegestaan   = array_keys(self::vaardigheden_lijst());
        $vaardigheden = [];
        // Student kan geen vaardigheden instellen — haal ze op uit zijn gebruikersprofiel
        if ( is_user_logged_in() && current_user_can('psp_student') ) {
            $opgeslagen = get_user_meta( get_current_user_id(), 'psp_vaardigheden', true );
            if ( is_array($opgeslagen) ) {
                foreach ( $opgeslagen as $v ) {
                    $v = sanitize_key($v);
                    if ( in_array($v, $toegestaan, true) ) $vaardigheden[] = $v;
                }
            }
        } elseif (!empty($_POST['vaardigheden']) && is_array($_POST['vaardigheden'])) {
            foreach ($_POST['vaardigheden'] as $v) {
                $v = sanitize_key($v);
                if (in_array($v, $toegestaan, true)) $vaardigheden[] = $v;
            }
        }

        $id = PSP_DB::insert_beschikbaarheid([
            'naam'       => $naam,
            'email'      => $email,
            'telefoon'   => sanitize_text_field($_POST['telefoon'] ?? ''),
            'week_start' => $week,
            'dagen'      => $dagen,
            'vaardigheden' => $vaardigheden,
            'voorkeur'   => sanitize_textarea_field($_POST['voorkeur'] ?? ''),
        ]);

        if (!$id) {
            wp_send_json_error(['message' => 'Er ging iets mis. Probeer het opnieuw.']);
        }

        // Bevestiging e-mail naar student
        $record = PSP_DB::get_beschikbaarheid_by_id($id);
        if ($record) PSP_Mail::stuur_bevestiging_aan_student($record);

        wp_send_json_success(['message' => 'Beschikbaarheid ontvangen!']);
    }
}
