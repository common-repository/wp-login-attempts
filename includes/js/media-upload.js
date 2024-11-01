// Media upload
jQuery(document).ready(function($) {
	var custom_uploader, backgrnd_uploder;

	$( '#upload_image_button, #upload_background_img_bttn' ).click(function(e) {
            //console.log(this); 
            e.preventDefault();
            if(this.id =='upload_image_button'){
                // If the uploader object has already been created, reopen the dialog
		if (custom_uploader) {
			custom_uploader.open();
			return;
		}

		// Extend the wp.media object
		custom_uploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: false
		});

		// When a file is selected, grab the URL and set it as the text field's value
		custom_uploader.on( 'select', function() {
			attachment = custom_uploader.state().get( 'selection' ).first().toJSON();
			$( '#upload_image' ).val(attachment.url);
		});

		// Open the uploader dialog
		custom_uploader.open();
            }
            if(this.id =='upload_background_img_bttn'){
                // If the uploader object has already been created, reopen the dialog
		if (backgrnd_uploder) {
			backgrnd_uploder.open();
			return;
		}

		// Extend the wp.media object
		backgrnd_uploder = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: false
		});

		// When a file is selected, grab the URL and set it as the text field's value
		backgrnd_uploder.on( 'select', function() {
			attachment = backgrnd_uploder.state().get( 'selection' ).first().toJSON();
			 $( '#wla_lim_login_background_img' ).val(attachment.url);
		});

		// Open the uploader dialog
		backgrnd_uploder.open();
               
            }

	});

});