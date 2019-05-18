/**
 * @file
 * Attaches javascript datetimepicker for Droogle Hangout.
 */
(function($) {
  Drupal.behaviors.droogle_hangout_datetimepicker = {
    attach: function (context, settings) {
      $('#edit-droogle-hangout-calendar').datetimepicker({
          timeFormat: 'h:mm TT',
          showOn: 'both',
          buttonImageOnly: true,
          buttonImage: drupalSettings.droogle_hangout.droogleHangout.calendar_img_path,
          addSliderAccess: true,
	        sliderAccessArgs: { touchonly: false },
          minDateTime: new Date(),
        });
    }
  };
}(jQuery));
