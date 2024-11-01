<?php
/*
Plugin Name: ViralKit
Plugin URI: https://viralkit.com
Description: Welcome to ViralKit, where the future of contest creation awaits. Powered by advanced AI technology, our platform crafts tailored contests and giveaways designed to captivate your audience, skyrocket your social engagement, and drive an influx of followers and likes. Don't just host a contest; optimize it with the intelligence of ViralKit.
Version: 1.0
Requires at least: 3.0.1
Requires PHP: 7.0
Author: ViralKit
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include other PHP files, if required.

// Add an admin menu for the ViralKit plugin
add_action('admin_menu', 'viralkit_add_admin_menu');
function viralkit_add_admin_menu()
{
    add_menu_page('ViralKit Contests', 'ViralKit Contests', 'manage_options', 'viralkit', 'viralkit_admin_page_content', 'dashicons-awards', 6);
}

// Hook into the admin initialization process
add_action('admin_init', 'viralkit_redirect_to_admin');

function viralkit_redirect_to_admin()
{
    // Check if our option is set
    if (get_option('viralkit_plugin_activated', false)) {
        // Delete the option to prevent the redirect on every admin page load
        delete_option('viralkit_plugin_activated');

        // Perform the redirect
        wp_redirect(admin_url('admin.php?page=viralkit'));
        exit;
    }
}

// Hook into the activation process
register_activation_hook(__FILE__, 'viralkit_activate');

function viralkit_activate()
{
    // Set an option to trigger the redirect
    add_option('viralkit_plugin_activated', true);
}

// Content that will be rendered when the admin menu is clicked
function viralkit_admin_page_content()
{
    echo '<div class="viralkit_body">';

    // Check if apiKey is in the URL
    $apiKey = isset($_GET['apiKey']) ? sanitize_text_field($_GET['apiKey']) : '';
    if (!empty($apiKey)) {
        echo '<script type="text/javascript">
                  var viralkitApiKey = "' . esc_js($apiKey) . '";
              </script>';
    }

    $storedApiKey = get_option('viralkit_api_key');
    $connectedSiteName = get_bloginfo('name');  // Gets the name of the site.
    $connectedSiteLink = get_bloginfo('url');   // Gets the site URL.

    echo '<input type="hidden" id="connectedSiteName" value="' . esc_attr($connectedSiteName) . '" />';
    echo '<input type="hidden" id="connectedSiteLink" value="' . esc_url($connectedSiteLink) . '/wp-admin/admin.php?page=viralkit" />';

    // If the API key is stored, render the dashboard
    if ($storedApiKey) {
        // Save the wordpress viralkit_api_key so it can be fetch in viralkit.js
        echo '<input type="hidden" id="storedApiKey" value="' . esc_attr($storedApiKey) . '" />';

        // The code to render the ViralKit admin dashboard goes here.
        // Get user data
        $url = 'https://api.viralkit.com/api/hosts/wordpress/wordpress_get_user_data.php?api_key=' . urlencode($storedApiKey);
        $args = array(
            'timeout'     => 30,  // Set a reasonable timeout
            'redirection' => 10, // Number of max redirections
            'httpversion' => '1.1', // Correct key is 'httpversion', not 'http_version'
            'blocking'    => true, // Ensure blocking is true if you need the response immediately
            'headers'     => array(),
            'cookies'     => array()
        );

        $response = wp_remote_get($url, $args);

        $user_array = json_decode(wp_remote_retrieve_body($response), true);

        $name = sanitize_text_field($user_array["user_details"]["user"]["name"]);
        $email = sanitize_email($user_array["user_details"]["user"]["email"]);
        $active_brand_id = sanitize_text_field($user_array["user_details"]["user"]["active_brand_id"]);
        $timestamp_signed_up = intval($user_array["user_details"]["user"]["timestamp"]);
        $user_contests = $user_array["user_contests"]["user_contests"];

        $if_free_trial = boolval($user_array["user_details"]["user"]["if_free_trial"]);
        $current_timestamp = time();  // Gets the current timestamp in seconds
        $trial_duration = 7 * 24 * 60 * 60;  // Set to a 7-day trial duration

        $seconds_remaining = $trial_duration - ($current_timestamp - $timestamp_signed_up);

        // Ensure the remaining time does not go into negative values
        if ($seconds_remaining < 0) {
            $seconds_remaining = 0;
        }

        // Break down the remaining time into days, hours, minutes, and seconds
        $days_remaining = str_pad(floor($seconds_remaining / (24 * 60 * 60)), 2, '0', STR_PAD_LEFT);
        $seconds_remaining -= intval($days_remaining) * 24 * 60 * 60;

        $hours_remaining = str_pad(floor($seconds_remaining / (60 * 60)), 2, '0', STR_PAD_LEFT);
        $seconds_remaining -= intval($hours_remaining) * 60 * 60;

        $minutes_remaining = str_pad(floor($seconds_remaining / 60), 2, '0', STR_PAD_LEFT);
        $seconds_remaining -= intval($minutes_remaining) * 60;

        $seconds = str_pad($seconds_remaining, 2, '0', STR_PAD_LEFT);

        // Total seconds remaining of the trial
        $total_seconds_remaining = $days_remaining * 24 * 60 * 60 + $hours_remaining * 60 * 60 + $minutes_remaining * 60 + $seconds;

        // Constructing the message
        $time_remaining_string = '';
        $time_remaining_string .= $days_remaining . ' day' . ($days_remaining != 1 ? 's' : '') . ' ';
        $time_remaining_string .= $hours_remaining . ' hour' . ($hours_remaining != 1 ? 's' : '') . ' ';
        $time_remaining_string .= $minutes_remaining . ' minute' . ($minutes_remaining != 1 ? 's' : '') . ' ';
        $time_remaining_string .= $seconds . ' second' . ($seconds != 1 ? 's' : '');

?>
        <style>
            .viralkit_body .container_full {
                background-color: white;
                width: 100%;
                margin: <?php echo $if_free_trial == 1 ? '74px' : '20px'; ?> auto 50px auto;
                padding: 20px 40px 20px 20px;
                text-align: center;
                box-sizing: border-box;
            }
        </style>

        <div class="spinner-container">
            <div class="preloader-wrapper big active" id="loader" style="display: none; z-index: 999999999999;">
                <div class="spinner-layer spinner-blue-only">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div>
                    <div class="gap-patch">
                        <div class="circle"></div>
                    </div>
                    <div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="loading-container-wrapper">
            <div class="loading-container">
                <div class="loading-content">
                    <img src="<?php echo  esc_url(plugin_dir_url(__FILE__) . '/img/viraly-logo-square-3.png'); ?>" alt="Loading Icon" class="loading-icon">
                    <div class="loading-message" style="margin-top: 50px;">
                        Don't leave this page. Your giveaway will be ready in <span class="seconds-remaining">5</span> seconds or less
                        <span class="dot">.</span>
                        <span class="dot">.</span>
                        <span class="dot">.</span>
                    </div>
                    <div class="final-touches" style="display: none; margin-top: 50px;">
                        <i style="color: #0000BA; font-size: 26px; font-weight: bold;">Adding Final Touches...&nbsp;&nbsp;</i>
                        <!-- <div class="circular-progress"></div> -->
                    </div>
                </div>
            </div>
        </div>

        <?php

        // Build user dashboard
        ?>
        <div class="container_full">

            <?php if ($if_free_trial) { ?>
                <div id="banner">
                    <span class="banner-text">
                        Your "Elevate Plan" free trial ends in
                        <span id="countdown">
                            <?php echo esc_html($time_remaining_string); ?>
                        </span>!
                        <span id="upgrade_now_button_wrapper">
                            <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($active_brand_id); ?>&redirect_page=dashboard&hash=open_pricing&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank">
                                <button id="upgradeBtn">
                                    Upgrade Now
                                </button>
                            </a>
                        </span>
                    </span>

                    <input type="hidden" id="totalSecondsRemaining" value="<?php echo esc_attr($total_seconds_remaining); ?>" />
                </div>
            <?php } ?>

            <!-- The navigation bar -->
            <div class="navbar">
                <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($active_brand_id); ?>&redirect_page=dashboard&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank" class="btn waves-effect waves-light">
                    User Dashboard
                </a>
                <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($active_brand_id); ?>&redirect_page=build-contest&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank" class="btn waves-effect waves-light">
                    New Contest
                </a>
                <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($active_brand_id); ?>&redirect_page=ai-generator&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank" class="btn waves-effect waves-light">
                    AI Contest Builder
                </a>
                <a href="https://viralkit.com/docs" target="_blank" class="btn waves-effect waves-light">
                    Help Docs
                </a>
                <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($active_brand_id); ?>&redirect_page=subscription&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank" class="btn waves-effect waves-light">
                    Subscription
                </a>
            </div>

            <!-- Loop through each contest and display it -->
            <?php

            if (isset($user_contests) && is_array($user_contests)) {

                // If 1 or more contests are found
                if (count($user_contests) > 0) {

                    echo '<h4 style="margin-top: 60px; margin-bottom: 30px; font-weight: 400; font-size: 2.125rem; line-height: 1.235;">Manage Contests</h4>';

                    foreach ($user_contests as $contest) {

                        $contests_id = sanitize_text_field($contest['contests_id']);
                        $host_brands_id = sanitize_text_field($contest['host_brands_id']);
                        $title = sanitize_text_field($contest['title']);
                        $thumbnail = '';
                        $totalPageViews = intval($contest['totalPageViews']);
                        $totalSumEntries = intval($contest['totalSumEntries']);
                        $totalUniquePeople = intval($contest['totalUniquePeople']);

                        // Loop through photos object ot get the thumbnail
                        if (isset($contest["photos"])) {
                            $photos = json_decode($contest["photos"], true);  // decode the JSON data to an associative array

                            if (is_array($photos) && isset($photos[0]["image"]["thumbnail"])) {
                                $thumbnail = esc_url($photos[0]["image"]["thumbnail"]);
                            }
                        }

            ?>
                        <div class="contest">
                            <!-- Thumbnail Column -->
                            <div class="left-column">
                                <?php echo !empty($thumbnail) ? '<img src="' . esc_url($thumbnail) . '" class="center-block">' : '<img src="' . esc_url(plugin_dir_url(__FILE__) . '/img/viraly-logo-square-400.png') . '" class="center-block" style="background: #f6f6f6;" />'; ?>
                            </div>

                            <!-- Title & Actions Column -->
                            <div class="middle-column">
                                <!-- Title -->
                                <h5 class="contest_title">
                                    <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($host_brands_id); ?>&redirect_page=build-contest&contests_id=<?php echo esc_attr($contests_id); ?>&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank" style="color: #3c434a; outline: none;">
                                        <?php echo esc_html($title); ?>
                                    </a>
                                </h5>

                                <!-- Actions -->
                                <div class="contest-actions">
                                    <a href="https://viralkit.com/f/<?php echo esc_attr($contests_id); ?>" target="_blank">
                                        <button class="btn view-btn">
                                            View Contest
                                        </button>
                                    </a>

                                    <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($host_brands_id); ?>&redirect_page=build-contest&contests_id=<?php echo esc_attr($contests_id); ?>&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank">
                                        <button class="btn edit-btn">
                                            Edit
                                        </button>
                                    </a>

                                    <a href="#">
                                        <button class="btn embed-btn" data-contest-shortcode='[viralkit_contest id="<?php echo esc_attr($contests_id); ?>"]' data-contest-embed-code='<div class="viralkit-contest" contests-id="<?php echo esc_attr($contests_id); ?>"></div><script src="https://viralkit.com/api/embed.js"></script>'>
                                            Embed
                                        </button>
                                    </a>

                                    <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($host_brands_id); ?>&redirect_page=manage-entries&contests_id=<?php echo esc_attr($contests_id); ?>&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank">
                                        <button class="btn entries-btn">
                                            Manage Entries
                                        </button>
                                    </a>

                                    <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($host_brands_id); ?>&redirect_page=analytics&contests_id=<?php echo esc_attr($contests_id); ?>&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank">
                                        <button class="btn analytics-btn">
                                            Analytics
                                        </button>
                                    </a>
                                </div>
                            </div>

                            <!-- Visits, Entries, People Column -->
                            <div class="right-column">
                                <p class="contest_stats_each"><span style="width: 60px; float:left;">Visits:</span> <?php echo intval($totalPageViews); ?></p>
                                <p class="contest_stats_each"><span style="width: 60px; float:left;">Entries:</span> <?php echo intval($totalSumEntries); ?></p>
                                <p class="contest_stats_each"><span style="width: 60px; float:left;">People:</span> <?php echo intval($totalUniquePeople); ?></p>
                            </div>
                        </div>


                <?php
                    }
                }
                ?>

                <h4 style="margin-top: 60px; margin-bottom: 30px; font-weight: 400; font-size: 2.125rem; line-height: 1.235;">What kind of contest do you want to run?</h4>
                <div class="row">
                    <div class="row">
                        <div class="input-field col s12">
                            <textarea id="ai_generate_contest_user_input" class="materialize-textarea outlined-textarea" style="height: 125px;" placeholder="Type your contest details here. You can say what platforms you want to run your contest on, what you want to give away, and when you want it to end, and any other details you want to include. We'll take care of the rest."></textarea>
                        </div>
                    </div>
                    <button class="btn waves-effect waves-light" type="submit" id="ai_generate_contest_submit" style="height: 65px; padding-left: 40px; padding-right: 40px; font-size: 16px; font-weight: bold;">Generate My Contest</button>

                </div>
                <div class="center-align" style="width: 100%; margin-top: 10px; float: left;">
                    <a href="https://viralkit.com/wordpress-redirect?host_brands_id=<?php echo esc_attr($active_brand_id); ?>&redirect_page=build-contest&wordpress_api_key=<?php echo esc_attr($storedApiKey); ?>" target="_blank" style="font-size: 16px; color: #0000BA;">
                        Or, Build Your Contest Manually
                    </a>
                </div>

                <!-- Typing animation -->
                <div id="typingArea" class="typing-container">
                    <span id="sentence"></span>
                    <span class="cursor">|</span>
                </div>

            <?php
            } else {
                echo esc_html("No contests found.");
            }
            ?>

        </div>
    <?php
        return;
    }

    // Echo the WP admin's email
    // $admin_email = get_option('admin_email');

    // If the API key is not stored, render the authentication form
    ?>
    <div class="container_full">
        <div class="row" id="authentication-section">
            <div class="col s12" style="text-align: center;">
                <div class="card" style="display: inline-block; margin: 100px auto; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: none; float: none;">
                    <div class="card-content">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/img/viral-kit-logo-200.png'); ?>" alt="ViralKit Logo" class="responsive-img center-block" style="max-width: 200px; margin-bottom: 25px;">
                        <div id="form_wrapper">
                            <span class="card-title" style="margin-bottom: 20px; font-weight: bold;">Use the power of AI to create viral giveaways.</span>
                            <p style="margin-bottom: 30px; font-size: 16px;">Enter your full name and email address to get started.</p>
                            <div class="input-field">
                                <input id="name" type="text" placeholder="Full Name">
                            </div>
                            <div class="input-field">
                                <input id="email" type="email" placeholder="Email Address">
                            </div>
                            <button class="btn waves-effect waves-light" id="authenticate-btn" style="margin-top: 15px; background-color: #0000BA !important; border-radius: 5px;">Authenticate Your Email</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

    echo '</div>';
}
// Enqueue Materialize CSS, jQuery, and our custom script
add_action('admin_enqueue_scripts', 'viralkit_enqueue_scripts');
function viralkit_enqueue_scripts($hook)
{
    wp_enqueue_script('viralkit-js-app', plugin_dir_url(__FILE__) . 'viralkit.js', ['jquery'], '1.1.0', true);

    wp_enqueue_style('materialize-css',  plugin_dir_url(__FILE__) . '/styles/materialize.css');
    wp_enqueue_script('swal',  plugin_dir_url(__FILE__) . '/js/sweet-alert-11.4.38.js', [], null, true);

    // Localize script to pass data
    wp_localize_script('viralkit-js-app', 'viralkitData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('viralkit_nonce')
    ));

    wp_enqueue_style('custom-stylesheet',  plugin_dir_url(__FILE__) . '/styles/custom-stylesheet.css');
}

// Handle storing of API key via AJAX
add_action('wp_ajax_store_viralkit_api_key', 'viralkit_store_api_key_via_ajax');
add_action('wp_ajax_nopriv_store_viralkit_api_key', 'viralkit_store_api_key_via_ajax');
function viralkit_store_api_key_via_ajax()
{
    check_ajax_referer('viralkit_nonce', 'security');
    $apiKey = isset($_POST['apiKey']) ? sanitize_text_field($_POST['apiKey']) : '';
    if (empty($apiKey)) {
        wp_send_json_error(array('message' => 'API key is required.'));
    } else {
        update_option('viralkit_api_key', sanitize_text_field($apiKey));
        wp_send_json_success(array('message' => 'API key stored successfully.'));
    }
    exit();
}

// Shortcode for embedding a contest
function viralkit_contest_shortcode($atts)
{
    // Extract the attributes from the shortcode
    $a = shortcode_atts(array(
        'id' => '', // default value, which is an empty string
    ), $atts);

    // Check if 'id' attribute is provided, otherwise return an error message (or you can return nothing)
    if (empty($a['id'])) {
        return 'Contest ID is missing!';
    }

    // Construct the output based on the provided attributes
    $output = '<div class="viralkit-contest" contests-id="' . esc_attr($a['id']) . '"></div>';
    $output .= '<script src="' . esc_url(plugin_dir_url(__FILE__) . '/img/embed.js') . '"></script>';

    return $output;
}

// Register the shortcode with WordPress
add_shortcode('viralkit_contest', 'viralkit_contest_shortcode');
?>