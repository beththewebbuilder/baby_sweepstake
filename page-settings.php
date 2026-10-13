<?php
/*
Template Name: Baby Results
*/

// Check permissions before any of the page is output
if (!is_user_logged_in()) {
    // Send to the login page, then back here afterwards
    auth_redirect();
}
if (!current_user_can('manage_options')) {
    wp_die('Sorry, you do not have permission to view this page.', 403);
}

get_header();

// Save all settings
if (isset($_POST['save_settings'])) {

    check_admin_referer('save_baby_settings');

    // Baby details
    update_option('baby_name', sanitize_text_field($_POST['baby_name']));
    update_option('baby_gender', sanitize_text_field($_POST['gender']));
    update_option('baby_weight', intval($_POST['weight']));      // grams
    update_option('baby_length', intval($_POST['length']));    // cm
    update_option('baby_arrival', sanitize_text_field($_POST['arrival']));

    // Points
    update_option('family_name', sanitize_text_field($_POST['family_name']));
    update_option('points_gender_correct', intval($_POST['points_gender_correct']));
    update_option('points_gender_incorrect', intval($_POST['points_gender_incorrect']));
    update_option('points_weight_exact', intval($_POST['points_weight_exact']));
    // Deduction ranges are divisors and deductions drive the scoring loops, so they must never be 0
    update_option('points_weight_gram_deduction', max(1, intval($_POST['points_weight_gram_deduction'])));
    update_option('points_length_exact', intval($_POST['points_length_exact']));
    update_option('points_length_cm_deduction', max(1, intval($_POST['points_length_cm_deduction'])));
    update_option('points_correct_date', intval($_POST['points_correct_date']));
    update_option('points_day_deduction', max(1, intval($_POST['points_day_deduction'])));
    update_option('points_correct_time', intval($_POST['points_correct_time']));
    update_option('points_time_hr_difference', max(1, intval($_POST['points_time_hr_difference'])));
    update_option('points_time_hr_deduction', max(1, intval($_POST['points_time_hr_deduction'])));

    update_option('gender_guess_enabled', isset($_POST['gender_guess_enabled']) ? 1 : 0);
    update_option('total_points', baby_guess_total_points());

    // Only score guesses once all the baby's details are in (re-scored on every save, so points changes apply too).
    // The baby's gender is only needed if guests were asked to guess it
    $baby_details_complete = get_option('baby_name') !== ''
        && (!baby_guess_gender_enabled() || in_array(get_option('baby_gender'), ['boy', 'girl'], true))
        && get_option('baby_weight') > 0
        && get_option('baby_length') > 0
        && get_option('baby_arrival') !== '';

    // Results can only be published once the baby's details are complete
    $wants_results_shown = isset($_POST['baby_has_arrived']);
    update_option('baby_has_arrived', ($wants_results_shown && $baby_details_complete) ? 1 : 0);

    if ($baby_details_complete) {
        calculate_baby_sweepstake_results();
    }

    if ($wants_results_shown && !$baby_details_complete) {
        echo '<div class="container mt-3"><div class="alert alert-warning">Settings saved, but the results haven\'t been published - please fill in all the baby\'s details first.</div></div>';
    } elseif ($baby_details_complete) {
        echo '<div class="container mt-3"><div class="alert alert-success">🎉 Settings saved & winners calculated successfully!</div></div>';
    } else {
        echo '<div class="container mt-3"><div class="alert alert-success">🎉 Settings saved successfully!</div></div>';
    }
}

// Read existing values
$name  = get_option('baby_name', '');
$gender  = get_option('baby_gender', '');
$weight  = get_option('baby_weight', '');
$length  = get_option('baby_length', '');
$arrival = get_option('baby_arrival', '');

$family_name = get_option('family_name', '');
$correct_gender = get_option('points_gender_correct', '');
$incorrect_gender = get_option('points_gender_incorrect', '');
$weight_exact = get_option('points_weight_exact', '');
$weight_g_deduction = get_option('points_weight_gram_deduction', '');
$length_exact = get_option('points_length_exact', '');
$length_cm_deduction = get_option('points_length_cm_deduction', '');
$correct_date = get_option('points_correct_date', '');
$day_deduction = get_option('points_day_deduction', '');
$correct_time = get_option('points_correct_time', '');
$time_difference = get_option('points_time_hr_difference', '');
$time_deduction = get_option('points_time_hr_deduction', '');
$total_points = get_option('total_points', '');

?>

