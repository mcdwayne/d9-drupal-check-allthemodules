(function ($) {
    Drupal.behaviors.quranTrans = {
        attach: function (context, settings) {
            $('#edit-trans').change(function() {
                $(this).closest('form').submit();
            });
        }
    };
})(jQuery);