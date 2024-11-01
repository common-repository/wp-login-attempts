<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wla-menusec-set w3-sidebar w3-bar-block w3-black w3-card" style="width:130px">
    <h5 class="w3-bar-item"><?php echo __('Menu', 'wp-login-attempts'); ?></h5>  
    <button class="wla-list-itm w3-bar-item w3-button tablink w3-red" onclick="openLink(event, 'attempt-set')"><?php echo __('Attempts Settings', 'wp-login-attempts'); ?></button>

    <button class="wla-list-itm w3-bar-item w3-button tablink" <?php
    if (isset($_GET['setingid']) && !empty($_GET['setingid'])) {
        if ($_GET['setingid'] === 'reCaptcha-set') {
            ?>
                    style="color:#fff!important;background-color:#f44336!important"      
                <?php
                }
            }
            ?> onclick="openLink(event, 'reCaptcha-set')">ReCaptcha Settings</button>

    <button class="wla-list-itm w3-bar-item w3-button tablink" <?php
            if (isset($_GET['setingid']) && !empty($_GET['setingid'])) {
                if ($_GET['setingid'] === 'lockout-log') {
                    ?>
                    style="color:#fff!important;background-color:#f44336!important"  
    <?php
    }
}
?> onclick="openLink(event, 'lockout-log')">Lockout log</button>

    <button class="wla-list-itm w3-bar-item w3-button tablink" <?php
            if (isset($_GET['setingid']) && !empty($_GET['setingid'])) {
                if ($_GET['setingid'] === 'statistics-set') {
                    ?>
                    style="color:#fff!important;background-color:#f44336!important"
        <?php
        }
    }
    ?> onclick="openLink(event, 'statistics-set')"><?php echo __('Statistics', 'wp-login-attempts'); ?></button>
    <button class="wla-list-itm w3-bar-item w3-button tablink" <?php
            if (isset($_GET['setingid']) && !empty($_GET['setingid'])) {
                if ($_GET['setingid'] === 'logoset') {
            ?>
                    style="color:#fff!important;background-color:#f44336!important" 
                <?php
                }
            }
            ?> onclick="openLink(event, 'logoset')"><?php echo __('Custom Logo', 'wp-login-attempts'); ?></button>
    <button class="wla-list-itm w3-bar-item w3-button tablink" <?php
            if (isset($_GET['setingid']) && !empty($_GET['setingid'])) {
                if ($_GET['setingid'] === 'hidelogin') {
                    ?>
                    style="color:#fff!important;background-color:#f44336!important" 
                <?php
                }
            }
            ?> onclick="openLink(event, 'hidelogin')">Hide login</button>
</div>

<div class="wla-content-sec"> 
    <div id="attempt-set" class="w3-container city w3-animate-zoom">
        <h3><?php echo __('Options', 'wp-login-attempts'); ?></h3>
        <form action="options-general.php?page=wp-login-attempts&setingid=attempt-set" method="post" id="loginlockout">
