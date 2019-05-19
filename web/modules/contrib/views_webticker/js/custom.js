(function ($) {
    Drupal.behaviors.webtickerBehavior = {
        attach: function (context, settings) {

            var $this_settings = $.parseJSON(settings.settings);
            $('#id_views_webticker').webTicker(
                $this_settings
            );
        }
    };

})(jQuery);