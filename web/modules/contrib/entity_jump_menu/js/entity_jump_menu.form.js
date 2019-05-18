/**
 * @file
 * Defines the behavior of the Entity jump menu form.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Entity jump menu behavior for form.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.entityJumpMenuForm = {
    attach: function (context) {
      // Select the entity id when the textfield receives focus.
      $('#entity-jump-menu-form [data-drupal-selector="edit-entity-id"]', context).focus(
        function () {
          $(this).select();
        }
      );
    }
  };

}(jQuery, Drupal, drupalSettings));
