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

// Save the form
if (isset($_POST['save_results'])) {

    check_admin_referer('save_baby_results');

    update_option('baby_has_arrived', isset($_POST['baby_has_arrived']) ? 1 : 0);
    update_option('baby_name', sanitize_text_field($_POST['baby_name']));
    update_option('baby_gender', sanitize_text_field($_POST['gender']));
    update_option('baby_weight', intval($_POST['weight']));      // grams
    update_option('baby_length', intval($_POST['length']));    // cm
    update_option('baby_arrival', sanitize_text_field($_POST['arrival']));

    calculate_baby_sweepstake_results();

    echo '<div class="container mt-3"><div class="alert alert-success">🎉 Baby results saved & winners calculated successfully!</div></div>';
}

// Save point form
if(isset($_POST['save_points'])) {
    check_admin_referer('save_result_points');

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

    // Maximum available points (incorrect gender points are not part of the best possible score)
    $totalPoints = intval($_POST['points_gender_correct']) + intval($_POST['points_weight_exact']) + intval($_POST['points_length_exact']) + intval($_POST['points_correct_date']) +intval($_POST['points_correct_time']);
    update_option('total_points', intval($totalPoints));

    echo '<div class="container mt-3"><div class="alert alert-success">🎉 Guess points saved successfully!</div></div>';
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

    <div class="row">
        <div class="col-12 col-xl-6 mt-4">
            <h1>👶 Baby Results</h1>

            <form method="post">
                <?php wp_nonce_field('save_baby_results'); ?>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="baby_has_arrived" value="1"
                <?php checked(get_option('baby_has_arrived'), 1); ?>>

                    <label class="form-check-label">
                        Baby has arrived 🎉
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Baby name</label>

                    <input
                        class="form-control"
                        name="baby_name"
                        value="<?php echo esc_attr($name); ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Gender</label>

                    <select class="form-select" name="gender">

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
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Length (cm)</label>

                    <input
                        class="form-control"
                        type="number"
                        name="length"
                        value="<?php echo esc_attr($length); ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Arrival</label>

                    <input
                        class="form-control"
                        type="datetime-local"
                        name="arrival"
                        value="<?php echo esc_attr($arrival); ?>"
                        required
                    >
                </div>

                <button 
                    class="btn btn-success btn-lg"
                    type="submit"
                    name="save_results">
                    💾 Save Results & Calculate Winners
                </button>

            </form>
        </div>

        <!-- score points -->
        <div class="col-12 col-xl-6 mt-4">
            <h1>📈 Allocated Points</h1>

            <form method="post">

                <?php wp_nonce_field('save_result_points'); ?>

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Family name (last name)</label>
                        <input class="form-control" name="family_name" value="<?php echo esc_attr($family_name); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Correct Gender</label>
                        <input class="form-control" type="number" name="points_gender_correct" value="<?php echo esc_attr($correct_gender); ?>" required >
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Incorrect Gender</label>
                        <input class="form-control" type="number" name="points_gender_incorrect" value="<?php echo esc_attr($incorrect_gender); ?>" required >
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

                <button 
                    class="btn btn-primary btn-lg"
                    type="submit"
                    name="save_points">
                    Save Settings
                </button>

            </form>
        </div>
    </div>
    
</div>

<?php get_footer(); ?>