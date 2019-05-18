(function($, Drupal) {
  'use strict';

  var INSERT_TYPE_MEDIA = 'media';

  Drupal.behaviors.insert_media = {};

  Drupal.behaviors.insert_media.attach = function(context) {
    $('.insert', context).each(function() {
      var $insert = $(this);

      if ($insert.data('insert-type') !== INSERT_TYPE_MEDIA) {
        return;
      }

      var $inserter = $($insert.data('insert'));

      // Be sure to have the event listener attached only once.
      $inserter.off('.insert_media').on('insert.insert_media', function() {
        var $viewModes = $insert.find('[name$="[view_modes]"]');
        var viewMode = $viewModes.val();
        return $insert.find('[name="template[' + viewMode + ']"]').val();
      });
    });
  }

})(jQuery, Drupal);
