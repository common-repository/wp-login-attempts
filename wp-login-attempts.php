<?php
/*
*Plugin Name: WP Login Attempts
*Plugin URI: https://wordpress.org/plugins/wp-login-attempts/
*Description: WP Login attempts is a security plugin which can protect the site from brute force attacks. It allows the options to add Google reCAPTCHA, the maximum number of attempts to the login page and notify the user about remaining retries. This plugin gives the ability to change the URL of the login page to hide the existing login page. It has some other features like a set custom logo, background image, colour etc. on the login page.
*Author: Galaxy Weblinks
*Author URI: http://galaxyweblinks.com
*Text Domain: wp-login-attempts
*Version: 5.3
*License:GPL2  
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/* Get remote address: from direct & proxy and notify value */
define('WP_LOGIN_ATT_DIR_ADD', 'REMOTE_ADDR');
define('WP_LOGIN_ATT_PROXY_ADD', 'HTTP_X_FORWARDED_FOR');
define('WP_LOGIN_ATT_LOCKOUT_NOTIFY', 'log,email');
$wla_login_att_err = false; 
$wla_login_att_lockedout = false; 
$wla_login_att_nonempty_credentials = false;

//Add setings link in the plugins page (beside the activate/deactivate links)
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'wla_add_action_settings_link' );
function wla_add_action_settings_link ( $links ) {
 $mylinks = array(
 '<a href="' . admin_url( 'options-general.php?page=wp-login-attempts' ) . '">Settings</a>',
 );
return array_merge( $links, $mylinks );
}

//Redirect on the setting page after activate plugin
function wla_register_activation_hook_after_activate( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'options-general.php?page=wp-login-attempts' ) ) );
    }
}
add_action( 'activated_plugin', 'wla_register_activation_hook_after_activate' );

$wp_login_att_opt = array(
    'client_type' => WP_LOGIN_ATT_DIR_ADD,    
    'allowed_retries' => 4, //Lock out after this many attempts    
    'lockout_duration' => 1200, //Lock out for duration 20 minutes    
    'allowed_lockouts' => 4, // Long lock out after this many lockouts     
    'long_duration' => 86400, // Long lock out for this many seconds  24 hours
    'valid_duration' => 43200, // Reset failed attempts after this many seconds 12 hours    
    'cookies' => true, //Also limit malformed/forged cookies?    
    'lockout_notify' => 'log', // Notify on lockout.    
    'notify_email_after' => 4
);
/*
 * Enqueue script for login
 */

add_action('login_enqueue_scripts', 'wla_lim_login_recaptcha_script');

function wla_lim_login_recaptcha_script() {
    wp_register_script('recaptcha_login', 'https://www.google.com/recaptcha/api.js');
    wp_enqueue_script('recaptcha_login');
    wp_enqueue_script('jquery');
    wp_enqueue_script('wla_lim_customjs', plugin_dir_url(__FILE__) . 'includes/js/wla-custom-script.js', array('jquery'));
}

/*
 * enqueue css and js file for admin
 */
add_action('admin_enqueue_scripts', 'wla_lim_enqueue_script_style');
if (!function_exists('wla_lim_enqueue_script_style')) {

    function wla_lim_enqueue_script_style() {
        wp_enqueue_script('wla_lim_vtabjs', plugin_dir_url(__FILE__) . 'includes/js/wla-setting-opt-tab.js');
        wp_enqueue_media();
        wp_enqueue_script('wla_lim_media_uplod', plugin_dir_url(__FILE__) . 'includes/js/media-upload.js');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wla_lim_color_pic', plugin_dir_url(__FILE__) . 'includes/js/color-picker.js', array('wp-color-picker'));

        wp_enqueue_style('wla_lim_vtab_style', plugin_dir_url(__FILE__) . 'includes/css/wla-settings-opts.css');
    }

}

/* include google captcha configuration file */
if (!empty(get_option('g_reCaptcha_enable')) && get_option('g_reCaptcha_enable') == 'true') {
    require_once( plugin_dir_path(__FILE__) . 'wla-google-recaptcha-form.php');
}

/* login_head action for customize login form style */
add_action('login_head', 'wla_lim_logo_file_callback_fun');
if (!function_exists('wla_lim_logo_file_callback_fun')) {

    function wla_lim_logo_file_callback_fun() {
        if (get_option('wla_lim_logo_file') != '') {
            echo '<style>.login h1 a { background-image: url("' . esc_url(get_option('wla_lim_logo_file')) . '"); background-size: contain; width: 320px; }</style>';
        }
        if (get_option('wla_lim_login_background_color') != '') {
            echo '<style>body { background-color: ' . esc_html(get_option('wla_lim_login_background_color')) . '!important; } </style>';
        }
        if (get_option('wla_lim_login_background_img') != '') {
            echo '<style>body { background-image: url("' . esc_html(get_option('wla_lim_login_background_img')) . '"); background-position: center top; background-repeat: no-repeat; background-size: cover; } </style>';
        }
        
    }

}

/* logo link */
add_filter('login_headerurl', 'wla_lim_logo_link_url');
if (!function_exists('wla_lim_logo_link_url')) {
    function wla_lim_logo_link_url($url) {
        if (get_option('wla_lim_logo_url') != '') {
            return esc_url(get_option('wla_lim_logo_url'));
        } else {
            return esc_url(get_bloginfo('url'));
        }
    }
}

/* Remove lost password url from login page */
function wla_remove_lostpassword_text($text) {
    if ($text == 'Lost your password?') {
        $text = '';
    }
    return $text;
}

if (get_option('wla_lim_lost_ur_pwd') != '') {
    add_filter('gettext', 'wla_remove_lostpassword_text');
}

/* Get options and setup filters & actions */
add_action('plugins_loaded', 'wla_wp_login_att_setup', 99999);

