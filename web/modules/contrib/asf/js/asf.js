(function ($) {
  Drupal.behaviors.asf = {
    attach: function (context, settings) {
      $('.asf-inline').parent('.form-item').addClass('asf-inline');
      $('.asf-clear').parent('.form-item').addClass('asf-clear');
      $('.iteration_toggler').change(function() {
        Drupal.behaviors.asf.change($(this),$(this).val());
      });
      $('.iteration_toggler').trigger('change');
      //Drupal.behaviors.asf.change($('.iteration_toggler'),$('.iteration_toggler').val());

    },
    change: function($item, item_class) {
      var $parent = $item.parent().parent();
      $parent.find('.asf-datetime-wrapper').hide();
      $parent.children('.form-item').hide();
      $parent.find('.iteration_toggler').parent().show();
      $parent.find('.' + item_class).parents('.asf-datetime-wrapper').show();
      $parent.find('.' + item_class).parents('.form-item').show();
    }
  };
  $(function () {
    //Drupal.behaviors.asf.load();
  });
}(jQuery));