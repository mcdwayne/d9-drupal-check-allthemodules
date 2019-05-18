/**
 * @file
 * Consent iFrame: Parent response.
 */

(function (w, c) {

  var eventMethod = w.addEventListener ? 'addEventListener' : 'attachEvent';
  var eventName = 'consent:UserOptedIn';
  var messageEvent = eventMethod === 'attachEvent' ? 'onmessage' : 'message';
  var eventer = window[eventMethod];

  function sendParentResponse() {
    if (w.parent) {
      w.parent.postMessage({
        type: 'consent-response',
        action: 'accept'
      }, '*');
    }
  }

  function receiveMessage(event) {
    function eventDataContains(str) {
      return JSON.stringify(event.data).indexOf(str) !== -1;
    }
    if (event && event.data && (eventDataContains('oil_hide_layer'))) {
      sendParentResponse();
    }
  }

  if (c.userOptedIn()) {
    sendParentResponse();
  }
  else {
    w[eventMethod](eventName, sendParentResponse, false);
  }

  eventer(messageEvent, receiveMessage, false);

}(window, window.Consent));
