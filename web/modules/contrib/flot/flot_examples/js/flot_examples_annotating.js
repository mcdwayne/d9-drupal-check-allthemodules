/**
 * @file
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      // The flotArray global contains all the plot objects on the page.
      var o = window.flotArray['flot-chart'].pointOffset({x: 2, y: -1.2});
      var placeholder = $('#flot-chart');
      // Append it to the placeholder that Flot already uses for positioning.
      placeholder.append("<div style='position:absolute;left:" + (o.left + 4) + "px;top:" + o.top + "px;color:#666;font-size:smaller'>" + Drupal.t('Warming up') + "</div>");
      o = window.flotArray['flot-chart'].pointOffset({x: 8, y: -1.2});
      placeholder.append("<div style='position:absolute;left:" + (o.left + 4) + "px;top:" + o.top + "px;color:#666;font-size:smaller'>" + Drupal.t('Actual measurements') + "</div>");
      // Draw a little arrow on top of the last label to demonstrate canvas
      // drawing.
      var ctx = window.flotArray['flot-chart'].getCanvas().getContext('2d');
      ctx.beginPath();
      o.left += 4;
      ctx.moveTo(o.left, o.top);
      ctx.lineTo(o.left, o.top - 10);
      ctx.lineTo(o.left + 10, o.top - 5);
      ctx.lineTo(o.left, o.top);
      ctx.fillStyle = '#000';
      ctx.fill();
    }
  };
}(jQuery, Drupal));
