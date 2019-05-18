(function ($, Drupal, drupalSettings) {
  /**
   * For all header tag, add more/less UI logic.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.inmailAllHeaders = {
    attach: function (context, settings) {
      $('.inmail-message__header', context).once('inmail-message').each(function () {
        var $this = $(this);
        var $thisMsg = $this.find('.inmail-message__header__all');
        $thisMsg.addClass('show-less');

        // Create a more/less link.
        var $link = $('<a href="#" class="inmail-message__more_link collapsed">' + Drupal.t('Expand header') + '</a>');
        $link.click(function () {
          if ($thisMsg.hasClass('show-less')) {
            $thisMsg.removeClass('show-less');
            $link.removeClass('collapsed');
            $link.addClass('expanded');
            $link.text(Drupal.t('Hide header'));
          }
          else {
            $thisMsg.addClass('show-less');
            $link.removeClass('expanded');
            $link.addClass('collapsed');
            $link.text(Drupal.t('Expand header'));
          }
        });
        $link.insertBefore($thisMsg);
      });
    }
  };

  /**
   * For body tag, adds tabs for selecting how the content will be displayed.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.bodyTabs = {
    attach: function (context, settings) {
      $('.inmail-message__body ul', context).once('inmail-message').each(function () {
        var $this = $(this);
        $this.parent().tabs();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
