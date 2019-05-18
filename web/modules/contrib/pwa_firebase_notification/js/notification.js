(function(drupalSettings) {
  var button = document.getElementById('pwa_firebase_notifications');
  var form = document.getElementById('notification_subscription_form');

  if ('serviceWorker' in navigator && 'PushManager' in window && drupalSettings.firebaseConfig !== undefined) {
    var firebaseMessagingToken;
    var notificationsEnabled = false;
    var firebaseEnabled = false;

    // Function checks initializes the notification object and functions that need to be handled.
    function initNotification() {
      // Check if firebase can be created.
      if (!firebaseEnabled) {
        firebase.initializeApp(drupalSettings.firebaseConfig);
        firebaseEnabled = true;
      }

      messaging = firebase.messaging();

      // Asking permission from the user and if we have permission we send our token to the backend to subscribe to notifications.
      messaging.requestPermission()
        .then(function () {
          updateButtonUi();
          return messaging.getToken(true)
        })
        .then(function (currentToken) {
          firebaseMessagingToken = currentToken;
          sendTokenToServer(currentToken);
        })
        .catch(function (err) {
          console.log('An error occurred while retrieving token. ', err);
          notificationsEnabled = false;
          updateButtonUi();
        });

      // Receiving a message when the window is on the foreground.
      messaging.onMessage(function (payload) {
        console.log("Message received. ", payload);
      });

      // Handles when the token is refreshed so we send the new token to the backend.
      messaging.onTokenRefresh(function () {
        messaging.getToken()
          .then(function (refreshedToken) {
            firebaseMessagingToken = refreshedToken;
            sendTokenToServer(refreshedToken);
          })
          .catch(function (err) {
            notificationsEnabled = false;
            updateButtonUi();
            console.log('Unable to retrieve refreshed token ', err);
          });
      });
    }

    // Sending the messaging token to the backend.
    function sendTokenToServer(token) {
      var xhr = new XMLHttpRequest();
      xhr.open("POST", '/firebase-send-token/'+ token, true);

      xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
          console.info('token send to server');
        }
      };
      xhr.send();
    }

    // The cookie saves if the user already gave permission so we don't need to ask again.
    function getCookie() {
      var enableNotificationCookie = document.cookie.replace(/(?:(?:^|.*;\s*)enableNotifications\s*\=\s*([^;]*).*$)|^.*$/, "$1");

      if (enableNotificationCookie === '') {
        document.cookie += 'enableNotifications=false;';
        enableNotificationCookie = false;
      }

      // Convert string to boolean.
      notificationsEnabled = enableNotificationCookie === 'true';
    }

    // Get cookies, update button block, make sure we can receive notifications.
    window.onload = function () {
      getCookie();

      initNotification();

      if (button != null) {
        updateButtonUi();
        button.addEventListener("click", function(e) {
          notificationsEnabled = !notificationsEnabled;
          updateButtonUi();
          updateSubscriprion();
        });
      }

      if (form != null) {
        form.addEventListener("submit", function(e) {
          notificationsEnabled = !!+e.target.push.value;
          updateSubscriprion();
          e.preventDefault();
        }, true);
      }

    };

    // Changes button text.
    function updateSubscriprion() {
      document.cookie = "enableNotifications=" + notificationsEnabled;
      if (notificationsEnabled) {
        initNotification();
      }
      else {
        messaging.deleteToken(firebaseMessagingToken);
      }
    }

    function updateButtonUi() {
      if (button == null) {
        return;
      }
      if (notificationsEnabled) {
        button.innerText = 'Disable notifications';
      }
      else {
        button.innerText = 'Enable notifications';
      }
    }
  } else {
    if (button != null) {
      button.parentNode.removeChild(button);
    }

    if (form != null) {
      form.parentNode.removeChild(form);
    }
  }
})(drupalSettings);