/**
 * @file
 * Embeds Klipfolio widgets.
 */

(function ($) {
  Drupal.behaviors.klipfolioEmbed = {
    attach: function (context, settings) {
      $("[data-klipfolio-id]", context).each(function () {
        var $id = $(this).attr('data-klipfolio-id');
        var $theme = $(this).attr('data-klipfolio-theme');
        var $width = parseInt($(this).attr('data-klipfolio-width'));
        var $title = $(this).attr('data-klipfolio-title');
        if (KF && KF.embed) {
          var $options = {
            settings: {}
          };
          $options.profile = $id;
          if ($width) {
            $options.settings.width = $width;
          }
          if ($theme) {
            $options.settings.theme = $theme.toLowerCase();
          }
          $options.title = $title ? $title : "";
          KF.embed.embedKlip($options);
        }
      })
    }
  };
})(jQuery, Drupal);