function wla_wp_login_att_setup() {
    load_plugin_textdomain('wp-login-attempts', false
            , dirname(plugin_basename(__FILE__)));

    wla_wp_login_att_setup_opt();

    /* Filters and actions */
    /* Hide login limit */
    if (get_option('wla_disable_login_attmpt') != 'true'){
        add_action('wp_login_failed', 'wla_login_attempt_failed');
        if (wp_login_att_option('cookies')) {
            wla_lim_login_handle_cookies_fun();
            add_action('auth_cookie_bad_username', 'wla_login_attempt_failed_cookie');

            global $wp_version;

            if (version_compare($wp_version, '3.0', '>=')) {
                add_action('auth_cookie_bad_hash', 'wla_login_attempt_failed_cookie_hash');
                add_action('auth_cookie_valid', 'wla_lim_valid_cookie', 10, 2);
            } else {
                add_action('auth_cookie_bad_hash', 'wla_login_attempt_failed_cookie');
            }
        }
        add_action('login_head', 'wla_login_attempt_add_err_mesgs');
    }

    add_filter('wp_authenticate_user', 'wla_login_attempt_authenticate_user', 99999, 2);
    add_filter('shake_error_codes', 'wla_login_attempt_failure_shake');    
    add_action('login_errors', 'wla_login_attempt_fixup_err_mesgs');
    add_action('admin_menu', 'wla_login_attempt_adminmenu');    
    add_action('wp_authenticate', 'wla_login_attempt_track_user_credentials', 10, 2);
    
}

/* Get current option value */

function wp_login_att_option($option_name) {
    global $wp_login_att_opt;

    if (isset($wp_login_att_opt[$option_name])) {
        return $wp_login_att_opt[$option_name];
    } else {
        return null;
    }
}

/* Get correct remote address */

function wla_login_attempt_get_remote_add($type_name = '') {
    $type = $type_name;
    if (empty($type)) {
        $type = wp_login_att_option('client_type');
    }

    if (isset($_SERVER[$type])) {
        return $_SERVER[$type];
    }
    if (empty($type_name) && $type == WP_LOGIN_ATT_PROXY_ADD && isset($_SERVER[WP_LOGIN_ATT_DIR_ADD])) {       

        return $_SERVER[WP_LOGIN_ATT_DIR_ADD];
    }

    return '';
}

/*
 * Check if IP is whitelisted. *
 */

function wla_is_login_attp_ip_whitelisted($ip = null) {
    if (is_null($ip)) {
        $ip = wla_login_attempt_get_remote_add();
    }
    $whitelisted = apply_filters('limit_login_whitelist_ip', false, $ip);

    return ($whitelisted === true);
}

/* Check if it is ok to login */

function wla_is_limit_login_attempt_ok() {
    $ip = wla_login_attempt_get_remote_add();

    /* Check external whitelist filter */
    if (wla_is_login_attp_ip_whitelisted($ip)) {
        return true;
    }

    /* lockout active? */
    $lockouts = get_option('wla_lim_lockouts_cal');
    return (!is_array($lockouts) || !isset($lockouts[$ip]) || time() >= $lockouts[$ip]);
}

/* Filter: allow login attempt? (called from wp_authenticate()) */

function wla_login_attempt_authenticate_user($user, $password) {
    if (is_wp_error($user) || wla_is_limit_login_attempt_ok()) {
        return $user;
    }

    global $wla_login_att_err;
    $wla_login_att_err = true;

    $error = new WP_Error();
    // This error should be the same as in "shake it" filter below
    $error->add('too_many_retries', wla_login_attempt_err_msgs());
    return $error;
}

/* Filter: add this failure to login page "Shake it!" */

function wla_login_attempt_failure_shake($error_codes) {
    $error_codes[] = 'too_many_retries';
    return $error_codes;
}

function wla_lim_login_handle_cookies_fun() {
    if (wla_is_limit_login_attempt_ok()) {
        return;
    }

    wla_clear_auth_cookie();
}

/*
 * Action: failed cookie login hash
 */

function wla_login_attempt_failed_cookie_hash($cookie_elements) {
    wla_clear_auth_cookie();

    /*
     * Under some conditions an invalid auth cookie will be used multiple
     * times, which results in multiple failed attempts from that one
     * cookie.
    */

    extract($cookie_elements, EXTR_OVERWRITE);

    // Check if cookie is for a valid user
    $user = get_userdatabylogin($username);
    if (!$user) {
        // "shouldn't happen" for this action
        wla_login_attempt_failed($username);
        return;
    }

    $previous_cookie = get_user_meta($user->ID, 'wla_lim_prev_cookie', true);
    if ($previous_cookie && $previous_cookie == $cookie_elements) {
        // Identical cookies, ignore this attempt
        return;
    }

    // Store cookie
    if ($previous_cookie)
        update_user_meta($user->ID, 'wla_lim_prev_cookie', $cookie_elements);
    else
        add_user_meta($user->ID, 'wla_lim_prev_cookie', $cookie_elements, true);

    wla_login_attempt_failed($username);
}

/*
 * successful cookie login
 */

function wla_lim_valid_cookie($cookie_elements, $user) {
    if (get_user_meta($user->ID, 'wla_lim_prev_cookie')) {
        delete_user_meta($user->ID, 'wla_lim_prev_cookie');
    }
}

/* failed cookie login */

function wla_login_attempt_failed_cookie($cookie_elements) {
    wla_clear_auth_cookie();

    /*
     * Invalid username gets counted every time.
     */

    wla_login_attempt_failed($cookie_elements['username']);
}

/* Make sure auth cookie really get cleared (for this session too) */

function wla_clear_auth_cookie() {
    wp_clear_auth_cookie();

    if (!empty($_COOKIE[AUTH_COOKIE])) {
        $_COOKIE[AUTH_COOKIE] = '';
    }
    if (!empty($_COOKIE[SECURE_AUTH_COOKIE])) {
        $_COOKIE[SECURE_AUTH_COOKIE] = '';
    }
    if (!empty($_COOKIE[LOGGED_IN_COOKIE])) {
        $_COOKIE[LOGGED_IN_COOKIE] = '';
    }
}

/*
 * When login attempt failed
 *
 */

