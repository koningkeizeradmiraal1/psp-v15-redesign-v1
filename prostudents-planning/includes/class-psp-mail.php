<?php
defined('ABSPATH') || exit;

class PSP_Mail {

    public static function stuur_koppeling_bevestiging(object $beschikbaarheid, object $dienst, int $koppeling_id): bool {
        $datum_nl  = date_i18n('l j F Y', strtotime($dienst->datum));
        $subject   = "Je bent ingepland! {$datum_nl} bij {$dienst->opdrachtgever}";

        $body = "Beste {$beschikbaarheid->naam},\n\n";
        $body .= "Goed nieuws! Je bent ingepland voor de volgende dienst:\n\n";
        $body .= "📅 Datum:          {$datum_nl}\n";
        $body .= "⏰ Tijd:           {$dienst->tijdstip_van} – {$dienst->tijdstip_tot}\n";
        $body .= "🏢 Opdrachtgever:  {$dienst->opdrachtgever}\n";
        if ($dienst->locatie)   $body .= "📍 Locatie:        {$dienst->locatie}\n";
        if ($dienst->type_werk) $body .= "💼 Werkzaamheden:  {$dienst->type_werk}\n";
        if ($dienst->omschrijving) $body .= "\n{$dienst->omschrijving}\n";
        $body .= "\nHeb je vragen? Neem dan contact op met ProStudents:\n";
        $body .= "📞 050 – 311 23 22\n";
        $body .= "📧 info@prostudents.nl\n\n";
        $body .= "Met vriendelijke groet,\nProStudents Groningen";

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ProStudents <info@prostudents.nl>',
        ];

        $ok = wp_mail($beschikbaarheid->email, $subject, $body, $headers);
        if ($ok) PSP_DB::mark_notificatie_verzonden($koppeling_id);
        return $ok;
    }

    /* ─── Welkomstmail bij goedkeuring aanmelding ─── */
    public static function stuur_welkomstmail( int $user_id ): bool {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) return false;

        $login_url = wp_login_url( home_url('/mijn-rooster/') );
        $subject   = 'Je account is goedgekeurd — welkom bij ProStudents!';

        $body  = "Beste {$user->display_name},\n\n";
        $body .= "Goed nieuws! Je aanmelding bij ProStudents Planning is goedgekeurd.\n";
        $body .= "Je kunt nu inloggen met de gegevens die je bij aanmelding hebt gekozen.\n\n";
        $body .= "Gebruikersnaam: {$user->user_login}\n";
        $body .= "Inlogpagina:    {$login_url}\n\n";
        $body .= "Na het inloggen kun je je beschikbaarheid doorgeven en je rooster bekijken.\n\n";
        $body .= "Heb je vragen? Neem contact op met ProStudents:\n";
        $body .= "📞 050 – 311 23 22\n";
        $body .= "📧 info@prostudents.nl\n\n";
        $body .= "Met vriendelijke groet,\nProStudents Groningen";

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ProStudents <info@prostudents.nl>',
        ];

        return wp_mail( $user->user_email, $subject, $body, $headers );
    }

    /* ─── Notificatie aan admin bij nieuwe aanmelding ─── */
    public static function stuur_aanmelding_notificatie( int $user_id, string $naam, string $email ): void {
        $admin_email   = get_option('admin_email');
        $dashboard_url = home_url('/planning-dashboard/');
        $subject       = "Nieuwe aanmelding: {$naam}";

        $body  = "Er is een nieuwe aanmelding binnengekomen via de ProStudents portal.\n\n";
        $body .= "Naam:   {$naam}\n";
        $body .= "E-mail: {$email}\n\n";
        $body .= "Beoordeel de aanmelding via het dashboard:\n";
        $body .= "{$dashboard_url}\n";
        $body .= "(Tabblad Beheer → Aanmeldingen)\n\n";
        $body .= "ProStudents Planning";

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ProStudents Planning <info@prostudents.nl>',
        ];

        wp_mail( $admin_email, $subject, $body, $headers );
    }

    public static function stuur_bevestiging_aan_student(object $beschikbaarheid): bool {
        $week_label = date_i18n('j F Y', strtotime($beschikbaarheid->week_start));
        $subject    = "Beschikbaarheid ontvangen – week van {$week_label}";

        $body  = "Beste {$beschikbaarheid->naam},\n\n";
        $body .= "We hebben je beschikbaarheid ontvangen voor de week van {$week_label}.\n";
        $body .= "Zodra we je inplannen ontvang je een bevestigingsmail.\n\n";
        $body .= "Met vriendelijke groet,\nProStudents Groningen\n";
        $body .= "📞 050 – 311 23 22 | 📧 info@prostudents.nl";

        $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: ProStudents <info@prostudents.nl>'];
        return wp_mail($beschikbaarheid->email, $subject, $body, $headers);
    }
}
