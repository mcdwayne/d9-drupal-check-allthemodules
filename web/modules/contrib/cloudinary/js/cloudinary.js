/**
 * @file
 * JavaScript behaviors for the image effect form page of Cloudinary.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.cloudinary = Drupal.behaviors.cloudinary || {};

  Drupal.behaviors.cloudinary.attach = function (context) {
    Drupal.cloudinary.ui(context);
  };

  Drupal.cloudinary = Drupal.cloudinary || {};

  Drupal.cloudinary.ui = function (context) {
    var farb_change_color = function (obj, color) {
      if (!color) {
        color = '#000';
      }
      obj.css({
        backgroundColor: color,
        'color': farb.RGBToHSL(farb.unpack(color))[2] > 0.5 ? '#000' : '#fff'
      });
    };

    var $picker = $('<div id="placeholder"></div>')
      .css({'position': 'absolute', 'z-index': 999999})
      .appendTo('#farbtastic-color')
      .hide()
      .once('farbtastic');
    var farb = $.farbtastic($picker);
    // Bind input as color picker.
    $('.input_color', context).once('input_color', function () {
      var $ic = $(this);
      farb_change_color($ic, $ic.val());
      $ic.focus(function () {
        var position = $ic.position();
        var left = position.left - ((195 - $ic.width()) / 2);
        var top = position.top - 195;

        farb.linkTo(function (color) {
          farb_change_color($ic.val(color), color);
        }).setColor($ic.val());

        $picker.css({'left': left, 'top': top}).fadeIn();
      }).focusout(function () {
        farb.setColor($ic.val());
        $picker.hide();
      });
    });
    // Bind textfield as jquery ui slider.
    $('.input_slider', context).once('input_slider', function () {
      var $select = $(this);
      var min = 0;
      var max = 200;
      var fixed = 'dynamic';
      var css = 'slider';
      var data = $select.attr('data');
      // Assign data if get custom value.
      if (data) {
        var datas = data.split('_', 4);
        if (datas.length >= 3) {
          fixed = datas[0];
          min = parseInt(datas[1]);
          max = parseInt(datas[2]);
          if (datas[3]) {
            css = datas[3];
          }
        }
      }
      // Insert jquery ui slider before textfield.
      var $slider = $('<div></div>').addClass(css).insertBefore($select).slider({
        min: min,
        max: max,
        range: 'min',
        value: parseInt($select.val()) || 0,
        slide: function (event, ui) {
          $select.val(ui.value);
        }
      });
      // Update slider max if input value large.
      // Update value with min if less than min value.
      $select.change(function () {
        var current_value = parseInt($(this).val());
        if (current_value < min) {
          current_value = min;
        }
        else if (fixed === 'fixed' && current_value > max) {
          current_value = max;
        }
        $(this).val(current_value);
        $slider.slider('value', current_value);
        if (fixed !== 'fixed' && current_value > max) {
          $slider.slider('option', 'max', current_value);
        }
      });
    });
    // Bind reset button.
    $('#edit-reset').click(function () {
      $(this).parents('form').map(function () {
        this.reset();
        var $input = $(this).find(':input');
        $input.not('.input_slider').change();
        $input.filter('.input_color').map(function () {
          farb_change_color($(this), $(this).val());
        });
        $input.filter('.input_slider').map(function () {
          var cv = $(this).val();
          $(this).change().val(cv);
        });
      });

      return false;
    });
  };

})(jQuery);
