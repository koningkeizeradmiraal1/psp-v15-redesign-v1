<?php
defined('ABSPATH') || exit;

class PSP_DB {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE " . PSP_TABLE_BESCHIKBAARHEID . " (
            id           bigint(20)   NOT NULL AUTO_INCREMENT,
            naam         varchar(100) NOT NULL,
            email        varchar(100) NOT NULL,
            telefoon     varchar(30)  DEFAULT '',
            week_start   date         NOT NULL,
            dagen        text         NOT NULL,
            vaardigheden text,
            voorkeur     text         DEFAULT '',
            status       varchar(20)  DEFAULT 'actief',
            created_at   datetime     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY week_start (week_start),
            KEY email (email)
        ) $charset;

        CREATE TABLE " . PSP_TABLE_DIENSTEN . " (
            id            bigint(20)    NOT NULL AUTO_INCREMENT,
            titel         varchar(200)  NOT NULL,
            opdrachtgever varchar(150)  NOT NULL,
            datum         date          NOT NULL,
            tijdstip_van  time          NOT NULL,
            tijdstip_tot  time          NOT NULL,
            locatie       varchar(250)  DEFAULT '',
            type_werk     varchar(150)  DEFAULT '',
            omschrijving  text          DEFAULT '',
            status        varchar(20)   DEFAULT 'open',
            created_at    datetime      DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY datum (datum),
            KEY status (status)
        ) $charset;

        CREATE TABLE " . PSP_TABLE_KOPPELINGEN . " (
            id                    bigint(20) NOT NULL AUTO_INCREMENT,
            beschikbaarheid_id    bigint(20) NOT NULL,
            dienst_id             bigint(20) NOT NULL,
            notificatie_verzonden tinyint(1) DEFAULT 0,
            created_at            datetime   DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniek_koppeling (beschikbaarheid_id, dienst_id),
            KEY dienst_id (dienst_id)
        ) $charset;

        CREATE TABLE " . PSP_TABLE_TARIEVEN . " (
            id             bigint(20)   NOT NULL AUTO_INCREMENT,
            student_email  varchar(100) NOT NULL,
            opdrachtgever  varchar(150) NOT NULL,
            uurtarief      decimal(8,2) DEFAULT NULL,
            loon           decimal(8,2) DEFAULT NULL,
            bevestigd      tinyint(1)   DEFAULT 0,
            bevestigd_op   datetime     DEFAULT NULL,
            aangemaakt_op  datetime     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY student_klant (student_email, opdrachtgever)
        ) $charset;

        CREATE TABLE " . PSP_TABLE_WB_TEMPLATES . " (
            id             bigint(20)    NOT NULL AUTO_INCREMENT,
            opdrachtgever  varchar(255)  NOT NULL DEFAULT \'\',
            naam           varchar(255)  NOT NULL DEFAULT \'\',
            onderwerp      varchar(500)  NOT NULL DEFAULT \'\',
            inhoud         longtext      NOT NULL,
            aangemaakt_op  datetime      DEFAULT CURRENT_TIMESTAMP,
            bijgewerkt_op  datetime      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY opdrachtgever (opdrachtgever)
        ) $charset;

