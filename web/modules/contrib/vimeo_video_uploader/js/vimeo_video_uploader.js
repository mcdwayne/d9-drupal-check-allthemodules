(function ($, Drupal, debounce) {
    Drupal.behaviors.deleteVideoLink = {
        attach() {
            $('#edit-field-embeddedvideo-wrapper').hide();
            $('#delete_link').css('cursor','pointer');
            $("#delete_link").on('click',function(){
                $( "input[id^='edit-field-embeddedvideo']").val('');
                $('.video-thumbnail-box').remove();
            });
        },
    };
}(jQuery, Drupal, Drupal.debounce));
