(function ($) {
  Drupal.behaviors.MMFieldsPerms = {
    attach: function (context) {
      $('.mm-fields-perms', context)
        .once('mm-fields-perms')
        .each(function () {
          var outerDiv = this;
          if ($('#edit-mm-fields-perms-use-defaults')
            .click(function() {
              $(outerDiv).slideToggle('fast');
              $('#edit-no-save').val($(this).is(':checked') ? 1 : 0);
              $('#message').remove();
            }).is(':not(:checked)')) {
              $(this).show();  // init
              $('#edit-no-save').val(0);
            }
        });
      }
  };
})(jQuery);