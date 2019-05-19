(function ($, drupalSettings) {
  // Get all taggd images.
  var taggd_images = $('.taggd-image');
  var taggd_settings = drupalSettings.taggd_formatter || [];
  // Loop over images and apply Taggd.
  taggd_images.each(function (index, image) {
    // This is jQuery object.
    var $image = $(image);
    // This is pure JS object.
    var t_image = $image.get(0);
    var taggd_id = $image.attr('data-taggd');
    // Check if settings are set for current image.
    if (taggd_id in taggd_settings) {
      // Check if parent container is bigger than image width.
      if ($image.parent().width() > $(image).attr('width')) {
        // If so make set parent dimensions equal to image dimension.
        // Because taggd library sets width of image to 100%.
        $image.parent().width($image.attr('width')).height($image.attr('height'));
      }

      var settings = taggd_settings[taggd_id];
      var data = [];

      // Add tags to image.
      $.each(settings, function (key, setting) {
        data.push(Taggd.Tag.createFromObject(setting));
      });
      var taggd = new Taggd(t_image, taggd_settings.options, data);
    }
  });
})(jQuery, drupalSettings);
