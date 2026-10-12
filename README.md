# Baby Sweepstakes WordPress Theme

A custom WordPress theme for managing a Baby Sweepstakes competition.

## Features

* Custom WordPress theme.
* Creates its own database table and settings on activation.
* Stores sweepstakes configuration values.
* Competition points can be adjusted on the `/settings` page.
* Actual baby details can be added to the `/settings` page and calculate guess points.

## Installation

1. Clone or download this repository into your WordPress themes directory.
2. Activate the theme from **Appearance → Themes**.
3. On first activation, the theme will:

   * Create the required database table.
   * Add default scoring settings (existing values are never overwritten).
   * Add a **Settings** page at `/settings` (if one doesn't already exist).

## Database

The theme creates a table called `wp_guess_list` (using your site's table prefix). If the table structure changes in a future version, bump `BABY_GUESS_DB_VERSION` in `functions.php` and it will be updated automatically.

The table contains the following fields:

| Field      | Type              | Description               |
| ---------- | ----------------- | ------------------------- |
| User_ID    | INT (Primary Key) | Unique record ID          |
| name       | TEXT              | Participant name    |
| gender     | TEXT              | Predicted gender          |
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
| respond | TEXT              | Submission timestamp      |
| total_points | INT          | Sum of all points after calculation      |

## Settings Page

The theme creates this page on activation. It uses the `page-settings.php` template because of its `settings` slug, and only administrators can view it.

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

* Show/hide length if not available.
* Allow edit or delete of own entry.
* Opening and closing dates.
* Admin dashboard statistics.
* Tie-breaker support.
* AJAX leaderboard updates.
* CSV export of entries.
* Email notifications?

## Requirements

* WordPress 6.x+
* PHP 8.x+
* MySQL/MariaDB

## License

This project is provided as-is for custom development.
