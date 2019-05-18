/**
 * @file
 * Consent: Submit consent to backend storage.
 */

(function (w, c) {

  var eventMethod = w.addEventListener ? 'addEventListener' : 'attachEvent';
  var removeMethod = eventMethod === 'attachEvent' ? 'detachEvent' : 'removeEventListener';
  var eventName = 'consent:UserOptedIn';
  var triggered = false;

  function trigger(e) {
    var request;
    if (!triggered) {
      triggered = true;
      request = new XMLHttpRequest();
      request.open('POST', '/consent/submit', true);
      request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
      request.send('c=' + c.category);
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
