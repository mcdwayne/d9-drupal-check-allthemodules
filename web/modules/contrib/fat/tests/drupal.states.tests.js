/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * States.
   */
  Drupal.tests.testswarm_forms_states = {
    getInfo: function() {
      return {
        name: 'States',
        description: 'Tests for States.',
        group: 'System'
      };
    },
    tests: {
      optional_checked: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the required marker is present on page load.
          QUnit.ok($('label[for="edit-text1"] abbr.form-required').length, Drupal.t('Required marker found'));
          // Check the checkbox to make text1 optional.
          $(':input[name="optionaltext1"]').click().trigger('change');
          // Check if the required marker was removed
          QUnit.ok(!$('label[for="edit-text1"] abbr.form-required').length, Drupal.t('Required marker removed'));
          // Uncheck the checkbox to make text1 required.
          $(':input[name="optionaltext1"]').click().trigger('change');
          // Check if the required marker was added
          QUnit.ok($('label[for="edit-text1"] abbr.form-required').length, Drupal.t('Required marker found'));
        };
      },
      disabled_checked: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the textfield is enabled on page load.
          QUnit.ok(!$('#edit-text1').attr('disabled'), Drupal.t('Textfield is not disbabled'));
          // Check the checkbox to make text1 disabled.
          $(':input[name="disabletext1"]').click().trigger('change');
          // Check if the textfield is disabled
          QUnit.ok($('#edit-text1').attr('disabled'), Drupal.t('Textfield is disabled'));
          // Uncheck the checkbox to make text1 enabled.
          $(':input[name="disabletext1"]').click().trigger('change');
          // Check if the textfield is enabled
          QUnit.ok(!$('#edit-text1').attr('disabled'), Drupal.t('Textfield is not disbabled'));
        };
      },
      invisible_checked: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the textfield is visible on page load.
          QUnit.ok($('#edit-text1').is(':visible'), Drupal.t('Textfield is visible'));
          // Check the checkbox to make text1 invisible.
          $(':input[name="hidetext1"]').click().trigger('change');
          // Check if the textfield is hidden
          QUnit.ok(!$('#edit-text1').is(':visible'), Drupal.t('Textfield is invisible'));
          // Uncheck the checkbox to make text1 visible.
          $(':input[name="hidetext1"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-text1').is(':visible'), Drupal.t('Textfield is visible'));
        };
      },
      visible_empty: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the text is visible on page load.
          QUnit.ok($('#edit-item1').is(':visible'), Drupal.t('Text is visible'));
          // Enter something in the textfield to make the text invisible.
          $('#edit-text1').val(Drupal.t('Not empty')).trigger('keyup');
          // Check if the text is hidden
          QUnit.ok(!$('#edit-item1').is(':visible'), Drupal.t('Text is invisible'));
          // Remove the data from the textfield to make the text visible.
          $('#edit-text1').val('').trigger('keyup');
          // Check if the text is visible
          QUnit.ok($('#edit-item1').is(':visible'), Drupal.t('Text is visible'));
        };
      },
      visible_filled: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the text is invisible on page load.
          QUnit.ok(!$('#edit-item2').is(':visible'), Drupal.t('Text is invisible'));
          // Enter something in the textfield to make the text visible.
          $('#edit-text1').val(Drupal.t('Not empty')).trigger('keyup');
          // Check if the text is visible
          QUnit.ok($('#edit-item2').is(':visible'), Drupal.t('Text is visible'));
          // Remove the data from the textfield to make the text invisible.
          $('#edit-text1').val('').trigger('keyup');
          // Check if the text is invisible
          QUnit.ok(!$('#edit-item2').is(':visible'), Drupal.t('Text is invisible'));
        };
      },
      visible_checked: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the textfield is invisible on page load.
          QUnit.ok(!$('#edit-text2').is(':visible'), Drupal.t('Textfield is invisible'));
          // Check the checkbox to make text2 visible.
          $(':input[name="showtext2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-text2').is(':visible'), Drupal.t('Textfield is visible'));
          // Uncheck the checkbox to make text2 invisible.
          $(':input[name="showtext2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok(!$('#edit-text2').is(':visible'), Drupal.t('Textfield is invisible'));
        };
      },
      enabled_checked: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check the checkbox to make text2 visible, makes it easier to work with.
          $(':input[name="showtext2"]').click().trigger('change');
          // Check if the textfield is disabled on page load.
          QUnit.ok($('#edit-text2').attr('disabled'), Drupal.t('Textfield is disbabled'));
          // Check the checkbox to make text1 enabled.
          $(':input[name="enabletext2"]').click().trigger('change');
          // Check if the textfield is enabled
          QUnit.ok(!$('#edit-text2').attr('disabled'), Drupal.t('Textfield is enabled'));
          // Uncheck the checkbox to make text1 enabled.
          $(':input[name="enabletext2"]').click().trigger('change');
          // Check if the textfield is enabled
          QUnit.ok($('#edit-text2').attr('disabled'), Drupal.t('Textfield is disbabled'));
          // Uncheck the checkbox to make text2 invisible again.
          $(':input[name="showtext2"]').click().trigger('change');
        };
      },
      required_checked: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check the checkbox to make text2 visible, makes it easier to work with.
          $(':input[name="showtext2"]').click().trigger('change');
          // Check if the required marker is present on page load.
          QUnit.ok(!$('label[for="edit-text2"] abbr.form-required').length, Drupal.t('Required marker not found'));
          // Check the checkbox to make text1 required.
          $(':input[name="requiredtext2"]').click().trigger('change');
          // Check if the required marker was added
          QUnit.ok($('label[for="edit-text2"] abbr.form-required').length, Drupal.t('Required marker found'));
          // Uncheck the checkbox to make text1 optional.
          $(':input[name="requiredtext2"]').click().trigger('change');
          // Check if the required marker was removed
          QUnit.ok(!$('label[for="edit-text2"] abbr.form-required').length, Drupal.t('Required marker not found'));
          // Uncheck the checkbox to make text2 invisible again.
          $(':input[name="showtext2"]').click().trigger('change');
        };
      },
      checked_filled: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the checkbox is unchecked on page load.
          QUnit.ok(!$('#edit-checkbox1').attr('checked'), Drupal.t('Checkbox not checked'));
          // Enter something in the textfield to check the checkbox.
          $(':input[name="checkcheckbox1"]').val(Drupal.t('This is some text')).trigger('keyup');
          // Check if the checkbox is checked
          QUnit.ok($('#edit-checkbox1').attr('checked'), Drupal.t('Checkbox checked'));
          // Empty the textfield to uncheck the checkbox
          $(':input[name="checkcheckbox1"]').val('').trigger('keyup');
          // Check if the checkbox is unchecked
          QUnit.ok(!$('#edit-checkbox1').attr('checked'), Drupal.t('Checkbox not checked'));
        };
      },
      unchecked_empty: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          // Check if the checkbox is checked on page load.
          QUnit.ok($('#edit-checkbox2').attr('checked'), Drupal.t('Checkbox checked'));
          // Empty the textfield to uncheck the checkbox
          $(':input[name="uncheckcheckbox2"]').val('').trigger('keyup');
          // Check if the checkbox is unchecked
          QUnit.ok(!$('#edit-checkbox2').attr('checked'), Drupal.t('Checkbox not checked'));
          // Enter something in the textfield to check the checkbox.
          $(':input[name="uncheckcheckbox2"]').val(Drupal.t('This is some text')).trigger('keyup');
          // Check if the checkbox is checked
          QUnit.ok($('#edit-checkbox2').attr('checked'), Drupal.t('Checkbox checked'));
        };
      },
      collapsed_value: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(4);
          var collapseDelay = 1000;
          // Check if the details is expanded on page load.
          QUnit.ok($('#edit-details1').find('div.details-wrapper').is(':visible'), Drupal.t('Details is initially expanded.'));
          // Enter 'collapse' in to the textfield to collapse the details
          $(':input[name="collapsedetails1"]').val('collapse').trigger('keyup');
          QUnit.stop();
          setTimeout(function() {
            // Check if the details is collapsed
            QUnit.ok($('#edit-details1').find('div.details-wrapper').is(':hidden'), Drupal.t('Details is collapsed.'));
            // Enter something else in to the textfield to expand the details
            $(':input[name="collapsedetails1"]').val(Drupal.t('This is some text')).trigger('keyup');
            setTimeout(function() {
              // Check if the details is expanded
              QUnit.ok($('#edit-details1').find('div.details-wrapper').is(':visible'), Drupal.t('Details is expanded.'));
              // Empty the textfield and check if the details is still expanded
              $(':input[name="collapsedetails1"]').val('').trigger('keyup');
              setTimeout(function() {
                // Check if the details is expanded
                QUnit.ok($('#edit-details1').find('div.details-wrapper').is(':visible'), Drupal.t('Details is expanded.'));
                QUnit.start();
              }, collapseDelay);
            }, collapseDelay);
          }, collapseDelay);
        };
      },
      expanded_value: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(4);
          var collapseDelay = 1000;
          // Check if the details is collapsed on page load.
          QUnit.ok($('#edit-details2').find('div.details-wrapper').is(':hidden'), Drupal.t('Details is initially collapsed.'));
          // Enter 'expand' in to the textfield to expand the details
          $(':input[name="expanddetails2"]').val('expand').trigger('keyup');
          QUnit.stop();
          setTimeout(function() {
            // Check if the details is expanded
            QUnit.ok($('#edit-details2').find('div.details-wrapper').is(':visible'), Drupal.t('Details is expanded.'));
            // Enter something else in to the textfield to collapse the details
            $(':input[name="expanddetails2"]').val(Drupal.t('This is some text')).trigger('keyup');
            setTimeout(function() {
              // Check if the details is collapsed
              QUnit.ok($('#edit-details2').find('div.details-wrapper').is(':hidden'), Drupal.t('Details is collapsed.'));
              // Empty the textfield and check if the details is still collapsed
              $(':input[name="expanddetails2"]').val('').trigger('keyup');
              setTimeout(function() {
                // Check if the details is expanded
                QUnit.ok($('#edit-details2').find('div.details-wrapper').is(':hidden'), Drupal.t('Details is collapsed.'));
                QUnit.start();
              }, collapseDelay);
            }, collapseDelay);
          }, collapseDelay);
        };
      },
      states_or: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(5);
          // Check if the textfield is hidden on page load.
          QUnit.ok(!$('#edit-or-textfield').is(':visible'), Drupal.t('Textfield hidden on page load'));
          // Check one checkbox
          $(':input[name="or_checkbox_1"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-or-textfield').is(':visible'), Drupal.t('Textfield visible'));
          // Uncheck the first checkbox and check the second checkbox
          $(':input[name="or_checkbox_1"]').click().trigger('change');
          $(':input[name="or_checkbox_2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-or-textfield').is(':visible'), Drupal.t('Textfield visible'));
          // Check the first checkbox so both checkboxes are checked
          $(':input[name="or_checkbox_1"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-or-textfield').is(':visible'), Drupal.t('Textfield visible'));
          // Uncheck both checkboxes.
          $(':input[name="or_checkbox_1"]').click().trigger('change');
          $(':input[name="or_checkbox_2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok(!$('#edit-or-textfield').is(':visible'), Drupal.t('Textfield invisible'));
        };
      },
      states_xor: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(5);
          // Check if the textfield is hidden on page load.
          QUnit.ok(!$('#edit-xor-textfield').is(':visible'), Drupal.t('Textfield hidden on page load'));
          // Check one checkbox
          $(':input[name="xor_checkbox_1"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-xor-textfield').is(':visible'), Drupal.t('Textfield visible'));
          // Uncheck the first checkbox and check the second checkbox
          $(':input[name="xor_checkbox_1"]').click().trigger('change');
          $(':input[name="xor_checkbox_2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-xor-textfield').is(':visible'), Drupal.t('Textfield visible'));
          // Check the first checkbox so both checkboxes are checked
          $(':input[name="xor_checkbox_1"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok(!$('#edit-or-textfield').is(':visible'), Drupal.t('Textfield invisible'));
          // Uncheck both checkboxes.
          $(':input[name="xor_checkbox_1"]').click().trigger('change');
          $(':input[name="xor_checkbox_2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok(!$('#edit-xor-textfield').is(':visible'), Drupal.t('Textfield invisible'));
        };
      },
      states_and: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(5);
          // Check if the textfield is hidden on page load.
          QUnit.ok(!$('#edit-and-textfield').is(':visible'), Drupal.t('Textfield hidden on page load'));
          // Check one checkbox
          $(':input[name="and_checkbox_1"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok(!$('#edit-and-textfield').is(':visible'), Drupal.t('Textfield invisible'));
          // Uncheck the first checkbox and check the second checkbox
          $(':input[name="and_checkbox_1"]').click().trigger('change');
          $(':input[name="and_checkbox_2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok(!$('#edit-and-textfield').is(':visible'), Drupal.t('Textfield invisible'));
          // Check the first checkbox so both checkboxes are checked
          $(':input[name="and_checkbox_1"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok($('#edit-and-textfield').is(':visible'), Drupal.t('Textfield visible'));
          // Uncheck both checkboxes.
          $(':input[name="and_checkbox_1"]').click().trigger('change');
          $(':input[name="and_checkbox_2"]').click().trigger('change');
          // Check if the textfield is visible
          QUnit.ok(!$('#edit-and-textfield').is(':visible'), Drupal.t('Textfield invisible'));
        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