        CREATE TABLE " . PSP_TABLE_WERKBEVESTIGINGEN . " (
            id              bigint(20)    NOT NULL AUTO_INCREMENT,
            dienst_id       bigint(20)    NOT NULL,
            student_email   varchar(100)  NOT NULL,
            opdrachtgever   varchar(255)  NOT NULL DEFAULT \'\',
            onderwerp       varchar(500)  NOT NULL DEFAULT \'\',
            inhoud          longtext      NOT NULL,
            status          varchar(20)   NOT NULL DEFAULT \'verzonden\',
            verzonden_op    datetime      DEFAULT CURRENT_TIMESTAMP,
            bevestigd_op    datetime      DEFAULT NULL,
            PRIMARY KEY (id),
            KEY dienst_id (dienst_id),
            KEY student_email (student_email),
            KEY status (status)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /* ══════ Beschikbaarheid ══════ */

    public static function insert_beschikbaarheid( $data ) {
        global $wpdb;
        $ok = $wpdb->insert( PSP_TABLE_BESCHIKBAARHEID, array(
            'naam'       => sanitize_text_field( $data['naam'] ),
            'email'      => sanitize_email( $data['email'] ),
            'telefoon'   => sanitize_text_field( isset( $data['telefoon'] ) ? $data['telefoon'] : '' ),
            'week_start' => $data['week_start'],
            'dagen'        => wp_json_encode( $data['dagen'] ),
            'vaardigheden' => wp_json_encode( isset( $data['vaardigheden'] ) ? $data['vaardigheden'] : array() ),
            'voorkeur'   => sanitize_textarea_field( isset( $data['voorkeur'] ) ? $data['voorkeur'] : '' ),
        ) );
        return $ok ? $wpdb->insert_id : false;
    }

    public static function get_beschikbaarheid_by_week( $week_start ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . PSP_TABLE_BESCHIKBAARHEID . " WHERE week_start = %s AND status = 'actief' ORDER BY naam ASC",
            $week_start
        ) );
    }

    public static function get_beschikbaarheid_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . PSP_TABLE_BESCHIKBAARHEID . " WHERE id = %d",
            $id
        ) );
    }

    public static function get_beschikbaar_voor_datum( $datum, $van, $tot ) {
        $day_nl = self::datum_to_dag_nl( $datum );
        $rows   = self::get_beschikbaarheid_by_week( self::week_start_for_datum( $datum ) );
        $result = array();
        foreach ( $rows as $row ) {
            $dagen = json_decode( $row->dagen, true );
            if ( ! isset( $dagen[ $day_nl ] ) ) continue;
            $d = $dagen[ $day_nl ];
            if ( empty( $d['van'] ) || empty( $d['tot'] ) ) continue;
            if ( $d['van'] <= $van && $d['tot'] >= $tot ) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public static function week_start_for_datum( $datum ) {
        $dt  = new DateTime( $datum );
        $dow = (int) $dt->format( 'N' );
        $dt->modify( '-' . ( $dow - 1 ) . ' days' );
        return $dt->format( 'Y-m-d' );
    }

    public static function datum_to_dag_nl( $datum ) {
        $map = array( '1'=>'ma','2'=>'di','3'=>'wo','4'=>'do','5'=>'vr','6'=>'za','7'=>'zo' );
        $key = ( new DateTime( $datum ) )->format( 'N' );
        return isset( $map[ $key ] ) ? $map[ $key ] : 'ma';
    }

    /* ══════ Diensten ══════ */

    public static function insert_dienst( $data ) {
        global $wpdb;
        $ok = $wpdb->insert( PSP_TABLE_DIENSTEN, array(
            'titel'         => sanitize_text_field( $data['titel'] ),
            'opdrachtgever' => sanitize_text_field( $data['opdrachtgever'] ),
            'datum'         => $data['datum'],
            'tijdstip_van'  => $data['tijdstip_van'],
            'tijdstip_tot'  => $data['tijdstip_tot'],
            'locatie'       => sanitize_text_field( isset( $data['locatie'] ) ? $data['locatie'] : '' ),
            'type_werk'     => sanitize_text_field( isset( $data['type_werk'] ) ? $data['type_werk'] : '' ),
            'omschrijving'  => sanitize_textarea_field( isset( $data['omschrijving'] ) ? $data['omschrijving'] : '' ),
        ) );
        return $ok ? $wpdb->insert_id : false;
    }

    public static function get_diensten( $status = '' ) {
        global $wpdb;
        $where = $status ? $wpdb->prepare( "WHERE status = %s", $status ) : '';
        return $wpdb->get_results(
            "SELECT * FROM " . PSP_TABLE_DIENSTEN . " $where ORDER BY datum ASC, tijdstip_van ASC"
        );
    }

    public static function get_diensten_voor_week( $week_start ) {
        global $wpdb;
        $dt       = new DateTime( $week_start );
        $week_end = $dt->modify( '+6 days' )->format( 'Y-m-d' );
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . PSP_TABLE_DIENSTEN . " WHERE datum BETWEEN %s AND %s ORDER BY datum ASC, tijdstip_van ASC",
            $week_start,
            $week_end
        ) );
    }

    public static function get_dienst_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . PSP_TABLE_DIENSTEN . " WHERE id = %d",
            $id
        ) );
    }

    public static function update_dienst( $id, $data ) {
        global $wpdb;
        $allowed = array( 'titel','opdrachtgever','datum','tijdstip_van','tijdstip_tot','locatie','type_werk','omschrijving','status' );
        $clean   = array_intersect_key( $data, array_flip( $allowed ) );
        return (bool) $wpdb->update( PSP_TABLE_DIENSTEN, $clean, array( 'id' => $id ) );
    }

    public static function delete_dienst( $id ) {
        global $wpdb;
        $wpdb->delete( PSP_TABLE_KOPPELINGEN, array( 'dienst_id' => $id ) );
        $wpdb->delete( PSP_TABLE_DIENSTEN,    array( 'id'        => $id ) );
    }

    /* ══════ Koppelingen ══════ */

    public static function insert_koppeling( $beschikbaarheid_id, $dienst_id ) {
        global $wpdb;
        $ok = $wpdb->insert( PSP_TABLE_KOPPELINGEN, array(
            'beschikbaarheid_id' => $beschikbaarheid_id,
            'dienst_id'          => $dienst_id,
        ) );
        return $ok ? $wpdb->insert_id : false;
    }

    public static function get_koppeling_voor_dienst( $dienst_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT k.*, b.naam, b.email, b.telefoon
             FROM " . PSP_TABLE_KOPPELINGEN . " k
             JOIN " . PSP_TABLE_BESCHIKBAARHEID . " b ON b.id = k.beschikbaarheid_id
             WHERE k.dienst_id = %d LIMIT 1",
            $dienst_id
        ) );
    }

    public static function verwijder_koppeling( $dienst_id ) {
        global $wpdb;
        $wpdb->delete( PSP_TABLE_KOPPELINGEN, array( 'dienst_id' => $dienst_id ) );
        $wpdb->update( PSP_TABLE_DIENSTEN, array( 'status' => 'open' ), array( 'id' => $dienst_id ) );
    }

    public static function mark_notificatie_verzonden( $koppeling_id ) {
        global $wpdb;
        $wpdb->update( PSP_TABLE_KOPPELINGEN, array( 'notificatie_verzonden' => 1 ), array( 'id' => $koppeling_id ) );
    }

    public static function get_koppelingen_voor_beschikbaarheid( $beschikbaarheid_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT k.*, d.titel, d.datum, d.tijdstip_van, d.tijdstip_tot, d.opdrachtgever, d.locatie
             FROM " . PSP_TABLE_KOPPELINGEN . " k
             JOIN " . PSP_TABLE_DIENSTEN . " d ON d.id = k.dienst_id
             WHERE k.beschikbaarheid_id = %d",
            $beschikbaarheid_id
        ) );
    }
    public static function delete_beschikbaarheid( $id ) {
        global $wpdb;
        // Reset gekoppelde diensten naar 'open'
        $koppelingen = $wpdb->get_results( $wpdb->prepare(
            "SELECT dienst_id FROM " . PSP_TABLE_KOPPELINGEN . " WHERE beschikbaarheid_id = %d",
            $id
        ) );
        foreach ( $koppelingen as $kop ) {
            $wpdb->update( PSP_TABLE_DIENSTEN, array( 'status' => 'open' ), array( 'id' => $kop->dienst_id ) );
        }
        $wpdb->delete( PSP_TABLE_KOPPELINGEN,     array( 'beschikbaarheid_id' => $id ) );
        $wpdb->delete( PSP_TABLE_BESCHIKBAARHEID, array( 'id'                => $id ) );
    }

    /* ══════ Tarieven (eerste keer) ══════ */

    public static function is_eerste_keer( $email, $opdrachtgever ) {
        global $wpdb;
        $tbl = PSP_TABLE_TARIEVEN;
        return ! $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $tbl WHERE student_email = %s AND opdrachtgever = %s LIMIT 1",
            $email, $opdrachtgever
        ) );
    }

    public static function registreer_eerste_keer( $email, $opdrachtgever ) {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO " . PSP_TABLE_TARIEVEN . "
             (student_email, opdrachtgever) VALUES (%s, %s)",
            $email, $opdrachtgever
        ) );
    }

    public static function save_tarief( $email, $opdrachtgever, $uurtarief, $loon ) {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO " . PSP_TABLE_TARIEVEN . "
             (student_email, opdrachtgever, uurtarief, loon, bevestigd, bevestigd_op)
             VALUES (%s, %s, %f, %f, 1, NOW())
             ON DUPLICATE KEY UPDATE
               uurtarief    = VALUES(uurtarief),
               loon         = VALUES(loon),
               bevestigd    = 1,
               bevestigd_op = NOW()",
            $email, $opdrachtgever, (float) $uurtarief, (float) $loon
        ) );
    }

    public static function get_onbevestigde_tarieven() {
        global $wpdb;
        $tbl  = PSP_TABLE_TARIEVEN;
        $btbl = PSP_TABLE_BESCHIKBAARHEID;
        return $wpdb->get_results(
            "SELECT t.*, b.naam AS student_naam
             FROM $tbl t
             LEFT JOIN $btbl b ON b.email = t.student_email
             WHERE t.bevestigd = 0
             GROUP BY t.id
             ORDER BY t.aangemaakt_op DESC"
        );
    }

    /* ══════ Werkbevestiging templates ══════ */

    public static function get_wb_templates( $opdrachtgever = '' ) {
        global $wpdb;
        $tbl = PSP_TABLE_WB_TEMPLATES;
        if ( $opdrachtgever ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $tbl WHERE opdrachtgever = %s ORDER BY naam ASC",
                $opdrachtgever
            ) );
        }
        return $wpdb->get_results( "SELECT * FROM $tbl ORDER BY opdrachtgever ASC, naam ASC" );
    }

    public static function get_wb_ogs() {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT DISTINCT opdrachtgever FROM " . PSP_TABLE_WB_TEMPLATES . " ORDER BY opdrachtgever ASC"
        );
    }

    public static function save_wb_template( $data ) {
        global $wpdb;
        $id            = (int) ( isset( $data['wb_id'] ) ? $data['wb_id'] : 0 );
        $opdrachtgever = sanitize_text_field( isset( $data['opdrachtgever'] ) ? $data['opdrachtgever'] : '' );
        $naam          = sanitize_text_field( isset( $data['naam'] ) ? $data['naam'] : '' );
        $onderwerp     = sanitize_text_field( isset( $data['onderwerp'] ) ? $data['onderwerp'] : '' );
        $inhoud        = wp_kses_post( isset( $data['inhoud'] ) ? $data['inhoud'] : '' );

        if ( $id ) {
            $wpdb->update(
                PSP_TABLE_WB_TEMPLATES,
                compact( 'opdrachtgever', 'naam', 'onderwerp', 'inhoud' ),
                array( 'id' => $id )
            );
            return $id;
        }
        $wpdb->insert(
            PSP_TABLE_WB_TEMPLATES,
            compact( 'opdrachtgever', 'naam', 'onderwerp', 'inhoud' )
        );
        return $wpdb->insert_id;
    }

    public static function delete_wb_template( $id ) {
        global $wpdb;
        $wpdb->delete( PSP_TABLE_WB_TEMPLATES, array( 'id' => (int) $id ) );
    }


    /* ══════ Verstuurde werkbevestigingen ══════ */

    public static function save_werkbevestiging( $data ) {
        global $wpdb;
        $dienst_id    = (int) ( isset($data['dienst_id'])    ? $data['dienst_id']    : 0 );
        $student_email= sanitize_email( isset($data['student_email']) ? $data['student_email'] : '' );
        $opdrachtgever= sanitize_text_field( isset($data['opdrachtgever']) ? $data['opdrachtgever'] : '' );
        $onderwerp    = sanitize_text_field( isset($data['onderwerp'])     ? $data['onderwerp']     : '' );
        $inhoud       = wp_kses_post( isset($data['inhoud']) ? $data['inhoud'] : '' );

        // Upsert op dienst_id
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM " . PSP_TABLE_WERKBEVESTIGINGEN . " WHERE dienst_id = %d LIMIT 1",
            $dienst_id
        ) );
        if ( $existing ) {
            $wpdb->update(
                PSP_TABLE_WERKBEVESTIGINGEN,
                array( 'onderwerp' => $onderwerp, 'inhoud' => $inhoud, 'status' => 'verzonden', 'bevestigd_op' => null, 'verzonden_op' => current_time('mysql') ),
                array( 'id' => $existing )
            );
            return (int) $existing;
        }
        $wpdb->insert( PSP_TABLE_WERKBEVESTIGINGEN, array(
            'dienst_id'     => $dienst_id,
            'student_email' => $student_email,
            'opdrachtgever' => $opdrachtgever,
            'onderwerp'     => $onderwerp,
            'inhoud'        => $inhoud,
            'status'        => 'verzonden',
        ) );
        return $wpdb->insert_id;
    }

    public static function get_wb_voor_dienst( $dienst_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . PSP_TABLE_WERKBEVESTIGINGEN . " WHERE dienst_id = %d LIMIT 1",
            $dienst_id
        ) );
    }

    public static function get_wbs_voor_student( $email ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT w.*, d.datum, d.tijdstip_van, d.tijdstip_tot, d.locatie, d.type_werk
             FROM " . PSP_TABLE_WERKBEVESTIGINGEN . " w
             LEFT JOIN " . PSP_TABLE_DIENSTEN . " d ON d.id = w.dienst_id
             WHERE w.student_email = %s
             ORDER BY w.verzonden_op DESC",
            $email
        ) );
    }

    public static function bevestig_wb( $id, $email ) {
        global $wpdb;
        return $wpdb->update(
            PSP_TABLE_WERKBEVESTIGINGEN,
            array( 'status' => 'bevestigd', 'bevestigd_op' => current_time('mysql') ),
            array( 'id' => (int) $id, 'student_email' => $email )
        );
    }

    public static function get_nieuwe_bevestigingen() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT w.*, d.datum, d.tijdstip_van, d.tijdstip_tot
             FROM " . PSP_TABLE_WERKBEVESTIGINGEN . " w
             LEFT JOIN " . PSP_TABLE_DIENSTEN . " d ON d.id = w.dienst_id
             WHERE w.status = \'bevestigd\'
             ORDER BY w.bevestigd_op DESC
             LIMIT 50"
        );
    }


}
