<?php
/**
 * Plugin Name: ProStudents Planning
 * Plugin URI:  https://prostudents.nl
 * Description: Beschikbaarheid en dienstplanning voor studenten en opdrachtgevers.
 * Version:     1.2.0
 * Author:      ProStudents
 * Text Domain: psp
 */
defined('ABSPATH') || exit;

define('PSP_VERSION', '1.5.0');
define('PSP_DIR',     plugin_dir_path(__FILE__));
define('PSP_URL',     plugin_dir_url(__FILE__));
define('PSP_TABLE_BESCHIKBAARHEID', $GLOBALS['wpdb']->prefix . 'ps_beschikbaarheid');
define('PSP_TABLE_DIENSTEN',        $GLOBALS['wpdb']->prefix . 'ps_diensten');
define('PSP_TABLE_KOPPELINGEN',     $GLOBALS['wpdb']->prefix . 'ps_koppelingen');
define('PSP_TABLE_TARIEVEN',        $GLOBALS['wpdb']->prefix . 'ps_tarieven');
define('PSP_TABLE_WB_TEMPLATES',    $GLOBALS['wpdb']->prefix . 'ps_wb_templates');
define('PSP_TABLE_WERKBEVESTIGINGEN', $GLOBALS['wpdb']->prefix . 'ps_werkbevestigingen');

/* ── Activeren ── */
register_activation_hook(__FILE__, 'psp_activate');
function psp_activate() {
    require_once PSP_DIR . 'includes/class-psp-db.php';
    PSP_DB::create_tables();
    psp_create_pages();
    psp_register_rollen();
}

function psp_register_rollen() {
    if ( ! get_role('psp_student') ) {
        add_role('psp_student', 'PSP Student', [
            'read'        => true,
            'psp_student' => true,
        ]);
    }
    if ( ! get_role('psp_aanvraag') ) {
        add_role('psp_aanvraag', 'PSP Aanvraag', [
            'read' => true,
        ]);
    }
}
// Zorg ook dat de rol altijd beschikbaar is na heractivatie
add_action('init', 'psp_register_rollen');

function psp_create_pages() {
    if ( ! get_page_by_path('beschikbaarheid') ) {
        wp_insert_post( array(
            'post_title'   => 'Beschikbaarheid opgeven',
            'post_name'    => 'beschikbaarheid',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[ps_beschikbaarheid]',
        ) );
    }
    if ( ! get_page_by_path('mijn-rooster') ) {
        wp_insert_post( array(
            'post_title'   => 'Mijn Rooster',
            'post_name'    => 'mijn-rooster',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[psp_mijn_rooster]',
        ) );
    }
    if ( ! get_page_by_path('planning-dashboard') ) {
        wp_insert_post( array(
            'post_title'   => 'Planning Dashboard',
            'post_name'    => 'planning-dashboard',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[psp_dashboard]',
        ) );
    }
    if ( ! get_page_by_path('aanmelden') ) {
        wp_insert_post( array(
            'post_title'   => 'Aanmelden als uitzendkracht',
            'post_name'    => 'aanmelden',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[psp_registreer]',
        ) );
    }
}

/* ── Includes laden ── */
add_action('plugins_loaded', function () {
    require_once PSP_DIR . 'includes/class-psp-db.php';
    require_once PSP_DIR . 'includes/class-psp-frontend.php';
    require_once PSP_DIR . 'includes/class-psp-mail.php';
    require_once PSP_DIR . 'includes/class-psp-admin.php';
    require_once PSP_DIR . 'includes/class-psp-dashboard.php';
    require_once PSP_DIR . 'includes/class-psp-student.php';

    PSP_Frontend::init();
    PSP_Admin::init();
    PSP_Dashboard::init();
    PSP_Student::init();
});

/* ── Frontend assets ── */
add_action('wp_enqueue_scripts', function () {
    // Studentenformulier — altijd laden op frontend
    wp_enqueue_style(  'psp-frontend', PSP_URL . 'assets/css/psp-frontend.css', array(), PSP_VERSION );
    wp_enqueue_script( 'psp-frontend', PSP_URL . 'assets/js/psp-frontend.js',   array(), PSP_VERSION, true );
    wp_localize_script( 'psp-frontend', 'pspData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('psp_frontend'),
    ) );

    // Dashboard — laden voor alle ingelogde redacteuren (geen has_shortcode check, werkt niet met pagebuilders)
    if ( is_user_logged_in() && current_user_can('edit_posts') ) {
        wp_enqueue_style(  'psp-dashboard', PSP_URL . 'assets/css/psp-dashboard.css', array(), PSP_VERSION );
        wp_enqueue_script( 'psp-dashboard', PSP_URL . 'assets/js/psp-dashboard.js',   array(), PSP_VERSION, true );
        wp_localize_script( 'psp-dashboard', 'pspDash', array(
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'mijnRoosterUrl' => home_url('/mijn-rooster/'),
        ) );
    }

    // Mijn Rooster — alleen voor psp_student
    if ( is_user_logged_in() && current_user_can('psp_student') ) {
        wp_enqueue_style(  'psp-student', PSP_URL . 'assets/css/psp-student.css', array(), PSP_VERSION );
        wp_enqueue_script( 'psp-student', PSP_URL . 'assets/js/psp-student.js',   array(), PSP_VERSION, true );
        wp_localize_script( 'psp-student', 'pspStudent', array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('psp_student'),
            'roosterUrl' => home_url('/mijn-rooster/'),
        ) );
    }
}, 10 );

/* ── Admin assets ── */
add_action('admin_enqueue_scripts', function ( $hook ) {
    if ( strpos($hook, 'psp') === false ) return;
    wp_enqueue_style(  'psp-admin', PSP_URL . 'assets/css/psp-admin.css', array(), PSP_VERSION );
    wp_enqueue_script( 'psp-admin', PSP_URL . 'assets/js/psp-admin.js',   array(), PSP_VERSION, true );
    wp_localize_script( 'psp-admin', 'pspAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('psp_admin'),
    ) );
} );
