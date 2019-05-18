/**
 * @file
 * Paragraphs Previewer handling.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  var previewer = {};

  /**
   * Reset extended dialog properties.
   *
   * @param {Drupal.dialog} dialog
   *   The dialog object
   */
  previewer.dialogReset = function (dialog) {
    dialog.isLoading = false;
    dialog.loadedCount = 0;
    dialog.loadableCount = 0;
  };

  /**
   * Set the initial dialog settings based on client side information.
   *
   * @param {Drupal.dialog} dialog
   *   The dialog object
   * @param {jQuery} $element
   *   The element jQuery object.
   * @param {Object} settings
   *   Optional The combined dialog settings.
   */
  previewer.dialogInitialize = function (dialog, $element, settings) {
    dialog.isLoading = true;
    dialog.loadedCount = 0;
    dialog.loadableCount = 0;

    var windowHeight = $(window).height();
    if (windowHeight > 0) {
      // Set maxHeight based on calculated pixels.
      // Setting a relative value (100%) server side did not allow scrolling
      // within the modal.
      settings.maxHeight = windowHeight;
    }
  };

  /**
   * Set the dialog settings based on the content.
   *
   * @param {Drupal.dialog} dialog
   *   The dialog object
   * @param {jQuery} $element
   *   The element jQuery object.
   * @param {Object} settings
   *   The combined dialog settings.
   */
  previewer.dialogUpdateForContent = function (dialog, $element, settings) {
    if (!dialog.isLoading && settings.maxHeight) {
      var $content = $('.paragraphs-previewer-iframe', $element).contents().find('body');

      if ($content.length) {
        // Fit content.
        var contentHeight = $content.outerHeight();
        var modalContentContainerHeight = $element.height();

        var fitHeight;
        if (contentHeight < modalContentContainerHeight) {
          var modalHeight = $element.parent().outerHeight();
          var modalNonContentHeight = modalHeight - modalContentContainerHeight;
          fitHeight = contentHeight + modalNonContentHeight;
        }
        else {
          fitHeight = 0.98 * settings.maxHeight;
        }

        // Set to the new height bounded by min and max.
        var newHeight = fitHeight;
        if (fitHeight < settings.minHeight) {
          newHeight = settings.minHeight;
        }
        else if (fitHeight > settings.maxHeight) {
          newHeight = settings.maxHeight;
        }
        settings.height = newHeight;
        $element.dialog('option', 'height', settings.height);
      }
    }
  };

  /**
   * Determine if an dialog event is a previewer dialog.
   *
   * @param {Drupal.dialog} dialog
   *   The dialog object
   * @param {jQuery} $element
   *   The element jQuery object.
   * @param {Object} settings
   *   Optional. The combined dialog settings.
   *
   * @return {Boolean}
   *   TRUE if the dialog is a previewer dialog.
   */
  previewer.dialogIsPreviewer = function (dialog, $element, settings) {
    var dialogClass = '';
    if (typeof settings === 'object' && ('dialogClass' in settings)) {
      dialogClass = settings.dialogClass;
    }
    else if ($element.length && !!$element.dialog) {
      dialogClass = $element.dialog('option', 'dialogClass');
    }

    return dialogClass && dialogClass.indexOf('dialog-preview-paragraph') > -1;
  };

  /**
   * Disable redirect links and submit buttons in preview modal.
   *
   * To prevent users accidentally clicking on them.
   *
   * @param {Drupal.dialog} dialog
   *   The dialog object
   * @param {jQuery} $element
   *   The element jQuery object.
   *
   * @return {void}
   */
  previewer.disableLinks = function (dialog, $element) {
    $element.find('a:not([href^="#"]), input[type="submit"], button[type="submit"]').on('click', function (e) {
      e.preventDefault();
      return false;
    });
  };

  // Dialog listeners.
  Drupal.behaviors.previewParagraph = {
    attach: function (context, settings) {
      $(window, context).once('preview-paragraph').on({
        'dialog:beforecreate': function (event, dialog, $element, settings) {
          if (previewer.dialogIsPreviewer(dialog, $element, settings)) {
            // Initialize the dialog.
            previewer.dialogInitialize(dialog, $element, settings);
          }
        },
        'dialog:aftercreate': function (event, dialog, $element, settings) {
          if (previewer.dialogIsPreviewer(dialog, $element, settings)) {
            previewer.disableLinks(dialog, $element);

            // Set body class to disable scrolling.
            $('body').addClass('dialog-active');
          }
        },
        'dialog:afterclose': function (event, dialog, $element) {
          if (previewer.dialogIsPreviewer(dialog, $element)) {
            // Reset extended properties.
            previewer.dialogReset(dialog);

            // Remove body class to enable scrolling in the parent window.
            $('body').removeClass('dialog-active');
          }
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