function wla_login_attempt_failed($username) {
    $ip = wla_login_attempt_get_remote_add();

    /* if currently locked-out, do not add to retries */
    $lockouts = get_option('wla_lim_lockouts_cal');
    if (!is_array($lockouts)) {
        $lockouts = array();
    }
    if (isset($lockouts[$ip]) && time() < $lockouts[$ip]) {
        return;
    }

    /* Get the arrays with retries and retries-valid information */
    $retries = get_option('wla_lim_login_retries');
    $valid = get_option('wla_lim_login_retries_valid');
    if (!is_array($retries)) {
        $retries = array();
        add_option('wla_lim_login_retries', $retries, '', 'no');
    }
    if (!is_array($valid)) {
        $valid = array();
        add_option('wla_lim_login_retries_valid', $valid, '', 'no');
    }

    /* Check validity and add one to retries */
    if (isset($retries[$ip]) && isset($valid[$ip]) && time() < $valid[$ip]) {
        $retries[$ip] ++;
    } else {
        $retries[$ip] = 1;
    }
    $valid[$ip] = time() + wp_login_att_option('valid_duration');

    /* lockout? */
    if ($retries[$ip] % wp_login_att_option('allowed_retries') != 0) {
        wla_lim_cleanup_lockout($retries, null, $valid);
        return;
    }

    /* lockout! */

    $whitelisted = wla_is_login_attp_ip_whitelisted($ip);

    $retries_long = wp_login_att_option('allowed_retries') * wp_login_att_option('allowed_lockouts');

    /*
     * Note that retries and statistics are still counted and notifications
     * done as usual for whitelisted ips , but no lockout is done.
     */
    if ($whitelisted) {
        if ($retries[$ip] >= $retries_long) {
            unset($retries[$ip]);
            unset($valid[$ip]);
        }
    } else {
        global $wla_login_att_lockedout;
        $wla_login_att_lockedout = true;

        /* setup lockout, reset retries as needed */
        if ($retries[$ip] >= $retries_long) {
            /* long lockout */
            $lockouts[$ip] = time() + wp_login_att_option('long_duration');
            unset($retries[$ip]);
            unset($valid[$ip]);
        } else {
            /* normal lockout */
            $lockouts[$ip] = time() + wp_login_att_option('lockout_duration');
        }
    }

    /* do housecleaning and save values */
    wla_lim_cleanup_lockout($retries, $lockouts, $valid);

    /* do any notification */
    wla_lim_loginnotify($username);

    /* increase statistics */
    $total = get_option('wla_lim_lockoutstotal');
    if ($total === false || !is_numeric($total)) {
        add_option('wla_lim_lockoutstotal', 1, '', 'no');
    } else {
        update_option('wla_lim_lockoutstotal', $total + 1);
    }
}

/* Clean up old lockouts and retries, and save supplied arrays */

function wla_lim_cleanup_lockout($retries = null, $lockouts = null, $valid = null) {
    $now = time();
    $lockouts = !is_null($lockouts) ? $lockouts : get_option('wla_lim_lockouts_cal');

    /* remove old lockouts */
    if (is_array($lockouts)) {
        foreach ($lockouts as $ip => $lockout) {
            if ($lockout < $now) {
                unset($lockouts[$ip]);
            }
        }
        update_option('wla_lim_lockouts_cal', $lockouts);
    }

    /* remove retries that are no longer valid */
    $valid = !is_null($valid) ? $valid : get_option('wla_lim_login_retries_valid');
    $retries = !is_null($retries) ? $retries : get_option('wla_lim_login_retries');
    if (!is_array($valid) || !is_array($retries)) {
        return;
    }

    foreach ($valid as $ip => $lockout) {
        if ($lockout < $now) {
            unset($valid[$ip]);
            unset($retries[$ip]);
        }
    }

    /* go through retries directly, if for some reason they've gone out of sync */
    foreach ($retries as $ip => $retry) {
        if (!isset($valid[$ip])) {
            unset($retries[$ip]);
        }
    }

    update_option('wla_lim_login_retries', $retries);
    update_option('wla_lim_login_retries_valid', $valid);
}

/* Is this WP Multisite? */

function wla_lim_login_is_login_multisite() {
    return function_exists('get_site_option') && function_exists('is_multisite') && is_multisite();
}

/* Email notification of lockout to admin (if configured) */

function wla_lim_login_notify_eml($user) {
    $ip = wla_login_attempt_get_remote_add();
    $whitelisted = wla_is_login_attp_ip_whitelisted($ip);

    $retries = get_option('wla_lim_login_retries');
    if (!is_array($retries)) {
        $retries = array();
    }

    /* check if we are at the right nr to do notification */
    if (isset($retries[$ip]) && ( ($retries[$ip] / wp_login_att_option('allowed_retries')) % wp_login_att_option('notify_email_after') ) != 0) {
        return;
    }

    /* Format message. First current lockout duration */
    if (!isset($retries[$ip])) {
        /* longer lockout */
        $count = wp_login_att_option('allowed_retries') * wp_login_att_option('allowed_lockouts');
        $lockouts = wp_login_att_option('allowed_lockouts');
        $time = round(wp_login_att_option('long_duration') / 3600);
        $when = sprintf(_n('%d hour', '%d hours', $time, 'wp-login-attempts'), $time);
    } else {
        /* normal lockout */
        $count = $retries[$ip];
        $lockouts = floor($count / wp_login_att_option('allowed_retries'));
        $time = round(wp_login_att_option('lockout_duration') / 60);
        $when = sprintf(_n('%d minute', '%d minutes', $time, 'wp-login-attempts'), $time);
    }

    $blogname = wla_lim_login_is_login_multisite() ? get_site_option('site_name') : get_option('blogname');

    if ($whitelisted) {
        $subject = sprintf(__("[%s] Failed login attempts from whitelisted IP"
                        , 'wp-login-attempts')
                , $blogname);
    } else {
        $subject = sprintf(__("[%s] Too many failed login attempts"
                        , 'wp-login-attempts')
                , $blogname);
    }

    $message = sprintf(__("%d failed login attempts (%d lockout(s)) from IP: %s"
                    , 'wp-login-attempts') . "\r\n\r\n"
            , $count, $lockouts, $ip);
    if ($user != '') {
        $message .= sprintf(__("Last user attempted: %s", 'wp-login-attempts')
                . "\r\n\r\n", $user);
    }
    if ($whitelisted) {
        $message .= __("IP was NOT blocked because of external whitelist.", 'wp-login-attempts');
    } else {
        $message .= sprintf(__("IP was blocked for %s", 'wp-login-attempts'), $when);
    }

    $admin_email = wla_lim_login_is_login_multisite() ? get_site_option('admin_email') : get_option('admin_email');

    @wp_mail($admin_email, $subject, $message);
}

/* Logging of lockout (if configured) */

function wla_lim_login_notify_log($user) {
    $log = $option = get_option('wla_lim_login_handle_log_key');
    if (!is_array($log)) {
        $log = array();
    }
    $ip = wla_login_attempt_get_remote_add();

    /* can be written much simpler, if you do not mind php warnings */
    if (isset($log[$ip])) {
        if (isset($log[$ip][$user])) {
            $log[$ip][$user] ++;
        } else {
            $log[$ip][$user] = 1;
        }
    } else {
        $log[$ip] = array($user => 1);
    }

    if ($option === false) {
        add_option('wla_lim_login_handle_log_key', $log, '', 'no'); /* no autoload */
    } else {
        update_option('wla_lim_login_handle_log_key', $log);
    }
}

