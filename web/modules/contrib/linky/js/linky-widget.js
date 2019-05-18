/**
 * @file
 * Attaches entity-type selection behaviors to the widget form.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.linkyWidget = {
    attach: function (context) {
      drupalSettings.dynamic_entity_reference = drupalSettings.dynamic_entity_reference || {};
      function linkyWidget(e) {
        var data = e.data;
        var $select = $('.' + data.select);
        var $autocomplete = $select.parents('.container-inline').find('.form-autocomplete');
        var $title = $select.parents('.container-inline').find('.linky__title');
        var entityTypeId = $select.val();
        if (entityTypeId === 'linky') {
          $title.removeClass('invisible');
          $autocomplete.attr('placeholder', 'Link URL');
        }
        else {
          $title.addClass('invisible');
        }
      }
      Object.keys(drupalSettings.dynamic_entity_reference).forEach(function (field_class) {
        $(context)
          .find('.' + field_class)
          .once('linky')
          .on('change', {select: field_class}, linkyWidget);
        $(context)
          .find('.' + field_class)
          .once('linky-title')
          .each(function() {
            var $el = $(this);
            var $autocomplete = $el.parents('.container-inline').find('.form-autocomplete');
            var $title = $el.parents('.container-inline').find('.linky__title');
            $autocomplete.on('keyup', function( event ) {
              // If this is a linky reference and the user has started typing, they've not selected an autocomplete
              // option, so we need to allow title entry. We ignore non-printable keys like Enter, tab, arrow keys, end
              // etc. See https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/keyCode
              var keycode = event.keyCode;
              var valid =
                (keycode > 47 && keycode < 58)   || // number keys
                keycode == 32 || keycode == 8    || // spacebar or backspace
                (keycode > 64 && keycode < 91)   || // letter keys
                (keycode > 95 && keycode < 112)  || // numpad keys
                (keycode > 185 && keycode < 193) || // ;=,-./` (in order)
                (keycode > 218 && keycode < 223);   // [\]' (in order)

              if ($el.val() === 'linky' && valid) {
                $title.removeClass('invisible');
              }
            });
            $autocomplete.on( "autocompleteselect", function( event, ui ) {
              $title.addClass('invisible');
            });
          });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
