(function($) {
  Drupal.behaviors.eqsf = {
    attach: function(context, settings) {
      var $eqsf_selector = $('select.eqsf-selector[name$=\'select]\']');
      $eqsf_selector.change(function() {
        Drupal.behaviors.eqsf.change($(this), $(this).val());
      });
      $eqsf_selector.trigger('change');
    },
    change: function($item, $selector) {
      if ($selector !== 'empty') {
        $item.parent().siblings('.eqsf-field-wrapper').show();
      } else {
        $item.parent().siblings('.eqsf-field-wrapper').hide();
      }
    },
  };
  $(function() {
    //Drupal.behaviors.entityqueue-scheduler.load();
  });
}(jQuery));
