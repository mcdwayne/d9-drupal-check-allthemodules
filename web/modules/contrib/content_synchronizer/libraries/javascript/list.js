(function (drupalSettings, $) {
    "use strict";
    Drupal.behaviors.content_synchronizer_list = {
        attach: function (context) {
            $('[name="check_all"]').change(function(){
                if( $('.entity_selector').not(':checked').length == 0 ){
                    $('.entity_selector,[name="check_all"]').prop('checked', false);
                }
                else{
                    $('.entity_selector,[name="check_all"]').prop('checked', true);
                }
            } );
        }
    };
})(drupalSettings, jQuery);