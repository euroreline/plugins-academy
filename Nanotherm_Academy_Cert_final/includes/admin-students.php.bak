<?php
if (!defined('ABSPATH')) exit;

/* =====================================================
   SETTINGS
===================================================== */
define('NAC_REMINDER_TEST_EMAIL', 'blockart@gmail.com');
define('NAC_REMINDER_ADMIN_NOTIFY', 'euroreline@gmail.com');
define('NAC_REMINDER_DAY_1', 1);
define('NAC_REMINDER_DAY_4', 4);

/* =====================================================
   GETRESPONSE MANUAL SEND SETTINGS
===================================================== */
define('NAC_GETRESPONSE_API_KEY', 'i9cryes8d1o4ao6wy5ydxlhznht1x8v6');
define('NAC_GETRESPONSE_CAMPAIGN_ID', 'L8ADw');

/* =====================================================
   Ensure WP-Cron schedule exists (safe)
   (Αν ήδη έχεις schedule στο main plugin file, δεν δημιουργεί διπλό.)
===================================================== */
add_action('init', function () {
    if (!wp_next_scheduled('nac_daily_reminder_event')) {
        wp_schedule_event(time(), 'daily', 'nac_daily_reminder_event');
    }
});

/* =====================================================
   AUTO REMINDERS (stops at 4/4)
===================================================== */
add_action('nac_daily_reminder_event', function () {

    global $wpdb;

    $progress_table = $wpdb->prefix . 'nac_progress';
    $users = get_users(['role' => 'nac_student']);

    $admin_report = [];

    foreach ($users as $user) {

        $passed_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$progress_table} WHERE user_id = %d AND passed = 1",
                $user->ID
            )
        );

        // σταματάμε όταν γίνει 4/4
        if ($passed_count >= 4) continue;

        $registered_ts = strtotime($user->user_registered);
        $days = (int) floor((time() - $registered_ts) / 86400);

        $rem1 = get_user_meta($user->ID, 'nac_reminder_1_sent', true);
        $rem2 = get_user_meta($user->ID, 'nac_reminder_2_sent', true);

        // 1η ημέρα
        if ($days >= NAC_REMINDER_DAY_1 && !$rem1) {
            nac_send_branded_reminder_email($user->user_email, (string) get_user_meta($user->ID, 'first_name', true));
            update_user_meta($user->ID, 'nac_reminder_1_sent', 1);
            $admin_report[] = $user->user_email . ' (day 1)';
        }

        // 4η ημέρα
        if ($days >= NAC_REMINDER_DAY_4 && !$rem2) {
            nac_send_branded_reminder_email($user->user_email, (string) get_user_meta($user->ID, 'first_name', true));
            update_user_meta($user->ID, 'nac_reminder_2_sent', 1);
            $admin_report[] = $user->user_email . ' (day 4)';
        }
    }

    if (!empty($admin_report)) {
        wp_mail(
            NAC_REMINDER_ADMIN_NOTIFY,
            'Nanotherm Academy – Reminder Report',
            "Sent reminders to:\n\n" . implode("\n", $admin_report)
        );
    }
});

/* =====================================================
   Branded Reminder Email (HTML) - SAME TEMPLATE FOR REAL + TEST
===================================================== */
function nac_send_branded_reminder_email(string $to_email, string $first_name = ''): bool {

    $first_name = trim($first_name) ?: 'συνεργάτη';
    $dashboard = site_url('/dashboard/');

    $subject = "Ολοκλήρωσε την Πιστοποίησή σου – Μένει μόνο ένα βήμα";
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $message = '
    <div style="font-family:Arial;background:#f4f6f9;padding:40px">
      <div style="max-width:620px;margin:auto;background:#ffffff;padding:30px;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.06)">
        <h2 style="margin:0 0 10px;color:#0b3c5d;">Nanotherm Academy</h2>
        <p style="margin:0 0 18px;color:#111;">Αγαπητέ ' . esc_html($first_name) . ',</p>

        <p style="color:#333;line-height:1.6;">
          Ξεκίνησες την εκπαίδευση NanoTherm αλλά δεν την έχεις ολοκληρώσει ακόμη.
          Η πιστοποίηση είναι σημαντική γιατί ενισχύει την αξιοπιστία σου ως εφαρμοστής.
        </p>

        <div style="text-align:center;margin:26px 0;">
          <a href="' . esc_url($dashboard) . '" style="background:#0b3c5d;color:#fff;padding:12px 22px;text-decoration:none;border-radius:7px;display:inline-block;">
            Συνέχισε την Πιστοποίηση
          </a>
        </div>

        <p style="color:#333;line-height:1.6;">
          Αν αντιμετώπισες οποιοδήποτε πρόβλημα ή χρειάζεσαι βοήθεια, στείλε μας μήνυμα στο
          <strong>euroreline@gmail.com</strong>.
        </p>

        <p style="color:#8a8a8a;font-size:12px;margin-top:22px;">
          NanoTherm Academy – Professional Certification Program
        </p>
      </div>
    </div>';

    return (bool) wp_mail($to_email, $subject, $message, $headers);
}

