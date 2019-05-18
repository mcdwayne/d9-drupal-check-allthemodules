/**
 * @file
 * jstree_menu.js javascript file.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.jstree_menu = {
    attach: function (context, settings) {

     var params = {};

      try {
        Drupal.jstree_menu.variables.theme = Drupal.jstree_menu.removeDoubleQuotes(drupalSettings.jstree_menu.theme);
        Drupal.jstree_menu.variables.rem_border = Drupal.jstree_menu.removeDoubleQuotes(drupalSettings.jstree_menu.rem_border);
        Drupal.jstree_menu.variables.height = Drupal.jstree_menu.removeDoubleQuotes(drupalSettings.jstree_menu.height);
      }
      catch (err) {
        // Default values if something goes wrong.
        Drupal.jstree_menu.variables.theme = 'default';
        Drupal.jstree_menu.variables.rem_border = 0;
        Drupal.jstree_menu.variables.height = 'auto';
      }

      if (Drupal.jstree_menu.variables.theme == 'proton') {
        params = {
          'core': {
            'themes': {
              'name': Drupal.jstree_menu.variables.theme,
            }
          }
        };
      }

      $('[id^=menu-jstree-]').jstree(params);
      $('[id^=menu-jstree-]').css('height', Drupal.jstree_menu.variables.height);

      if (Drupal.jstree_menu.variables.rem_border == 1) {
        $('[id^=menu-jstree-]').addClass('rem-border');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);

jQuery(document).ready(function() {
  // When a link is clicked, we redirect to href.
  jQuery('[id^=menu-jstree-]').on("select_node.jstree", function (e, data) {
    var href = data.node.a_attr.href;
    document.location.href = href;
  });
});

/**
 * Attach utility functions.
 */
Drupal.jstree_menu = Drupal.jstree_menu || {};

/**
 * Initialize variable array.
 */
Drupal.jstree_menu.variables = {theme:'default', rem_border: 0, height: 'auto'};

/**
 * Converts string from drupal settings variable to integers.
 */
Drupal.jstree_menu.removeDoubleQuotes = function (configuration) {

  var str = String(configuration);

  // Erase first " character.
  str = str.replace('"', "");
  // Erase second " character.
  str = str.replace('"', "");

  return str;
};