/* Handle notification in event of lockout */

function wla_lim_loginnotify($user) {
    $args = explode(',', wp_login_att_option('lockout_notify'));

    if (empty($args)) {
        return;
    }

    foreach ($args as $mode) {
        switch (trim($mode)) {
            case 'email':
                wla_lim_login_notify_eml($user);
                break;
            case 'log':
                wla_lim_login_notify_log($user);
                break;
        }
    }
}

/* Construct informative error message */

function wla_login_attempt_err_msgs() {
    $ip = wla_login_attempt_get_remote_add();
    $lockouts = get_option('wla_lim_lockouts_cal');

    $msg = __('<strong>ERROR</strong>: Too many failed login attempts.', 'wp-login-attempts') . ' ';

    if (!is_array($lockouts) || !isset($lockouts[$ip]) || time() >= $lockouts[$ip]) {
        /* Huh? No timeout active? */
        $msg .= __('Please try again later.', 'wp-login-attempts');
        return $msg;
    }

    $when = ceil(($lockouts[$ip] - time()) / 60);
    if ($when > 60) {
        $when = ceil($when / 60);
        $msg .= sprintf(_n('Please try again in %d hour.', 'Please try again in %d hours.', $when, 'wp-login-attempts'), $when);
    } else {
        $msg .= sprintf(_n('Please try again in %d minute.', 'Please try again in %d minutes.', $when, 'wp-login-attempts'), $when);
    }

    return $msg;
}

/* Construct retries remaining message */

function wla_lim_login_retries_remaining_msg() {
    $ip = wla_login_attempt_get_remote_add();
    $retries = get_option('wla_lim_login_retries');
    $valid = get_option('wla_lim_login_retries_valid');

    /* Should we show retries remaining? */

    if (!is_array($retries) || !is_array($valid)) {
        /* no retries at all */
        return '';
    }
    if (!isset($retries[$ip]) || !isset($valid[$ip]) || time() > $valid[$ip]) {
        /* no: no valid retries */
        return '';
    }
    if (($retries[$ip] % wp_login_att_option('allowed_retries')) == 0) {
        /* no: already been locked out for these retries */
        return '';
    }

    $remaining = max((wp_login_att_option('allowed_retries') - ($retries[$ip] % wp_login_att_option('allowed_retries'))), 0);
    return sprintf(_n("<strong>%d</strong> attempt remaining.", "<strong>%d</strong> attempts remaining.", $remaining, 'wp-login-attempts'), $remaining);
}

/* Return current (error) message to show, if any */

function wla_login_attempt_get_errs_mesgs() {
    /* Check external whitelist */
    if (wla_is_login_attp_ip_whitelisted()) {
        return '';
    }

    /* Is lockout in effect? */
    if (!wla_is_limit_login_attempt_ok()) {
        return wla_login_attempt_err_msgs();
    }

    return wla_lim_login_retries_remaining_msg();
}

/* Should we show errors and messages on this page? */

function wla_login_attempt_disply_mesgs() {
    if (isset($_GET['key'])) {
        /* reset password */
        return false;
    }

    $action = isset($_REQUEST['action']) ? esc_html($_REQUEST['action']) : '';

    return ( $action != 'lostpassword' && $action != 'retrievepassword' && $action != 'resetpass' && $action != 'rp' && $action != 'register' );
}

/* Fix up the error message before showing it */

function wla_login_attempt_fixup_err_mesgs($content) {
    global $wla_login_att_lockedout, $wla_login_att_nonempty_credentials, $wla_login_att_err;

    if (!wla_login_attempt_disply_mesgs()) {
        return $content;
    }

    if (!wla_is_limit_login_attempt_ok() && !$wla_login_att_lockedout) {
        return wla_login_attempt_err_msgs();
    }

    $msgs = explode("<br />\n", $content);

    if (strlen(end($msgs)) == 0) {
        /* remove last entry empty string */
        array_pop($msgs);
    }

    $count = count($msgs);
    $my_warn_count = $wla_login_att_err ? 1 : 0;

    if ($wla_login_att_nonempty_credentials && $count > $my_warn_count) {
        /* Replace error message, including ours if necessary */
        $content = __('<strong>ERROR</strong>: Incorrect username or password.', 'wp-login-attempts') . "<br />\n";
        if ($wla_login_att_err) {
            $content .= "<br />\n" . wla_login_attempt_get_errs_mesgs() . "<br />\n";
        }
        return $content;
    } elseif ($count <= 1) {
        return $content;
    }

    $new = '';
    while ($count-- > 0) {
        $new .= array_shift($msgs) . "<br />\n";
        if ($count > 0) {
            $new .= "<br />\n";
        }
    }

    return $new;
}

/* Add a message to login page when necessary */

function wla_login_attempt_add_err_mesgs() {
    global $error, $wla_login_att_err;

    if (!wla_login_attempt_disply_mesgs() || $wla_login_att_err) {
        return;
    }

    $msg = wla_login_attempt_get_errs_mesgs();

    if ($msg != '') {
        $wla_login_att_err = true;
        $error .= $msg;
    }

    return;
}

/* Keep track of if user or password are empty, to filter errors correctly */

function wla_login_attempt_track_user_credentials($user, $password) {
    global $wla_login_att_nonempty_credentials;
    $wla_login_att_nonempty_credentials = (!empty($user) && !empty($password));
}

/*
 * Admin stuff
 */

/* Make a guess if we are behind a proxy or not */

function wla_login_attempt_guess_proxy_servr() {
    return isset($_SERVER[WP_LOGIN_ATT_PROXY_ADD]) ? WP_LOGIN_ATT_PROXY_ADD : WP_LOGIN_ATT_DIR_ADD;
}

/* Only change var if option exists */

function wla_lim_login_get_opt($option, $var_name) {
    $a = get_option($option);

    if ($a !== false) {
        global $wp_login_att_opt;

        $wp_login_att_opt[$var_name] = $a;
    }
}

/* Setup global variables from options */

