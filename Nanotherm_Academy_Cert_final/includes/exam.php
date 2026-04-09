<?php
if (!defined('ABSPATH')) exit;

add_shortcode('nac_exam', 'nac_exam_shortcode');

function nac_exam_shortcode($atts){

    if (!is_user_logged_in()) {
        return '<p>Πρέπει να συνδεθείτε για να δώσετε την εξέταση.</p>';
    }

    $atts = shortcode_atts(['level' => 1], $atts);
    $level = intval($atts['level']);

    global $wpdb;
    $table = $wpdb->prefix . 'nac_progress';
    $user_id = get_current_user_id();

    $json_path = NAC_PATH . "assets/questions/level{$level}.json";

    if (!file_exists($json_path)) {
        return '<p>Δεν βρέθηκαν ερωτήσεις.</p>';
    }

    $data = json_decode(file_get_contents($json_path), true);

    if (!$data) {
        return '<p>Σφάλμα φόρτωσης δεδομένων.</p>';
    }

    $total_questions = count($data['questions']);

    ob_start();

    echo '<div class="nac-wrapper">';
    echo '<div class="nac-progress-label">Ενότητα '.$level.' / 4</div>';
    echo '<h2>'.$data['title'].'</h2>';

    // =========================
    // SUBMIT LOGIC
    // =========================

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nac_submit_exam'])) {

        $correct = 0;

        foreach ($data['questions'] as $index => $question) {
            $field = 'question_' . $index;

            if (isset($_POST[$field]) && $_POST[$field] === $question['correct']) {
                $correct++;
            }
        }

        $percentage = round(($correct / $total_questions) * 100);

        // ✅ Threshold 70%
        $passed_now = ($percentage >= 70) ? 1 : 0;

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d AND level = %d",
                $user_id,
                $level
            )
        );

        if ($existing) {

            if ($percentage > $existing->score) {

                $wpdb->update(
                    $table,
                    [
                        'score' => $percentage,
                        'passed' => ($percentage >= 70 ? 1 : 0),
                        'completed_at' => ($percentage >= 70 ? current_time('mysql') : null)
                    ],
                    ['user_id' => $user_id, 'level' => $level]
                );
            }

        } else {

            $wpdb->insert(
                $table,
                [
                    'user_id' => $user_id,
                    'level' => $level,
                    'score' => $percentage,
                    'passed' => $passed_now,
                    'completed_at' => $passed_now ? current_time('mysql') : null
                ]
            );
        }

        // =========================
        // LEVEL 4 SUCCESS EMAIL TRIGGER
        // =========================

       if ($passed_now && $level == 4 && function_exists('nac_maybe_send_certification_success_emails')) {
    nac_maybe_send_certification_success_emails($user_id);
}
        // =========================
        // RESULT SCREEN
        // =========================

        echo '<div class="nac-result">';
        echo '<h3>Αποτέλεσμα Εξέτασης</h3>';

        echo '<div class="nac-score-circle">'.$percentage.'%</div>';
        echo '<p><strong>Σωστές απαντήσεις:</strong> '.$correct.' / '.$total_questions.'</p>';

        if ($passed_now) {

            echo '<p class="nac-pass">✔ Ολοκληρώθηκε επιτυχώς η Ενότητα '.$level.'.</p>';
            echo '<p>Ξεκλειδώθηκε η επόμενη ενότητα.</p>';
            echo '<p>Μεταφορά στο Dashboard σε <span id="nac-countdown">3</span>...</p>';

            echo '
            <script>
                let seconds = 3;
                let countdown = document.getElementById("nac-countdown");

                let interval = setInterval(function(){
                    seconds--;
                    if(seconds <= 0){
                        clearInterval(interval);
                        window.location.href = "'.site_url('/dashboard/').'";
                    } else {
                        countdown.textContent = seconds;
                    }
                },1000);
            </script>
            ';

        } else {

            echo '<p class="nac-fail">✘ Δυστυχώς θέλεις ακόμα λίγο για να περάσεις. Μπορείς να ξαναδοκιμάσεις.</p>';

            echo '<div style="margin-top:20px;">';
            echo '<a href="' . site_url('/level' . $level) . '" style="display:inline-block;padding:12px 24px;background:#0b3c5d;color:#fff;text-decoration:none;border-radius:6px;">Ξαναδοκίμασε την Ενότητα</a>';
            echo '</div>';

        }

        echo '</div>';
        echo '</div>';

        return ob_get_clean();
    }

    // =========================
    // THEORY
    // =========================

    if (!empty($data['theory'])) {
        echo '<div class="nac-theory">';
        echo $data['theory'];
        echo '</div>';
    }

    // =========================
    // QUIZ FORM
    // =========================

    echo '<form method="post" class="nac-quiz">';

    foreach ($data['questions'] as $index => $question) {

        echo '<div class="nac-question">';
        echo '<p><strong>'.($index+1).'. '.$question['q'].'</strong></p>';

        foreach ($question['a'] as $answer) {
            echo '<label>';
            echo '<input type="radio" name="question_'.$index.'" value="'.esc_attr($answer).'" required> ';
            echo esc_html($answer);
            echo '</label>';
        }

        echo '</div>';
    }

    echo '<button type="submit" name="nac_submit_exam" class="nac-btn">Υποβολή Εξέτασης</button>';
    echo '</form>';

    echo '</div>';

    return ob_get_clean();
}