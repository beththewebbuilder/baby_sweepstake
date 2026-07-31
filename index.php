<?php
global $wpdb;

$table = $wpdb->prefix . 'guess_list';
$showResults = get_option('baby_has_arrived');

$user_guesses = $wpdb->get_results("SELECT * FROM $table ORDER BY respond DESC");
if($showResults) {
    $user_guesses = $wpdb->get_results("SELECT * FROM $table ORDER BY total_points DESC");
}

if (isset($_POST['save_predictions'])) {

    check_admin_referer('baby_save_prediction');

    $created_at = date('Y-m-d H:i:s');
        
    $weight_grams = 0;
    $weight_kg = isset($_POST['weight-kg']) ? floatval($_POST['weight-kg']) : 0;
    $weight_pound = isset($_POST['weight-pound']) ? intval($_POST['weight-pound']) : 0;
    $weight_ounce = isset($_POST['weight-ounce']) ? intval($_POST['weight-ounce']) : 0;

    if ($weight_kg > 0) {
        // Metric conversion
        $weight_grams = round($weight_kg * 1000);
    } else {
        // Imperial conversion
        $weight_grams = round(
            ($weight_pound * 453.592) +
            ($weight_ounce * 28.3495)
        );
    }

    $result = $wpdb->insert(
        $wpdb->prefix . 'guess_list',
        [
            'name'     => sanitize_text_field($_POST['name']),
            'gender'   => sanitize_text_field($_POST['gender']),
            'weight'   => $weight_grams,
            'length'   => intval($_POST['length']),
            'arrival'  => $_POST['arrival-datetime'],
            'respond'  => $created_at,
        ]
    );
    $guess_id = $wpdb->insert_id;

    if ($result === false) {
        die($wpdb->last_error);
    }
    wp_safe_redirect(
        get_permalink() . '?saved=1&guess=' . $guess_id . '#guesses'
    );
    exit;
}

