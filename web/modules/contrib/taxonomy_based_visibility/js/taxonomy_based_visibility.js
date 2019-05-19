(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.AudienceVisibilityBehavior = {
    attach: function (context, settings) {
      // move description below labels. //
      $('.description').each(function () {
        $(this).parent().prepend(this);
      });
      $('.form-checkbox').click(function () {
        var id = this.id;
        id = id.split('--', 1)[0];
        var id_splitted = id.split('-');
        var id_trimmed = '';
        if (id_splitted.slice(-1)[0] === 'all') {
          id_trimmed = id.substring(0, id.lastIndexOf('-'));
          $('[id*=' + id_trimmed + ']').prop('checked', $(this).prop('checked'));
        }
        else {
          id_trimmed = id.substring(0, id.lastIndexOf('-'));
          $('[id*=' + id_trimmed + '-all]').prop('checked', false);
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