/* =====================================================
   MANUAL SEND TO GETRESPONSE
===================================================== */
function nac_send_user_to_getresponse_admin_manual(int $user_id) {

    $user = get_userdata($user_id);

    if (!$user) {
        return new WP_Error('nac_no_user', 'User not found.');
    }

    $email = trim((string) $user->user_email);

    if ($email === '' || !is_email($email)) {
        return new WP_Error('nac_invalid_email', 'Invalid email.');
    }

    $first_name = trim((string) get_user_meta($user_id, 'first_name', true));
    $last_name  = trim((string) get_user_meta($user_id, 'last_name', true));
    $city       = trim((string) get_user_meta($user_id, 'city', true));
    $phone      = trim((string) get_user_meta($user_id, 'phone', true));
    $username   = trim((string) $user->user_login);

    $full_name = trim($first_name . ' ' . $last_name);
    if ($full_name === '') {
        $full_name = $username !== '' ? $username : $email;
    }

    global $wpdb;
    $progress_table = $wpdb->prefix . 'nac_progress';
    $cert_table     = $wpdb->prefix . 'nac_certificates';

    $passed_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$progress_table} WHERE user_id = %d AND passed = 1",
            $user_id
        )
    );

    $cert = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$cert_table} WHERE user_id = %d",
            $user_id
        )
    );

    $completion_date = '';
    if ($cert && !empty($cert->issued_at)) {
        $completion_date = $cert->issued_at;
    } elseif ($passed_count >= 4) {
        $completion_date = current_time('mysql');
    }

    $note_lines = [
        'Username: ' . $username,
        'First Name: ' . $first_name,
        'Last Name: ' . $last_name,
        'City: ' . $city,
        'Phone: ' . $phone,
        'Email: ' . $email,
        'Certification Status: ' . ($passed_count >= 4 ? 'Certified Nanotherm Applicator' : 'Incomplete'),
        'Completion Date: ' . $completion_date,
    ];

    if ($cert && !empty($cert->certificate_id)) {
        $note_lines[] = 'Certificate ID: ' . $cert->certificate_id;
    }

    $body = [
        'name'     => $full_name,
        'email'    => $email,
        'campaign' => [
            'campaignId' => NAC_GETRESPONSE_CAMPAIGN_ID,
        ],
        'note'     => implode("\n", $note_lines),
    ];

    $response = wp_remote_post(
        'https://api.getresponse.com/v3/contacts',
        [
            'timeout' => 20,
            'headers' => [
                'X-Auth-Token' => 'api-key ' . NAC_GETRESPONSE_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $resp_body = (string) wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        return true;
    }

    if ($code === 409) {
        return true;
    }

    return new WP_Error(
        'nac_getresponse_error',
        'GetResponse error. HTTP ' . $code . '. ' . $resp_body
    );
}

/* =====================================================
   TEST REMINDER (ALWAYS SEND, ignores 4/4) - NOW SAME TEMPLATE
===================================================== */
add_action('admin_post_nac_test_reminder', function () {

    if (!current_user_can('manage_options')) {
        wp_die('No permission.');
    }

    // ΠΑΝΤΑ στέλνει στο blockart@gmail.com με το ΙΔΙΟ template
    $sent = nac_send_branded_reminder_email(NAC_REMINDER_TEST_EMAIL, 'Test');

    wp_redirect(admin_url('admin.php?page=nac_students&test_sent=' . ($sent ? '1' : '0')));
    exit;
});

/* =====================================================
   AJAX: MANUAL SEND TO GETRESPONSE
===================================================== */
add_action('wp_ajax_nac_send_to_getresponse_manual', function () {

    if (!current_user_can('manage_options')) {
        wp_send_json_error('No permission.');
    }

    check_ajax_referer('nac_send_to_getresponse_manual_nonce', 'nonce');

    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    if ($user_id <= 0) {
        wp_send_json_error('Invalid user.');
    }

    $result = nac_send_user_to_getresponse_admin_manual($user_id);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success('Sent successfully.');
});

/* =====================================================
   Students List Table
===================================================== */
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class NAC_Students_List_Table extends WP_List_Table {

    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'name'        => 'Όνομα',
            'email'       => 'Email',
            'city'        => 'Πόλη',
            'phone'       => 'Τηλέφωνο',
            'level1'      => 'L1',
            'level2'      => 'L2',
            'level3'      => 'L3',
            'level4'      => 'L4',
            'status'      => 'Status',
            'certificate' => 'Certificate ID',
            'issued'      => 'Issued',
            'actions'     => 'Actions',
            'registered'  => 'Registered'
        ];
    }

    protected function column_cb($item) {
        return '<input type="checkbox" name="users[]" value="' . esc_attr($item['user_id']) . '" />';
    }

    protected function column_actions($item) {

        if (($item['status'] ?? '') !== 'Certified') return '—';

        $uid = (int) $item['user_id'];

        // compatible with your certificate.php
        $preview  = site_url('/?preview_certificate=1&user_id=' . $uid);
        $email    = site_url('/?email_certificate=1&user_id=' . $uid);
        $reissue  = site_url('/?download_certificate=1&user_id=' . $uid);

        return
            '<a class="button button-small" target="_blank" href="' . esc_url($preview) . '">Preview</a> ' .
            '<a class="button button-small" href="' . esc_url($email) . '">Email</a> ' .
            '<a class="button button-small" href="' . esc_url($reissue) . '">Reissue</a> ' .
            '<button type="button" class="button button-small nac-send-getresponse-btn" data-user-id="' . esc_attr($uid) . '">Send to GetResponse</button>';
    }

    public function get_bulk_actions() {
        return [
            'export_selected' => 'Export Selected CSV',
            'export_all'      => 'Export All'
        ];
    }

    public function prepare_items() {

        global $wpdb;

        $progress_table = $wpdb->prefix . 'nac_progress';
        $cert_table     = $wpdb->prefix . 'nac_certificates';

        $users = get_users(['role' => 'nac_student']);
        $data  = [];

        foreach ($users as $user) {

            $levels = [];
            for ($i = 1; $i <= 4; $i++) {
                $row = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT passed FROM {$progress_table} WHERE user_id=%d AND level=%d",
                        $user->ID, $i
                    )
                );
                $levels[$i] = ($row && (int)$row->passed === 1) ? '✔' : '—';
            }

            $passed_count = (int)$wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$progress_table} WHERE user_id=%d AND passed=1",
                    $user->ID
                )
            );

            $cert = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$cert_table} WHERE user_id=%d",
                    $user->ID
                )
            );

            $data[] = [
                'user_id'     => $user->ID,
                'name'        => trim(get_user_meta($user->ID,'first_name',true) . ' ' . get_user_meta($user->ID,'last_name',true)),
                'email'       => $user->user_email,
                'city'        => get_user_meta($user->ID,'city',true),
                'phone'       => get_user_meta($user->ID,'phone',true),
                'level1'      => $levels[1],
                'level2'      => $levels[2],
                'level3'      => $levels[3],
                'level4'      => $levels[4],
                'status'      => ($passed_count >= 4 ? 'Certified' : 'Incomplete'),
                'certificate' => $cert ? $cert->certificate_id : '',
                'issued'      => $cert ? date('d/m/Y', strtotime($cert->issued_at)) : '',
                'registered'  => date('d/m/Y', strtotime($user->user_registered)),
            ];
        }

        $this->items = $data;
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '';
    }
}

