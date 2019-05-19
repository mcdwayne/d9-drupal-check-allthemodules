(function ($, Drupal) {

  'use strict';

  Drupal.whitelabel_preview = {
    callback: function callback(context, settings, form) {//, farb, height, width) {
      var accum = void 0;
      var delta = void 0;

      form.find('.color-preview').css('backgroundColor', settings.whitelabel.reference.bg);

      form.find('.color-preview-main').css('color', settings.whitelabel.reference.text);
      form.find('.color-preview-main a, .color-preview-main h2').css('color', settings.whitelabel.reference.link);

      /*
      function gradientLineColor(i, element) {
        Object.keys(accum || {}).forEach(function (k) {
          accum[k] += delta[k];
        });
        element.style.backgroundColor = farb.pack(accum);
      }

      var colorStart = void 0;
      var colorEnd = void 0;
      Object.keys(settings.gradients || {}).forEach(function (i) {
        colorStart = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[0] + ']"]').val());
        colorEnd = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[1] + ']"]').val());
        if (colorStart && colorEnd) {
          delta = [];
          Object.keys(colorStart || {}).forEach(function (colorStartKey) {
            delta[colorStartKey] = (colorEnd[colorStartKey] - colorStart[colorStartKey]) / (settings.gradients[i].vertical ? height[i] : width[i]);
          });
          accum = colorStart;

          form.find('#gradient-' + i + ' > div').each(gradientLineColor);
        }
      });
      */
    }
  };

  Drupal.behaviors.whitelabel_preview = {
    attach: function (context, settings) {

      var form = $(context).once('.color-preview');

      function preview() {
        Drupal.whitelabel_preview.callback(context, settings, form);//, farb, height, width);
      }

      preview();
    }
  };

})(jQuery, Drupal);
