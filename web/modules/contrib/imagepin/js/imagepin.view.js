/**
 * @file
 * JS implementation for viewing imagepin items.
 */

(function ($, Drupal, window) {

  var $window;

  Drupal.imagepin = Drupal.imagepin || {$window: $(window), widgets: {}};

  $window = Drupal.imagepin.$window;

  Drupal.imagepin.attachPin = function (pin) {
    var widgets = pin.data('imagepinWidgets');
    var attach_id = widgets.data('imagepinAttachTo');
    var image = widgets.data('imagepinImage');

    image.before(pin);
    // Ensure the parent is a block,
    // and its positioning isn't influenced by its parents.
    var parent = pin.parent();
    parent.css('display', 'block');
    parent.css('position', 'relative');

    Drupal.imagepin.setPosition(pin, image);
    pin.attr('data-imagepin-attached-to', attach_id);
    pin.data('imagepinAttachedTo', attach_id);
  };

  Drupal.imagepin.setPosition = function (pin, image) {

    // Set the default positioning, if available.
    var position = pin.data('positionDefault');
    if (typeof position === 'object') {
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
    var collected = Drupal.imagepin.widgets;
    for (var attach_id in collected) {
      if (collected.hasOwnProperty(attach_id)) {
        var widgets = collected[attach_id];
        var image = widgets.data('imagepinImage');
        var pins = widgets.data('imagepinPins');
        var pinsLength = pins.length;
        for (var i = 0; i < pinsLength; i++) {
          Drupal.imagepin.setPosition(pins[i], image);
        }
      }
    }
  };

  Drupal.imagepin.overlay = function (pin, widget) {
    var overlay = widget.clone();
    var top_position = parseInt(pin.css('top')) - (parseInt(pin.css('height')) / 2) - 10;
    var left_position = parseInt(pin.css('left')) - (parseInt(widget.css('width')) / 2);
    pin.before(overlay);
    overlay.attr('class', widget.data('imagepinOverlayClass'));
    overlay.css('position', 'absolute');
    overlay.css('top', (top_position).toString() + 'px');
    overlay.css('left', (left_position).toString() + 'px');
    overlay.css('z-index', '9');
    overlay.css('display', 'none');
    overlay.fadeIn('fast');
    overlay.mouseleave(function () {
      overlay.fadeOut('slow', function () {
        overlay.remove();
      });
    });
  };

  Drupal.imagepin.onDesktop = function (widgets) {
    var breakpoint = widgets.data('imagepinBreakpoint');
    if (typeof breakpoint !== 'number') {
      return false;
    }
    return !($window.width() < breakpoint);
  };

  $window.resize(function () {
    Drupal.imagepin.adjustPositions();
    var collected = Drupal.imagepin.widgets;
    for (var attach_id in collected) {
      if (collected.hasOwnProperty(attach_id)) {
        var widgets = collected[attach_id];
        if (Drupal.imagepin.onDesktop(widgets)) {
          widgets.css('display', 'none');
        }
        else {
          widgets.css('display', 'block');
        }
      }
    }
  });

  $window.on('load', function () {
    Drupal.imagepin.adjustPositions();
  });

  /**
   * Initialize / Uninitialize the view for imagepin items.
   */
  Drupal.behaviors.imagepinView = {
    attach: function (context, settings) {
      var collected = Drupal.imagepin.widgets;
      $('.imagepin-widgets', context).each(function () {
        var widgets = $(this);
        var attach_id = widgets.data('imagepinAttachTo');
        if (collected.hasOwnProperty(attach_id)) {
          // Already collected and initialized.
          return;
        }
        collected[attach_id] = widgets;

        var image = $("img[data-imagepin-attach-from='" + attach_id + "']", context);
        widgets.data('imagepinImage', image);
        image.data('imagepinWidgets', widgets);

        image.on('load', function () {
          widgets.css('max-width', image.width());
          Drupal.imagepin.adjustPositions();
        });

        // Prepare widgets container display.
        if (Drupal.imagepin.onDesktop(widgets)) {
          widgets.css('display', 'none');
        }
        else {
          widgets.css('max-width', image.width());
        }

        var pins = [];
        var widget_items = [];
        widgets.find('.imagepin').each(function () {
          var pin = $(this);
          var widget = pin.parent();
          pin.data('imagepinWidget', widget);
          pin.data('imagepinWidgets', widgets);
          widget.data('imagepinPin', pin);
          pins.push(pin);
          widget_items.push(widget);
        });
        widgets.data('imagepinPins', pins);
        widgets.data('imagepinWidgetItems', widget_items);

        var pinsLength = pins.length;

        for (var i = 0; i < pinsLength; i++) {
          var pin = pins[i];
          var widget = widget_items[i];
          Drupal.imagepin.attachPin(pin);

          pin.on('mouseover click touchstart', function () {
            var pin = this;
            var widget = pin.data('imagepinWidget');
            // Mark the selected pin and widget.
            for (var i = 0; i < pinsLength; i++) {
              pins[i].removeClass('imagepin-selected');
              widget_items[i].removeClass('imagepin-selected');
            }
            pin.addClass('imagepin-selected');
            widget.addClass('imagepin-selected');

            if (Drupal.imagepin.onDesktop(widgets)) {
              Drupal.imagepin.overlay(pin, widget);
            }
          }.bind(pin));

          widget.on('click touchend', function () {
            var widget = this;
            var pin = widget.data('imagepinPin');
            // Mark the selected pin and widget.
            for (var i = 0; i < pinsLength; i++) {
              pins[i].removeClass('imagepin-selected');
              widget_items[i].removeClass('imagepin-selected');
            }
            pin.addClass('imagepin-selected');
            widget.addClass('imagepin-selected');
          }.bind(widget));
        }

      });

      if ('extensions' in Drupal.imagepin) {
        for (var extension in Drupal.imagepin.extensions) {
          if (Drupal.imagepin.extensions.hasOwnProperty(extension)) {
            Drupal.imagepin.extensions[extension].attach(context, settings);
          }
        }
      }
    },

    detach: function (context, settings) {
      if ('extensions' in Drupal.imagepin) {
        for (var extension in Drupal.imagepin.extensions) {
          if (Drupal.imagepin.extensions.hasOwnProperty(extension)) {
            Drupal.imagepin.extensions[extension].detach(context, settings);
          }
        }
      }
    }
  };
}(jQuery, Drupal, window));
