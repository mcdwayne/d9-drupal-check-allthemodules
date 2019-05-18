(function($) {
  Drupal.behaviors.DatePopupTimepicker = {
    attach: function(context, settings) {
      for (var name in settings.datePopup) {
      // @todo Do we need to use .once() here? date_popup doesn't use it for some reason.
        $("input[name='" + name + "']", context).once('date-popup-timepicker')
            .timepicker(settings.datePopup[name].settings);
      }
    }
  };
})(jQuery);