/* =====================================================
   CSV EXPORT
===================================================== */
add_action('admin_init', function () {

    if (!current_user_can('manage_options')) return;

    $action = $_POST['action'] ?? '';

    if ($action === '-1' || $action === '') {
        $action = $_POST['action2'] ?? '';
    }

    if ($action !== 'export_selected' && $action !== 'export_all') return;

    global $wpdb;

    $progress_table = $wpdb->prefix . 'nac_progress';
    $cert_table     = $wpdb->prefix . 'nac_certificates';

    $users = [];

    if ($action === 'export_selected' && !empty($_POST['users'])) {
        foreach ((array)$_POST['users'] as $id) {
            $users[] = get_user_by('id', (int)$id);
        }
    }

    if ($action === 'export_all') {
        $users = get_users(['role' => 'nac_student']);
    }

    header('Content-Type:text/csv; charset=utf-8');
    header('Content-Disposition:attachment; filename=nanotherm-students.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['Name','Email','City','Phone','L1','L2','L3','L4','Status','Certificate ID','Issued','Registered']);

    foreach ($users as $user) {
        if (!$user) continue;

        $levels = [];
        for ($i = 1; $i <= 4; $i++) {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT passed FROM {$progress_table} WHERE user_id=%d AND level=%d",
                    $user->ID, $i
                )
            );
            $levels[$i] = ($row && (int)$row->passed === 1) ? 'Yes' : 'No';
        }

        $passed_count = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$progress_table} WHERE user_id=%d AND passed=1",
                $user->ID
            )
        );

        $cert = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$cert_table} WHERE user_id=%d",
                $user->ID
            )
        );

        fputcsv($output, [
            trim(get_user_meta($user->ID,'first_name',true) . ' ' . get_user_meta($user->ID,'last_name',true)),
            $user->user_email,
            get_user_meta($user->ID,'city',true),
            get_user_meta($user->ID,'phone',true),
            $levels[1], $levels[2], $levels[3], $levels[4],
            ($passed_count >= 4 ? 'Certified' : 'Incomplete'),
            $cert ? $cert->certificate_id : '',
            $cert ? $cert->issued_at : '',
            $user->user_registered,
        ]);
    }

    fclose($output);
    exit;
});

