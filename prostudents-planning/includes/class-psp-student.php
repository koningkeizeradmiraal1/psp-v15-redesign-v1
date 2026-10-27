<?php
defined('ABSPATH') || exit;

class PSP_Student {

    public static function init() {
        add_shortcode('psp_mijn_rooster', [self::class, 'shortcode']);
        add_action('wp_ajax_psp_mijn_diensten', [self::class, 'ajax_mijn_diensten']);
        add_action('wp_ajax_psp_wb_bevestig',   [self::class, 'ajax_wb_bevestig']);
    }

    /** Navigatie voor studenten: Beschikbaarheid | Mijn rooster. $actief = 'beschikbaarheid' of 'rooster'. */
    public static function nav_html( string $actief ): string {
        $items = array(
            'beschikbaarheid' => array( '&#128197; Beschikbaarheid', home_url('/beschikbaarheid/') ),
            'rooster'         => array( '&#128203; Mijn rooster',    home_url('/mijn-rooster/') ),
        );
        $html = '<nav class="psp-student-nav" style="display:flex;gap:6px;margin:0 0 22px;border-bottom:2px solid #eee">';
        foreach ( $items as $key => $it ) {
            $aan  = ( $key === $actief );
            $css  = 'padding:10px 18px;text-decoration:none;font-weight:700;font-size:.95rem;border-bottom:3px solid '
                  . ( $aan ? '#d31775' : 'transparent' ) . ';margin-bottom:-2px;color:' . ( $aan ? '#d31775' : '#666' );
            $html .= '<a href="' . esc_url( $it[1] ) . '" style="' . $css . '"' . ( $aan ? ' aria-current="page"' : '' ) . '>' . $it[0] . '</a>';
        }
        return $html . '</nav>';
    }

    /** Kaart met de twee links om uren door te geven (gebruikt in Mijn Rooster en het beschikbaarheidsformulier). */
    public static function uren_links_html(): string {
        $links = array(
            'ProStudents' => 'https://prostudents.easyflex2go.nl/login',
            'Proattent'   => 'https://proattent.easyflex2go.nl/login',
        );
        $btn = 'display:inline-block;background:#d31775;color:#fff;text-decoration:none;font-weight:700;'
             . 'padding:9px 18px;border-radius:6px;font-size:.9rem;margin:4px 8px 4px 0';
        $html  = '<div class="psp-uren-links" style="background:#fff8fc;border:1px solid #f3c6dc;border-radius:8px;padding:14px 18px;margin:0 0 22px">';
        $html .= '<strong style="display:block;margin-bottom:2px">&#9200; Uren doorgeven</strong>';
        $html .= '<p style="margin:0 0 8px;color:#666;font-size:.85rem">Kies hieronder het bedrijf waarvoor je hebt gewerkt en geef je uren door.</p>';
        foreach ( $links as $naam => $url ) {
            $html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" style="' . $btn . '">Uren doorgeven &mdash; ' . esc_html( $naam ) . ' &rarr;</a>';
        }
        $html .= '</div>';
        return $html;
    }

    /** Verwerk een bevestigingslink uit de mail (?wb_bevestig=TOKEN). Geeft een melding (HTML) terug, of ''. */
    private static function verwerk_bevestig_token(): string {
        if ( empty( $_GET['wb_bevestig'] ) ) return '';
        $wb = PSP_DB::verifieer_wb_token( sanitize_text_field( wp_unslash( $_GET['wb_bevestig'] ) ) );
        if ( ! $wb ) {
            return '<div style="background:#fff0f4;border-left:4px solid #d31775;padding:12px 16px;border-radius:6px;margin:0 0 18px;color:#a0124d">'
                 . 'Deze bevestigingslink is ongeldig of verlopen. Log in en bevestig je werkbevestiging in je rooster.</div>';
        }
        if ( $wb->status !== 'bevestigd' ) {
            PSP_DB::bevestig_wb( $wb->id, $wb->student_email );
        }
        return '<div style="background:#eafbf0;border-left:4px solid #27ae60;padding:12px 16px;border-radius:6px;margin:0 0 18px;color:#1a6b3a">'
             . '<strong>&#10003; Bedankt!</strong> Je hebt je werkbevestiging bij ' . esc_html( $wb->opdrachtgever ) . ' bevestigd.</div>';
    }

    public static function shortcode() {
        $token_melding = self::verwerk_bevestig_token();

        if ( ! is_user_logged_in() ) {
            return $token_melding . '<div class="psp-rooster-login"><p>Je moet ingelogd zijn om je rooster te bekijken.</p>'
                 . '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="psp-rooster-btn">Inloggen</a></div>';
        }

        // Aanvraag nog in behandeling? Check op rol (niet op meta, die kan achterblijven)
        if ( in_array( 'psp_aanvraag', (array) wp_get_current_user()->roles, true ) ) {
            return '<div class="psp-rooster-wrap">'
                 . '<div class="psp-aanvraag-pending">'
                 . '<div class="psp-aanvraag-icon">&#9203;</div>'
                 . '<h3>Aanmelding in behandeling</h3>'
                 . '<p>Je aanmelding is ontvangen en wordt zo snel mogelijk beoordeeld door ProStudents.<br>'
                 . 'Je ontvangt een e-mail zodra je toegang hebt.</p>'
                 . '<p class="psp-aanvraag-contact">Vragen? Bel <a href="tel:0503112322">050&nbsp;&ndash;&nbsp;311&nbsp;23&nbsp;22</a> '
                 . 'of mail <a href="mailto:info@prostudents.nl">info@prostudents.nl</a>.</p>'
                 . '</div></div>';
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

            <?php echo $token_melding; ?>
            <?php echo self::nav_html( 'rooster' ); ?>

            <div class="psp-rooster-header">
                <div>
                    <p class="psp-rooster-welkom">Hallo, <strong><?php echo esc_html( $user->display_name ); ?></strong></p>
                </div>
                <a href="<?php echo esc_url( home_url('/beschikbaarheid/') ); ?>" class="psp-rooster-btn">
                    + Beschikbaarheid doorgeven
                </a>
            </div>

            <?php echo self::uren_links_html(); ?>

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
                'wb_id'         => null,
                'wb_status'     => null,
                'wb_bevestigd_op' => null,
            );
            $wb = PSP_DB::get_wb_voor_dienst( $d->id );
            if ( $wb && strcasecmp( $wb->student_email, $email ) === 0 ) {
                $item['wb_id']           = (int) $wb->id;
                $item['wb_status']       = $wb->status;
                $item['wb_bevestigd_op'] = $wb->bevestigd_op ? date_i18n( 'j M Y H:i', strtotime( $wb->bevestigd_op ) ) : null;
            }
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
