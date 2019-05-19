/**
 * @file
 * Drupal's batch API.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches the batch behavior to progress bars.
   *
   * @type {Drupal~behavior}
   */
   Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
     

        jQuery('#simple_revision_dt').DataTable();
        
    }
  };


})(jQuery, Drupal);