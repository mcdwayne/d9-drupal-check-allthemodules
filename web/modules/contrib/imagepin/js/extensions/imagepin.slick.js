/**
 * @file
 * JS implementation for extending the display of imagepin widgets with the Slick carousel.
 */

(function ($, Drupal, window) {

  var $window;

  Drupal.imagepin = Drupal.imagepin || {$window: $(window), widgets: {}};

  Drupal.imagepin.extensions = Drupal.imagepin.extensions || {};

  $window = Drupal.imagepin.$window;

  $window.resize(function () {
    var collected = Drupal.imagepin.widgets;
    for (var attach_id in collected) {
      if (collected.hasOwnProperty(attach_id)) {
        var widgets = collected[attach_id];
        var image = widgets.data('imagepinImage');
        widgets.css('max-width', image.width());
      }
    }
  });

  /**
   * Initialize / Uninitialize the slick extension for imagepin widgets.
   */
  Drupal.imagepin.extensions.slick = {
    onWidgetsInit: function (slick) {
      var widgets = this;
      var attach_id = widgets.data('imagepinAttachTo');
      var widget_items = widgets.data('imagepinWidgetItems');
      var items_length = widget_items.length;
      var pins = widgets.data('imagepinPins');

      var pin;
      var i;
      for (i = 0; i < items_length; i++) {
        var widget = widget_items[i];
        pin = widget.data('imagepinPin');
        pin.on('mouseover touchstart', function () {
          var pin = this;
          var key = pin.data('imagepinKey');
          var original_slide = $(".slick-slide[data-imagepin-key='" + key + "']:not(.slick-cloned)", widgets[0]);
          var slide = original_slide.length > 0 ? original_slide.first() : $(".slick-slide[data-imagepin-key='" + key + "']", widgets[0]).first();
          if (!slide.hasClass('slick-current')) {
            for (var i = 0; i < items_length; i++) {
              widget_items[i].removeClass('imagepin-selected');
            }
            slide.click();
          }
        }.bind(pin));
      }
      var first_slide = $('.slick-current', widgets[0]).first();
      var first_key = first_slide.data('imagepinKey');

      for (i = 0; i < items_length; i++) {
        pin = pins[i];
        if ((pin.data('imagepinKey') === first_key) && (pin.data('imagepinAttachedTo') === attach_id)) {
          pin.addClass('imagepin-selected');
        }
      }
    },
    attach: function (context, settings) {
      var collected = Drupal.imagepin.widgets;
      for (var attach_id in collected) {
        if (collected.hasOwnProperty(attach_id)) {
          var widgets = collected[attach_id];
          var slick_initialized = widgets.data('imagepinSlickInitialized');
          if (typeof slick_initialized !== 'boolean') {
            slick_initialized = widgets.hasClass('slick-slider');
          }
          if (slick_initialized !== true) {
            widgets.data('imagepinSlickInitialized', true);

            widgets.on('init', this.onWidgetsInit.bind(widgets));

            widgets.slick({
              infinite: true,
              centerMode: true,
              focusOnSelect: true,
              variableWidth: true,
              arrows: false,
              speed: 700
            });

            widgets.on('afterChange', function (event, slick, currentSlide) {
              var widgets = this;
              var slide = $(slick.$slides.get(currentSlide));
              var key = slide.data('imagepinKey');

              var pins = widgets.data('imagepinPins');
              var pins_length = pins.length;
              var widget_items = widgets.data('imagepinWidgetItems');
              for (var i = 0; i < pins_length; i++) {
                var pin = pins[i];
                var widget = widget_items[i];
                if (key === pin.data('imagepinKey')) {
                  pin.addClass('imagepin-selected');
                  widget.addClass('imagepin-selected');
                }
                else {
                  pin.removeClass('imagepin-selected');
                  widget.removeClass('imagepin-selected');
                }
              }
            }.bind(widgets));
          }
        }
      }
    },
    detach: function (context, settings) {}
  };
}(jQuery, Drupal, window));
