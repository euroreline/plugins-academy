<?php
if (!defined('ABSPATH')) exit;

/* ===================================
   ACADEMY LANDING PAGE CONTENT
=================================== */

function nac_get_academy_page_content() {

    $register_url = esc_url(wp_registration_url());
    $login_url    = esc_url(wp_login_url(site_url('/dashboard/')));
    $logo_url     = esc_url('https://skyblue-viper-538056.hostingersite.com/wp-content/uploads/2026/02/Nanotherm-new-logo.png');

    return '
<style>
.nt-academy-wrapper {
    font-family: "Segoe UI", Arial, sans-serif;
    color: #1a1a1a;
    line-height: 1.6;
}

.nt-section {
    padding: 90px 20px;
    max-width: 1150px;
    margin: auto;
}

.nt-hero {
    text-align: center;
    background: linear-gradient(135deg, #0b3c5d 0%, #145a86 100%);
    color: white;
    padding: 110px 20px;
}

.nt-logo {
    width: 385px;
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto 25px auto;
    opacity: 0.95;
}

.nt-hero h1 {
    font-size: 42px;
    font-weight: 600;
    margin-bottom: 20px;
}

.nt-hero p {
    font-size: 18px;
    max-width: 750px;
    margin: 0 auto 30px auto;
    opacity: 0.95;
}

.nt-instructions {
    max-width: 600px;
    margin: 0 auto 25px auto;
    font-size: 15px;
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 8px;
}

.nt-btn {
    display: inline-block;
    background: white;
    color: #0b3c5d;
    padding: 15px 32px;
    text-decoration: none;
    font-weight: 600;
    border-radius: 6px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.nt-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.25);
}

.nt-link {
    display:block;
    margin-top:15px;
    color:white;
    text-decoration:underline;
    font-size:14px;
}

.nt-grey {
    background: #f4f6f9;
}

.nt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 30px;
    margin-top: 50px;
}

.nt-card {
    background: white;
    padding: 35px 25px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    text-align: center;
    transition: 0.3s;
}

.nt-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

.nt-center {
    text-align: center;
}

.nt-dark {
    background: #0b3c5d;
    color: white;
    text-align: center;
}

.nt-dark .nt-btn {
    background: white;
    color: #0b3c5d;
}

.nt-list {
    max-width: 750px;
    margin: 40px auto 0 auto;
}

@media(max-width:768px){

    .nt-hero {
        padding: 80px 20px;
    }

    .nt-hero h1 {
        font-size: 28px;
        line-height: 1.3;
    }

    .nt-hero p {
        font-size: 16px;
    }

    .nt-btn {
        width: 100%;
        max-width: 280px;
        margin: 0 auto;
        display: block;
    }

    .nt-section {
        padding: 60px 20px;
    }

}
</style>