function wla_wp_login_att_setup_opt() {
    wla_lim_login_get_opt('wla_lim_login_clienttype_key', 'client_type');
    wla_lim_login_get_opt('wla_disable_login_attmpt', 'disable_login_attmpt');
    wla_lim_login_get_opt('wla_lim_login_allowed_retries_key', 'allowed_retries');
    wla_lim_login_get_opt('wla_lim_login_lockout_duration_key', 'lockout_duration');
    wla_lim_login_get_opt('wla_lim_login_valid_duration_key', 'valid_duration');
    wla_lim_login_get_opt('wla_lim_login_cookies_key', 'cookies');
    wla_lim_login_get_opt('wla_lim_login_lockout_notify_key', 'lockout_notify');
    wla_lim_login_get_opt('wla_lim_login_allowed_lockouts_key', 'allowed_lockouts');
    wla_lim_login_get_opt('wla_lim_login_long_duration_key', 'long_duration');
    wla_lim_login_get_opt('wla_lim_login_notify_email_after_key', 'notify_email_after');

    wla_login_attempt_sanitize_variables();
}

/* Update options in db from global variables */

function wla_login_attempt_updateopt() {
    $client_type = update_option('wla_lim_login_clienttype_key', wp_login_att_option('client_type'));
    $allowed_ret = update_option('wla_lim_login_allowed_retries_key', wp_login_att_option('allowed_retries'));
    $lockout_duration = update_option('wla_lim_login_lockout_duration_key', wp_login_att_option('lockout_duration'));
    $allowed_lockouts = update_option('wla_lim_login_allowed_lockouts_key', wp_login_att_option('allowed_lockouts'));
    $long_duration = update_option('wla_lim_login_long_duration_key', wp_login_att_option('long_duration'));
    $valid_duration = update_option('wla_lim_login_valid_duration_key', wp_login_att_option('valid_duration'));
    $lockout_notify = update_option('wla_lim_login_lockout_notify_key', wp_login_att_option('lockout_notify'));
    $notify_email_after = update_option('wla_lim_login_notify_email_after_key', wp_login_att_option('notify_email_after'));
    $cookies = update_option('wla_lim_login_cookies_key', wp_login_att_option('cookies') ? '1' : '0');

    //Disable limit login attempt
    if (isset($_POST['disable_login_attmpt'])) {
        if(filter_var($_POST['disable_login_attmpt'], FILTER_UNSAFE_RAW)){
            $wp_login_att_opt['disable_login_attmpt'] =  filter_var($_POST['disable_login_attmpt'], FILTER_UNSAFE_RAW);
            $disable_login_attmpt = update_option('wla_disable_login_attmpt', $wp_login_att_opt['disable_login_attmpt']);
        }
    }
    else{
        $disable_login_attmpt = update_option('wla_disable_login_attmpt', 'false');
    }

    if (!empty($allowed_ret) || !empty($disable_login_attmpt) || !empty($client_type) || !empty($lockout_duration) || !empty($allowed_lockouts) || !empty($long_duration) || !empty($valid_duration) || !empty($lockout_notify) || !empty($notify_email_after) || !empty($cookies)) {
        ?>
        <!--<div id="message" class="opt-updated fade11">
           <p><?php _e("Options saved", "wp-login-attempts"); ?></p>
        </div>-->
        <div id="setting-error-settings_updated" class="wla-set-update-notice updated settings-error notice is-dismissible">
            <p><strong><?php _e("Options saved.", "wp-login-attempts"); ?></strong></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
    <?php
    }
}

/* Make sure the variables make sense -- simple integer */

function wla_login_attempt_sanitize_int($var_name) {
    global $wp_login_att_opt;

    $wp_login_att_opt[$var_name] = max(1, intval(wp_login_att_option($var_name)));
}

/* Make sure the variables make sense */

function wla_login_attempt_sanitize_variables() {
    global $wp_login_att_opt;

    wla_login_attempt_sanitize_int('allowed_retries');
    wla_login_attempt_sanitize_int('lockout_duration');
    wla_login_attempt_sanitize_int('valid_duration');
    wla_login_attempt_sanitize_int('allowed_lockouts');
    wla_login_attempt_sanitize_int('long_duration');

    $wp_login_att_opt['cookies'] = !!wp_login_att_option('cookies');

    $notify_email_after = max(1, intval(wp_login_att_option('notify_email_after')));
    $wp_login_att_opt['notify_email_after'] = min(wp_login_att_option('allowed_lockouts'), $notify_email_after);

    $args = explode(',', wp_login_att_option('lockout_notify'));
    $args_allowed = explode(',', WP_LOGIN_ATT_LOCKOUT_NOTIFY);
    $new_args = array();
    foreach ($args as $a) {
        if (in_array($a, $args_allowed)) {
            $new_args[] = $a;
        }
    }
    $wp_login_att_opt['lockout_notify'] = implode(',', $new_args);

    if (wp_login_att_option('client_type') != WP_LOGIN_ATT_DIR_ADD && wp_login_att_option('client_type') != WP_LOGIN_ATT_PROXY_ADD) {
        $wp_login_att_opt['client_type'] = WP_LOGIN_ATT_DIR_ADD;
    }
}

/* Add admin options page */

function wla_login_attempt_adminmenu() {
    global $wp_version;

    // Modern WP?
    if (version_compare($wp_version, '3.0', '>=')) {
        add_options_page('WP Login Attempts', 'WP Login Attempts', 'manage_options', 'wp-login-attempts', 'wp_login_att_option_page');
        return;
    }

   
    // Older WP
    add_options_page('WP Login Attempts', 'WP Login Attempts', 9, 'wp-login-attempts', 'wp_login_att_option_page');
}

/* Show log on admin page */

function wla_show_log_in_admin($log) {
    if (!is_array($log) || count($log) == 0) {
        return;
    }

    echo('<tr><th scope="col">' . _x("IP", "Internet address", 'wp-login-attempts') . '</th><th scope="col">' . __('Tried to log in as', 'wp-login-attempts') . '</th></tr>');
    foreach ($log as $ip => $arr) {
        echo('<tr><td class="limit-login-ip">' . $ip . '</td><td class="limit-login-max">');
        $first = true;
        foreach ($arr as $user => $count) {
            $count_desc = sprintf(_n('%d lockout', '%d lockouts', $count, 'wp-login-attempts'), $count);
            if (!$first) {
                echo(', ' . $user . ' (' . $count_desc . ')');
            } else {
                echo($user . ' (' . $count_desc . ')');
            }
            $first = false;
        }
        echo('</td></tr>');
    }
}

/* Actual admin page */

