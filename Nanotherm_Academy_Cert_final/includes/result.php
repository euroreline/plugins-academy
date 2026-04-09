<?php
if (!defined('ABSPATH')) exit;

/*
====================================================
GET USER RESULT DATA
====================================================
*/

function nac_get_user_level_result($user_id, $level) {

    global $wpdb;

    $table = $wpdb->prefix . 'nac_progress';

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND level = %d",
            $user_id,
            $level
        )
    );
}


/*
====================================================
CHECK IF USER COMPLETED CERTIFICATION
====================================================
*/

function nac_is_user_certified($user_id) {

    global $wpdb;

    $table = $wpdb->prefix . 'nac_progress';

    $levels = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT level, passed FROM $table WHERE user_id = %d",
            $user_id
        )
    );

    if (!$levels) return false;

    foreach ($levels as $level) {

        if ($level->passed != 1) {

            return false;

        }

    }

    return true;

}


/*
====================================================
SEND USER TO GETRESPONSE AFTER LEVEL 4 SUCCESS
====================================================
*/

function nac_send_user_to_getresponse($user_id) {

    // prevent duplicate sending
    if (get_user_meta($user_id, 'nac_sent_to_getresponse', true)) {
        return;
    }

    $user = get_userdata($user_id);

    if (!$user) return;

    $email = $user->user_email;
    $username = $user->user_login;

    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name  = get_user_meta($user_id, 'last_name', true);
    $city       = get_user_meta($user_id, 'city', true);
    $phone      = get_user_meta($user_id, 'phone', true);

    $completion_date = current_time('mysql');

    $note  = "City: {$city}\n";
    $note .= "Phone: {$phone}\n";
    $note .= "Username: {$username}\n";
    $note .= "Completion Date: {$completion_date}\n";
    $note .= "Certification Status: Certified Nanotherm Applicator";

    $body = array(
        "email" => $email,
        "name"  => trim($first_name . ' ' . $last_name),
        "campaign" => array(
            "campaignId" => "L8ADw"
        ),
        "note" => $note
    );

    $response = wp_remote_post(
        'https://api.getresponse.com/v3/contacts',
        array(
            'headers' => array(
                'X-Auth-Token' => 'api-key i9cryes8d1o4ao6wy5ydxlhznht1x8v6',
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 20
        )
    );

    if (!is_wp_error($response)) {

        update_user_meta($user_id, 'nac_sent_to_getresponse', 'yes');

    }

}


/*
====================================================
HOOK AFTER LEVEL RESULT SAVED
====================================================
*/

add_action('nac_after_result_saved', function($user_id, $level, $passed) {

    if ($level == 4 && $passed == 1) {

        nac_send_user_to_getresponse($user_id);

    }

}, 10, 3);