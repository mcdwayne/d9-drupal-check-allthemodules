/**
 * @file
 * Contains the definition of the behaviour JscrollPane.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

    /**
     * Attaches the JscrollPane Behaviour.
     */
  Drupal.behaviors.jscrollpane = {
    attach: function (context, settings) {

      if (typeof drupalSettings.jscrollpane != 'undefined') {
        var elements = drupalSettings.jscrollpane.selectors;

        jQuery(elements).jScrollPane({
                // Global Settings.
          autoReinitialise: drupalSettings.jscrollpane.properties.autoReinitialise,
          showArrows: drupalSettings.jscrollpane.properties.showArrows,
          arrowScrollOnHover: drupalSettings.jscrollpane.properties.arrowScrollOnHover,
          mouseWheelSpeed: parseInt(drupalSettings.jscrollpane.properties.mouseWheelSpeed),
          arrowButtonSpeed: parseInt(drupalSettings.jscrollpane.properties.arrowButtonSpeed),
                // Vertcal Bar Settings.
          verticalGutter: parseInt(drupalSettings.jscrollpane.properties.verticalGutter),
          verticalDragMinHeight: parseInt(drupalSettings.jscrollpane.properties.verticalDragMinHeight),
          verticalDragMaxHeight: parseInt(drupalSettings.jscrollpane.properties.verticalDragMaxHeight),
          verticalDragMinWidth: parseInt(drupalSettings.jscrollpane.properties.verticalDragMinWidth),
          verticalDragMaxWidth: parseInt(drupalSettings.jscrollpane.properties.verticalDragMaxWidth),
                // Horizontal Bar Settings.
          horizontialGutter: parseInt(drupalSettings.jscrollpane.properties.horizontialGutter),
          horizontialDragMinHeight: parseInt(drupalSettings.jscrollpane.properties.horizontialDragMinHeight),
          horizontialDragMaxHeight: parseInt(drupalSettings.jscrollpane.properties.horizontialDragMaxHeight),
          horizontialDragMinWidth: parseInt(drupalSettings.jscrollpane.properties.horizontialDragMinWidth),
          horizontialDragMaxWidth: parseInt(drupalSettings.jscrollpane.properties.horizontialDragMaxWidth)
        });
      }

      jQuery('.scroll-pane').jScrollPane({
        showArrows: true
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
