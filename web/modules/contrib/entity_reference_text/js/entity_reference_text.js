(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.entityReferenceTextAutocompletion = {
    attach: function (context) {
      $(context).find('[data-atjs]').once('entity-reference-text-autocompletion').each(function () {
        var $that = $(this);
        $that.html(Drupal.entity_reference_text.convertStringToHtml($that[0].innerHTML));

        var $input_element = $('#' + $that.data('atjs-input-element-id'));
        var data_url = $that.data('atjs-url');
        var $atWho = $that.atwho({
          at: '@',
          data: data_url,
          insertTpl: '<span class="entity_reference_text-entity">${atwho-at}${name}</span>',
          alias: 'ERT'
        });

        // Reload the data when the textfield is entered.
        $atWho.on('focus', function () {
          $atWho.atwho('load', 'ERT', data_url);
        });

        // Find related form.
        $that.parents('form').submit(function () {
          $input_element.val(Drupal.entity_reference_text.convertHtmlToString($that[0].innerHTML));
        });
      });
    }
  };

  Drupal.entity_reference_text = {};

  /**
   * Convert a string like "from <span>@foo</span>" to "from @foo" .
   *
   * @param {string} html_string
   *   The string.
   * @returns {string}
   *   The replaced string
   */
  Drupal.entity_reference_text.convertHtmlToString = function (html_string) {
    // Just strip all HTML.
    return html_string.replace(/<(?:.|\n)*?>/gm, '');
  };

  /**
   * Convert a string like "from @foo" to "from <span>@foo</span>".
   *
   * @param {string} string
   *   The string.
   * @returns {string}
   *   The replaced string
   */
  Drupal.entity_reference_text.convertStringToHtml = function (string) {
    var regex = /(@\w+)/ig;
    var match;
    var result_string = string;
    while ((match = regex.exec(string)) != null) {
      result_string = result_string.replace(match[0], '<span class="entity_reference_text-entity">' + match[0] + '</span>');
    }
    return result_string;
  };

})(jQuery, Drupal);
