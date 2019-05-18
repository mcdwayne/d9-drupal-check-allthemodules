(function ($, Drupal) {
  Drupal.behaviors.seasideAdminToolbar = {
    attach: function (context, settings) {
      var val = $.cookie('seaside-admin-toolbar-size');
      var new_val;

      if (val == 'seaside-admin-toolbar-small') {
        $('body', context).addClass('seaside-admin-toolbar-small');
      }

      $('.toolbar-icon-switch-size', context).click(function () {
        $('body', context).toggleClass('seaside-admin-toolbar-small');
        (val == 'seaside-admin-toolbar-small') ? new_val = '' : new_val = 'seaside-admin-toolbar-small';
        $.cookie('seaside-admin-toolbar-size', new_val);
        return false;
      });

      $

    }
  };
})(jQuery, Drupal);