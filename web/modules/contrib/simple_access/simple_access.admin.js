(function ($) {

Drupal.behaviors.simpleAccessFieldsetSummaries = {
  attach: function (context) {
    $(context).find('details#edit-simple-access').drupalSetSummary(function (context) {
      if (!$('.form-checkbox:checked', context).length) {
        return Drupal.t('No restrictions');
      }
      else {
        return Drupal.t('Restricted access');
      }
    });
  }
};

})(jQuery);
