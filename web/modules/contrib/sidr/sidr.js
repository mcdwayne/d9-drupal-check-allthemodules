/**
 * @file
 * Sidr behaviors.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Sidr triggers.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sidr_trigger = {
    attach: function (context, drupalSettings) {
      // Initialize all sidr triggers.
      $(context)
        .find('.js-sidr-trigger')
        .once('sidr-trigger')
        .each(function () {
          var $trigger = $(this);

          // Prepare options.
          var options = $trigger.attr('data-sidr-options') || '{}';
          options = $.parseJSON(options);

          // Determine target.
          var $target = $(options.source);
          if ($target.length === 0) {
            Drupal.throwError('Target element not found: ' + options.source);
            return;
          }

          // Handle Sidr open.
          options.onOpenEnd = function () {
            var sidr = this;

            // Unhide the Sidr for screen-readers.
            sidr.item.attr('aria-hidden', 'false');

            // Focus the first focusable element in the Sidr when opened.
            //
            // TODO: Remove this when it is added to Sidr.
            // https://github.com/artberri/sidr/issues/289
            var $target = this.item.find(':input, a').filter(':visible').first();
            $target.focus();

            // Mark all triggers as active.
            $('[aria-controls="' + sidr.name + '"]')
              .addClass('is-active')
              .attr('aria-expanded', true);
          };

          // Handle Sidr close.
          options.onCloseEnd = function () {
            var sidr = this;

            // Hide the Sidr for screen-readers.
            sidr.item.attr('aria-hidden', 'true');

            // Mark all triggers as inactive.
            $('[aria-controls="' + sidr.name + '"]')
              .removeClass('is-active')
              .attr('aria-expanded', false);
          };

          // Bind Sidr plugin.
          $trigger.sidr(options);
          var sidrId = $trigger.data('sidr');
          var $sidr = $('#' + sidrId);

          // Set initial 'aria' attributes for the trigger.
          $trigger
            .attr('aria-controls', sidrId)
            .attr('aria-expanded', false);

          // Hide Sidr for screen-readers.
          $sidr.attr('aria-hidden', 'true');

          // Populate the Sidr with original DOM elements instead of copying
          // their inner HTML. This removes duplicate IDs and preserves event
          // handlers attached to the source elements.
          if (options.nocopy && $target.length > 0) {
            var $inner = $('<div class="sidr-inner"></div>').append($target);
            $sidr.html($inner);
          }

          // Attach behaviors to Sidr contents.
          Drupal.attachBehaviors($sidr[0], drupalSettings);

          // Remember the last used trigger. When "escape" is pressed to to
          // an open Sidr, we will bring back the focus on this trigger.
          $trigger.click(function () {
            $(document.body).data('sidr.lastTrigger', this);
          });
        });

      // Ensure Sidr close on unfocus.
      //
      // TODO: Remove this if and when it is added to Sidr.
      // https://github.com/artberri/sidr/issues/338
      $(document.body).once('sidr-unfocus')
        .bind('click keyup', function (e) {
          // If no sidr is currently open, do nothing.
          var openSidr = jQuery.sidr('status').opened;
          if (!openSidr) {
            return;
          }

          // Determine if the Sidr is going out of focus.
          var isBlur = true;

          // If the event is coming from within a Sidr.
          if ($(e.target).closest('.sidr').length !== 0) {
            isBlur = false;
          }

          // If the event is coming from within a trigger.
          if ($(e.target).closest('.js-sidr-trigger').length !== 0) {
            isBlur = false;
          }

          // If the "escape" key was pressed.
          if (e.type === 'keyup' && e.keyCode == 27) {
            isBlur = true;
          }

          // Close the Sidr if it is not in focus.
          if (isBlur) {
            $.sidr('close', openSidr);

            // If the user triggered the blur using the keyboard.
            if (e.type === 'keyup') {
              // Revert focus back to the last used trigger.
              // This handles "escape" and "shift + tab" press.
              var lastTrigger = $(document.body).data('sidr.lastTrigger');
              if (lastTrigger) {
                $(lastTrigger).focus();
              }
            }
          }
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
