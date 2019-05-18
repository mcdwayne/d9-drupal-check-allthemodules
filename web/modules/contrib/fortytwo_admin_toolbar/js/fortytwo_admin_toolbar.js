(function ($, Drupal) {
  Drupal.behaviors.fortytwoAdminToolbar = {
    attach: function (context, settings) {
      var val = $.cookie('fortytwo-admin-toolbar-size');
      var new_val;

      if (val == 'fortytwo-admin-toolbar-small') {
        $('body', context).addClass('fortytwo-admin-toolbar-small');
      }

      $('.toolbar-icon-switch-size', context).click(function () {
        $('body', context).toggleClass('fortytwo-admin-toolbar-small');
        (val == 'fortytwo-admin-toolbar-small') ? new_val = '' : new_val = 'fortytwo-admin-toolbar-small';
        $.cookie('fortytwo-admin-toolbar-size', new_val);
        return false;
      });

    }
  };
})(jQuery, Drupal);