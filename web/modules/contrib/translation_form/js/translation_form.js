(function ($, Drupal, window) {
  'use strict';

  Drupal.translationForm = Drupal.translationForm || {};

  /**
   * Filter summary label text.
   */
  $.fn.filterSummaryLabel = function () {
    return this.text().split(/\s\(/).shift();
  };

  /**
   * Convert field's machine name to a class format.
   */
  String.prototype.machineNameToClass = function () {
    return this.split('_').join('-');
  };

  Drupal.behaviors.translationFormFieldsTranslationsPreview = {
    attach: function (context, settings) {
      $(window).on('load', function () {
        var translations    = settings.translationFormFieldsTranslationsPreview;
        var currentLanguage = settings.translationFormCurrentLanguageName;
        if (translations) {
          var prefix = 'field--name-';
          $.each(translations.tables, function (name, table) {
            var $field, label_selector, $preview, $label,
              $row, $last_row, $container, $legend;
            if (name.indexOf('_summary') === -1) {
              $field = $('.' + prefix + name.machineNameToClass(), context);
              label_selector = '.form-item > label:not([for*="format"],[for*="alt"],[for*="title"]), .form-item > label[for*="title-0-value"]';
              if (!$field.length) {
                $field = $('.form-item-' + name.machineNameToClass(), context);
                label_selector = 'label:not([for*="format"],[for*="alt"],[for*="title"]), label[for*="title-0-value"]';
              }
              if ($field.length > 0) {
                if (!isContainerExists($field)) {
                  $('<div class="translation-form-preview"></div>')
                    .insertAfter($field.find(label_selector));
                }
                $(table).appendTo(
                  $field.find('.translation-form-preview')
                );

                $preview = $field.find('.translation-form-preview');
                $label = $preview.prev();
                $preview.detach().prependTo($field);
                $label.detach().prependTo($field);
                $label.removeClass('option');
                // Prepare new table row.
                $row = $('<tr><td></td><td>' + currentLanguage + '</td></td>');
                // Inherit odd/even classes flow
                // to prevent any style bugs.
                $last_row = $(table).find('tr:last');
                if ($last_row.hasClass('odd')) {
                  $row.addClass('even');
                }
                else if ($last_row.hasClass('even')) {
                  $row.addClass('odd');
                }
                $container = $row.find('td:first');
                appendFieldRecursive($container, $field);
                $field.find('label[for$="summary"]').hide();
                // Additional fix for fieldset's "legend" element.
                $legend = $container.find('fieldset legend');
                if ($legend.length > 0) {
                  $legend.detach().prependTo($field);
                  $($field.find('legend')[0])
                    .replaceWith('<label>' + $legend.html() + '</label>');
                }
                $row.appendTo($field.find('.translation-form-preview table tbody'));
              }
            }
            else {
              var field_name = name.replace('_summary', '');
              $field = $('.field--type-text-with-summary.field--name-' + field_name.machineNameToClass(), context);
              if ($field.length) {
                label_selector = 'label[for$="summary"]';
                if ($field.find(label_selector).find('.translation-form-preview').length < 2) {
                  $('<div class="translation-form-preview"></div>')
                    .appendTo($field);
                }
                $(table).appendTo(
                  $field.find('.translation-form-preview')
                );
                var $summary = $field.find('textarea[name$="[summary]"]')
                  .closest('.text-summary-wrapper');

                // Prepare new table row.
                $row = $('<tr><td></td><td>' + currentLanguage + '</td></td>');
                // Inherit odd/even classes flow
                // to prevent any style bugs.
                $last_row = $(table).find('tr:last');
                if ($last_row.hasClass('odd')) {
                  $row.addClass('even');
                }
                else if ($last_row.hasClass('even')) {
                  $row.addClass('odd');
                }
                $container = $row.find('td:first');
                $summary.detach().appendTo($container);
                $row.appendTo($field.find('.translation-form-preview:last table tbody'));
                var field_label = $field.find('label[for$="value"]').filterSummaryLabel();
                $field.find(label_selector).text($field.find(label_selector).filterSummaryLabel() + ' (' + field_label + ')');
                $field.find(label_selector).show();
                $field.find(label_selector).detach()
                  .insertBefore($field.find('.translation-form-preview:last'));
                $field.find('.translation-form-preview:last .text-summary-wrapper').show();
                $field.find('.translation-form-preview:first label[for$="summary"]').remove();
                if ($field.find('.translation-form-preview').length > 1) {
                  $field.find('> label[for$="value"] button').remove();
                  $field.find('> label[for$="value"]').text(field_label);
                }
              }
            }
          });

          // Cleanup redundant tables.
          $('.translation-form-preview', context).each(function () {
            var $tables = $(this).find('table');
            if ($tables.length > 1) {
              for (var i = 1; i <= $tables.length; i++) {
                if ($tables[i]) {
                  $($tables[i]).prev('.translation-form-language-toggle').remove();
                  $tables[i].remove();
                }
              }
            }
          });

          // Add language toggle link.
          Drupal.translationForm.languageToggle(translations, context);
        }
      });

      /**
       * Recursively rebuild the HTML structure of the field element.
       *
       * @param {jQuery} $container
       *   Container DOM.
       * @param {jQuery} $field
       *   Field DOM.
       *
       * @return null
       *   NULL when the recursive iterations are finished.
       */
      function appendFieldRecursive($container, $field) {
        var $input = $($field.find('.translation-form-preview').next()[0]);
        if ($input.length < 1) {
          return null;
        }
        $input.detach().prependTo($container);
        $container.find('.translation-form-preview').remove();
        return appendFieldRecursive($container, $field);
      }

      /**
       * Check if table container was already attached.
       *
       * @param {Object} $field
       *   JQuery DOM object.
       *
       * @return {Boolean}
       *   TRUE - if container is already exists, FALSE otherwise.
       */
      function isContainerExists($field) {
        return $field.find('label')
          .next()
          .hasClass('translation-form-preview');
      }
    }
  };

  Drupal.behaviors.translationFormSummaryExpandingHandler = {
    attach: function (context, settings) {
      // Fix the behavior of the opening/closing textarea
      // for the summary sub-field.
      var handler = function () {
        var $container = $('.translation-form-preview', context);
        if ($container.length > 0 && $container.find('table tr').length >= 1) {
          var $link = $container.closest('.field--widget-text-textarea-with-summary')
            .find('button.link-edit-summary');
          if ($link.length > 0) {
            $link.on('click', function (event) {
              $container = $(this).closest('.field--widget-text-textarea-with-summary');
              var $summary_wrapper = $container.find('.text-summary-wrapper');
              if ($summary_wrapper.css('display') === 'none') {
                $(this).html(Drupal.t('Hide summary'));
                if ($container.find('.translation-form-preview').length < 2) {
                  $('<div class="translation-form-preview"><table class="summary"><tbody><tr><td></td><td>'
                    + settings.translationFormCurrentLanguageName
                    + '</td></tr></tbody></table></div>')
                    .insertAfter($container.find('.translation-form-preview'));
                  var $second_container = $container.find('.translation-form-preview:last');
                  var $cell = $second_container.find('tr td:first');
                  $summary_wrapper.detach()
                    .appendTo($cell).show();
                  $container.find('label[for$="summary"]')
                    .detach()
                    .insertBefore($second_container)
                    .show();
                  var field_label = $container.find('label[for$="value"]').filterSummaryLabel();
                  if (!/\(*\)/.test($container.find('label[for$="summary"]').text())) {
                    $container.find('label[for$="summary"]')
                      .text(
                        $container.find('label[for$="summary"]').text() + ' (' + field_label + ')'
                      );
                  }
                }
              }
              else {
                $(this).html(Drupal.t('Edit summary'));
                $container.find('.translation-form-preview:last tr td:first > div')
                  .detach()
                  .prependTo($(this).parent().parent())
                  .hide();
                $container.find('label[for$="summary"]')
                  .detach()
                  .insertBefore($container.find('label[for$="value"]'))
                  .hide();
                $container.find('.translation-form-preview:last')
                  .remove();
              }
              event.preventDefault();
              return false;
            });
          }
        }
      };
      $(window).on('load', handler);
      $(document).ajaxSuccess(handler);
    }
  };

  Drupal.behaviors.translationFormImagesPreviewHandler = {
    attach: function (context) {
      $(window).on('load', function () {
        var $container = $('.translation-form-preview', context);
        if ($container.length > 0) {
          var $untranslatables = $container.find('.untranslatable-image-file');
          if ($untranslatables.length > 0) {
            $untranslatables.each(function () {
              $(this).removeClass('untranslatable-image-file');
              var $table = $(this).closest('table');
              if (!$table.prev().is('img')) {
                $(this).find('img').detach()
                  .insertBefore($table).addClass('untranslatable-image-file');
              }
              else {
                $(this).find('img').remove();
              }
            });
          }
        }
      });
      $('input[name$="remove_button"]', context).on('mousedown', function() {
        $(this)
          .closest('.field--type-image')
          .find('.untranslatable-image-file')
          .remove();
      });
    }
  };

  /**
   * Attach toggle language link.
   */
  Drupal.translationForm.languageToggle = function (translations, context) {
    $(translations.language_toggle).prependTo($('.translation-form-preview:first', context));

    $('.translation-form-language-toggle').sticky({ topSpacing:50 });

    // Hide/show language column.
    $('.translation-form-language-toggle', context).on('click', function (e) {
      e.preventDefault();
      var $this = $(this);

      // We need to set nowrap fro smooth slide.
      $this.parents('form').find('.translation-form-preview td:last-child').css('white-space', 'nowrap').toggle(400, function() {
        $(this).css('white-space', 'normal');
      });

      $this.toggleClass('translation-form-language-toggle-hidden');
    });
  }
})(jQuery, Drupal, window);