/* =====================================================
   ADMIN MENU + PAGES
===================================================== */
add_action('admin_menu', function () {

    add_menu_page(
        'Nanotherm Academy',
        'Nanotherm Academy',
        'manage_options',
        'nac_students',
        'nac_students_page',
        'dashicons-welcome-learn-more',
        26
    );

    add_submenu_page(
        'nac_students',
        'Test Reminder',
        'Test Reminder',
        'manage_options',
        'nac_test_reminder',
        'nac_test_reminder_page'
    );
});

function nac_students_page() {

    echo '<div class="wrap"><h1>Nanotherm Academy – Students</h1>';

    if (isset($_GET['sent'])) {
        echo '<div class="notice notice-success"><p>Το πιστοποιητικό στάλθηκε με email.</p></div>';
    }

    if (isset($_GET['test_sent'])) {
        echo '<div class="notice notice-' . ($_GET['test_sent'] == '1' ? 'success' : 'error') . '"><p>'
            . ($_GET['test_sent'] == '1'
                ? 'Test reminder sent to blockart@gmail.com'
                : 'Test reminder FAILED. Check WP Mail SMTP logs.')
            . '</p></div>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">
        <input type="hidden" name="action" value="nac_test_reminder">
        <button class="button button-primary">Send Test Reminder (blockart@gmail.com)</button>
    </form><br>';

    $table = new NAC_Students_List_Table();
    $table->prepare_items();

    echo '<form method="post">';
    $table->display();
    echo '</form>';

    echo '</div>';
}

function nac_test_reminder_page() {

    echo '<div class="wrap"><h1>Test Reminder</h1>';

    echo '<p>Στέλνει το <strong>ίδιο</strong> branded reminder email στο <strong>blockart@gmail.com</strong> (αγνοεί τον κανόνα 4/4).</p>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">
        <input type="hidden" name="action" value="nac_test_reminder">
        <button class="button button-primary">Send Test Reminder Now</button>
    </form>';

    echo '</div>';
}

/* =====================================================
   ADMIN FOOTER JS FOR MANUAL GETRESPONSE SEND
===================================================== */
add_action('admin_footer', function () {

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || $screen->id !== 'toplevel_page_nac_students') {
        return;
    }

    $nonce = wp_create_nonce('nac_send_to_getresponse_manual_nonce');
    ?>
    <script>
    jQuery(function($){
        $(document).on('click', '.nac-send-getresponse-btn', function(e){
            e.preventDefault();

            var $btn = $(this);
            var userId = $btn.data('user-id');

            if (!userId) {
                alert('Missing user id.');
                return;
            }

            $btn.prop('disabled', true).text('Sending...');

            $.post(ajaxurl, {
                action: 'nac_send_to_getresponse_manual',
                nonce: '<?php echo esc_js($nonce); ?>',
                user_id: userId
            })
            .done(function(response){
                if (response && response.success) {
                    $btn.text('Sent');
                } else {
                    var msg = (response && response.data) ? response.data : 'Failed.';
                    alert(msg);
                    $btn.prop('disabled', false).text('Send to GetResponse');
                }
            })
            .fail(function(){
                alert('AJAX request failed.');
                $btn.prop('disabled', false).text('Send to GetResponse');
            });
        });
    });
    </script>
    <?php
});