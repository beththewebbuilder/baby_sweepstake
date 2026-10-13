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

function styles_scripts_init() {
    //enqueue stylesheets
    wp_enqueue_style('style', get_template_directory_uri() . '/style.css');
}
add_action( 'wp_enqueue_scripts', 'styles_scripts_init' );

// Bump this when the table structure or default settings change, so existing sites are updated too
define('BABY_GUESS_DB_VERSION', '2');

// Whether guests are asked to guess the gender (couples who already know can turn it off)
function baby_guess_gender_enabled() {
    return (bool)get_option('gender_guess_enabled', 1);
}

// Maximum available points (incorrect gender points are not part of the best possible score)
function baby_guess_total_points() {
    return (baby_guess_gender_enabled() ? (int)get_option('points_gender_correct') : 0) +
        (int)get_option('points_weight_exact') +
        (int)get_option('points_length_exact') +
        (int)get_option('points_correct_date') +
        (int)get_option('points_correct_time');
}

// Create the guess table, default settings and the /settings page
function baby_guess_setup() {
    global $wpdb;
    $table = $wpdb->prefix . 'guess_list';
    $charset_collate = $wpdb->get_charset_collate();

    // dbDelta needs each column on its own line and two spaces after PRIMARY KEY.
    // Calculated columns default to 0 so guesses save on MySQL servers in strict mode
    // (defaults must be quoted for dbDelta to spot changes to them)
    $sql = "CREATE TABLE $table (
  User_ID int(11) NOT NULL AUTO_INCREMENT,
  name text NOT NULL,
  gender text NOT NULL,
  gender_points int(11) NOT NULL DEFAULT '0',
  gender_correct tinyint(1) NOT NULL DEFAULT '0',
  weight int(11) NOT NULL DEFAULT '0',
  weight_points int(11) DEFAULT NULL,
  weight_difference int(11) NOT NULL DEFAULT '0',
  length int(11) NOT NULL DEFAULT '0',
  length_points int(11) DEFAULT NULL,
  length_difference int(11) NOT NULL DEFAULT '0',
  arrival datetime DEFAULT NULL,
  arrival_date_points int(11) DEFAULT NULL,
  arrival_day_difference int(11) NOT NULL DEFAULT '0',
  arrival_time_points int(11) NOT NULL DEFAULT '0',
  arrival_time_difference int(11) NOT NULL DEFAULT '0',
  arrival_total_points int(11) NOT NULL DEFAULT '0',
  respond text NOT NULL,
  total_points int(11) DEFAULT NULL,
  PRIMARY KEY  (User_ID)
) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Default settings (add_option never overwrites values already saved)
    $defaults = [
        'family_name'                  => '',
        'baby_has_arrived'             => 0,
        'gender_guess_enabled'         => 1,
        'points_gender_correct'        => 20,
        'points_gender_incorrect'      => 0,
        'points_weight_exact'          => 20,
        'points_weight_gram_deduction' => 60,
        'points_length_exact'          => 20,
        'points_length_cm_deduction'   => 1,
        'points_correct_date'          => 15,
        'points_day_deduction'         => 3,
        'points_correct_time'          => 5,
        'points_time_hr_difference'    => 2,
        'points_time_hr_deduction'     => 1,
    ];
    foreach ($defaults as $option => $value) {
        add_option($option, $value);
    }
    add_option('total_points', baby_guess_total_points());

    // The settings page uses page-settings.php because of its "settings" slug
    if (!get_page_by_path('settings')) {
        wp_insert_post([
            'post_title'  => 'Settings',
            'post_name'   => 'settings',
            'post_type'   => 'page',
            'post_status' => 'publish',
        ]);
    }

    update_option('baby_guess_db_version', BABY_GUESS_DB_VERSION);
}
add_action('after_switch_theme', 'baby_guess_setup');

// Also run setup for sites that already had the theme active before this code existed
function baby_guess_maybe_setup() {
    if (get_option('baby_guess_db_version') !== BABY_GUESS_DB_VERSION) {
        baby_guess_setup();
    }
}
add_action('admin_init', 'baby_guess_maybe_setup');

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

    // No gender points when guests weren't asked to guess it
    if(!baby_guess_gender_enabled()) {
        $total_points = 0;
        $correct_gender = 0;
    }
    elseif($gender == $guess->gender) {
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
    $weight_range = max(1, (int)get_option('points_weight_gram_deduction'));
    $weight_available_pts = get_option('points_weight_exact');

    $difference = $guess->weight - $weight;

    // If exact, get full points
    if($difference == 0) {
        $total_points = $weight_available_pts;
    }
    else {
        // Else, deduct 1 point for every full deduction range away
        $weight_difference = floor(abs($difference) / $weight_range);
        $total_points = $weight_available_pts - $weight_difference;

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
    $length_range = max(1, (int)get_option('points_length_cm_deduction'));
    $length_available_pts = get_option('points_length_exact');

    $difference = (int)$guess->length - (int)$length;

    // If exact, get full points
    if($difference == 0) {
        $total_points = $length_available_pts;
    }
    else {
        // Else, deduct 1 point for every full deduction range away
        $length_difference = floor(abs($difference) / $length_range);
        $total_points = $length_available_pts - $length_difference;

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
            $hr_difference = max(1, (int)get_option('points_time_hr_difference'));

            $guess_time = new DateTime($arrival);
            $baby_arrival_time= new DateTime($guess->arrival);

            $difference_time = $guess_time->diff($baby_arrival_time);
            $time_difference = $difference_time->h + ($difference_time->i / 60);

            // Full points within the first range (e.g. ≤2hrs), then deduct for each further range
            $total_difference = max(0, ceil($time_difference / $hr_difference) - 1);
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