<div class="container py-5">    

    <form method="post">
    <?php wp_nonce_field('save_baby_settings'); ?>

    <div class="row">
        <div class="col-12 col-xl-6 mt-4">
            <h1>👶 Baby Results</h1>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="baby_has_arrived" id="baby_has_arrived" value="1"
                <?php checked(get_option('baby_has_arrived'), 1); ?>>

                    <label class="form-check-label" for="baby_has_arrived">
                        Baby has arrived 🎉
                    </label>
                    <div class="form-text">Ticking this publishes the results. The baby's details below are required once it's ticked.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Baby name</label>

                    <input
                        class="form-control"
                        name="baby_name"
                        value="<?php echo esc_attr($name); ?>"
                        data-required-when-arrived
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Gender</label>

                    <select class="form-select" name="gender" data-required-when-arrived data-gender-field>

                        <option value="">Select...</option>

                        <option value="boy" <?php selected($gender, 'boy'); ?>>
                            Boy
                        </option>

                        <option value="girl" <?php selected($gender, 'girl'); ?>>
                            Girl
                        </option>

                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Weight (grams)</label>

                    <input
                        class="form-control"
                        type="number"
                        name="weight"
                        value="<?php echo esc_attr($weight); ?>"
                        data-required-when-arrived
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Length (cm)</label>

                    <input
                        class="form-control"
                        type="number"
                        name="length"
                        value="<?php echo esc_attr($length); ?>"
                        data-required-when-arrived
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Arrival</label>

                    <input
                        class="form-control"
                        type="datetime-local"
                        name="arrival"
                        value="<?php echo esc_attr($arrival); ?>"
                        data-required-when-arrived
                    >
                </div>

        </div>

        <!-- score points -->
        <div class="col-12 col-xl-6 mt-4">
            <h1>📈 Allocated Points</h1>

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Family name (last name)</label>
                        <input class="form-control" name="family_name" value="<?php echo esc_attr($family_name); ?>" required>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="gender_guess_enabled" id="gender_guess_enabled" value="1"
                <?php checked(baby_guess_gender_enabled()); ?>>

                    <label class="form-check-label" for="gender_guess_enabled">
                        Guests guess the gender
                    </label>
                    <div class="form-text">Untick if the gender has already been shared. It will be hidden from the guess form and scoring.</div>
                </div>

                <div class="row mb-3" id="gender-points">
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Correct Gender</label>
                        <input class="form-control" type="number" name="points_gender_correct" value="<?php echo esc_attr($correct_gender); ?>" data-gender-field>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Incorrect Gender</label>
                        <input class="form-control" type="number" name="points_gender_incorrect" value="<?php echo esc_attr($incorrect_gender); ?>" data-gender-field>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Exact Weight</label>
                        <input class="form-control" type="number" name="points_weight_exact" value="<?php echo esc_attr($weight_exact); ?>" required >
                        <small class="form-text text-muted">Within the deduction range</small>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label">Weight Deduction (grams)</label>
                        <input class="form-control" type="number" name="points_weight_gram_deduction" min="1" value="<?php echo esc_attr($weight_g_deduction); ?>" required >
                        <small id="weightDeductionHelp" class="form-text text-muted">1 point deducted for every [entered g] away from actual.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Exact Length</label>
                        <input class="form-control" type="number" name="points_length_exact" value="<?php echo esc_attr($length_exact); ?>" required >
                        <small class="form-text text-muted">Within the deduction range</small>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label">Length Deduction (cm)</label>
                        <input class="form-control" type="number" name="points_length_cm_deduction" min="1" value="<?php echo esc_attr($length_cm_deduction); ?>" required >
                        <small id="lengthDeductionHelp" class="form-text text-muted">1 point deducted for every entered length(cm) away from actual.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Exact Arrival Date</label>
                        <input class="form-control" type="number" name="points_correct_date" value="<?php echo esc_attr($correct_date); ?>" required >
                        <small class="form-text text-muted">Total if selected date is correct</small>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label">Day Deduction</label>
                        <input class="form-control" type="number" name="points_day_deduction" min="1" value="<?php echo esc_attr($day_deduction); ?>" required >
                        <small class="form-text text-muted">Points deducted for every day away from actual. Make sure it's dividable by the total exact date points.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-sm-4">
                        <label class="form-label">Exact Arrival Time</label>
                        <input class="form-control" type="number" name="points_correct_time" value="<?php echo esc_attr($correct_time); ?>" required >
                        <small class="form-text text-muted">Only if selected date is correct, within difference range</small>
                    </div>

                    <div class="col-12 col-sm-4">
                        <label class="form-label">Hour Differnce</label>
                        <input class="form-control" type="number" name="points_time_hr_difference" min="1" value="<?php echo esc_attr($time_difference); ?>" required >
                        <small class="form-text text-muted">Hours within point deduction range.</small>
                    </div>

                    <div class="col-12 col-sm-4">
                        <label class="form-label">Time Deduction</label>
                        <input class="form-control" type="number" name="points_time_hr_deduction" min="1" value="<?php echo esc_attr($time_deduction); ?>" required >
                        <small class="form-text text-muted">Points deducted for selected no. of hours away.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <h2><strong>Total Points: <?php echo $total_points;?></strong></h2>
                    </div>
                </div>

        </div>
    </div>

    <div class="mt-4">
        <button
            class="btn btn-success btn-lg"
            type="submit"
            name="save_settings">
            💾 Save Settings
        </button>
        <div class="form-text">Once the baby's details are filled in, saving also recalculates everyone's scores.</div>
    </div>

    </form>

    <script>
        // The baby's details are only required once "Baby has arrived" is ticked,
        // and gender fields are only needed (and shown for points) when guests guess the gender
        const arrived = document.getElementById('baby_has_arrived');
        const genderEnabled = document.getElementById('gender_guess_enabled');
        const genderPoints = document.getElementById('gender-points');
        function updateRequiredFields() {
            document.querySelectorAll('[data-required-when-arrived]').forEach(function(field) {
                field.required = arrived.checked && (!field.hasAttribute('data-gender-field') || genderEnabled.checked);
            });
            genderPoints.querySelectorAll('[data-gender-field]').forEach(function(field) {
                field.required = genderEnabled.checked;
            });
            genderPoints.style.display = genderEnabled.checked ? '' : 'none';
        }
        arrived.addEventListener('change', updateRequiredFields);
        genderEnabled.addEventListener('change', updateRequiredFields);
        updateRequiredFields();
    </script>

</div>

<?php get_footer(); ?>