/**
 * @file
 * Consent: Triggers further events regards consent.
 */

(function (w, c) {

  var eventMethod = w.addEventListener ? 'addEventListener' : 'attachEvent';
  var messageEvent = eventMethod === 'attachEvent' ? 'onmessage' : 'message';

  function trigger(event) {
    var messageId;
    var customEvent;
    var consentEvents = {'oil_optin_done': 'UserOptedIn'};
    if (typeof event.data === 'string') {
      messageId = event.data;
      if (messageId.indexOf('oil_') === 0) {
        if (c.layerReady === false) {
          c.layerReady = true;
          customEvent = w.document.createEvent('CustomEvent');
          customEvent.initCustomEvent('consent:LayerReady', false, false, messageId);
          w.dispatchEvent(customEvent);
        }
        if (consentEvents.hasOwnProperty(messageId)) {
          customEvent = w.document.createEvent('CustomEvent');
          customEvent.initCustomEvent('consent:' + consentEvents[messageId], false, false, messageId);
          w.dispatchEvent(customEvent);
        }
      }
      switch (messageId) {
        case 'oil_optin_done':
        case 'oil_has_optedin':
          c.given = true;
          break;
        case 'oil_shown':
        case 'oil_no_cookies_allowed':
          c.given = false;
          break;
      }
    }
  }

  w[eventMethod](messageEvent, trigger, false);

}(window, window.Consent));
