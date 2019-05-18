(function ($) {

  'use strict';

  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * This script adds the required and error classes to the table wrapper.
   */
  Drupal.behaviors.fieldGroupTable = {
    attach: function (context) {
      $(context).find('.field-group-table').once('field-group-table').each(function () {
        var $this = $(this);

        if ($this.is('.required-fields') && ($this.find('[required]').length > 0 || $this.find('.form-required').length > 0)) {
          $('summary', $this).first().addClass('form-required');
        }
      });
    }
  };

})(jQuery);
