/**
 * @file
 * File to initialize accordion.
 */

(function ($, Drupal, drupalSettings){
    $(".entity-ref-tab-formatter-accordion").accordion({
        collapsible: true,
        active: false
    });
})(jQuery, Drupal, drupalSettings);
