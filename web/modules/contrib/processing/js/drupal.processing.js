/**
 * @file
 * js/drupal.processing.js
 */


/**
 * Processing module javascript file to switch between render & source.
 *
 * We can't generate script elements on the fly because in Webkit,
 * Gecko, and IE it will always try to compile the script in JavaScript
 * even when type is NOT text/javascript. This doesn't make any sense.
 *
 * A note on performance. There can be a maximum of 0..n processing
 * instances loaded on a page where n is the number of [processing] tag
 * blocks on the page. This value is always increasing and never
 * decreasing so it is possible for a user to overload themselves for
 * high values of n.
 */
(function($, Drupal) {

  "use strict";

  var instances = [];

  Drupal.behaviors.processing = {
    /**
     * Try to invoke Processing.js instance
     * 
     * @param {HTMLCanvasElement} canvas
     */
    invoke: function (canvas) {
      var script = canvas.prev('script');
      var canvas_element = canvas.get(0);

      try {
        instances[canvas.attr('id')] = new Processing(canvas_element, script.get(0).text);
      }
      catch (err) {
        if (!canvas.parent().siblings('div.messages').length) {
          canvas.parent().before('<div class="messages error">' + Drupal.t('Syntax Error: ') + err.message + '</div>');
        }
      }
    },

    _handleClick: function (e) {
      var el = e.target;
      e.preventDefault();
      if (el.innerHTML === Drupal.t('Render Sketch')) {
        var pre = $(el).nextAll('pre');
        var canvas = $(el).nextAll('canvas');

        // Hide/Show
        el.innerHTML = Drupal.t('Show Source');
        pre.addClass('processing__source--hidden').animate({}, 400);
        canvas.removeClass('processing__canvas--hidden').animate({}, 400);

        if (instances[canvas.attr('id')] === undefined) {
          Drupal.behaviors.processing.invoke(canvas);
        }
      }
      else {
        el.innerHTML = Drupal.t('Render Sketch');
        $(el).nextAll('pre').removeClass('processing__source--hidden').animate({}, 400);
        $(el).nextAll('canvas').addClass('processing__canvas--hidden').animate({}, 400);
      }
    },

    /**
     * Attach behavior.
     */
    attach: function (context, settings) {

      // Load any visible canvases immediately.
      $('canvas.processing__canvas--hidden').not().each(function() {
        if (instances[$(this).attr('id')] === undefined) {
          Drupal.behaviors.processing.invoke($(this));
        }
      });

      // When a Render Sketch or View Source button is clicked show/hide, and
      // try to invoke if rendering.
      $(context).find('.processing__button').once().click(Drupal.behaviors.processing._handleClick.bind(this));
    }
  };
})(jQuery, Drupal);
