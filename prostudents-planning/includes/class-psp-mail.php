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
