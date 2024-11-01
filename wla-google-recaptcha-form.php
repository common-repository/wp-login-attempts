<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action( 'login_form', 'wla_add_login_fields' );

function wla_add_login_fields() { ?>    
	<script>
	function onSubmit(token) {
	document.getElementById('loginform').submit();
	}  
	</script>    
    

    <?php
        
	if(null !==get_option('g_reCaptcha_version') && (!empty(get_option('g_reCaptcha_version')))){
		if(get_option('g_reCaptcha_version')=='v3' && (!empty(get_option('g_reCaptcha_site_key')))){
			?>
            <script src="https://www.google.com/recaptcha/api.js?render=<?php echo str_replace("", "", trim(get_option('g_reCaptcha_site_key_v3'))); ?>"></script>
            <script>
            grecaptcha.ready(function () {
            grecaptcha.execute('<?php echo str_replace("", "", trim(get_option('g_reCaptcha_site_key_v3'))); ?>', { action: 'validate_captcha' }).then(function (token) {
            var recaptchaResponse = document.getElementById('g-recaptcha-response');
            recaptchaResponse.value = token;
            });
            });
            </script>
			<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
            <input type="hidden" name="action" value="validate_captcha">			
		<?php
		}
		if(get_option('g_reCaptcha_version')=='v2' && (!empty(get_option('g_reCaptcha_site_key')))){
			?>
			<p id="llawp-recaptcha-id" class="lla-grecapt lla-err"><button class='g-recaptcha' data-sitekey='<?php echo str_replace(" ", "", trim(get_option('g_reCaptcha_site_key'))); ?>' data-size='normal' style='display:none;'>Submit</button></p>
                        <p><span class="msg-error error"></span></p></br>
                        <style type="text/css" media="screen">
				.login-action-login #loginform {
					width: 302px !important;
				}
				#login_error,
				.message {
					width: 322px !important;
				}
                                msg-error {
                                    color: #c65848;
                                }
                                .lla-err.error {
                                    border: solid 3px #dc3232;
                                    width: 100%;
                                }
                                span.msg-error.error {
                                    color: #dc3232;
                                    font-size: 15px;
                                }
			</style>
			<?php
		}
	}
}

add_filter('wp_authenticate_user', 'wla_verify_recaptcha_on_login_page', 10, 2);

function wla_verify_recaptcha_on_login_page($user, $password) {
	if (isset($_POST['g-recaptcha-response'])) {
		if(null !==get_option('g_reCaptcha_version') && (get_option('g_reCaptcha_version')=='v2') && (!empty(get_option('g_reCaptcha_secret_key')))){
                    $secrt_key = str_replace(" ", "", trim(get_option('g_reCaptcha_secret_key')));

                    $getresponse = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret='.$secrt_key.'&response='. esc_html($_POST['g-recaptcha-response'] ));                    
                    
		}
                if(null !==get_option('g_reCaptcha_version') && (get_option('g_reCaptcha_version')=='v3') && (!empty(get_option('g_reCaptcha_secret_key_v3')))){
                    $secrt_key = str_replace(" ", "", trim(get_option('g_reCaptcha_secret_key_v3')));
                    
                    $getresponse = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret='.$secrt_key.'&response='. esc_html($_POST['g-recaptcha-response'] ));
    
		}

                if ( is_array( $getresponse ) && ! is_wp_error( $getresponse ) ) {
                    $headers = $getresponse['headers']; // array of http header lines
                    $body    = $getresponse['body']; // use the content
                    $datas = json_decode( $body );     
                    foreach ($datas as $key => $value) {
                        if($key == 'error-codes' && is_array($value)){
                            if(in_array("missing-input-response", $value)){
                                        //echo "Missing Captcha";
                                add_filter( 'login_errors', function(){
                                    $err = __('<p><strong>ERROR</strong>: You are a bot. If not then enable Captcha.</p><br/>','wp-login-attempts');
                                    $err .= "<p>\n" . wla_login_attempt_get_errs_mesgs() . "<br/>\n</p>";
                                    return $err;
                                } );
                            }
                            if(in_array("invalid-input-secret", $value)){
                                        //echo "Invalid secret key";
                                add_filter( 'login_errors', function(){
                                    $err = __('<div id="login_error"><strong>ERROR</strong>: Invalid secret key. Please go to the plugin <a href="'.admin_url("options-general.php?page=wp-login-attempts").'"> setting page </a> and insert valid secret key according to Captcha version.</div></br>','wp-login-attempts');
                                    return $err;
                                } );
                            }

                        }
                        else if($key == 'success' && $value ==true) {
                            return $user;
                        }
                        else{
                            add_filter( 'login_errors', function(){
                                $err = __('<p><strong>ERROR</strong>: You are a bot. If not then enable Captcha.</p><br/>','wp-login-attempts');
                                $err .= "<p>\n" . wla_login_attempt_get_errs_mesgs() . "<br/>\n</p>";
                                return $err;
                            } );
                        }
                    }
                }                
	} 
	else {
		return new WP_Error( 'Captcha Invalid', __('<strong>ERROR</strong>: You are a bot. If not then enable JavaScript.') );
	}
}