(function ($) {

Drupal.behaviors.privateFieldsetSummaries = {
  attach: function (context) {
    $('fieldset.node-form-private', context).drupalSetSummary(function (context) {
      var checkbox = $('.form-item-private input', context);
      if (checkbox.is(':checked')) {
        return Drupal.t('Private');
      }

      return Drupal.t('Not private');
    });
  }
};

})(jQuery);