function wp_login_att_option_page() {
    wla_lim_cleanup_lockout();

    if (!current_user_can('manage_options')) {
        wp_die('Sorry, but you do not have permissions to change settings.');
    }

    /* Make sure post was from this page */
    if (count($_POST) > 0) {
        check_admin_referer('wp-login-attempts-options');
    }

    /* Should we clear log? */
    if (isset($_POST['clear_log'])) {
        delete_option('wla_lim_login_handle_log_key'); ?>
        <div id="setting-error-settings_updated" class="wla-set-update-notice updated settings-error notice is-dismissible">
            <p><strong><?php _e("Cleared IP log(s).", "wp-login-attempts"); ?></strong></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
        <?php
    }

    /* Should we reset counter? */
    if (isset($_POST['reset_total'])) {
        update_option('wla_lim_lockoutstotal', 0); ?>
        <div id="setting-error-settings_updated" class="wla-set-update-notice updated settings-error notice is-dismissible">
            <p><strong><?php _e("Reset lockout counter.", "wp-login-attempts"); ?></strong></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
        <?php
    }

    /* Should we restore current lockouts? */
    if (isset($_POST['reset_current'])) {
        update_option('wla_lim_lockouts_cal', array());
        echo '<div id="message" class="updated fade"><p>'
        . __('Cleared current lockouts', 'wp-login-attempts')
        . '</p></div>';
    }

    /* Should we update options? */
    if (isset($_POST['update_options'])) {
        global $wp_login_att_opt;
        if (filter_var($_POST['client_type'], FILTER_UNSAFE_RAW)) {
           $wp_login_att_opt['client_type'] =  filter_var($_POST['client_type'], FILTER_UNSAFE_RAW);
        }

        if(filter_var($_POST['allowed_retries'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1,"max_range" => 40)))){
            $wp_login_att_opt['allowed_retries'] =  filter_var($_POST['allowed_retries'], FILTER_VALIDATE_INT);
        }

        if(filter_var($_POST['lockout_duration'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1,"max_range" => 10080)))){
            $wp_login_att_opt['lockout_duration'] =  filter_var($_POST['lockout_duration'], FILTER_VALIDATE_INT) * 60;
        } 

        if(filter_var($_POST['valid_duration'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1,"max_range" => 168)))){
            $wp_login_att_opt['valid_duration'] =  filter_var($_POST['valid_duration'], FILTER_VALIDATE_INT)* 3600;
        }

        if(filter_var($_POST['allowed_lockouts'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1,"max_range" => 100)))){
            $wp_login_att_opt['allowed_lockouts'] =  filter_var($_POST['allowed_lockouts'], FILTER_VALIDATE_INT);
        }

        if(filter_var($_POST['long_duration'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1,"max_range" => 8760)))){
            $wp_login_att_opt['long_duration'] =  filter_var($_POST['long_duration'], FILTER_VALIDATE_INT) * 3600;
        }

        if(filter_var($_POST['email_after'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1,"max_range" => 100)))){
            $wp_login_att_opt['notify_email_after'] =  filter_var($_POST['email_after'], FILTER_VALIDATE_INT);
        }


        $wp_login_att_opt['cookies'] = (isset($_POST['cookies']) && filter_var($_POST['cookies'], FILTER_VALIDATE_INT) == '1');
        $v = array();
        if (isset($_POST['lockout_notify_log'])) {
            $v[] = 'log';
        }
        if (isset($_POST['lockout_notify_email'])) {
            $v[] = 'email';
        }
        $wp_login_att_opt['lockout_notify'] = implode(',', $v);

        wla_login_attempt_sanitize_variables();
        wla_login_attempt_updateopt();
    }

    /* save captcha settings */

    if (isset($_POST['update_recpat'])) {
        global $wp_login_att_opt;
        if (isset($_POST['g_reCaptcha_enable'])) {
            if (filter_var($_POST['g_reCaptcha_enable'], FILTER_UNSAFE_RAW) === 'true')
            { 
                $wp_login_att_opt['g_reCaptcha_enable'] = filter_var($_POST['g_reCaptcha_enable'], FILTER_UNSAFE_RAW);
            }  
            else 
            { 
                $wp_login_att_opt['g_reCaptcha_enable'] = false;
            }
        }
        if(isset($_POST['gglcptch_recaptcha_version'])){
            if (filter_var($_POST['gglcptch_recaptcha_version'], FILTER_UNSAFE_RAW) === 'v2' || filter_var($_POST['gglcptch_recaptcha_version'], FILTER_UNSAFE_RAW) === 'v3'){ 
            $wp_login_att_opt['gglcptch_recaptcha_version'] = filter_var($_POST['gglcptch_recaptcha_version'], FILTER_UNSAFE_RAW);
            }
        }
        
        
        // recaptcha v2
        $wp_login_att_opt['gsite_key'] = sanitize_text_field($_POST['g_reCaptcha_site_key']);
        $wp_login_att_opt['gsecret_key'] = sanitize_text_field($_POST['g_reCaptcha_secret_key']);
        // recaptcha v3
        $wp_login_att_opt['g_reCaptcha_site_key_v3'] = sanitize_text_field($_POST['g_reCaptcha_site_key_v3']);
        $wp_login_att_opt['g_reCaptcha_secret_key_v3'] = sanitize_text_field($_POST['g_reCaptcha_secret_key_v3']);

        $g_reCap_enable = update_option('g_reCaptcha_enable', wp_login_att_option('g_reCaptcha_enable'));
        $gcap_var = update_option('g_reCaptcha_version', wp_login_att_option('gglcptch_recaptcha_version'));
        
        $gsite_key = update_option('g_reCaptcha_site_key', wp_login_att_option('gsite_key'));
        $gsecret_key = update_option('g_reCaptcha_secret_key', wp_login_att_option('gsecret_key'));
        
        $site_key_v3 = update_option('g_reCaptcha_site_key_v3', wp_login_att_option('g_reCaptcha_site_key_v3'));
        $secret_key_v3 = update_option('g_reCaptcha_secret_key_v3', wp_login_att_option('g_reCaptcha_secret_key_v3'));
        
        if (!empty($gcap_var) || !empty($gsite_key) || !empty($gsecret_key) || !empty($g_reCap_enable) || !empty($site_key_v3) || !empty($secret_key_v3)) {
            ?>
            <div id="setting-error-settings_updated" class="wla-set-update-notice updated settings-error notice is-dismissible">
                <p><strong><?php _e("Options saved.", "wp-login-attempts"); ?></strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>
        <?php
        }
    }

    /* save login's logo settings */

    if (isset($_POST['logo_setng'])) {
        global $wp_login_att_opt;
        $wp_login_att_opt['wla_lim_logo_url'] = esc_url($_POST['wla_lim_logo_url']);
        $wp_login_att_opt['wla_lim_logo_file'] = esc_url($_POST['wla_lim_logo_file']);
        $wp_login_att_opt['wla_lim_login_background_img'] = esc_url($_POST['wla_lim_login_background_img']);
        $wp_login_att_opt['wla_lim_login_background_color'] = sanitize_hex_color($_POST['wla_lim_login_background_color']);
        if (isset($_POST['wla_lim_lost_ur_pwd'])) {           

            $wp_login_att_opt['wla_lim_lost_ur_pwd'] = filter_var($_POST['wla_lim_lost_ur_pwd'], FILTER_UNSAFE_RAW);
        }
        
        $wla_lim_logo_url = update_option('wla_lim_logo_url', wp_login_att_option('wla_lim_logo_url'));
        $wla_lim_logo_file = update_option('wla_lim_logo_file', wp_login_att_option('wla_lim_logo_file'));
        $wla_lim_login_bground_img = update_option('wla_lim_login_background_img', wp_login_att_option('wla_lim_login_background_img'));
        $wla_lim_back_color = update_option('wla_lim_login_background_color', wp_login_att_option('wla_lim_login_background_color'));
        $wla_lim_lost_ur_pwd = update_option('wla_lim_lost_ur_pwd', wp_login_att_option('wla_lim_lost_ur_pwd'));
        
        if (!empty($wla_lim_logo_url) || !empty($wla_lim_logo_file) || !empty($wla_lim_login_bground_img) || !empty($wla_lim_back_color) || !empty($wla_lim_lost_ur_pwd) ) {
            ?>
            <div id="setting-error-settings_updated" class="wla-set-update-notice updated settings-error notice is-dismissible">
                <p><strong><?php _e("Settings saved.", "wp-login-attempts"); ?></strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>
        <?php
        }
    }

    /* save login url slug */
    if (isset($_POST['hidelogin'])) {
        global $wp_login_att_opt;
        $wp_login_att_opt['wla_lim_hide_login_page'] = esc_html(strip_tags($_POST['wla_lim_hide_login_page']));
        $wp_login_att_opt['wla_lim_redirect_login_wpadmin'] = esc_html(strip_tags($_POST['wla_lim_redirect_login_wpadmin']));

        $wla_lim_hide_login_page = update_option('wla_lim_hide_login_page', wp_login_att_option('wla_lim_hide_login_page'));
        $wla_lim_redirect_login_wpadmin = update_option('wla_lim_redirect_login_wpadmin', wp_login_att_option('wla_lim_redirect_login_wpadmin'));
        if (!empty($wla_lim_hide_login_page) || !empty($wla_lim_redirect_login_wpadmin)) {
            ?>
            <div id="setting-error-settings_updated" class="wla-set-update-notice updated settings-error notice is-dismissible">
                <p><strong><?php _e("Settings saved.", "wp-login-attempts"); ?></strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>
        <?php
        }
    }

    $lockouts_total = get_option('wla_lim_lockoutstotal', 0);
    $lockouts = get_option('wla_lim_lockouts_cal');
    $lockouts_now = is_array($lockouts) ? count($lockouts) : 0;

    $cookies_yes = wp_login_att_option('cookies') ? ' checked ' : '';
    $cookies_no = wp_login_att_option('cookies') ? '' : ' checked ';

    $client_type = wp_login_att_option('client_type');
    $client_type_direct = $client_type == WP_LOGIN_ATT_DIR_ADD ? ' checked ' : '';
    $client_type_proxy = $client_type == WP_LOGIN_ATT_PROXY_ADD ? ' checked ' : '';

    $client_type_guess = wla_login_attempt_guess_proxy_servr();

    if ($client_type_guess == WP_LOGIN_ATT_DIR_ADD) {
        $client_type_message = sprintf(__('It appears the site is reached directly (from your IP: %s)', 'wp-login-attempts'), wla_login_attempt_get_remote_add(WP_LOGIN_ATT_DIR_ADD));
    } else {
        $client_type_message = sprintf(__('It appears the site is reached through a proxy server (proxy IP: %s, your IP: %s)', 'wp-login-attempts'), wla_login_attempt_get_remote_add(WP_LOGIN_ATT_DIR_ADD), wla_login_attempt_get_remote_add(WP_LOGIN_ATT_PROXY_ADD));
    }
    $client_type_message .= '<br />';

    $client_type_warning = '';
    if ($client_type != $client_type_guess) {
        $faq = 'http://wordpress.org/extend/plugins/wp-login-attempts/faq/';

        $client_type_warning = '<br /><br />' . sprintf(__('<strong>Current setting appears to be invalid</strong>. Please make sure it is correct. Further information can be found <a href="%s" title="FAQ">here</a>', 'wp-login-attempts'), $faq);
    }

    $v = explode(',', wp_login_att_option('lockout_notify'));
    $log_checked = in_array('log', $v) ? ' checked ' : '';
    $email_checked = in_array('email', $v) ? ' checked ' : '';

    /*Incluce setting file*/
    require_once( plugin_dir_path(__FILE__) . 'wla-settings-options.php');
}

