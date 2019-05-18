(function ($) {
  Drupal.behaviors.powertagging_slider = {
    attach: function () {

      $.each(drupalSettings.powertagging_slider, function(id, settings) {
        var input = $("#" + id + "-value");
        var slider = $("#" + id + "-slider");
        var info = $("#" + id + "-info");
        info.text(input.val());
        slider.slider({
          animate: settings.animate,
          min: settings.min,
          max: settings.max,
          orientation: settings.orientation,
          step: settings.step,
          range: settings.range,
          value: input.val(),
          slide: function( event, ui ) {
            input.val( ui.value );
            info.text( ui.value );
          }
        });
      });

    }
  };
})(jQuery);

