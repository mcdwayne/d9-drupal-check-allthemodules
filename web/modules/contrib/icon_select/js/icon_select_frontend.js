/**
 * @file
 * Icon select javascript behaviours
 */

/** Polyfill for IE11 */
(function () {
  function CustomEvent ( event, params ) {
    params = params || { bubbles: false, cancelable: false, detail: undefined };
    var evt = document.createEvent( 'CustomEvent' );
    evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
    return evt;
  }

  CustomEvent.prototype = window.Event.prototype;

  window.CustomEvent = CustomEvent;
})();

(function (Drupal, window, document) {
  'use strict';

  Drupal.behaviors.icon_select_frontend = {
    attach: function (context, settings) {

      // Full support for IE 11.
      var iconSelectEvent = new CustomEvent('iconselectloaded');

      settings.icon_select = settings.icon_select || drupalSettings.icon_select;

      var xhr = new XMLHttpRequest();
      xhr.open('get', settings.icon_select.icon_select_url, true);
      xhr.responseType = 'document';
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) {
          return;
        }

        try {
          var svg = xhr.responseXML.documentElement;
          svg = document.importNode(svg, true);
          svg.id = 'svg-icon-sprite';
          document.body.appendChild(svg);

          svg.style.display = 'none';
          svg.style.display = 'block';
        }
        catch (e) {
          console.log(e);
        }

        window.dispatchEvent(iconSelectEvent);
      };

      xhr.send();
    }
  };

})(Drupal, this, this.document);