/* Load login page hide */

if(!empty(get_option('wla_lim_hide_login_page'))){
    add_action('plugins_loaded', 'wla_lim_load_plugin_hide_page');
    add_action('wp_loaded', 'wla_lim_loaded_fun');
    add_filter('site_url', 'wla_lim_site_url', 10, 4);
    add_filter('wp_redirect', 'wla_lim_redirect', 10, 2);
    remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
    add_action('template_redirect', 'wla_login_attempt_redirect_expdata');
    add_action('setup_theme', 'wla_login_attempt_setuptheme', 1);
}
function wla_lim_load_plugin_hide_page() {

    global $pagenow, $wp_login_php;

    $request = parse_url($_SERVER['REQUEST_URI']);

    if (( strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-login.php') !== false || ( isset($request['path']) && untrailingslashit($request['path']) === site_url('wp-login', 'relative') ) ) && !is_admin()) {

        $wp_login_php = true;

        $_SERVER['REQUEST_URI'] = wla_login_attempt_usr_trailingslashit('/' . str_repeat('-/', 10));

        $pagenow = 'index.php';
    } elseif (( isset($request['path']) && untrailingslashit($request['path']) === home_url(get_option('wla_lim_hide_login_page'), 'relative') ) || (!get_option('permalink_structure') && isset($_GET[get_option('wla_lim_hide_login_page')]) && empty($_GET[get_option('wla_lim_hide_login_page')]) )) {

        $pagenow = 'wp-login.php';
    } elseif (( strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-register.php') !== false || ( isset($request['path']) && untrailingslashit($request['path']) === site_url('wp-register', 'relative') ) ) && !is_admin()) {

        $wp_login_php = true;

        $_SERVER['REQUEST_URI'] = wla_login_attempt_usr_trailingslashit('/' . str_repeat('-/', 10));

        $pagenow = 'index.php';
    }
}


function wla_lim_loaded_fun() {
    global $pagenow, $wp_login_php;
    $request = parse_url($_SERVER['REQUEST_URI']);
    if (!isset($_POST['post_password'])) {

        if (is_admin() && !is_user_logged_in() && !defined('DOING_AJAX') && $pagenow !== 'admin-post.php' && $request['path'] !== '/wp-admin/options.php') {
            wp_safe_redirect(wla_login_attempt_newredirect());
            die();
        }

        if ($pagenow === 'wp-login.php' && $request['path'] !== wla_login_attempt_usr_trailingslashit($request['path']) && get_option('permalink_structure')) {

            wp_safe_redirect(wla_login_attempt_usr_trailingslashit(wla_login_attempt_newlogin_url()) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ));

            die;
        } elseif ($wp_login_php) {

            if (( $referer = wp_get_referer() ) && strpos($referer, 'wp-activate.php') !== false && ( $referer = parse_url($referer) ) && !empty($referer['query'])) {

                parse_str($referer['query'], $referer);

                @require_once WPINC . '/ms-functions.php';

                if (!empty($referer['key']) && ( $result = wpmu_activate_signup($referer['key']) ) && is_wp_error($result) && ( $result->get_error_code() === 'already_active' || $result->get_error_code() === 'blog_taken' )) {

                    wp_safe_redirect(wla_login_attempt_newlogin_url()
                            . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ));

                    die;
                }
            }
            wla_lim_template_loader();
        } elseif ($pagenow === 'wp-login.php') {
            global $error, $interim_login, $action, $user_login;

            if (is_user_logged_in() && !isset($_REQUEST['action'])) {
                wp_safe_redirect(admin_url());
                die();
            }

            @require_once ABSPATH . 'wp-login.php';

            die;
        }
    }
}

