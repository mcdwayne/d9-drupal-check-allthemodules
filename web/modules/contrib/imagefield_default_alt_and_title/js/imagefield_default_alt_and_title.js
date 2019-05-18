/**
 * @file
 * JavaScript behaviors for Imagefield default alt and title.
 */

(function ($) {

  'use strict';

  /**
   * Init Imagefield default alt and title.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the Imagefield default alt and title.
   */
  Drupal.behaviors.initImgAltTitle = {
    attach: function (context) {
      // Set default status and default value.
      $(context).find('.image-widget-data input.form-text').once('initImgAltTitle').each(function () {
        var $this = $(this);
        var titleVal = $('.field--name-title input, .field--name-name input').val();
        $this.data('imgAltTitle', {'needAdd': !$this.val()});

        // Set default value if need.
        if (titleVal && $this.data('imgAltTitle')['needAdd']) {
          $this.val(titleVal);
        }
      });

      // Switch status.
      $(context).find('.image-widget-data input.form-text').on('change.imgAltTitle', function (e) {
        $(e.target).data('imgAltTitle', {'needAdd': false});
      });

      // Set value.
      $(context).find('.field--name-title input, .field--name-name input').on('change.imgAltTitle', function (e) {
        $('.image-widget-data input.form-text').each(function () {
          var $this = $(this);
          if ($this.data('imgAltTitle')['needAdd']) {
            $this.val($(e.target).val());
          }
        });
      });
    }
  };

})(jQuery);
