
/**
 * @file
 * Javascript behaviors for the RegEx module.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.regExTester = {
    attach: function (context) {
      $('form.regex-tester-form:not(.regex-tester-processed)', context).each(function () {
        var $form = $(this),
          selectorChange = [
            ':input[name="function"]',
            ':input[name="flags[javascript_multiline]"]',
            ':input[name="flags[javascript_global]"]',
            ':input[name="flags[javascript_ignorecase]"]'
          ],
          selectorKeyUp = [
            ':input[name="pattern"]',
            ':input[name="replacement"]',
            ':input[name="subject"]'
          ];

        $form.addClass('regex-tester-processed');

        $(':input[name="function"]', $form).change(Drupal.regExTester.functionOnChange);
        $(selectorChange.join(', '), $form).change(Drupal.regExTester.jsRelatedOnChange);
        $(selectorKeyUp.join(', '), $form).keyup(Drupal.regExTester.jsRelatedOnChange);

        Drupal.regExTester.toggleReplacementVisibility($form);
        Drupal.regExTester.toggleFlags($form);
      });
    }
  };

  Drupal.regExTester = Drupal.regExTester || {};

  Drupal.regExTester.functionOnChange = function () {
    var $form = $($(this).parents('form').get(0));
    Drupal.regExTester.toggleReplacementVisibility($form);
    Drupal.regExTester.toggleFlags($form);
  };

  Drupal.regExTester.jsRelatedOnChange = function () {
    var $form = $($(this).parents('form').get(0));
    Drupal.regExTester.executeJavaScript($form);
  };

  Drupal.regExTester.toggleReplacementVisibility = function ($form) {
    var fun = $(':input[name="function"]', $form).val(),
      selector = '.form-item-replacement';

    if (fun.match('_replace') !== null) {
      $(selector, $form).show();
    } else {
      $(selector, $form).hide();
    }
  };

  Drupal.regExTester.toggleFlags = function ($form) {
    var fun = $(':input[name="function"]', $form).val(),
      flags = [
        'PREG_PATTERN_ORDER',
        'PREG_SET_ORDER',
        'PREG_OFFSET_CAPTURE',
        'PREG_SPLIT_NO_EMPTY',
        'PREG_SPLIT_DELIM_CAPTURE',
        'PREG_SPLIT_OFFSET_CAPTURE',
        'mb_ereg_replace_i',
        'mb_ereg_replace_x',
        'mb_ereg_replace_m',
        'mb_ereg_replace_p',
        'mb_ereg_replace_e',
        'javascript_global',
        'javascript_ignorecase',
        'javascript_multiline'
      ],
      flagKey,
      flag = null,
      flagsToShow = [],
      $flagElement = null,
      selector = null;

    if (fun.match('preg_match')) {
      flagsToShow.push(
        'PREG_PATTERN_ORDER',
        'PREG_SET_ORDER',
        'PREG_OFFSET_CAPTURE'
      );
    } else if (fun.match('preg_split')) {
      flagsToShow.push(
        'PREG_SPLIT_DELIM_CAPTURE',
        'PREG_SPLIT_NO_EMPTY',
        'PREG_SPLIT_OFFSET_CAPTURE'
      );
    } else if (fun.match('mb_ereg_replace|mb_eregi_replace')) {
      flagsToShow.push(
        'mb_ereg_replace_i',
        'mb_ereg_replace_x',
        'mb_ereg_replace_m',
        'mb_ereg_replace_p',
        'mb_ereg_replace_e'
      );
    } else if (fun.match('javascript')) {
      flagsToShow.push(
        'javascript_global',
        'javascript_ignorecase',
        'javascript_multiline'
      );
    }

    for (flagKey in flags) {
      if (flags.hasOwnProperty(flagKey)) {
        flag = flags[flagKey];
        $flagElement = $(':input[name="flags[' + flag + ']"]').parent();

        if (flagsToShow.indexOf(flag) > -1) {
          $flagElement.show();
        } else {
          $flagElement.hide();
        }

        selector = '.form-item-flags';
        if (flagsToShow.length) {
          $(selector, $form).show();
        } else {
          $(selector, $form).hide();
        }
      }
    }
  };

  Drupal.regExTester.executeJavaScript = function ($form) {
    var fun = $(':input[name="function"]', $form).val();

    if (fun.match('javascript') === null) {
      return;
    }

    var pattern = $(':input[name="pattern"]', $form).val(),
      replacement = $(':input[name="replacement"]', $form).val(),
      subject = $(':input[name="subject"]', $form).val(),
      modifiers = '',
      $messages = $('.regex-messages', $form),
      output = '';

    if ($(':input[name="flags[javascript_global]"]', $form).is(':checked')) {
      modifiers = modifiers + 'g';
    }

    if ($(':input[name="flags[javascript_ignorecase]"]', $form).is(':checked')) {
      modifiers = modifiers + 'i';
    }

    if ($(':input[name="flags[javascript_multiline]"]', $form).is(':checked')) {
      modifiers = modifiers + 'm';
    }

    if (pattern.match(/^[\s\t\r\n]*$/) || subject.match(/^[\s\t\r\n]*$/)) {
      $messages.html('');

      return;
    }

    switch (fun) {
      case 'javascript_exec':
        var regexp,
          errors = [];

        try {
          regexp = new RegExp(pattern, modifiers);
        }
        catch (e) {
          errors.push(e.message);
        }

        output += '<div class="messages">';
        if (errors.length) {
          output += '<div class="message error">' + errors[0] + '</div>';
        } else {
          var matches = regexp.exec(subject);
          while (matches !== null) {
            $.each(matches, function (key, value) {
              output += '<div class="message status">' + key + ' = ' + value + '</div>';
            });
            matches = regexp.exec(RegExp.rightContext);
          }

          if (output.length === 0) {
            output += '<div class="messages warning">' + Drupal.t('No match', {}, {}) + '</div>';
          }
        }
        output += '</div>';

        break;

      case 'javascript_search':
        break;

      case 'javascript_replace':
        break;

      case 'javascript_split':
        break;

    }

    $messages.html(output);
  };

})(jQuery, Drupal);
