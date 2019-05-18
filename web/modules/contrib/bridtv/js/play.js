/**
 * @file
 * Player initialization for embedded videos.
 */

window.bridPlayStack = window.bridPlayStack || [];
// http://developer.brid.tv/brid-player/code-examples/basic
window.document.blockSimultaneousPlay = true;

(function (playStack, Drupal) {

  Drupal.behaviors.bridPlay = {
    attach: function (context, settings) {
      var buffer;
      if (window.$bp) {
        while (playStack.length > 0) {
          buffer = playStack.shift();
          window.$bp(buffer.div, buffer.obj);
        }
      }
      else {
        while (playStack.length > 0) {
          window._bp = window._bp || [];
          window._bp.push(playStack.shift());
        }
      }
    }
  }

}(window.bridPlayStack, window.Drupal));

window.Drupal.behaviors.bridPlay.attach();
