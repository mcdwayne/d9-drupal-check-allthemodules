/*
 By which we are handling the popup should be show on the every page so that we can enable the module
 */
(function ($, window, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.chrome_notification = { // the name of our behavior
    attach: function (context, settings) {
      // Check the browser that chrome push notification pop up should be come only in the chrome not for the all browser
      if (/chrom(e|ium)/.test(navigator.userAgent.toLowerCase())) {
        var confirmationDialog = Drupal.dialog('<div class="cpn_message_div" style="display: none !important;"></div>', {
          title: Drupal.t('Get the latest updates through website notifications?'),
          dialogClass: 'cpn-model-popup',
          resizable: false,
          buttons: [
            {
              text: Drupal.t('Allow'),
              class: 'button button--allow',
              click: function () {
                subscribe();
                confirmationDialog.close();
              }
            },
            {
              text: Drupal.t('Later'),
              class: 'button button--cancel',
              click: function () {
                confirmationDialog.close();
              }
            }
          ],
          // Prevent this modal from being closed without the user making a choice
          // as per http://stackoverflow.com/a/5438771.
          closeOnEscape: false,
          create: function () {

          },
          beforeClose: false,
          close: function (event) {
            // Automatically destroy the DOM element that was used for the dialog.
            // $(event.target).remove();
          }
        });
        if (window.location.protocol === 'https:' && $.cookie(COOKIE_VARIABLE) !== 'true') {
          confirmationDialog.showModal();
        }
      }
    }
  };
})(jQuery, window, Drupal, drupalSettings);

function saveChromeDataOnServer(register_id) {
  'use strict';
  if (register_id !== '') {
    jQuery.ajax({url: '/chrome_push_notification/add_device/' + register_id, success: function (result) {
      // console.log('successfully registration of chrome');
    }});
  }
}

/**
 * @file
 * Attaches behavior for the Chrome Notification module.
 */

var GCM_ENDPOINT = 'https://android.googleapis.com/gcm/send';
var COOKIE_VARIABLE = 'chrome_notification_set';

// This method handles the removal of subscriptionId
// in Chrome 44 by concatenating the subscription Id
// to the subscription endpoint
function endpointWorkaround(pushSubscription) {
  'use strict';
  // Make sure we only mess with GCM
  if (pushSubscription.endpoint.indexOf('https://android.googleapis.com/gcm/send') !== 0) {
    return pushSubscription.endpoint;
  }

  var mergedEndpoint = pushSubscription.endpoint;
  // Chrome 42 + 43 will not have the subscriptionId attached
  // to the endpoint.
  if (pushSubscription.subscriptionId &&
    pushSubscription.endpoint.indexOf(pushSubscription.subscriptionId) === -1) {
    // Handle version 42 where you have separate subId and Endpoint
    mergedEndpoint = pushSubscription.endpoint + '/' +
      pushSubscription.subscriptionId;
  }
  return mergedEndpoint;
}

// This method handles the removal of subscriptionId
// in Chrome 44 by concatenating the subscription Id
// to the subscription endpoint
function sendSubscriptionToServer(subscription) {
  'use strict';
  // TODO: Send the subscription.endpoint
  // to your server and save it to send a
  // push message at a later date
  //
  // For compatibly of Chrome 43, get the endpoint via
  // set the cookie when the user has subscribe the data
  var mergedEndpoint = endpointWorkaround(subscription);
  // This is just for demo purposes / an easy to test by
  // generating the appropriate cURL command
  // The curl command to trigger a push message straight from GCM
  if (mergedEndpoint.indexOf(GCM_ENDPOINT) !== 0) {
    // console.log('This browser isn\'t currently supported for this demo', true);
    return;
  }
  if (getCookie(COOKIE_VARIABLE) !== 'true') {
    setCookie(COOKIE_VARIABLE, true);
    var endpointSections = mergedEndpoint.split('/');
    var subscriptionId = endpointSections[endpointSections.length - 1];
    // save subscription id on the server
    saveChromeDataOnServer(subscriptionId);
  }
}

function subscribe() {
  'use strict';
  navigator.serviceWorker.ready.then(function (serviceWorkerRegistration) {
    serviceWorkerRegistration.pushManager.subscribe({userVisibleOnly: true})
      .then(function (subscription) {
        // TODO: Send the subscription subscription.endpoint
        // to your server and save it to send a push message
        // at a later date
        return sendSubscriptionToServer(subscription);
      })
      .catch(function (e) {
        if (Notification.permission === 'denied') {
          // The user denied the notification permission which
          // means we failed to subscribe and the user will need
          // to manually change the notification permission to
          // subscribe to push messages
          // console.log('Permission for Notifications was denied', true);
        }
        else {
          // A problem occurred with the subscription, this can
          // often be down to an issue or lack of the gcm_sender_id
          // and / or gcm_user_visible_only
          // console.log('Unable to subscribe to push.', e);
        }
      });
  });
}

// Once the service worker is registered set the initial state
function initialiseState() {
  'use strict';
  // Are Notifications supported in the service worker?
  if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
    // console.log('Notifications aren\'t supported.', true);
    return;
  }

  // Check the current Notification permission.
  // If its denied, it's a permanent block until the
  // user changes the permission
  if (Notification.permission === 'denied') {
    // console.log('The user has blocked notifications.', true);
    return;
  }

  // Check if push messaging is supported
  if (!('PushManager' in window)) {
    // console.log('Push messaging isn\'t supported.', true);
    return;
  }

  // We need the service worker registration to check for a subscription
  navigator.serviceWorker.ready.then(function (serviceWorkerRegistration) {
    // Do we already have a push message subscription?
    serviceWorkerRegistration.pushManager.getSubscription()
      .then(function (subscription) {
        if (!subscription) {
          // We aren’t subscribed to push, so set UI
          // to allow the user to enable push
          return;
        }

        // Keep your server in sync with the latest subscription
        sendSubscriptionToServer(subscription);
      })
      .catch(function (err) {
        // console.log('Error during getSubscription()', err);
      });
  });
}

window.addEventListener('load', function () {
  'use strict';
  // Check that service workers are supported, if so, progressively
  // enhance and add push messaging support, otherwise continue without it.
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
      .then(initialiseState);
  }
  else {
    // console.log('Service workers aren\'t supported in this browser.');
  }
});

/*
 Function for getting the cookie value using the javascript because in the service worker only javascript will work
 */
function getCookie(cname) {
  'use strict';
  var name = cname + '=';
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) === ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) === 0) {
      return c.substring(name.length, c.length);
    }
  }
  return '';
}

/*
 Function for set the cookie value using the javascript because in the service worker only javascript will work
 */
function setCookie(cname, cvalue, exdays) {
  'use strict';
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = 'expires=' + d.toUTCString();
  document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/';
}
