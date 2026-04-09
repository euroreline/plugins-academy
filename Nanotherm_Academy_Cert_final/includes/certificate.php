<?php
if (!defined('ABSPATH')) exit;

add_action('init', function(){

    if (
        !isset($_GET['download_certificate']) &&
        !isset($_GET['preview_certificate']) &&
        !isset($_GET['email_certificate'])
    ) {
        return;
    }

    global $wpdb;

    $progress_table = $wpdb->prefix . 'nac_progress';
    $cert_table     = $wpdb->prefix . 'nac_certificates';

    /* =========================
       DETERMINE TARGET USER
    ========================== */

    if (current_user_can('manage_options') && isset($_GET['user_id'])) {
        $target_user_id = intval($_GET['user_id']);
    } else {
        if (!is_user_logged_in()) return;
        $target_user_id = get_current_user_id();
    }

    /* =========================
       CHECK COMPLETION
    ========================== */

    $passed_count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$progress_table} WHERE user_id = %d AND passed = 1",
            $target_user_id
        )
    );

    if ($passed_count < 4) {
        wp_die('Ο χρήστης δεν έχει ολοκληρώσει όλες τις ενότητες.');
    }

    require_once NAC_PATH . 'includes/lib/tfpdf/tfpdf.php';

    /* =========================
       USER DATA
    ========================== */

    $first = trim(get_user_meta($target_user_id, 'first_name', true));
    $last  = trim(get_user_meta($target_user_id, 'last_name', true));

    if (empty($first) || empty($last)) {
        wp_die('Λείπουν στοιχεία Ονόματος / Επωνύμου.');
    }

    $full_name = $first . ' ' . $last;

    /* =========================
       CERTIFICATE LOGIC
    ========================== */

    $existing_cert = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$cert_table} WHERE user_id = %d",
            $target_user_id
        )
    );

    if ($existing_cert) {

        $certificate_id = $existing_cert->certificate_id;
        $issued_at      = $existing_cert->issued_at;

    } else {

        $certificate_id = 'NAC-' . strtoupper(wp_generate_password(8, false, false));
        $issued_at = current_time('mysql');

        $total_score = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(score) FROM {$progress_table} WHERE user_id = %d",
                $target_user_id
            )
        );

        $wpdb->insert(
            $cert_table,
            [
                'user_id'        => $target_user_id,
                'certificate_id' => $certificate_id,
                'issued_at'      => $issued_at,
                'total_score'    => intval($total_score)
            ]
        );
    }

    $date = date('d/m/Y', strtotime($issued_at));

    /* =========================
       PDF GENERATION
    ========================== */

    $pdf = new tFPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    $pdf->AddFont('DejaVu','','DejaVuSans.ttf',true);
    $pdf->AddFont('DejaVu','B','DejaVuSans-Bold.ttf',true);

    $background = NAC_PATH . 'assets/certificate-bg.png';
    if (file_exists($background)) {
        $pdf->Image($background, 0, 0, 297, 210);
    }

    // ΟΝΟΜΑ (ΔΕΝ ΠΕΙΡΑΖΩ ΘΕΣΕΙΣ)
    $pdf->SetFont('DejaVu','B',28);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(0,135);
    $pdf->Cell(297,10,$full_name,0,0,'C');

    // ΗΜΕΡΟΜΗΝΙΑ
    $pdf->SetFont('DejaVu','',14);
    $pdf->SetXY(40,175);
    $pdf->Cell(60,10,$date,0,0,'L');

    // CERTIFICATE ID
    $pdf->SetFont('DejaVu','',9);
    $pdf->SetTextColor(90,90,90);
    $pdf->SetXY(180,201.5);
    $pdf->Cell(90,8,$certificate_id,0,0,'R');

    /* =========================
       ACTIONS
    ========================== */

    if (isset($_GET['preview_certificate'])) {
        $pdf->Output('I', 'Certificate-'.$certificate_id.'.pdf');
        exit;
    }

    if (isset($_GET['email_certificate'])) {

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'].'/Certificate-'.$certificate_id.'.pdf';
        $pdf->Output('F', $file_path);

        $user = get_user_by('id', $target_user_id);

        wp_mail(
            $user->user_email,
            'Το Πιστοποιητικό σας - Nanotherm Academy',
            'Σας επισυνάπτουμε το Πιστοποιητικό σας.',
            ['Content-Type: text/html; charset=UTF-8'],
            [$file_path]
        );

        unlink($file_path);

        wp_redirect(admin_url('admin.php?page=nac_students&sent=1'));
        exit;
    }

    // DEFAULT DOWNLOAD
    $pdf->Output('D', 'Nanotherm-Certificate-'.$certificate_id.'.pdf');
    exit;

});