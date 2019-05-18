/**
 * @file
 * blocktabs behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Add jquery ui accordion effect.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *
   */
  Drupal.behaviors.blocktabs_accordion = {
    attach: function (context, settings) {
      $(context).find('div.blocktabs.accordion').each(function () {
        if ($(this).hasClass('click')) {
          $(this).accordion({
            collapsible: true,
            event: 'click'
          });
        }
        else {
          $(this).accordion({
            collapsible: true,
            event: 'mouseover'
          });
        }
      });
    }
  };

}(jQuery, Drupal));
