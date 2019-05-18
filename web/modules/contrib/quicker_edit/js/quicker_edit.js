(function ($) {
  /**
   * Triggers the corresponding quickedit contextual link for a quickedit-field,
   * that was dblclicked.
   */
  Drupal.behaviors.quickerEditTrigger = {
    attach: function (context, settings) {
      $('[data-quickedit-field-id]').on('dblclick', function () {
        var $fieldElement = $(this);

        // Fetch the quickedit field id of the clicked element.
        var fieldID = $fieldElement.data('quickedit-field-id');

        // Extract the entity id.
        var entityID = fieldID.split('/').slice(0, 2).join('/');

        // Find the entity element.
        var $entityElement = $('[data-quickedit-entity-id="' + entityID + '"]');

        // Enable the Quick Edit by programatically triggering the contextual link.
        $entityElement.find('[data-contextual-id] > button').trigger('click');
        $entityElement.find('[data-contextual-id] .quickedit > a').trigger('click');

        // Programmatically trigger a click once again on the field, to start
        // editing the field. We need a timeout, to make sure the contextual
        // trigger callback chain was finished successfully.
        setTimeout(function () {
          $fieldElement.trigger('click');
        }, 100);
      });
    }
  };
})(jQuery);
