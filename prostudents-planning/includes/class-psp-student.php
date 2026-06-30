<?php
defined('ABSPATH') || exit;

class PSP_Student {

    public static function init() {
        add_shortcode('psp_mijn_rooster', [self::class, 'shortcode']);
        add_action('wp_ajax_psp_mijn_diensten', [self::class, 'ajax_mijn_diensten']);
        add_action('wp_ajax_psp_wb_bevestig',   [self::class, 'ajax_wb_bevestig']);
    }

    public static function shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<div class="psp-rooster-login"><p>Je moet ingelogd zijn om je rooster te bekijken.</p>'
                 . '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="psp-rooster-btn">Inloggen</a></div>';
        }

        $user  = wp_get_current_user();
        $email = $user->user_email;

        // ── Werkbevestigingen server-side laden ──
        $wbs_raw    = PSP_DB::get_wbs_voor_student( $email );
        $wbs_open   = array_filter( $wbs_raw, function( $w ) { return $w->status === 'verzonden'; } );
        $wbs_open   = array_values( $wbs_open );

        ob_start();
        ?>
        <div class="psp-rooster-wrap" id="psp-mijn-rooster">

            <div class="psp-rooster-header">
                <div>
                    <p class="psp-rooster-welkom">Hallo, <strong><?php echo esc_html( $user->display_name ); ?></strong></p>
                </div>
                <a href="<?php echo esc_url( home_url('/beschikbaarheid/') ); ?>" class="psp-rooster-btn">
                    + Beschikbaarheid doorgeven
                </a>
            </div>

            <?php if ( ! empty( $wbs_open ) ) : ?>
            <!-- ── Openstaande werkbevestigingen ── -->
            <div class="psp-wb-sectie" id="psp-wb-sectie">
                <div class="psp-wb-banner">
                    <span class="psp-wb-banner-icon">&#128231;</span>
                    <div>
                        <strong>Openstaande werkbevestiging(en)</strong>
                        <p style="margin:2px 0 0;font-size:.85rem;color:#555">Lees onderstaande werkbevestiging(en) en bevestig dat je ze hebt gelezen.</p>
                    </div>
                </div>

                <?php foreach ( $wbs_open as $wb ) :
                    $datum_nl = $wb->datum ? date_i18n( 'l j F Y', strtotime( $wb->datum ) ) : '';
                    $van      = $wb->tijdstip_van ? substr( $wb->tijdstip_van, 0, 5 ) : '';
                    $tot      = $wb->tijdstip_tot ? substr( $wb->tijdstip_tot, 0, 5 ) : '';
                ?>
                <div class="psp-wb-kaart-student" id="psp-wb-kaart-<?php echo (int) $wb->id; ?>">
                    <div class="psp-wb-kaart-meta">
                        <span><strong><?php echo esc_html( $wb->opdrachtgever ); ?></strong></span>
                        <?php if ( $datum_nl ) : ?>
                        <span><?php echo esc_html( $datum_nl ); ?><?php if ( $van ) echo ' &middot; ' . esc_html($van) . '&ndash;' . esc_html($tot); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $wb->onderwerp ) : ?>
                    <div class="psp-wb-kaart-onderwerp">&#128231; <?php echo esc_html( $wb->onderwerp ); ?></div>
                    <?php endif; ?>
                    <div class="psp-wb-kaart-inhoud">
                        <pre style="white-space:pre-wrap;font-family:inherit;margin:0"><?php echo esc_html( $wb->inhoud ); ?></pre>
                    </div>
                    <div class="psp-wb-kaart-footer">
                        <button class="psp-rooster-btn psp-wb-bevestig-btn"
                                data-id="<?php echo (int) $wb->id; ?>"
                                data-nonce="<?php echo esc_attr( wp_create_nonce('psp_student') ); ?>">
                            &#10003; Gelezen en akkoord
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="psp-rooster-tabs">
                <button class="psp-rtab active" data-tab="komend">Komende diensten</button>
                <button class="psp-rtab" data-tab="verleden">Verleden</button>
            </div>

            <div id="psp-rtab-komend" class="psp-rtab-panel">
                <div id="psp-diensten-komend"><p class="psp-rooster-laden">Rooster laden...</p></div>
            </div>
            <div id="psp-rtab-verleden" class="psp-rtab-panel" style="display:none">
                <div id="psp-diensten-verleden"><p class="psp-rooster-laden">Laden...</p></div>
            </div>

            <!-- Detailmodal -->
            <div id="psp-dienst-modal" class="psp-student-modal" style="display:none">
              <div class="psp-student-modal-box">
                <div class="psp-student-modal-header">
                  <h3 id="psp-dm-titel">Dienstdetails</h3>
                  <button class="psp-student-modal-sluit" id="psp-dm-sluit">&#10005;</button>
                </div>
                <div class="psp-student-modal-body" id="psp-dm-info"></div>
              </div>
            </div>

            <?php wp_nonce_field('psp_student', 'psp_student_nonce'); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ─── AJAX: mijn diensten ─── */
    public static function ajax_mijn_diensten() {
        check_ajax_referer('psp_student', 'nonce');
        if ( ! is_user_logged_in() ) wp_send_json_error();

        global $wpdb;
        $email = wp_get_current_user()->user_email;
        $today = current_time('Y-m-d');

        $diensten = $wpdb->get_results( $wpdb->prepare(
            "SELECT d.id, d.datum, d.tijdstip_van, d.tijdstip_tot,
                    d.opdrachtgever, d.type_werk, d.locatie, d.status,
                    d.omschrijving, d.titel
             FROM   " . PSP_TABLE_DIENSTEN . " d
             JOIN   " . PSP_TABLE_KOPPELINGEN . " k ON k.dienst_id = d.id
             JOIN   " . PSP_TABLE_BESCHIKBAARHEID . " b ON b.id = k.beschikbaarheid_id
             WHERE  b.email = %s
             ORDER  BY d.datum ASC, d.tijdstip_van ASC",
            $email
        ) );

        $komend = array(); $verleden = array();
        foreach ( $diensten as $d ) {
            $item = array(
                'id'            => (int) $d->id,
                'datum_nl'      => date_i18n( 'l j F Y', strtotime( $d->datum ) ),
                'van'           => substr( $d->tijdstip_van, 0, 5 ),
                'tot'           => substr( $d->tijdstip_tot, 0, 5 ),
                'opdrachtgever' => $d->opdrachtgever,
                'type_werk'     => $d->type_werk,
                'locatie'       => $d->locatie,
                'omschrijving'  => $d->omschrijving,
                'titel'         => $d->titel,
                'status'        => $d->status,
            );
            if ( $d->datum >= $today ) $komend[]   = $item;
            else                       $verleden[] = $item;
        }

        wp_send_json_success( array(
            'komend'   => $komend,
            'verleden' => array_reverse( $verleden ),
        ) );
    }

    /* ─── AJAX: werkbevestiging bevestigen ─── */
    public static function ajax_wb_bevestig() {
        // Accepteer zowel losse nonce per knop als globale nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'psp_student' ) ) wp_send_json_error( array('message' => 'Sessie verlopen, herlaad de pagina.') );
        if ( ! is_user_logged_in() ) wp_send_json_error();

        $id    = (int) ( isset($_POST['wb_id']) ? $_POST['wb_id'] : 0 );
        $email = wp_get_current_user()->user_email;
        if ( ! $id ) wp_send_json_error( array( 'message' => 'Ongeldig ID.' ) );

        $ok = PSP_DB::bevestig_wb( $id, $email );
        if ( $ok === false ) wp_send_json_error( array( 'message' => 'Bevestigen mislukt.' ) );
        wp_send_json_success( array( 'message' => '✓ Bevestigd.' ) );
    }
}