<?php wp_nonce_field('wp-login-attempts-options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row" valign="top"><?php echo __('Lockout', 'wp-login-attempts'); ?></th>
                    <td>
                        <input class="left" id="allowed_retries" type="number" size="3" min="1" max="40" value="<?php echo(wp_login_att_option('allowed_retries')); ?>" name="allowed_retries" /> <?php echo __('Max Login Retries', 'wp-login-attempts'); ?> <br />
                        <input type="number" size="3" min="1" max="10080" value="<?php echo(wp_login_att_option('lockout_duration') / 60); ?>" name="lockout_duration" /> <?php echo __('Retry Time Period (Minutes)', 'wp-login-attempts'); ?> <br />
                        <input type="number" min="1" max="100" size="3" value="<?php echo(wp_login_att_option('allowed_lockouts')); ?>" name="allowed_lockouts" /> <?php echo __('Lockouts Increase Time to', 'wp-login-attempts'); ?> <input type="number" min="1" max="8760" size="3" maxlength="4" value="<?php echo(wp_login_att_option('long_duration') / 3600); ?>" name="long_duration" /> <?php echo __('Hours', 'wp-login-attempts'); ?> <br />
                        <div class="resetlogut" style="display: none;"><input type="number" size="3" maxlength="4" value="<?php echo(wp_login_att_option('valid_duration') / 3600); ?>" name="valid_duration" /> <?php echo __('Hours Until Retries Are Reset', 'wp-login-attempts'); ?></div>
                    </td>
                </tr>
                <tr style="display:none;">
                    <th scope="row" valign="top"><?php echo __('Site connection', 'wp-login-attempts'); ?></th>
                    <td>
                        <?php echo $client_type_message; ?>
                        <label>
                            <input type="radio" name="client_type" 
<?php echo $client_type_direct; ?> value="<?php echo WP_LOGIN_ATT_DIR_ADD; ?>" /> 
<?php echo __('Direct connection', 'wp-login-attempts'); ?> 
                        </label>
                        <label>
                            <input type="radio" name="client_type" 
<?php echo $client_type_proxy; ?> value="<?php echo WP_LOGIN_ATT_PROXY_ADD; ?>" /> 
<?php echo __('From behind a reversy proxy', 'wp-login-attempts'); ?>
                        </label>
<?php echo $client_type_warning; ?>
                    </td>
                </tr>
                <tr style="display:none;">
                    <th scope="row" valign="top"><?php echo __('Handle cookie login', 'wp-login-attempts'); ?></th>
                    <td>
                        <label><input type="radio" name="cookies" <?php echo $cookies_yes; ?> value="1" /> <?php echo __('Yes', 'wp-login-attempts'); ?></label> <label><input type="radio" name="cookies" <?php echo $cookies_no; ?> value="0" /> <?php echo __('No', 'wp-login-attempts'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row" valign="top"><?php echo __('Notify on lockout', 'wp-login-attempts'); ?></th>
                    <td>
                        <input type="checkbox" name="lockout_notify_log" <?php echo $log_checked; ?> value="log" /> <?php echo __('Log IP', 'wp-login-attempts'); ?><br />
                        <input type="checkbox" name="lockout_notify_email" <?php echo $email_checked; ?> value="email" /> <?php echo __('Email to admin after', 'wp-login-attempts'); ?> <input type="number" min="1" max ="100" size="3" value="<?php echo(wp_login_att_option('notify_email_after')); ?>" name="email_after" /> <?php echo __('lockouts', 'wp-login-attempts'); ?>
                    </td>
                </tr>
                <tr class="wla-hide-login-attmpt">
                <th scope="row" valign="top"><?php echo __('Disable Login Attempt Feature', 'wp-login-attempts'); ?></th>
                    <td>                    
                        <input class="disable_login_attmpt" id="disable_login_attmpt" type="checkbox" name="disable_login_attmpt" value="true" <?php if (get_option('wla_disable_login_attmpt') == 'true') { echo 'checked'; } ?>>
                    </td>
                </tr>         
            </table>
            <p class="submit">
                <input name="update_options" value="<?php echo __('Save Options', 'wp-login-attempts'); ?>" class="button-primary" type="submit" />
            </p>
        </form>
    </div>

    <div id="reCaptcha-set" class="w3-container city w3-animate-zoom" style="display:none">
        <form action="options-general.php?page=wp-login-attempts&setingid=reCaptcha-set" onload="getUrlParameter('reCaptcha-set')" method="post">
                               <?php wp_nonce_field('wp-login-attempts-options'); ?>
            <h3 class="bws_tab_label"><?php _e('Google Captcha Settings', 'wp-login-attempts'); ?></h3>
            <hr>

            <div class="wla_lim_tab_sub_label"><h4><?php _e('General', 'wp-login-attempts'); ?></h4></div>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Enable Captcha', 'wp-login-attempts'); ?></th>
                    <td><input type="checkbox" name="g_reCaptcha_enable" value="true" <?php
                                       if (get_option('g_reCaptcha_enable') == 'true') {
                                           echo 'checked';
                                       }
                                       ?>></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('reCAPTCHA Version', 'wp-login-attempts'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input class="g-re-captcha-ver" type="radio" name="gglcptch_recaptcha_version" value="v2" <?php
                                       if (get_option('g_reCaptcha_version') == 'v2') {
                                           echo 'checked';
                                       }
                                       ?>> <?php _e('Version 2', 'wp-login-attempts'); ?>
                            </label>
                            </br>
                            <label>
                                <input class="g-re-captcha-ver" type="radio" name="gglcptch_recaptcha_version" value="v3" <?php
                                       if (get_option('g_reCaptcha_version') == 'v3') {
                                           echo 'checked';
                                       }
                                       ?>> <?php _e('Version 3', 'wp-login-attempts'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <div class="wla_lim_tab_sub_label"><h4><?php _e('Authentication', 'wp-login-attempts'); ?></h4></div>
            <div class="bws_info"><?php _e('Register your website with Google to get required API keys and enter them below.', 'wp-login-attempts'); ?>
                <a target="_blank" href="https://www.google.com/recaptcha/admin#list"><?php _e('Get the API Keys', 'wp-login-attempts'); ?>
                </a>
            </div>
            <table class="form-table" id="re-capt-v2" <?php if (get_option('g_reCaptcha_version') == 'v2') { ?> style="display:block;" <?php } else { ?> style="display:none;" <?php } ?>>
                <tr>
                    <th><?php _e('Site Key', 'wp-login-attempts') ?></th>
                    <td>
                        <input class="regular-text" type="text" name="g_reCaptcha_site_key" value="<?php echo esc_html(str_replace(" ", "", trim(get_option('g_reCaptcha_site_key')))); ?>" maxlength="200" />

                        <label class="gglcptch_error_msg error"></label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Secret Key', 'wp-login-attempts'); ?></th>
                    <td>
                        <input class="regular-text" type="text" name="g_reCaptcha_secret_key" value="<?php echo esc_html(str_replace(" ", "", trim(get_option('g_reCaptcha_secret_key')))); ?>" maxlength="200" />
                        <label class="gglcptch_error_msg error"></label>
                    </td>
                </tr>
            </table>
            <table class="form-table" id="re-capt-v3" <?php if (get_option('g_reCaptcha_version') == 'v3') { ?> style="display:block;" <?php } else { ?> style="display:none;" <?php } ?>>
                <tr>
                    <th><?php _e('Site Key', 'wp-login-attempts') ?></th>
                    <td>
                        <input class="regular-text" type="text" name="g_reCaptcha_site_key_v3" value="<?php echo esc_html(str_replace(" ", "", trim(get_option('g_reCaptcha_site_key_v3')))); ?>" />
                        <label class="gglcptch_error_msg error"></label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Secret Key', 'wp-login-attempts'); ?></th>
                    <td>
                        <input class="regular-text" type="text" name="g_reCaptcha_secret_key_v3" value="<?php echo esc_html(str_replace(" ", "", trim(get_option('g_reCaptcha_secret_key_v3')))); ?>" />
                        <label class="gglcptch_error_msg error"></label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input name="update_recpat" value="<?php echo __('Save Options', 'wp-login-attempts'); ?>" class="button-primary" type="submit" />
            </p>
        </form>
    </div>

    <div id="lockout-log" class="w3-container city w3-animate-zoom" style="display:none">
<?php
$log = get_option('wla_lim_login_handle_log_key');

if (is_array($log) && count($log) > 0) {
    ?>
            <h3><?php echo __('Lockout log', 'wp-login-attempts'); ?></h3>
            <form action="options-general.php?page=wp-login-attempts&setingid=lockout-log" method="post">
    <?php wp_nonce_field('wp-login-attempts-options'); ?>
                <input type="hidden" value="true" name="clear_log" />
                <p class="submit">
                    <input name="submit" value="<?php echo __('Clear Log', 'wp-login-attempts'); ?>" type="submit" />
                </p>
            </form>
            <style type="text/css" media="screen">
                .limit-login-log th {
                    font-weight: bold;
                }
                .limit-login-log td, .limit-login-log th {
                    padding: 1px 5px 1px 5px;
                }
                td.limit-login-ip {
                    font-family:  "Courier New", Courier, monospace;
                    vertical-align: top;
                }
                td.limit-login-max {
                    width: 100%;
                }
            </style>
            <div class="limit-login-log">
                <table class="form-table">
    <?php wla_show_log_in_admin($log); ?>
                </table>
            </div>
                <?php
            } else {
                ?>
            <table class="form-table">
                <tr>
                    <th scope="row" valign="top">
                        <h3><?php echo __('Lockout log', 'wp-login-attempts'); ?></h3>
                    </th>
                    <td><?php echo __('No lockouts log yet', 'wp-login-attempts'); ?></td>
                </tr>
            </table>
                        <?php }
                        ?>
    </div>

    <div id="statistics-set" class="w3-container city w3-animate-zoom" style="display:none">
        <form action="options-general.php?page=wp-login-attempts&setingid=statistics-set" method="post">
<?php wp_nonce_field('wp-login-attempts-options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row" valign="top"><?php echo __('Total lockouts', 'wp-login-attempts'); ?></th>
                    <td>
                <?php if ($lockouts_total > 0) { ?>
                            <input name="reset_total" value="<?php echo __('Reset Counter', 'wp-login-attempts'); ?>" type="submit" />
    <?php echo sprintf(_n('%d lockout since last reset', '%d lockouts since last reset', $lockouts_total, 'wp-login-attempts'), $lockouts_total); ?>
<?php
} else {
    echo __('No lockouts yet', 'wp-login-attempts');
}
?>
                    </td>
                </tr>
<?php if ($lockouts_now > 0) { ?>
                    <tr>
                        <th scope="row" valign="top"><?php echo __('Active lockouts', 'wp-login-attempts'); ?></th>
                        <td>
                            <input name="reset_current" value="<?php echo __('Restore Lockouts', 'wp-login-attempts'); ?>" type="submit" />
    <?php echo sprintf(__('%d IP is currently blocked from trying to log in', 'wp-login-attempts'), $lockouts_now); ?> 
                        </td>
                    </tr>
<?php } ?>
            </table>
        </form>
    </div>
    <!-- login page logo setting-->

    <div id="logoset" class="w3-container city w3-animate-zoom" style="display:none">
        <h2><?php _e('Customize Login Options', 'wp-login-attempts'); ?></h2>
        <form method="post" action="options-general.php?page=wp-login-attempts&setingid=logoset">                   
<?php wp_nonce_field('wp-login-attempts-options'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Logo Link', 'wp-login-attempts'); ?></th>
                    <td>
                        <label for="wla_lim_logo_url">
                            <input type="text" id="ca_logo_url" name="wla_lim_logo_url" value="<?php echo esc_url(get_option('wla_lim_logo_url')); ?>" />
                            <p class="description"><?php _e('If not specified, clicking on the logo will return you to the homepage.', 'wp-login-attempts'); ?></p>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Logo', 'wp-login-attempts'); ?></th>
                    <td>
                        <label for="upload_image">
                            <input id="upload_image" type="text" size="36" name="wla_lim_logo_file" value="<?php echo esc_url(get_option('wla_lim_logo_file')); ?>" />
                            <input id="upload_image_button" type="button" value="<?php _e('Choose Image', 'wp-login-attempts'); ?>" class="button" />
                            <p class="description"><?php _e('Enter a URL or upload logo image. Maximum height: 70px, width: 310px.', 'wp-login-attempts'); ?></p>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Background Image', 'wp-login-attempts'); ?></th>
                    <td>
                        <label for="wla_lim_login_background_img">
                            <input id="wla_lim_login_background_img" type="text" size="36" name="wla_lim_login_background_img" value="<?php echo esc_url(get_option('wla_lim_login_background_img')); ?>" />
                            <input id="upload_background_img_bttn" type="button" value="<?php _e('Choose Image', 'wp-login-attempts'); ?>" class="button" />
                            <p class="description"><?php _e('Enter a URL or upload background image.', 'wp-login-attempts'); ?></p>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Background Color', 'wp-login-attempts'); ?></th>
                    <td>
                        <label for="wla_lim_login_background_color">
                            <input type="text" id="wla_lim_login_background_color" class="color-picker" name="wla_lim_login_background_color" value="<?php echo sanitize_hex_color(get_option('wla_lim_login_background_color')); ?>" />
                            <p class="description"><?php _e('Either choose background image or color.', 'wp-login-attempts'); ?></p>
                        </label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e('Hide "Lost your password?" link', 'wp-login-attempts'); ?></th>
                    <td>
                        <label class="switch" for="wla_lim_lost_ur_pwd">
                            <input type="checkbox" id="wla_lim_lost_ur_pwd" name="wla_lim_lost_ur_pwd" value="true" <?php
if (!empty(get_option('wla_lim_lost_ur_pwd'))) {
    echo "checked";
};
?> />
                            <span class="slider"></span>                                    
                        </label>
                        <p class="description"><?php _e('Hide Lost your password? link from login page.', 'wp-login-attempts'); ?></p>
                    </td>
                </tr>                
            </table>
            <p class="submit">
                <input name="logo_setng" type="submit" class="button-primary" value="<?php _e('Save Options', 'wp-login-attempts'); ?>" />
            </p>                    
        </form>
    </div>



    <div id="hidelogin" class="w3-container city w3-animate-zoom" style="display:none">
        <!--hide login url slug-->
        <div class="cards" id="card-description">
            <h3><?php _e('Please read carefully before proceeding', 'wp-login-attempts'); ?></h3>
            <p><?php _e('There is nothing wrong with changing the login or redirection URL. However,', 'wp-login-attempts'); ?> 
                <b><?php _e('in case you forget to remember it while login, there is a possibility you will need FTP access to log in your site to a working state', 'wp-login-attempts'); ?></b>
            </p>
            <b><?php _e('How to login to the site in case of forgetting login URL ', 'wp-login-attempts'); ?></b>
            <p><?php _e('Do not panic. No data is lost, and you will be log in the site again in minutes. 
                FTP to your site or open the server\'s control panel such as cPanel to locate the <b>wp-login-attempts</b> plugin folder in the <code>/wp-content/plugins/</code> directory. Once you find the plugin than rename it and log in the site with Wordpress default login URL. Now, again you have to rename the plugin to back to original, activate it and reset the login URL or remember it to further login.', 'wp-login-attempts'); ?>  
            </p>
        </div>
        <form method="post" action="options-general.php?page=wp-login-attempts&setingid=hidelogin">                   
<?php wp_nonce_field('wp-login-attempts-options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Login URL', 'wp-login-attempts'); ?></th>
                    <td>
                        <label for="wla_lim_hide_login_url">
                            <?php
                            if (get_option('permalink_structure')) {

                                echo '<code>' . trailingslashit(home_url()) . '</code> <input id="wla_lim_hide_login_page" type="text" name="wla_lim_hide_login_page" value="' . get_option('wla_lim_hide_login_page') . '">' . ( get_option('wla_lim_hide_login_page') ? ' <code>/</code>' : '' );
                            } else {

                                echo '<code>' . trailingslashit(home_url()) . '?</code> <input id="wla_lim_hide_login_page" type="text" name="wla_lim_hide_login_page" value="' . get_option('wla_lim_hide_login_page') . '">';
                            }
                            ?>                                   
                        </label>
                        <p class="description"><?php _e('Protect your website by changing the login URL and preventing access to the wp-login.php page and the wp-admin directory to non-connected people.', 'wp-login-attempts'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Redirection URL', 'wp-login-attempts'); ?></th>
                    <td>
                        <label for="wla_lim_redirect_login_wpadmin">
<?php
if (get_option('permalink_structure')) {

    echo '<code>' . trailingslashit(home_url()) . '</code> <input id="wla_lim_redirect_login_wpadmin" type="text" name="wla_lim_redirect_login_wpadmin" value="' . get_option('wla_lim_redirect_login_wpadmin') . '">' . ( get_option('wla_lim_redirect_login_wpadmin') ? ' <code>/</code>' : '' );
} else {

    echo '<code>' . trailingslashit(home_url()) . '?</code> <input id="wla_lim_redirect_login_wpadmin" type="text" name="wla_lim_redirect_login_wpadmin" value="' . get_option('wla_lim_redirect_login_wpadmin') . '">';
}
?>                                   
                        </label>
                        <p class="description"><?php _e('Redirect URL when someone tries to access the wp-admin directory. This works when the login URL set.', 'wp-login-attempts'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input name="hidelogin" type="submit" class="button-primary" value="<?php _e('Save Options', 'wp-login-attempts'); ?>" />
            </p> 
        </form>
    </div>
</div>