function wla_lim_template_loader() {
    global $pagenow;
    $pagenow = 'index.php';
    if (!defined('WP_USE_THEMES')) {
        define('WP_USE_THEMES', true);
    }
    wp();
    require_once( ABSPATH . WPINC . '/template-loader.php' );
    die;
}

function wla_login_attempt_newlogin_url($scheme = null) {

    if (get_option('permalink_structure')) {

        return wla_login_attempt_usr_trailingslashit(home_url('/', $scheme) . wla_login_attempt_newlogin_slug());
    } else {

        return home_url('/', $scheme) . '?' . wla_login_attempt_newlogin_slug();
    }
}

function wla_login_attempt_newlogin_slug() {
    if (!empty(get_option('wla_lim_hide_login_page'))) {
        $slug = get_option('wla_lim_hide_login_page');
        return $slug;
    }
}

function wla_login_attempt_usr_trailingslashit($string) {
    return wla_login_attempt_trailing_slashes() ? trailingslashit($string) : untrailingslashit($string);
}

function wla_login_attempt_trailing_slashes() {
    return ( '/' === substr(get_option('permalink_structure'), - 1, 1) );
}

function wla_login_attempt_newredirect($scheme = null) {

    if (get_option('permalink_structure')) {

        return wla_login_attempt_usr_trailingslashit(home_url('/', $scheme) . wla_login_attempt_newredirect_slg());
    } else {

        return home_url('/', $scheme) . '?' . wla_login_attempt_newredirect_slg();
    }
}

function wla_login_attempt_newredirect_slg() {
    if ($slug = get_option('wla_lim_redirect_login_wpadmin')) {
        return $slug;
    } else if ($slug = '404') {
        return $slug;
    }
}

function wla_lim_site_url($url, $path, $scheme, $blog_id) {
    return wla_login_attempt_filter_login_php($url, $scheme);
}

function wla_login_attempt_filter_login_php($url, $scheme = null) {
    if (strpos($url, 'wp-login.php?action=postpass') !== false) {
        return $url;
    }
    if (strpos($url, 'wp-login.php') !== false && strpos(wp_get_referer(), 'wp-login.php') === false) {
        if (is_ssl()) {
            $scheme = 'https';
        }
        $args = explode('?', $url);
        if (isset($args[1])) {
            parse_str($args[1], $args);

            if (isset($args['login'])) {
                $args['login'] = rawurlencode($args['login']);
            }

            $url = add_query_arg($args, wla_login_attempt_newlogin_url($scheme));
        } else {
            $url = wla_login_attempt_newlogin_url($scheme);
        }
    }
    return $url;
}

function wla_lim_redirect($location, $status) {
    if (strpos($location, 'https://wordpress.com/wp-login.php') !== false) {
        return $location;
    }
    return wla_login_attempt_filter_login_php($location);
}

function wla_login_attempt_redirect_expdata() {
    if (!empty($_GET) && isset($_GET['action']) && 'confirmaction' === $_GET['action'] && isset($_GET['request_id']) && isset($_GET['confirm_key'])) {
        $request_id = (int) $_GET['request_id'];
        $key = sanitize_text_field(wp_unslash($_GET['confirm_key']));
        $result = wp_validate_user_request_key($request_id, $key);

        if (!is_wp_error($result)) {
            wp_redirect(add_query_arg(array(
                'action' => 'confirmaction',
                'request_id' => sanitize_text_field($_GET['request_id']),
                'confirm_key' => sanitize_text_field($_GET['confirm_key'])
                            ), wla_login_attempt_newlogin_url()
            ));
            exit();
        }
    }
}

function wla_login_attempt_setuptheme() {
    global $pagenow;

    if (!is_user_logged_in() && 'customize.php' === $pagenow) {
        wp_die(__('This has been disabled', 'wp-login-attempts'), 403);
    }
}
