<?php
if (!defined('ABSPATH')) exit;

add_shortcode('nac_dashboard', 'nac_dashboard_shortcode');

function nac_dashboard_shortcode() {

    if (!is_user_logged_in()) {
        return '<p>Πρέπει να συνδεθείτε για να δείτε το Dashboard.</p>';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'nac_progress';
    $user_id = get_current_user_id();

    $levels = [1,2,3,4];

    $level_titles = [
        1 => 'Βασικές Αρχές NanoTherm',
        2 => 'Υποστρώματα & Αστάρια',
        3 => 'Εφαρμογή & Πυροπροστασία',
        4 => 'Διαχείριση Λαθών & Προβλήματα'
    ];

    $level_urls = [
        1 => site_url('/level1/'),
        2 => site_url('/level2/'),
        3 => site_url('/level3/'),
        4 => site_url('/level4/')
    ];

    ob_start();

    echo '<div class="nac-wrapper">';
    echo '<h2>Nanotherm Academy – Πιστοποιητικό Εφαρμοστή</h2>';

    $completed_count = 0;

    foreach ($levels as $level) {

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d AND level = %d",
                $user_id,
                $level
            )
        );

        $previous_passed = true;

        if ($level > 1) {
            $prev = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT passed FROM {$table} WHERE user_id = %d AND level = %d",
                    $user_id,
                    $level - 1
                )
            );

            if (!$prev || !$prev->passed) {
                $previous_passed = false;
            }
        }

        echo '<div class="nac-card">';
        echo '<strong>Ενότητα '.$level.' – '.$level_titles[$level].'</strong><br>';

        if ($row && $row->passed) {

            $completed_count++;

            echo '<span style="color:green;">✔ Ολοκληρώθηκε ('.$row->score.'%)</span><br>';
            echo '<a class="nac-btn" href="'.$level_urls[$level].'">Επανάληψη</a>';

        } elseif ($level == 1 || $previous_passed) {

            echo '<span style="color:#1e73be;">Διαθέσιμη</span><br>';
            echo '<a class="nac-btn" href="'.$level_urls[$level].'">Έναρξη</a>';

        } else {

            echo '<span style="color:gray;">Κλειδωμένη</span>';

        }

        echo '</div>';
    }

    $percentage = ($completed_count / 4) * 100;

    echo '<div class="nac-progress-bar">
            <div class="nac-progress-fill" style="width:'.$percentage.'%"></div>
          </div>';

    echo '<p style="text-align:center;">'.$completed_count.' / 4 Ενότητες Ολοκληρωμένες</p>';

    // ===============================
    // CERTIFICATE SECTION
    // ===============================

    if ($completed_count == 4) {

        $cert_url = site_url('/dashboard/?download_certificate=1');

        echo '
        <div style="
            margin-top:40px;
            padding:30px;
            text-align:center;
            background:linear-gradient(135deg,#0f172a,#1e293b);
            border-radius:12px;
            color:#fff;
            box-shadow:0 10px 30px rgba(0,0,0,0.2);
        ">
            <h3 style="margin-bottom:15px;">🎓 Συγχαρητήρια!</h3>
            <p style="margin-bottom:20px;">
                Έχετε ολοκληρώσει επιτυχώς όλες τις ενότητες της NanoTherm Academy.
            </p>
            <a href="'.$cert_url.'" 
               style="
                    display:inline-block;
                    padding:14px 28px;
                    background:#00c853;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                    font-weight:600;
               ">
               Λήψη Πιστοποιητικού (PDF)
            </a>
        </div>';
    }

    echo '</div>';

    return ob_get_clean();
}