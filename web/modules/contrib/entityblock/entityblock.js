(function ($) {

  "use strict";

  /**
   * Automatically fill in an entity title, if possible.
   */
  Drupal.behaviors.entityBlockAutomaticTitle = {
    attach: function (context) {
      var $context = $(context);
      $context.find('.field--type-entityblock fieldset, .field--type-entityblock .field-multiple-table tr').each(function () {
        var $this = $(this);
        var $checkbox = $this.find('.entityblock-enabled');
        var $entityblock_title = $this.find('.entityblock-title');

        // Entity title (currently only nodes).
        var $title = $this.closest('form').find('.entity-title-field input');

        // Bail out if we do not have all required fields.
        if (!($checkbox.length && $entityblock_title.length && $title.length)) {
          return;
        }
        // If there is a link title already, mark it as overridden. The user expects
        // that toggling the checkbox twice will take over the node's title.
        if ($checkbox.is(':checked') && $entityblock_title.val().length) {
          $entityblock_title.data('entityBlockAutomaticTitleOveridden', true);
        }
        // Whenever the value is changed manually, disable this behavior.
        $entityblock_title.on('keyup', function () {
          $entityblock_title.data('entityBlockAutomaticTitleOveridden', true);
        });
        // Global trigger on checkbox (do not fill-in a value when disabled).
        $checkbox.on('change', function () {
          if ($checkbox.is(':checked')) {
            if (!$entityblock_title.data('entityBlockAutomaticTitleOveridden')) {
              $entityblock_title.val($title.val());
            }
          }
          else {
            $entityblock_title.val('');
            $entityblock_title.removeData('entityBlockAutomaticTitleOveridden');
          }
          $checkbox.closest('.vertical-tabs-pane').trigger('summaryUpdated');
          $checkbox.trigger('formUpdated');
        });
        // Take over any title change.
        $title.on('keyup', function () {
          if (!$entityblock_title.data('entityBlockAutomaticTitleOveridden') && $checkbox.is(':checked')) {
            $entityblock_title.val($title.val());
            $entityblock_title.val($title.val()).trigger('formUpdated');
          }
        });
      });
    }
  };

})(jQuery);
