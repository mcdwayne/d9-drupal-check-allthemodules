/**
 * Behaviour.
 */
Drupal.behaviors.customCheckbox = {
  attach: function (context, settings) {
    if (jQuery('body', context).length || jQuery(".views-exposed-form", context).length) {
      jQuery('input[type="checkbox"]').each(function() {
        if ( jQuery(this).parent().find('span.checkmark').length == 0) {
          jQuery('<span class="checkmark"></span>').insertAfter(jQuery(this));
        }
      });
    }
  }
};
