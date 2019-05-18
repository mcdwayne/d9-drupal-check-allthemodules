/**
 * @file
 * JS implementation for editing imagepin widgets.
 */

(function ($, Drupal, window) {

  var $window;

  Drupal.imagepin = Drupal.imagepin || {$window: $(window), widgets: {}};

  $window = Drupal.imagepin.$window;

  Drupal.imagepin.attachPin = function (pin) {
    var key = pin.attr('data-imagepin-key');

    // Clone the selected pin (if not yet done)
    // and append it to the image for being dragged around.
    var existing = $(".imagepin-draggable[data-imagepin-key='" + key + "']").length;
    if (!existing) {
      var view_mode = pin.attr('data-view-mode');
      var image_selector = "img[data-view-mode='" + view_mode + "']";
      var clone = pin.clone(true, false);
      clone.addClass('imagepin-draggable');

      clone.draggable({
        containment: image_selector,
        stop: function (event, ui) {
          var key = $(this).attr('data-imagepin-key');
          var view_mode = $(this).attr('data-view-mode');
          var settings = $(".imagepin-positions-input[data-view-mode='" + view_mode + "']").attr('value');

          settings = JSON.parse(settings);
          settings[key] = {
            top: parseInt($(this).css('top')),
            left: parseInt($(this).css('left')),
            image_width: $(image_selector).prop('naturalWidth'),
            image_height: $(image_selector).prop('naturalHeight')
          };
          settings = JSON.stringify(settings);

          $(".imagepin-positions-input[data-view-mode='" + view_mode + "']").attr('value', settings);
        }
      });
      $(image_selector).before(clone);
      // Ensure the parent is a block,
      // and its positioning isn't influenced by its parents.
      clone.parent().css('display', 'block');
      clone.parent().css('position', 'relative');

      Drupal.imagepin.setPosition(clone, $(image_selector));
    }
  };

  Drupal.imagepin.setPosition = function (pin, image) {

    // Set the default positioning, if available.
    var position = pin.attr('data-position-default');
    if (position) {
      position = JSON.parse(position);

      // Try to set the position relatively to the given image size.
      var image_natural_width = image.prop('naturalWidth');
      var image_client_width = image.width();
      if ((image_client_width > 0) && (image_natural_width !== 'undefined')) {
        var image_natural_height = image.prop('naturalHeight');
        var image_client_height = image.height();
        var ratio_image_width = image_natural_width / position.image_width;
        var ratio_image_height = image_natural_height / position.image_height;
        var ratio_client_width = image_client_width / image_natural_width;
        var ratio_client_height = image_client_height / image_natural_height;
        pin.css('top', (position.top * ratio_client_height * ratio_image_height).toString() + 'px');
        pin.css('left', (position.left * ratio_client_width * ratio_image_width).toString() + 'px');
      }
      else {
        pin.css('top', (position.top).toString() + 'px');
        pin.css('left', (position.left).toString() + 'px');
      }
    }
  };

  Drupal.imagepin.adjustPositions = function () {
    $('img[data-view-mode]').each(function () {
      var view_mode = $(this).attr('data-view-mode');
      var image_selector = "img[data-view-mode='" + view_mode + "']";
      $(".imagepin-draggable[data-view-mode='" + view_mode + "']").each(function () {
        Drupal.imagepin.setPosition($(this), $(image_selector));
      });
    });
  };

  $window.resize(function () {
    Drupal.imagepin.adjustPositions();
  });

  $window.on('load', function () {
    Drupal.imagepin.adjustPositions();
  });

  /**
   * Initialize / Uninitialize editable imagepin widgets.
   */
  Drupal.behaviors.imagepinEditable = {
    attach: function (context, settings) {

      // Add all pins which already have been attached before.
      $('.imagepin[data-position-default]', context).each(function () {
        Drupal.imagepin.attachPin($(this));
      });

      $('img[data-view-mode]', context).droppable({
        accept: function (element) {
          var view_mode = $(this).attr('data-view-mode');
          if (element.hasClass('imagepin-draggable') && element.attr('data-view-mode') === view_mode) {
            return true;
          }
        },
        drop: function (event, ui) {}
      });

      $('.imagepin').mousedown(function () {
        // Mark the selected pin.
        var key = $(this).attr('data-imagepin-key');
        var selector = ".imagepin[data-imagepin-key='" + key + "']";
        $('.imagepin').removeClass('imagepin-selected');
        $(selector).addClass('imagepin-selected');

        // Attach the pin when required.
        Drupal.imagepin.attachPin($(this));
      });

      // Remove pins without existing widgets.
      $('.imagepin-remove', context).mousedown(function () {
        var key = $(this).attr('data-imagepin-key');
        $(".imagepin[data-imagepin-key='" + key + "']").remove();
      });

    },
    detach: function (context, settings) {}
  };

}(jQuery, Drupal, window));
