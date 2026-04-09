<?php
/*
Plugin Name: Nanotherm_Academy_Cert
Description: NanoTherm Certification Platform
Version: 3.0
Author: Χρήστος Κωνσταντόπουλος (blockart)
*/

if (!defined('ABSPATH')) exit;

/* =========================
   CONSTANTS
========================= */

define('NAC_VERSION', '3.0');
define('NAC_PATH', plugin_dir_path(__FILE__));
define('NAC_URL', plugin_dir_url(__FILE__));

/* =========================
   ACTIVATION
========================= */

register_activation_hook(__FILE__, 'nac_activate');

function nac_activate(){

    add_role(
        'nac_student',
        'NAC Student',
        ['read' => true]
    );

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    /* ---------- PROGRESS TABLE ---------- */

    $progress_table = $wpdb->prefix . 'nac_progress';

    $sql1 = "CREATE TABLE {$progress_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        level int(11) NOT NULL,
        score int(11) DEFAULT 0,
        passed tinyint(1) DEFAULT 0,
        completed_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_level (user_id, level)
    ) {$charset_collate};";

    dbDelta($sql1);

    /* ---------- CERTIFICATES TABLE ---------- */

    $cert_table = $wpdb->prefix . 'nac_certificates';

    $sql2 = "CREATE TABLE {$cert_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        certificate_id varchar(100) NOT NULL,
        issued_at datetime NOT NULL,
        total_score int(11) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY user_unique (user_id),
        UNIQUE KEY cert_unique (certificate_id)
    ) {$charset_collate};";

    dbDelta($sql2);
}

/* =========================
   ENQUEUE CSS
========================= */

add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style(
        'nac-style',
        NAC_URL . 'assets/css/academy.css',
        [],
        NAC_VERSION
    );
});





/* ===================================
   CERTIFICATION SUCCESS EMAILS (GLOBAL)
=================================== */

function nac_send_certification_success_emails($user_id){

    $user = get_userdata($user_id);

    if(!$user) return false;

    $email = $user->user_email;

    if(!is_email($email)) return false;

    $first_name = get_user_meta($user_id,'first_name',true);
    $last_name  = get_user_meta($user_id,'last_name',true);
    $city       = get_user_meta($user_id,'city',true);
    $phone      = get_user_meta($user_id,'phone',true);

    $full_name = trim($first_name.' '.$last_name);

    global $wpdb;

    $cert_table = $wpdb->prefix.'nac_certificates';

    $cert = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$cert_table} WHERE user_id=%d",
            $user_id
        )
    );

    $certificate_id = $cert->certificate_id ?? '';
    $issued_date = !empty($cert->issued_at)
        ? date_i18n('d/m/Y',strtotime($cert->issued_at))
        : date_i18n('d/m/Y');

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    /* USER EMAIL */

    $subject_user = 'Συγχαρητήρια! Ολοκληρώσατε επιτυχώς τη NanoTherm Academy';

    $message_user = "
    <p>Αγαπητέ {$full_name},</p>

    <p>
    Συγχαρητήρια! Ολοκληρώσατε επιτυχώς και τις 4 ενότητες της NanoTherm Academy.
    Πλέον ανήκετε στους πιστοποιημένους εφαρμοστές NanoTherm.
    </p>

    <p>
    Ημερομηνία ολοκλήρωσης: {$issued_date}<br>
    Certificate ID: {$certificate_id}
    </p>

    <p>
    Με εκτίμηση,<br>
    EURORELINE
    </p>
    ";

    wp_mail($email,$subject_user,$message_user,$headers);


    /* ADMIN EMAIL */

    $subject_admin = 'Νέος πιστοποιημένος εφαρμοστής NanoTherm';

    $message_admin = "
    Νέος χρήστης ολοκλήρωσε επιτυχώς τη NanoTherm Academy:

    Όνομα: {$full_name}
    Email: {$email}
    Πόλη: {$city}
    Τηλέφωνο: {$phone}
    Certificate ID: {$certificate_id}
    Ημερομηνία: {$issued_date}
    ";

    wp_mail('info@euroreline.gr',$subject_admin,$message_admin);

    return true;
}


/* ===================================
   SEND ONLY ONCE AFTER LEVEL 4 SUCCESS
=================================== */

function nac_maybe_send_certification_success_emails($user_id){

    if(get_user_meta($user_id,'nac_certification_success_email_sent',true)){
        return;
    }

    nac_send_certification_success_emails($user_id);

    update_user_meta(
        $user_id,
        'nac_certification_success_email_sent',
        1
    );

}

/* =========================
   LOAD MODULES
========================= */

