/**
 * @file
 * blocktabs behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Add jquery ui tabs effect.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *
   */
  Drupal.behaviors.blocktabs = {
    attach: function (context, settings) {
      $(context).find('div.blocktabs.default, div.blocktabs.vertical').each(function () {
        if ($(this).hasClass('click')) {
          $(this).tabs({
            event: 'click'
          });
        }
        else {
          $(this).tabs({
            event: 'mouseover'
          });
        }
        if ($(this).hasClass('vertical')) {
          $(this).addClass('ui-tabs-vertical ui-helper-clearfix');
          $(this).find('li').removeClass('ui-corner-top').addClass('ui-corner-left');
        }
      });
    }
  };

}(jQuery, Drupal));
