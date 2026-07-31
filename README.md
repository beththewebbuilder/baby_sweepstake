# Baby Sweepstakes WordPress Theme

A custom WordPress theme for managing a Baby Sweepstakes competition.

## Features

* Custom WordPress theme.
* Custom database table creation required.
* Stores sweepstakes configuration values.
* Competition points can be adjusted on the `/result` page.
* Actual baby details can be added to the `/result` page and calculate guess points.

## Installation

1. Clone or download this repository into your WordPress themes directory.
2. Activate the theme from **Appearance → Themes**.
3. On first activation, the theme will:

   * Create the required database table.
   * Register the required WordPress settings.
   * Add a **Result** page.

## Database

A custom database table is required called `wp_guess_list`.

The table should contain the following fields:

| Field      | Type              | Description               |
| ---------- | ----------------- | ------------------------- |
| User_ID    | INT (Primary Key) | Unique record ID          |
| name       | VARCHAR           | Participant name    |
| gender     | VARCHAR           | Predicted gender          |
| gender_points     | INT           | Calculated points          |
| gender_correct     | BOOLEAN           | Calculated true/false          |
| weight     | INT           | Predicted birth weight (grams)   |
| weight_points | INT             | Calculated points      |
| weight_difference | INT             | Calculated difference in prediction (grams)      |
| length     | INT           | Predicted baby length (cm)    |
| length_points | INT             | Calculated points      |
| length_difference | INT             | Calculated difference in prediction (cms)      |
| arrival | DATETIME              | Predicted birth date and time    |
| arrival_date_points | INT             | Calculated points      |
| arrival_day_difference | INT             | Calculated difference in prediction (days)      |
| arrival_time_points | INT             | Calculated points      |
| arrival_time_difference | INT             | Calculated difference in prediction (hours)      |
| respond | DATETIME          | Submission timestamp      |
| total_points | INT          | Sum of all points after calculation      |

## Settings Page

Make sure a new page is added in the WordPress application called `page-settings`.

**/settings**

### Points Allocated

The settings page should allow administrators to configure point scoring values such as:

**Gender**
* Point allocation for correct gender
* Point allocation for incorrect gender

**Weight**
* Point allocation for exact weight
* Grams away from actual weight point deduction

**Length**
* Point allocation for exact length
* Cms away from actual length point deduction

**Arrival**
* Point allocation for exact arrival date
* Points deducted for each day away from actual date
* Point allocation for exact arrival time _(if date correct)_
* Hours away from actual arrival time point deduction
* Points deducted for selected hours away from actual time

### After birth

After the baby is born, enter the official details on th same `/settings` page.

* Actual baby name
* Actual gender
* Actual birth date
* Actual birth time
* Actual birth weight
* Actual baby length
* Winner calculation options

### Calculate Details
Once saving the baby's details, the system will:

* Compare participant predictions with the official values.
* Calculate scores (if applicable).
* Display participant rankings.
* Highlight the winner(s).
* Show the official baby details.

## Development Notes

Recommended WordPress APIs:

* Settings API
* `$wpdb`
* `dbDelta()` for table creation
* Theme activation hook (or equivalent initialization logic)
* Nonces for form security
* Data sanitization and escaping
* Prepared SQL statements

## Future Improvements

* CSV export of entries.
* Opening and closing dates.
* Admin dashboard statistics.
* Email notifications.
* Tie-breaker support.
* Multiple competitions.
* AJAX leaderboard updates.

## Requirements

* WordPress 6.x+
* PHP 8.x+
* MySQL/MariaDB

## License

This project is provided as-is for custom development.
