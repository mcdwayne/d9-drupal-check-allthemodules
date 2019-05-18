(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.initDimension = {
    attach: function (context, settings) {
      $('fieldset.dimension-wrapper', context)
        .once('init-colorbox')
        .each(function() {
          Drupal.dimension.refresh($('legend', this), settings);
          $('input', this).on('blur', function() {
            Drupal.dimension.refresh(this, settings);
          });
        });
    }
  };

  /**
   * Dimension methods.
   *
   * @namespace
   */
  Drupal.dimension = {

    refresh: function(el, settings) {
      var wrapper = $(el).closest('.dimension-wrapper'),
        id = $(wrapper).attr('dimension-id'),
        value = 1;

      for (var key in settings.dimension[id]['fields']) {
        value = value *
          parseFloat($('input[dimension-key="'+key+'"]', wrapper)[0].value).toFixed(settings.dimension[id]['fields'][key]['scale']) *
          settings.dimension[id]['fields'][key]['factor'];
      }
      $('input[dimension-key="value"]', wrapper)[0].value = parseFloat(value).toFixed(settings.dimension[id]['value']['scale']);
    }
  };

})(jQuery, Drupal);
