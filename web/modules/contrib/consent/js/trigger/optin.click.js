/**
 * @file
 * Consent layer: Trigger opt-in consent when clicking.
 */

(function (w, dom, c) {

  var eventMethod = w.addEventListener ? 'addEventListener' : 'attachEvent';
  var removeMethod = eventMethod === 'attachEvent' ? 'detachEvent' : 'removeEventListener';
  var eventName = eventMethod === 'attachEvent' ? 'onclick' : 'click';
  var triggered = false;
  var deferred = false;

  function trigger(e) {
    var target = e.target;
    if (!deferred) {
      deferred = true;
      w.setTimeout(c.doOptIn.bind(c, 'optin_click_defer'), 15000);
    }
    if (!triggered) {
      if ((['A', 'BUTTON', 'INPUT'].indexOf(target.tagName) !== -1) && !c.elHasClass(target, 'no-optin') && (target.parentElement && !c.elHasClass(target.parentElement, 'as-oil-l-item'))) {
        triggered = true;
        c.doOptIn('optin_click_' + target.tagName);
        dom[removeMethod](eventName, trigger, false);
      }
      else if (target.parentElement) {
        return trigger({target: target.parentElement});
      }
    }

  }

  function add() {
    if (!c.userOptedIn()) {
      dom[eventMethod](eventName, trigger, false);
    }
  }

  if (c.layerReady) {
    add();
  }
  else {
    w[eventMethod]('consent:LayerReady', add, false);
  }

}(window, window.document, window.Consent));
