/**
 * @file
 * Consent layer: Trigger opt-in consent when scrolling.
 */

(function (w, c) {

  var eventMethod = w.addEventListener ? 'addEventListener' : 'attachEvent';
  var removeMethod = eventMethod === 'attachEvent' ? 'detachEvent' : 'removeEventListener';
  var eventName = eventMethod === 'attachEvent' ? 'onscroll' : 'scroll';
  var triggered = false;

  function trigger(e) {
    if (!triggered) {
      triggered = true;
      w.setTimeout(c.doOptIn.bind(c, 'optin_scroll'), 15000);
    }
    w[removeMethod](eventName, trigger, false);
  }

  function add() {
    if (!c.userOptedIn()) {
      w[eventMethod](eventName, trigger, false);
    }
  }

  if (c.layerReady) {
    add();
  }
  else {
    w[eventMethod]('consent:LayerReady', add, false);
  }

}(window, window.Consent));
