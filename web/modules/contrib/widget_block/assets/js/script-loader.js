(function ($, Drupal) {

  'use strict';

  /**
   * Declare a variable which represents the undefined primitive value.
   */
  var Undefined = void 0;

  /**
   * Get unique entries from specified array.
   *
   * @param {Array} array
   *   An array which should be filtered.
   *
   * @return {Array}
   *   An array which duplicate entries have been filtered.
   */
  function array_unique(array) {
    return array.filter(function (el, index, arr) {
      return index === arr.indexOf(el);
    });
  }

  /**
   * Define the Widget Block integration component.
   */
  Drupal.behaviors.widgetBlockIntegration = {

    /**
     * Register for Drupal attach behavior.
     *
     * @param {Object} context
     *   Context of the attachement behavior.
     * @param {Object} settings
     *   The available Drupal settings.
     */
    attach: function (context, settings) {
      // Check whether scripts have been added which should be loaded.
      if (settings.widget_block !== Undefined && settings.widget_block.script_loader !== Undefined) {
        // Get the script groups.
        var script_groups = settings.widget_block.script_loader;
        // Iterate through the different script sections.
        for (var script_group_name in script_groups) {
          // Check whether the script group is a valid property.
          if (script_groups.hasOwnProperty(script_group_name)) {
            // Get the scripts for given group name.
            var script_group = array_unique(script_groups[script_group_name]);
            // Iterate through the scripts which should be loaded.
            for (var i = 0; i < script_group.length; i++) {
              // Load and execute the script.
              $.getScript(script_group[i]);
            }
          }
        }
      }
    }
  };

})(jQuery, Drupal);
