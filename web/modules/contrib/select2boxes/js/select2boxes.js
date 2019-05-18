/**
 * @file
 * Select2 integration.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2widget = {
    attach: function (context) {
      // Additional code to make removing/creating elements correctly works.
      $('.select2-widget', context).each(function () {
        var $select2box = $(this);
        $(this).on('select2:close', function (event) {
          if (event.params.hasOwnProperty('originalSelect2Event')) {
            return;
          }
          // Allow this to be done only for multi-widget
          // with entity auto-creation enabled.
          if ($select2box.data('select2-multiple')
            && $select2box.data('auto-create-entity') === 'enabled'
          ) {
            // Get all the box values using it's options.
            var values = $.map(this.options, function (option) {
              return option.value;
            });

            $select2box.val(values).trigger('change');
          }
        });

        $(this).on('select2:select', function (event) {
          var values = $select2box.val();
          if (typeof values === 'object') {
            values.push(event.params.data.id);
          }
          $select2box.val(values);
        });



        $(this).on('select2:unselecting', function (event) {
          var params = event.params || false;
          if (params && params.hasOwnProperty('args')) {
            if (!params.args.data.hasOwnProperty('element')) {
              return;
            }
            $(params.args.data.element).remove();
          }
        });
      });
      // Entity reference fields Select2 widgets handler.
      $('.select2-boxes-widget', context).once().each(function () {
        // Prepare required arguments attached to the element.
        var url = $(this).data('autocomplete-path');
        var field_name = $(this).data('field-name');
        var autocreate = $(this).data('auto-create-entity') === 'enabled';
        var multiple = $(this).data('select2-multiple');
        drupalSettings.preloaded_entries = drupalSettings.preloaded_entries || false;
        $(this).select2({
          multiple: multiple,
          width: 'resolve',
          tags: autocreate,
          containerCssClass: (drupalSettings.preloaded_entries && drupalSettings.preloaded_entries[field_name])
            ? 'preloaded'
            : '',
          formatNoMatches: function () {
            return Drupal.t('No matches found');
          },
          formatInputTooShort: function (input, min) {
            return Drupal.t('Please enter !min_length or more characters', {'!min_length': String(min - input.length)});
          },
          formatInputTooLong: function (input, max) {
            return Drupal.formatPlural(String(input.length - max), 'Please delete 1 character', 'Please delete @count characters');
          },
          formatLoadMore: function () {
            return Drupal.t('Loading...');
          },
          formatSearching: function () {
            return Drupal.t('Searching...');
          },
          createSearchChoice: function (term, data) {
            // We need this only for the field widgets
            // with auto-creation options enabled.
            if (autocreate && $(data).filter(function () {
              return this.text.localeCompare(term) === 0;
            }).length === 0) {
              return {id: term, text: term};
            }
          },
          ajax: {
            url: url,
            data: function (params) {
              return {q: params.term};
            },
            processResults: function (data) {
              var res = $.map(data, function (item) {
                // Prevent empty options being passed to the results.
                if (item.label) {
                  return {
                    id: /\(([^)]+)\)/.exec(item.value)[1],
                    text: decodeHtmlEntities(item.label)
                  };
                }
              });

              // Handle preloaded entries if specified.
              if (drupalSettings.preloaded_entries && drupalSettings.preloaded_entries[field_name] && res.length <= 0) {
                $.each(drupalSettings.preloaded_entries[field_name], function (id, value) {
                  res.push({id: id, text: decodeHtmlEntities(value)});
                });
              }
              return {results: res};
            }
          },
          initSelection: function (element, callback) {
            var data = [];
            drupalSettings.initValues = drupalSettings.initValues || false;
            if (drupalSettings.initValues && drupalSettings.initValues[field_name]) {
              $.each(drupalSettings.initValues[field_name], function (id, name) {
                // Cleanup the options, to prevent duplicates.
                $(element).find('option').each(function () {
                  if ($(this).val() === id) {
                    $(this).remove();
                  }
                });
                // Push init values into the data array.
                data.push({id: id, text: name});
                // Create and append init values as a selected options.
                var newOption = new Option(name, id, true, true);
                $(element).append(newOption);
              });
            }
            callback(data);
          }
        });
        addDropdownArrow($(this));
      });
      // Additional handler for the list fields.
      $('[data-select2-autocomplete-list-widget]', context).once().each(function () {
        var multiple = $(this).data('select2-multiple');
        var autocreate = $(this).data('auto-create-entity') === 'enabled';
        // Defaults to 0.
        var minSearchInputLength = 0;
        // Handle "min length" feature only for widgets
        // without "autocreate" option and
        // if min search length attribute has been specified.
        if (!autocreate && typeof $(this).data('minimum-search-length') !== 'undefined') {
          var length = Number($(this).find('option').length);
          // Remove "_none" option from count(for single widgets only).
          if (!multiple &&
            ($(this).find('option[value=""]').length > 0
            || $(this).find('option[value="_none"]').length > 0
            || $(this).find('option[value="All"]').length > 0)
          ) {
            length--;
          }
          // Set minSearchInputLength to "Infinity"
          // if the options length is less than specified min search length.
          // @see https://select2.org/searching#single-select
          if (length < Number($(this).data('minimum-search-length'))) {
            minSearchInputLength = Infinity;
          }
          else {
            minSearchInputLength = Number($(this).data('minimum-search-length'));
          }
        }
        $(this).select2({
          formatNoMatches: function () {
            return Drupal.t('No matches found');
          },
          formatInputTooShort: function (input, min) {
            return Drupal.t('Please enter !min_length or more characters', {'!min_length': String(min - input.length)});
          },
          formatInputTooLong: function (input, max) {
            return Drupal.formatPlural(String(input.length - max), 'Please delete 1 character', 'Please delete @count characters');
          },
          formatLoadMore: function () {
            return Drupal.t('Loading...');
          },
          formatSearching: function () {
            return Drupal.t('Searching...');
          },
          // Set preloaded class to trigger JS/CSS rule
          // for adding the dropdown arrow to this widget.
          containerCssClass: multiple ? 'preloaded' : '',
          minimumResultsForSearch: minSearchInputLength,
          width: 'resolve',
          multiple: multiple,
          tags: autocreate,
          templateResult: flagsIcons,
          templateSelection: flagsIcons
        });
        addDropdownArrow($(this));
      });

      /**
       * Handle flag icons if needed.
       *
       * @param {Object} element
       *   Option element object.
       *
       * @return {String|Object}
       *   Option element text or jQuery object.
       */
      function flagsIcons(element) {
        // Prevent flags being added to the non-existing options
        // and "none" default options.
        if (element.element === undefined || !element.id || element.id === '_none') {
          return element.text;
        }
        // Disallow flags icons being added if an appropriate setting
        // wasn't enabled on fields widget config page.
        var el = $(element.element.parentNode);

        // Prepare flags classes.
        var flags_classes = false;
        drupalSettings.flagsClasses = drupalSettings.flagsClasses || false;
        if (drupalSettings.flagsClasses && drupalSettings.flagsClasses[element.id]) {
            flags_classes = drupalSettings
                .flagsClasses[element.id]
                .join(' ');
        }

        // If element contains class 'flag-icon' add flags.
        if (el.hasClass('flag-icon')) {
            var $icon = $('<span class="icon" role="img"></span>');
            return $('<div class="icon-wrapper">' + element.text + '</div>')
                .prepend($icon.addClass(flags_classes));
        }

        if (typeof el === 'undefined' || typeof el.attr('name') === 'undefined') {
          return element.text;
        }
        var field_name = el.attr('name')
          // Remove "[]" for multiple values field.
          .replace('[]', '');
        drupalSettings.flagsFields = drupalSettings.flagsFields || false;
        if (!drupalSettings.flagsFields || !drupalSettings.flagsFields[field_name]) {
          return element.text;
        }

        // If flags classes exist attach them to the element.
        if (flags_classes) {
          var $icon = $('<span class="icon" role="img"></span>');
          return $('<div class="icon-wrapper">' + element.text + '</div>')
            .prepend($icon.addClass(flags_classes));
        }
        return element.text;
      }

      /**
       * Decode HTML entities.
       *
       * @param {String} input
       *   Input string to decode.
       *
       * @return {String}
       *   Decoded string.
       */
      function decodeHtmlEntities(input) {
        return input.replace(/&#([0-9]{1,3});/gi, function (match, num_str) {
          return String.fromCharCode(parseInt(num_str, 10));
        });
      }

      /**
       * Attach dropdown arrow HTML elements to the select2 dropdown container.
       *
       * @param {Object} $dropdown
       *   JQuery object of dropdown.
       */
      function addDropdownArrow($dropdown) {
        var $container = $dropdown.next().find('.preloaded');
        if ($container.length >= 1) {
          $container.append('<span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span>');
        }
      }
    }
  };

  /**
   * Select2 boxes integration with the address fields.
   */
  Drupal.behaviors.select2boxesAddress = {
    attach: function (context, settings) {
      settings.addressFieldsSelect2 = settings.addressFieldsSelect2 || {};
      if (settings.addressFieldsSelect2 !== {}) {
        $.each(settings.addressFieldsSelect2, function (field_name) {
          $('select[name^="' + field_name + '"]', context).select2({
            formatNoMatches: function () {
              return Drupal.t('No matches found');
            },
            formatInputTooShort: function (input, min) {
              return Drupal.t('Please enter !min_length or more characters', {'!min_length': String(min - input.length)});
            },
            formatInputTooLong: function (input, max) {
              return Drupal.formatPlural(String(input.length - max), 'Please delete 1 character', 'Please delete @count characters');
            },
            formatLoadMore: function () {
              return Drupal.t('Loading...');
            },
            formatSearching: function () {
              return Drupal.t('Searching...');
            }
          });
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