$dashboard_file = NAC_PATH . 'includes/dashboard.php';
$exam_file      = NAC_PATH . 'includes/exam.php';
$cert_file      = NAC_PATH . 'includes/certificate.php';
$admin_file     = NAC_PATH . 'includes/admin-students.php';

if (file_exists($dashboard_file)) require_once $dashboard_file;
if (file_exists($exam_file)) require_once $exam_file;
if (file_exists($cert_file)) require_once $cert_file;
if (file_exists($admin_file)) require_once $admin_file;

/* ===================================
   LOGIN PAGE CUSTOMIZATION
=================================== */

add_action('login_enqueue_scripts', function() {
?>
<style>
body.login {
    background: linear-gradient(135deg,#0f172a,#1e293b);
}

#login {
    padding:40px 30px;
    background:#ffffff;
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
}

#login h1 a {
    background-image: url('https://skyblue-viper-538056.hostingersite.com/wp-content/uploads/2026/02/Nanotherm-new-logo.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    width: 240px;
    height: 90px;
}

.login #backtoblog,
.login #nav {
    text-align:center;
}

.login #backtoblog a {
    color:#111 !important;
    font-weight:600;
}

.login #nav a {
    color:#007bff !important;
}

.wp-core-ui .button-primary {
    background:#111;
    border:none;
}

.wp-core-ui .button-primary:hover {
    background:#007bff;
}
</style>
<?php
});

add_filter('login_headerurl', function() {
    return 'https://nanotherm.gr';
});

add_filter('login_headertext', function() {
    return 'Nanotherm Academy';
});

add_filter('login_site_html_link', function () {
    return '<a href="https://nanotherm.gr">← Go to Nanotherm.gr</a>';
});

/* ===================================
   FORCE LOGIN REDIRECT
=================================== */

add_filter('login_redirect', function($redirect_to, $request, $user){

    if (isset($user->roles) && is_array($user->roles)) {

        if (in_array('administrator', $user->roles)) {
            return $redirect_to;
        }

        return site_url('/dashboard/');
    }

    return $redirect_to;

}, 10, 3);

/* ===================================
   EXTENDED REGISTRATION FIELDS
=================================== */

add_action('register_form', function() {
?>

<p style="background:#f5f7fa;padding:10px;border-left:4px solid #1e73be;">
    <strong>Οδηγία:</strong> Συμπληρώστε όλα τα πεδία με τα σωστά και πλήρη στοιχεία σας,
    χωρίς λάθη, ώστε το Πιστοποιητικό να εκδοθεί σωστά.
</p>

<p>
    <label for="first_name">Όνομα<br/>
        <input type="text" name="first_name" id="first_name" class="input"
               value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>" size="25" />
    </label>
</p>

<p>
    <label for="last_name">Επώνυμο<br/>
        <input type="text" name="last_name" id="last_name" class="input"
               value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>" size="25" />
    </label>
</p>

<p>
    <label for="city">Πόλη<br/>
        <input type="text" name="city" id="city" class="input"
               value="<?php echo esc_attr($_POST['city'] ?? ''); ?>" size="25" />
    </label>
</p>

<p>
    <label for="phone">Τηλέφωνο<br/>
        <input type="text" name="phone" id="phone" class="input"
               value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" size="25" />
    </label>
</p>

<?php
});

add_filter('registration_errors', function($errors, $sanitized_user_login, $user_email) {

    if (empty($_POST['first_name'])) {
        $errors->add('first_name_error', '<strong>Σφάλμα:</strong> Το Όνομα είναι υποχρεωτικό.');
    }

    if (empty($_POST['last_name'])) {
        $errors->add('last_name_error', '<strong>Σφάλμα:</strong> Το Επώνυμο είναι υποχρεωτικό.');
    }

    if (empty($_POST['city'])) {
        $errors->add('city_error', '<strong>Σφάλμα:</strong> Η Πόλη είναι υποχρεωτική.');
    }

    if (empty($_POST['phone'])) {
        $errors->add('phone_error', '<strong>Σφάλμα:</strong> Το Τηλέφωνο είναι υποχρεωτικό.');
    }

    return $errors;

}, 10, 3);


add_action('user_register', function($user_id) {

    update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name'] ?? ''));
    update_user_meta($user_id, 'last_name',  sanitize_text_field($_POST['last_name'] ?? ''));
    update_user_meta($user_id, 'city',       sanitize_text_field($_POST['city'] ?? ''));
    update_user_meta($user_id, 'phone',      sanitize_text_field($_POST['phone'] ?? ''));

    $user = new WP_User($user_id);
    $user->set_role('nac_student');

}, 10, 1);

