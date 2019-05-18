/**
 * @file
 * Role inheritance mapping page behaviors.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Added edit link next to editable node redirect nodes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches links to editable menu items.
   */
  Drupal.behaviors.rn_link_edit = {
    attach: function (context) {

      // Find all editable menu items.
      $('li.js-redirect-node-can-edit').once('rn_link_edit').each(function () {
        var $this = $(this);
        var $menu_link = $this.children('a');
        // Get edit link from data attribute.
        var url = $this.data('rn-edit-url');

        // Build edit link template.
        var $edit_link_template = $('<a>')
          .append(
            $('<img>')
              .attr('src', drupalSettings.path.baseUrl + 'core/themes/stable/images/core/icons/ffffff/pencil.svg')
          )
          .attr('title', 'Click to edit this destination.')
          .addClass('redirect-node-edit-link js-redirect-node-edit-link')
          .attr('href', url);

        // Append key combo message to title.
        var title = '';
        if ($menu_link.length > 0) {
          title = $menu_link[0].title;
          if (title.length > 0) {
            title += ' ';
          }
        }
        title += '(Alt+Shift Click to edit)';

        // Bind keycombo click event, update title, and insert link
        $menu_link
          .bind('click', function (event) {
            if (event.altKey && event.shiftKey) {
              window.open(url, '_blank');
              return false;
            }
          })
          .attr('title', title)
          .prepend($edit_link_template);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