<div class="nt-academy-wrapper">

    <div class="nt-hero">
        <img src="' . $logo_url . '"
             alt="NanoTherm Logo"
             class="nt-logo">

        <h1>NanoTherm Academy</h1>

        <p>
            Γίνε Πιστοποιημένος Εφαρμοστής NanoTherm και απόκτησε τεχνική γνώση,
            επαγγελματικό κύρος και ανταγωνιστικό πλεονέκτημα μέσα από το επίσημο πρόγραμμα πιστοποίησης.
        </p>

        <div class="nt-instructions">
            Για να ξεκινήσετε την πιστοποίηση:
            <br><br>
            1️⃣ Πατήστε <strong>Register (Εγγραφή)</strong><br>
            2️⃣ Συμπληρώστε τα πεδία<br>
            3️⃣ Θα λάβετε email επιβεβαίωσης<br>
            4️⃣ Πατήστε τον σύνδεσμο στο email για να δημιουργήσετε συνθηματικό<br>
            5️⃣ Κάντε σύνδεση και συνεχίστε την πιστοποίηση
        </div>

        <a href="' . $register_url . '" class="nt-btn">
           Δημιούργησε Λογαριασμό
        </a>

        <a href="' . $login_url . '" class="nt-link">
            Έχετε ήδη λογαριασμό; Σύνδεση
        </a>
    </div>

    <div class="nt-section">
        <h2 class="nt-center">Τι είναι η NanoTherm Academy</h2>

        <div class="nt-list">
            Η NanoTherm Academy είναι δομημένο πρόγραμμα τεχνικής πιστοποίησης
            για επαγγελματίες που εφαρμόζουν το NanoTherm System.
            <br><br>
            Το πρόγραμμα καλύπτει:
            <ul>
                <li>Τεχνική κατανόηση συστήματος</li>
                <li>Προετοιμασία επιφανειών &amp; πρωτόκολλα εφαρμογής</li>
                <li>Θερμική απόδοση &amp; U-Value</li>
                <li>Πυροπροστασία &amp; ταξινομήσεις</li>
                <li>Διαχείριση έργου &amp; πελατών</li>
            </ul>
        </div>
    </div>

    <div class="nt-section nt-grey">
        <h2 class="nt-center">Δομή Πιστοποίησης</h2>

        <div class="nt-grid">
            <div class="nt-card">
                <h3>Ενότητα 1</h3>
                <p>Βασικές Αρχές NanoTherm</p>
            </div>

            <div class="nt-card">
                <h3>Ενότητα 2</h3>
                <p>Υποστρώματα &amp; Πρωτόκολλα Εφαρμογής</p>
            </div>

            <div class="nt-card">
                <h3>Ενότητα 3</h3>
                <p>Πυραντίσταση &amp; Θεσμικό Πλαίσιο</p>
            </div>

            <div class="nt-card">
                <h3>Ενότητα 4</h3>
                <p>Διαχείριση Έργου &amp; Επαγγελματική Συμπεριφορά</p>
            </div>
        </div>

        <div class="nt-center" style="margin-top:40px;">
            ✔ 20 ερωτήσεις ανά επίπεδο<br>
            ✔ 85% ποσοστό επιτυχίας<br>
            ✔ Online αξιολόγηση
        </div>
    </div>

    <div class="nt-section">
        <h2 class="nt-center">Τι Κερδίζεις</h2>

        <div class="nt-list">
            <ul>
                <li>Πιστοποιητικό NanoTherm Academy</li>
                <li>Επαγγελματικό positioning</li>
                <li>Τεχνική τεκμηρίωση γνώσεων</li>
                <li>Δυνατότητα επαγγελματικής εξέλιξης</li>
            </ul>
        </div>
    </div>

    <div class="nt-section nt-dark">
        <h2>Έτοιμος να ξεκινήσεις;</h2>
        <p>Δημιούργησε λογαριασμό και ξεκίνα το NanoTherm Academy Certification.</p>

        <a href="' . $register_url . '" class="nt-btn">
            Δημιούργησε Λογαριασμό
        </a>
    </div>

</div>';
}

/* ===================================
   CREATE OR OVERWRITE ACADEMY PAGE
=================================== */

function nac_create_or_update_academy_page() {

    $content = nac_get_academy_page_content();
    $page_id = (int) get_option('nac_academy_page_id');

    $page_args = [
        'post_title'   => 'NanoTherm Academy',
        'post_name'    => 'academy',
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];

    if ($page_id > 0 && get_post($page_id)) {
        $page_args['ID'] = $page_id;
        wp_update_post($page_args);
        return $page_id;
    }

    $existing_page = get_page_by_path('academy', OBJECT, 'page');

    if ($existing_page instanceof WP_Post) {
        $page_args['ID'] = (int) $existing_page->ID;
        wp_update_post($page_args);
        update_option('nac_academy_page_id', (int) $existing_page->ID);
        return (int) $existing_page->ID;
    }

    $new_page_id = wp_insert_post($page_args);

    if (!is_wp_error($new_page_id) && $new_page_id > 0) {
        update_option('nac_academy_page_id', (int) $new_page_id);
        return (int) $new_page_id;
    }

    return 0;
}