/**
 * @file
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.translatorTranslateDirty = {
    attach: function attach() {
      var $form = $('#translator-interface-edit-form').once('translatordirty');
      if ($form.length) {
        $form.one('formUpdated.translatorDirty', 'table', function () {
          var $marker = $(Drupal.theme('translatorTranslateChangedWarning')).hide();
          $(this).addClass('changed').before($marker);
          $marker.fadeIn('slow');
        });

        $form.on('formUpdated.translatorDirty', 'tr', function () {
          var $row = $(this);
          var $rowToMark = $row.once('localemark');
          var marker = Drupal.theme('translatorTranslateChangedMarker');

          $row.addClass('changed');

          if ($rowToMark.length) {
            $rowToMark.find('td:first-child .js-form-item').append(marker);
          }
        });
      }
    },
    detach: function detach(context, settings, trigger) {
      if (trigger === 'unload') {
        var $form = $('#translator-interface-edit-form').removeOnce('translatordirty');
        if ($form.length) {
          $form.off('formUpdated.translatorDirty');
        }
      }
    }
  };

  Drupal.behaviors.translatorHideUpdateInformation = {
    attach: function attach() {
      var $table = $('#translator-interface-edit-form').once('expand-updates');
      if ($table.length) {
        var $tbodies = $table.find('tbody');

        $tbodies.on('click keydown', '.description', function (e) {
          if (e.keyCode && e.keyCode !== 13 && e.keyCode !== 32) {
            return;
          }
          e.preventDefault();
          var $tr = $(this).closest('tr');

          $tr.toggleClass('expanded');

          $tr.find('.translator-update__prefix').text(function () {
            if ($tr.hasClass('expanded')) {
              return Drupal.t('Hide description');
            }

            return Drupal.t('Show description');
          });
        });
        $table.find('.requirements, .links').hide();
      }
    }
  };

  Drupal.behaviors.translatorInterfaceWrapWithOptgroups = {
    attach: function (context, settings) {
      $(document).ready(function () {
        settings.userRegisteredLanguages = settings.userRegisteredLanguages || false;
        if (settings.userRegisteredLanguages) {
          var user_registered = settings.userRegisteredLanguages;
          $.each(user_registered, function (name, registered) {
            var $select = name === 'from'
              ? $('select[name="langcode_from"]', context)
              : $('select[name="langcode"]', context);
            if (!registered.hasOwnProperty('length')) {
              processOptGroups(registered, $select);
            }
          });
        }
      });

      /**
       * Process optgroups for specified select.
       *
       * @param {Object} registered
       *   Associative array with registered languages.
       * @param {Object} $select
       *   jQuery DOM of the select element.
       */
      function processOptGroups(registered, $select) {
        if ($select.length > 0) {
          // Prepare some variables.
          var group_values = {};
          var group_others = {};
          var $opt_group = null;
          var labels = {
            registered: Drupal.t('Translation skills'),
            others: Drupal.t('Other languages')
          };
          $('<optgroup label="' + labels.registered + '" />').appendTo($select);
          $('<optgroup label="' + labels.others + '" />').appendTo($select);
          // Walk through the registered languages.
          $.each(registered, function (langcode) {
            // Loop through the existing options.
            $select.find('option').each(function () {
              var opt_value = $(this).val();
              var opt_text = $(this).text();
              var is_selected = $(this).attr('selected') === 'selected';
              // Put existing option's text and values
              // into the appropriate array.
              if (opt_value !== langcode) {
                group_others[opt_value] = [opt_text, is_selected];
              }
              else {
                group_values[opt_value] = [opt_text, is_selected];
              }

              if (group_values[opt_value]) {
                delete group_others[opt_value];
              }

              // Remove option.
              $(this).remove();
            });
            // Process registered optgroup.
            if (group_values !== {}) {
              // Prepare the optgroup DOM.
              $opt_group = $select.find('optgroup[label="' + labels.registered + '"]');
              // Add options to the optgroup.
              $.each(group_values, function (code, value) {
                $('<option value="' + code + '"/>')
                  .attr('selected', value[1] === true ? 'selected' : null)
                  .html(value[0])
                  .appendTo($opt_group);
              });
            }
            // Process "others" optgroup.
            if (group_others !== {}) {
              // Prepare the optgroup DOM.
              $opt_group = $select.find('optgroup[label="' + labels.others + '"]');
              // Add options to the optgroup.
              $.each(group_others, function (code, value) {
                $('<option value="' + code + '"/>')
                  .attr('selected', value[1] === true ? 'selected' : null)
                  .html(value[0])
                  .appendTo($opt_group);
              });
            }
          });
        }
      }
    }
  };

  $.extend(Drupal.theme, {
    translatorTranslateChangedMarker: function translatorTranslateChangedMarker() {
      return '<abbr class="warning ajax-changed" title="' + Drupal.t('Changed') + '">*</abbr>';
    },
    translatorTranslateChangedWarning: function translatorTranslateChangedWarning() {
      return '<div class="clearfix messages messages--warning">' + Drupal.theme('translatorTranslateChangedMarker') + ' ' + Drupal.t('Changes made in this table will not be saved until the form is submitted.') + '</div>';
    }
  });
})(jQuery, Drupal);