$stats = $wpdb->get_row("
    SELECT
        COUNT(*) AS total_guesses,
        SUM(gender = 'girl') AS girls,
        SUM(gender = 'boy') AS boys,
        AVG(weight) AS avg_weight,
        AVG(length) AS avg_length,
        AVG(UNIX_TIMESTAMP(arrival)) AS avg_arrival
    FROM $table
");
$girl_percentage = round(($stats->girls / $stats->total_guesses) * 100);
$boy_percentage  = 100 - $girl_percentage;

$average_weight  = round($stats->avg_weight);
$average_length  = round($stats->avg_length, 1);
$average_arrival = date('j M Y H:i', round($stats->avg_arrival));

$top_winners = $wpdb->get_results("
    SELECT *
    FROM $table
    ORDER BY total_points DESC,
    arrival_total_points DESC
    LIMIT 3
");
$weight_winner = $wpdb->get_row("SELECT * FROM $table ORDER BY weight_points DESC LIMIT 1");
$length_winner = $wpdb->get_row("SELECT * FROM $table ORDER BY length_points DESC LIMIT 1");
$arrival_winner = $wpdb->get_row("SELECT * FROM $table ORDER BY arrival_total_points DESC LIMIT 1");

function create_prediction_card($showResults, $header, $prediction, $secondary_prediction, $showDifference, $differenceSymbol, $difference, $differenceMetric, $points, $showSuccess) {
    $showValuesClass = "";
    $exactMatchClass = ($difference == 0) ? '' : 'd-none';
    $showDifferenceClass = (($difference == 0) || !$showDifference) ? 'd-none' : '';
    $showSecondaryValue = empty($secondary_prediction) ? 'd-none' : '';
    $pointPlus = "+";

    if (!$showResults) {
        $class = 'border-light';
        $showValuesClass = "d-none";
    }
    elseif ($difference == 0 || $showSuccess) {
        $class = 'border-success bg-success-subtle';
    }
    elseif ($points > 0) {
        $class = 'border-primary bg-primary-subtle';
    }
    else {
        $class = 'border-secondary bg-light';
        $pointPlus = "";
    }

    return '<div class="col-6 col-md-3 mb-3">
        <div class="border rounded p-3 h-100 ' . $class .'">
            <div class="small text-muted">'. $header .'</div>
            <div class="fs-5">'. $prediction .'
            <small class="text-muted '.$showSecondaryValue.'">
                ('.$secondary_prediction.')
            </small></div>
            <div class="'. $showValuesClass .'">
                <hr class="my-2">
                <div class="'.$exactMatchClass.' text-success fw-bold small">🎯 Exact match!</div>
                <div class="'.$showDifferenceClass.' small text-muted">'. $differenceSymbol . $difference . $differenceMetric . '</div>
                <div class="fw-bold mt-1">'. $pointPlus . $points .'pts</div>
            </div>
        </div>
    </div>';
}
get_header();
?>

<div class="background-fade"></div>

<div class="center-content">

    <img class="logo-header"
         src="<?php echo esc_url(get_template_directory_uri() . '/luffman_guess.png'); ?>"
         alt="Baby Luffman">

    <?php if (!$showResults): ?>
        <h1>Make your predictions!</h1>
        <p class="subtitle">
            Guess the <strong>gender</strong>,
            <strong>weight</strong>,
            <strong>length</strong> and
            <strong>arrival</strong> of Baby <?php echo esc_html(get_option('family_name', ''));?>!
        </p>
        <p>The most accurate guesses will earn the most points - maximum <?php echo get_option('total_points'); ?> points available!</p>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#howScoringWorks">
                <small>How does the scoring work?</small>
            </button>
        </p>

        <?php else: ?>
            <h1>The results are in!</h1>
            <p> Find out how you did!</p>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#howScoringWorks">
                <small>Remind me, how was the scoring calculated?</small>
            </button>
    <?php endif; ?>

    <!-- Modal -->
    <div class="modal fade" id="howScoringWorks" tabindex="-1" aria-labelledby="howScoringWorksLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h1 class="modal-title fs-5" id="howScoringWorksLabel">How Scoring Works</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-start">
            <p>Once the baby arrives, each prediction will be compared with the baby's actual birth details.</p>
            <p>Points are awarded for each category based on <strong>how close your guess is</strong>.</p>

            <h2>👶 Gender</h2>
            <ul>
                <li>Correct guess: <strong><?php echo esc_html(get_option('points_gender_correct', ''));?> points</strong>
                <li>Incorrect guess: <strong><?php echo esc_html(get_option('points_gender_incorrect', ''));?> points</strong>
            </ul>

            <h2>⚖️ Birth Weight</h2>
            <ul>
                <li>Exact prediction: <strong><?php echo esc_html(get_option('points_weight_exact', ''));?> points</strong></li>
                <li>1 point is deducted for every <Strong><?php echo esc_html(get_option('points_weight_gram_deduction', ''));?>g 
                <em>(approx.
                    <?php
                        $grams = (int)get_option('points_weight_gram_deduction', '');
                        $oz = round($grams / 28.3495);
                        echo esc_html($oz . "oz");
                    ?>)</em></strong> your guess is away from the actual birth weight (minimum 0 points).</li>
            </ul>
        
            <h2>📏 Birth Length</h2>
            <ul>
                <li>Exact prediction: <strong><?php echo esc_html(get_option('points_length_exact', ''));?> points</strong></li>
                <li>1 point is deducted for every <strong><?php echo esc_html(get_option('points_length_cm_deduction', ''));?>cm</strong> your guess is away from the actual birth length (minimum 0 points).</li>
                
            </ul>

            <h2>📅 Birth Date & Time</h2>
            <p>
                The closer your predicted date is to the actual birth, the more points you receive. Points reduce by <strong><?php echo esc_html(get_option('points_day_deduction', '')); ?></strong> for each additional day (minimum 0 points).
            </p>
            <ul>
                <?php 
                $pt = get_option('points_correct_date', '');
                $deduct = get_option('points_day_deduction', '');
                $day = 0;
                $dayWording = "day";
                while($pt > 0) {
                    if($day == 0) {
                        echo '<li>Correct date: <strong>' . $pt . ' points</strong></li>';
                    }
                    else {
                        echo '<li>'.$day.' '.$dayWording.' away: <strong>'.$pt.' points</strong></li>';
                    }
                    $pt -= $deduct;
                    $day += 1;
                    if($day > 1) {
                        $dayWording = "days";
                    }
                }
                ?>
            </ul>
            <p>Only if you have the <strong>correct date</strong>, you can get additional points for the predicted time.</p>
            <ul>
                <?php 
                $pt = get_option('points_correct_time', '');
                $pt_to_deduct = get_option('points_time_hr_deduction', '');
                $hrs_in_range = get_option('points_time_hr_difference', '');
                $hrs_difference = $hrs_in_range;
                
                while($pt >= 0) {
                    if($day == 0) {
                        echo '<li>≤'.$hrs_in_range.' hours from correct birth time: <strong>' . $pt . ' points</strong></li>';
                    }
                    else if($pt == 0) {
                        echo '<li>More than '.$hrs_difference.' hours: <strong>'.$pt.' points</strong></li>';
                    }
                    else {
                        echo '<li>≤'.$hrs_difference.' hours: <strong>'.$pt.' points</strong></li>';
                    }

                    $pt -= $pt_to_deduct;
                    if($pt > 0) {
                        $hrs_difference += $hrs_in_range;
                    } 
                    
                }
                ?>
            </ul>

            <h2>🏆 Winner</h2>
            <p>Your total score is the sum of all four categories, with a maximum possible score of <strong><?php echo esc_html(get_option('total_points', '')) ?> points</strong>.</p>
            <p>The person with the <strong>highest total score wins</strong>.</p>
            <p>If two or more people finish with the same score, the winner will be decided by the closest <strong>Birth Date & Time</strong> prediction.</p>

            <p class="text-muted fst-italic mt-4">These rules have been carefully documented to avoid any future <?php echo esc_html(get_option('family_name', ''));?> debates, appeals, VAR reviews or accusations of favouritism. 😂</p>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">👍 Makes sense</button>
        </div>
        </div>
    </div>
    </div>
    
    <!-- Baby results -->
    <?php if ($showResults): ?>

        <div class="card bg-light border-2 border-success shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h4">🥳 Baby <?php echo esc_html(get_option('family_name', ''));?> has arrived!</h2>

            <div>
                <p>The official results of Baby <strong><?php echo get_option('baby_name'); ?></strong> <?php echo esc_html(get_option('family_name', ''));?>'s grand entrance</p>

                <div class="row text-center">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 border-lg h-100">
                            <div><strong>Gender</strong></div>
                            <div class="fs-5">
                                <?php echo ucfirst(get_option('baby_gender')); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 border-lg h-100">
                            <div><strong>Born</strong></div>
                            <div class="fs-5">
                                <?php echo esc_html(date('j M Y H:i', strtotime(get_option('baby_arrival')))); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mt-3 mt-md-0">
                        <div class="border rounded p-3 border-lg h-100">
                            <div><strong>Weight</strong></div>
                            <div class="fs-5">
                                <?php
                                $grams_result = (int)get_option('baby_weight');;
                                $lbs_result = floor($grams_result / 453.59237);
                                $oz_result = round(($grams_result - ($lbs_result * 453.59237)) / 28.3495);
                                echo esc_html($lbs_result . "lb " . $oz_result . "oz");
                            ?> (<?php echo esc_html(round(($grams_result / 1000), 2));?>kg)
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mt-3 mt-md-0">
                        <div class="border rounded p-3 border-lg h-100">
                            <div><strong>Length</strong></div>
                            <div class="fs-5">
                                <?php echo get_option('baby_length'); ?>cm
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Leaderboard -->
    <?php if ($showResults): ?>
    <div class="card mt-4 bg-light border-2 border-warning shadow-sm">
        <div class="card-body">
            <h2 class="h4">🏅 Leaderboard</h2>
            <div class="row text-center">
                <?php 
                $positions = ['1st', '2nd', '3rd'];
                $index = 0;
                foreach ($top_winners as $winner) : ?>
                    <div class="col-12 col-md-4 mb-3">
                        <div class="border rounded p-3 border-lg">
                            <div><strong><?php echo $positions[$index]; ?></strong></div>
                            <div class="fs-5">
                                <?php echo esc_html($winner->name);?>
                                <br>
                                <small class="text-muted"><?php echo esc_html($winner->total_points);?>pts</small>
                            </div>
                        </div>
                    </div>
                <?php 
                $index += 1;
                endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Closest -->
    <?php if ($showResults): ?>
    <div class="card mt-4 bg-light border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h4">🎯 Closest Predictions</h2>
            <div class="row text-center">
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 border-lg">
                        <div class="small text-muted">Arrival</div>
                        <div class="fs-5">
                            <?php echo esc_html(date('j M Y H:i', strtotime($arrival_winner->arrival)));?>
                            <br>
                            <small class="text-muted"><?php echo esc_html($arrival_winner->name); ?> - <?php echo esc_html($arrival_winner->arrival_total_points);?>pts</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4 mt-3 mt-md-0">
                    <div class="border rounded p-3 border-lg">
                        <div class="small text-muted">Weight</div>
                        <div class="fs-5">
                            <?php
                                $grams_win = (int)$weight_winner->weight;
                                $lbs_win = floor($grams_win / 453.59237);
                                $oz_win = round(($grams_win - ($lbs_win * 453.59237)) / 28.3495); 
                                echo esc_html($lbs_win . "lb " . $oz_win . "oz");
                            ?> (<?php echo esc_html(round(($grams_win / 1000), 2));?>kg)
                            <br>
                            <small class="text-muted"><?php echo esc_html($weight_winner->name); ?> - <?php echo esc_html($weight_winner->weight_points);?>pts</small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4 mt-3 mt-md-0">
                    <div class="border rounded p-3 border-lg">
                        <div class="small text-muted">Length</div>
                        <div class="fs-5">
                            <?php echo esc_html($length_winner->length);?>cm
                            <br>
                            <small class="text-muted"><?php echo esc_html($length_winner->name); ?> - <?php echo esc_html($length_winner->length_points);?>pts</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Current predictions -->
    <div class="card mt-4 bg-light border-0 shadow-sm">
        <div class="card-body">
            <?php if (get_option('baby_has_arrived')): ?>
                <h2>📈 The Predictions</h2>
                <p class="text-muted">Averages of the submitted guesses</p>
            <?php else: ?>
                <h2>🔮 Current Predictions</h2>
                <p class="text-muted">Based on all guesses submitted so far...</p>
            <?php endif; ?>

            <div class="row text-center">
                <div class="col-6 col-md-2">
                    <div class="small text-muted">Total Guesses</div>
                    <div class="fs-5"><?php echo esc_html($stats->total_guesses); ?></div>
                </div>

                <div class="col-6 col-md-2">
                    <div class="small text-muted">Average Arrival</div>
                    <div class="fs-5">
                        <?php echo esc_html(date('j M Y', strtotime($average_arrival))); ?>
                        <small class="text-muted"> 
                            <?php echo esc_html(date('H:i', strtotime($average_arrival))); ?>
                        </small>
                    </div>
                </div>

                <div class="col-6 col-md-2 mt-3 mt-md-0">
                    <div class="small text-muted">Girls</div>
                    <div class="fs-5">
                        <?php echo esc_html($stats->girls); ?>
                        <small class="text-muted"> 
                            (<?php echo esc_html($girl_percentage);?>%)
                        </small>
                    </div>
                </div>

                <div class="col-6 col-md-2 mt-3 mt-md-0">
                    <div class="small text-muted">Boys</div>
                    <div class="fs-5">
                        <?php echo esc_html($stats->boys); ?>
                        <small class="text-muted"> 
                            (<?php echo esc_html($boy_percentage);?>%)
                        </small>
                    </div>
                </div>

                <div class="col-6 col-md-2 mt-3 mt-md-0">
                    <div class="small text-muted">Average Length</div>
                    <div class="fs-5">
                        <?php echo esc_html($average_length) ?>cm
                    </div>
                </div>

                <div class="col-6 col-md-2 mt-3 mt-md-0">
                    <div class="small text-muted">Average Weight</div>
                    <div class="fs-5">
                        <?php
                            $grams_avg = (int)$average_weight;
                            $lbs_avg = floor($grams_avg / 453.59237);
                            $oz_avg = round(($grams_avg - ($lbs_avg * 453.59237)) / 28.3495);
                            echo esc_html($lbs_avg . "lb " . $oz_avg . "oz");
                        ?>
                        <small class="text-muted"> 
                            (<?php echo esc_html(round(($grams_avg / 1000), 2));?>kg)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4" id="guesses">
        <h3>All Guesses</h3>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">
            🎉 Your guess has been submitted successfully!
            <br>
            <small>Come back here once the baby is born to see your score!</small>
        </div>
    <?php endif; ?>

    <!-- Guess Cards -->
    <?php foreach ($user_guesses as $guess) : ?>
    <div class="card shadow-sm mb-3 border-0 shadow-sm prediction-card mt-4">

        <div class="card-body">
            <?php if (isset($_GET['guess']) && $_GET['guess'] == $guess->User_ID): ?>
                <span class="badge bg-success w-100 mb-2 p-2">
                    ✨ Your guess
                </span>
            <?php endif; ?>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0 text-start"><?php echo esc_html($guess->name); ?></h5>
                    <small class="text-muted text-start">
                        Submitted
                        <?php echo esc_html(
                            date('j M Y H:i', strtotime($guess->respond))
                        );?>
                    </small>
                </div>

                <!-- Hidden until results -->
                <?php if ($showResults): ?>
                    <div class="text-end">
                        <span class="badge bg-warning text-dark fs-6">
                            <?php echo esc_html($guess->total_points); ?> pts
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Their prediction -->
            <!-- create_prediction_card($showResults, $header, $prediction, $secondary_prediction, $showDifference, $differenceSymbol, $difference, differenceMetric, $points, $showSuccess) -->
            <div class="row text-center">
                <?php
                    //Gender Card 
                    if($guess->gender_correct ? $gender_difference = 0 : $gender_difference = 1);
                    echo create_prediction_card($showResults, 'Gender', ucfirst(esc_html($guess->gender)), null, false, null, $gender_difference, null, $guess->gender_points, null);

                    //Length Card
                    $length_guess  = $guess->length . "cm";
                    $difference_symbol = ($guess->length_difference > 0 ? '+' : '');
                    $difference_metric = "cm";
                    echo create_prediction_card($showResults, 'Length', $length_guess, null, true, $difference_symbol, $guess->length_difference, $difference_metric, $guess->length_points, null);

                    //Weight Card
                    $grams = (int)$guess->weight;
                    $lbs = floor($grams / 453.59237);
                    $oz = round(($grams - ($lbs * 453.59237)) / 28.3495);
                    $weight_guess  = $lbs . "lb " . $oz . "oz";
                    $weight_guess_kg = round(($grams / 1000), 2) . "kg";
                    $difference_symbol = ($guess->weight_difference > 0 ? '+' : '');
                    $difference_metric = "g";
                    echo create_prediction_card($showResults, 'Weight', $weight_guess, $weight_guess_kg, true, $difference_symbol, $guess->weight_difference, $difference_metric, $guess->weight_points, null);

                    //Arrival Card
                    $arrival_date_guess  = date('j M Y', strtotime($guess->arrival));
                    $arrival_time_guess  = date('H:i', strtotime($guess->arrival));
                    $arrival_points = $guess->arrival_total_points;
                    $showSuccess = null;

                    $day_diff = $guess->arrival_day_difference;
                    $time_diff = $guess->arrival_time_difference;
                    $arrival_difference;
                    if($day_diff == 0) {
                        $showSuccess = true;
                        $arrival_difference = "<span class='text-success fw-bold'>✅ Correct date</span><br> 🕑 Time off by " . $time_diff . " hrs";
                    }
                    elseif($day_diff > 0) {
                        // >0 = days after
                        $day_string = $day_diff == 1 ? 'day' : 'days';
                        $arrival_difference = "📆 ". $day_diff . " " . $day_string . " late";
                    }
                    else {
                        $day_string = $day_diff == -1 ? 'day' : 'days';
                        $arrival_difference = "📆 ". ($day_diff * -1) . " " . $day_string . " early";
                    }

                    $length_difference = $guess->length_difference . "cm";
                    echo create_prediction_card($showResults, 'Arrival', $arrival_date_guess, $arrival_time_guess, true, null, $arrival_difference, null, $arrival_points, $showSuccess);
                ?>

            </div>

        </div>

    </div>
    <?php endforeach; ?>

    <?php if (!$showResults && !isset($_GET['saved'])): ?>
        <button type="button" class="guess-btn btn btn-success" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Make your guess">
            + Add your guess
        </button>
    <?php endif; ?>

    <!--Guess drawer-->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        
        <div class="offcanvas-header">
            <h5 class="offcanvas-title caveat-brush-regular" id="offcanvasNavbarLabel">🔮 Make your Baby <?php echo esc_html(get_option('family_name', ''));?> prediction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form method="post">

                <?php wp_nonce_field('baby_save_prediction'); ?>

                <p>No pressure... but remember, the <?php echo esc_html(get_option('family_name', ''));?> family will be checking the leaderboard.</p>
                <p>Fill out your guesses for Baby <?php echo esc_html(get_option('family_name', ''));?> below and click the submit button when you're finished!</p>

                <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#howScoringWorks">
                    <small>How does scoring work?</small>
                </button>

                <div class="mb-3 mt-3">
                    <label class="form-label">Gender</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="gender" id="boy" value="boy">
                        <label class="btn btn-boy" for="boy">Boy</label>

                        <input type="radio" class="btn-check" name="gender" id="girl" value="girl">
                        <label class="btn btn-girl" for="girl">Girl</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Weight</label>

                    <select class="form-select mb-2" id="weight-unit">
                        <option value="imperial">lb / oz</option>
                        <option value="metric">kg</option>
                    </select>

                    <div id="imperial-weight">
                        <div class="input-group">
                            <input type="number" min="3" max="20" name="weight-pound" class="form-control">
                            <span class="input-group-text">lb</span>

                            <input type="number" min="0" max="15" name="weight-ounce" class="form-control">
                            <span class="input-group-text">oz</span>
                        </div>
                    </div>

                    <div id="metric-weight" style="display:none;">
                        <div class="input-group">
                            <input type="number" step="0.01" min="1" max="8" name="weight-kg" class="form-control">
                            <span class="input-group-text">kg</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="length">
                        Length
                    </label>

                    <div class="input-group">
                        <input type="number" class="form-control" name="length" step="0.1" min="30" max="70" required>
                        <span class="input-group-text">cm</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="arrival-datetime">Arrival </label>
                    <input class="form-control mb-2" type="datetime-local" name="arrival-datetime" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="name">Almost done! Who should get the glory if you win?</label>
                    <input class="form-control" type="text" name="name" name="name" maxlength="40" required>
                    <small><em>Please leave your name so we know which is your guess</em></small>
                </div>

                <button class="btn btn-success w-100" type="submit" name="save_predictions">
                🍼 Submit my guess
            </button>
            </form>
        </div>
    </div>
  </div>

</div>

<footer>

    Website by favourite auntie 😉

</footer>

<script>

    // Show current field when unit selected for weight
    const unit = document.getElementById('weight-unit');
    const imperial = document.getElementById('imperial-weight');
    const metric = document.getElementById('metric-weight');
    unit.addEventListener('change', function(){

        if(this.value === "metric"){

            imperial.style.display="none";
            metric.style.display="block";

        }else{

            imperial.style.display="block";
            metric.style.display="none";

        }

    });

</script>

<?php get_footer(); ?>