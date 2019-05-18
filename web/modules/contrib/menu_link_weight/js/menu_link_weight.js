/**
 * @file
 * Menu Link Weight Javascript functionality.
 *
 * @see menu_ui.js
 */

(function ($) {
  /**
   * Automatically update the current link title in the menu link weight list.
   */
  Drupal.behaviors.menuLinkWeightAutomaticTitle = {
    attach: function (context) {
      var $context = $(context);
      $context.find('#menu-link-weight-wrapper').each(function () {
        var $this = $(this);
        var $checkbox = $this.parent().find('input.menu-link-enabled');
        var $link_title = $this.parent().find('input.menu-link-title');
        // Try to find menu settings widget elements as well as a 'title' field
        // in the form, but play nicely with user permissions and form
        // alterations.
        var $current_selection = $this.find('.menu-link-weight-link-current');
        var $title = $this.closest('form').find('.js-form-item-title-0-value input');

        // Take over the title of the link.
        $current_selection.html($link_title.val().substring(0, 30));
        $link_title.on('keyup', function () {
          $current_selection.html($link_title.val().substring(0, 30));
        });
        // Also update on node title change, as this may update the link title.
        $title.on('keyup', function() {
          if ($checkbox.is(':checked') && !$link_title.data('menuLinkAutomaticTitleOverridden')) {
            $current_selection.html($title.val().substring(0, 30));
          }
        });
        // Checking and unchecking the checkbox may also update the link title.
        $checkbox.on('change', function() {
          if ($checkbox.is(':checked') && !$link_title.data('menuLinkAutomaticTitleOverridden')) {
            $current_selection.html($title.val().substring(0, 30));
          }
        });
      });
    }
  };

})(jQuery);
