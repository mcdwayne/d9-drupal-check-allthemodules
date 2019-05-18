(function($, Drupal) {
  /**
   * Behaviors for setting summaries on fragment form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviors on content type edit forms.
   */
  Drupal.behaviors.fragmentForm = {
    attach: function (context) {
      var $context = $(context);

      $context.find('.fragment-form-publishing-status').drupalSetSummary(function (context) {
        var $statusContext = $(context);
        var statusCheckbox = $statusContext.find('#edit-status-value');

        if (statusCheckbox.is(':checked')) {
          return Drupal.t('Published');
        }

        return Drupal.t('Not published');
      });

      $context.find('.fragment-form-authoring-information').drupalSetSummary(function (context) {
        var $authorContext = $(context);
        var authorField = $authorContext.find('input');

        if (authorField.val().length) {
          return authorField.val();
        }
      });
    }
  }
})(jQuery, Drupal);
