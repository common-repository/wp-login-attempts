/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

jQuery(document).ready(function($){     
    $( '#loginform #wp-submit' ).click(function(){
        
        var $captcha = $( '#llawp-recaptcha-id' );
//        console.log($captcha);
//        alert($captcha);
//        document.getElementById('loginform').submit();
        
        var response = grecaptcha.getResponse();
        
        if (response.length === 0) {
          $('.msg-error').text('reCAPTCHA is mandatory');
          if( !$captcha.hasClass( 'error' ) ){
            $captcha.addClass( 'error' );
          }
          return false;
        } 
        else {
          $( '.msg-error' ).text('');
          $captcha.removeClass( 'error' );
        }
    });    
});
