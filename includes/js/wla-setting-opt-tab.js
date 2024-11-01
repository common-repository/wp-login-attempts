/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function openLink(evt, animName) {
  var i, x, tablinks;
  x = document.getElementsByClassName("city");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablink");
  for (i = 0; i < x.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" w3-red", "");
  }  
  document.getElementById(animName).style.display = "block";
  
  evt.currentTarget.className += " w3-red";
}

window.addEventListener('DOMContentLoaded', function(e) {
    
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    var sParameterName;
    var i;
    var event;
    var evnttrig;
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i];
        
        if ((sParameterName === 'setingid=reCaptcha-set')||(sParameterName === 'setingid=logoset') || (sParameterName === 'setingid=hidelogin') || (sParameterName === 'setingid=lockout-log') || (sParameterName === 'setingid=statistics-set')) {
            sParameterName = sParameterName.split('=');
            return openLink(e,sParameterName[1]);
        }
    }
}, true);

jQuery(document).ready(function(){
    jQuery('.tablink').on('click',function(){
        tablinks = document.getElementsByClassName("tablink");
        for (t = 0; t < tablinks.length; t++) {
            tablinks[t].removeAttribute("style");
        }
    }); 
    jQuery('button.notice-dismiss').on('click', function(){
        jQuery(this).parents('.wla-set-update-notice').hide();
    });   
});

jQuery(document).ready(function($){
    $(function() {
            //----- OPEN
            $('[data-popup-open]').on('click', function(e) {
                    //alert("hello");
                    var targeted_popup_class = jQuery(this).attr('data-popup-open');
                    $('[data-popup="' + targeted_popup_class + '"]').fadeIn(350);

                    e.preventDefault();
            });

            //----- CLOSE
            $('[data-popup-close]').on('click', function(e) {
                    var targeted_popup_class = jQuery(this).attr('data-popup-close');
                    $('[data-popup="' + targeted_popup_class + '"]').fadeOut(350);

                    e.preventDefault();
            });
    });
    
    // on change recaptcha version
    $(document).on('change','.g-re-captcha-ver',function(){
        var curval = this.value;
        if(curval === 'v3'){
            $('#re-capt-v3').show(400);
            $('#re-capt-v2').hide();
        }
        else{
            $('#re-capt-v2').show(400);
            $('#re-capt-v3').hide();
        }
    });
    
});

