<?php

function my_deregister_scripts(){
  wp_deregister_script( 'wp-embed' );
}
add_action( 'wp_footer', 'my_deregister_scripts' );

// Add thumbnails
add_theme_support('post-thumbnails');

//WordPress Titles
add_theme_support('title-tag');

//navigation menus
register_nav_menus(array(
  'primary' => __('Primary Menu'),
  'footer' => __('Footer Menu'),
));

add_action('wp_ajax_nopriv_bt_scf', 'bt_scf');
add_action('wp_ajax_save_bt_scf', 'bt_scf');

function styles_scripts_init() {
    //enqueue stylesheets
    wp_register_script('script', get_template_directory_uri() . '/script/ajax-script.js', array('jquery'),'',true);
    wp_localize_script('script','myajax',array('ajaxurl' => admin_url('admin-ajax.php')));
    wp_enqueue_script('script');
    
    wp_enqueue_style('style', get_template_directory_uri() . '/style.css');
}
add_action( 'wp_enqueue_scripts', 'styles_scripts_init' );

add_action('wp_ajax_save_guess', 'save_guess');
add_action('wp_ajax_nopriv_save_guess', 'save_guess');
function save_guess() {
  global $wpdb;

  if( $_POST['name'] != '') {
        try {
            $created_at = date('Y-m-d H:i:s');
            
            $weight_grams = 0;

            $weight_kg = isset($_POST['weight_kg']) ? floatval($_POST['weight_kg']) : 0;
            $weight_pound = isset($_POST['weight_pound']) ? intval($_POST['weight_pound']) : 0;
            $weight_ounce = isset($_POST['weight_ounce']) ? intval($_POST['weight_ounce']) : 0;

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

            $wpdb->insert(
                'wp_guess_list',
                array(
                    'name' => $_POST['name'],
                    'gender' => $_POST['gender'],
                    'weight' => $weight_grams,
                    'length' => $_POST['length'],
                    'arrival' => $_POST['arrival'],
                    'respond' => $created_at,
                )
            );
            echo "Success";
        }
        catch (Exception $e) {
            echo "Error";
        }     
    }
    die();
}

function calculate_baby_sweepstake_results() {
    global $wpdb;
    $table = $wpdb->prefix . 'guess_list';

    //Get baby details
    $gender = get_option('baby_gender');
    $weight = get_option('baby_weight');
    $length = get_option('baby_length');
    $arrival = get_option('baby_arrival');

    //Get every guess
    $guesses = $wpdb->get_results("SELECT * FROM $table");
    foreach($guesses as $guess) {

        $gender_result = calculate_gender_points($guess, $gender);
        $weight_result = calculate_weight_points($guess, $weight);
        $length_result = calculate_length_points($guess, $length);
        $arrival_result = calculate_arrival_points($guess, $arrival);

        $arrival_total_points = 
        $arrival_result['date_points'] +
        $arrival_result['time_points'];

        $total_points =
        $gender_result['points'] +
        $weight_result['points'] +
        $length_result['points'] +
        $arrival_total_points;

        $result = $wpdb->update(
            $table,
            [
                'gender_points'       => intval($gender_result['points']),
                'gender_correct'      => $gender_result['correct'],

                'weight_points'       => $weight_result['points'],
                'weight_difference'   => $weight_result['difference'],

                'length_points'       => $length_result['points'],
                'length_difference'   => $length_result['difference'],

                'arrival_date_points' => $arrival_result['date_points'],
                'arrival_day_difference'  => $arrival_result['date_difference'],
                'arrival_time_points' => $arrival_result['time_points'],
                'arrival_time_difference'  => $arrival_result['time_difference'],

                'arrival_total_points' => $arrival_total_points,

                'total_points'        => intval($total_points)
            ],
            ['User_ID' => $guess->User_ID]
        );
        //var_dump($result);
        //echo $wpdb->last_error;
    }
}

function calculate_gender_points($guess, $gender) {
    $total_points;
    $correct_gender;

    if($gender == $guess->gender) {
        $total_points = get_option('points_gender_correct');
        $correct_gender = 1;
    }
    else {
        $total_points = get_option('points_gender_incorrect');
        $correct_gender = 0;
    };

    return [
        'points'  => $total_points,
        'correct' => $correct_gender
    ];
}

function calculate_weight_points($guess, $weight) {
    $total_points;
    $difference;
    $weight_range = get_option('points_weight_gram_deduction');
    $weight_available_pts = get_option('points_weight_exact');

    $difference = $weight - $guess->weight;

    // If exact, get full points
    if($difference == 0) {
        $total_points = $weight_available_pts;
    }
    else {
        // Else, take the weight difference / deduction range from available points (minus an extra point for not being exact)
        $weight_difference = 1 * abs(round($difference / $weight_range));
        $total_points = $weight_available_pts - $weight_difference - 1;

        if($total_points < 0) {
            $total_points = 0;
        }
    }

    return [
        'points' => $total_points,
        'difference' => $difference
    ];
}

function calculate_length_points($guess, $length) {
    $total_points;
    $difference;
    $length_range = get_option('points_length_cm_deduction');
    $length_available_pts = get_option('points_length_exact');

    $difference = $length - $guess->length;

    // If exact, get full points
    if($difference == 0) {
        $total_points = $length_available_pts;
    }
    else {
        // Else, take the length difference / deduction range from available points (minus an extra point for not being exact)
        $length_difference = 1 * abs(round($difference / $length_range));
        $total_points = $length_available_pts - $length_difference - 1;

        if($total_points < 0) {
            $total_points = 0;
        }
    }

    return [
        'points' => $total_points,
        'difference' => $difference
    ];
}

function calculate_arrival_points($guess, $arrival) {
    $date_points = 0;
    $time_points = 0;
    $days_apart = 0;
    $time_difference = 0;

    if(!empty($guess->arrival)) {
        $guess_date = new DateTime($arrival);
        $baby_arrival= new DateTime($guess->arrival);

        // date comparison
        $total_date_points = get_option('points_correct_date');
        $day_deduction = get_option('points_day_deduction');

        $guess_date->setTime(0, 0, 0);
        $baby_arrival->setTime(0, 0, 0);

        $difference = $guess_date->diff($baby_arrival);
        $days_apart = $difference->invert ? -$difference->days : $difference->days;

        $date_points_to_subtract = 1 * abs($days_apart * $day_deduction);
        $date_points = $total_date_points - $date_points_to_subtract;

        if($date_points < 0) {
            $date_points = 0;
        } 

        // time comparison only if the date is correct
        if($days_apart == 0) {
            $total_time_points = get_option('points_correct_time');
            $time_deduction = get_option('points_time_hr_deduction');
            $hr_difference = get_option('points_time_hr_difference');

            $guess_time = new DateTime($arrival);
            $baby_arrival_time= new DateTime($guess->arrival);

            $difference_time = $guess_time->diff($baby_arrival_time);
            $time_difference = $difference_time->h + ($difference_time->i / 60);

            $total_difference = 1 * abs(round($time_difference / $hr_difference));
            $time_points = $total_time_points - ($total_difference * $time_deduction);

            if($time_points < 0) {
                $time_points = 0;
            } 
        }
    }

    return [
        'date_points' => $date_points,
        'date_difference' => $days_apart,
        'time_points' => $time_points,
        'time_difference' => $time_difference
    ];
}
 ?>
