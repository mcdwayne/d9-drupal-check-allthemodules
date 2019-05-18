/**
 * @file
 * Attaches the behaviors for the perfect scrollbar module.
 */
(function ($, Drupal) {
  Drupal.behaviors.perfectScrollbar = {
    attach: function(context, settings) {
    var $attribute;
    scroll_attributes = settings.perfect_scrollbar_setting.perfect_scrollbar;
    if((typeof settings.perfect_scrollbar_setting.perfect_scrollbar_classes !=
    'undefined') || (typeof settings.perfect_scrollbar_setting.perfect_scrollbar_ids !=
    'undefined') || (typeof settings.perfect_scrollbar_setting.perfect_scrollbar !=
    'undefined') && settings.perfect_scrollbar_status == 1) {
      $.each(scroll_attributes, function( index, value ) {
        $.each(value, function( index1, value1 ) {
          if(index1 == 'class' || index1 == 'id' ) {
            (index1 == 'class') ? $attribute = '.'+value1 : $attribute = '#'+value1;
             $($attribute).css('position','relative');
             $($attribute).perfectScrollbar();
           }
           else{
            $($attribute).css(index1,value1);
          }
        });
      });
    }

  }
};
})(jQuery, Drupal);

