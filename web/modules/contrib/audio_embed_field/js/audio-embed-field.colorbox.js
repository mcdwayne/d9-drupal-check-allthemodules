/**
 * @file
 * The audio_embed_field colorbox integration.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.audio_embed_field_colorbox = {
    attach: function (context, settings) {
      $('.audio-embed-field-launch-modal', context).once().click(function (e) {
        // Allow the thumbnail that launches the modal to link to other places
        // such as audio URL, so if the modal is sidestepped things degrade
        // gracefully.
        e.preventDefault();
        $.colorbox($.extend(settings.colorbox, {html: $(this).data('audio-embed-field-modal')}));
      });
    }
  };
})(jQuery);
