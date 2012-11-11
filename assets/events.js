jQuery( document ).ready(function( $ ){
     /**
     * Load tabs
     */
    $(function(){
        $( ".tabs-handle" ).tabs();
    });

    $( ".tabs-handle" ).tabs();

    /**
     * Attach the tinymce to our textarea
     *
     * Only if the jQuery plugin is loaded and a user is logged in.
     */
    if ( jQuery().tinymce && _user.ID != 0 ) {
        $('#tinymce_textarea').tinymce({

            // Location of TinyMCE script
            script_url : _vendor_url + '/tinymce/tiny_mce.js',

            // General options
            theme: "advanced",

            // Buttons
            theme_advanced_buttons1 : "bold, italic, strikethrough, |, bullist, numlist,|, link, |,code",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align: "center",
            theme_advanced_layout_manager: "SimpleLayout",
            theme_advanced_resizing : true,
            theme_advanced_resize_horizontal : false,
            height: "300",
            template_replace_values : {
                username : _user.profile.user_login,
                staffid : _user.profile.ID
            }
        });
    }
});