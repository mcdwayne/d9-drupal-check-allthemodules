/*
 Event Handler that will handle the showing off the notification, we are handling the showing of the message from the URL that's means data is storing in the files
 */
self.addEventListener('push', function (event) {
  'use strict';
  event.waitUntil(
    fetch('/chrome_push_notification/get_notification/', {
      method: 'get'
    }).then(function (response) {
      if (response.status !== 200) {
        // Either show a message to the user explaining the error
        // or enter a generic message and handle the
        // onnotificationclick event to direct the user to a web page
        // console.log('Looks like there was a problem. Status Code: ' + response.status);
        throw new Error();
      }

      // Examine the text in the response
      return response.json().then(function (data) {
        if (!data) {
          // console.error('The API returned an error.', data.error);
          throw new Error();
        }
        var title = data.title;
        var message = data.message;
        var icon = data.icon;
        var urlData = {
          url: data.url
        };

        var tag = {
          url: data.url
        };

        return self.registration.showNotification(title, {
          body: message,
          icon: icon,
          tag: tag,
          data: urlData
        });
      });
    }).catch(function (err) {
      // console.error('Unable to retrieve data', err);

      if (DEBUG_MODE) {
        var title = 'An error occurred';
        var message = 'We were unable to get the information for this push message';
        var notificationTag = 'notification-error';
        return self.registration.showNotification(title, {
          body: message,
          tag: notificationTag
        });
      }
    })
  );
});

/*
 Function that handle the click event after the clicking on the notification
 */
self.addEventListener('notificationclick', function (event) {
  'use strict';
  // console.log('On notification click: ', event.notification.tag);
  // Android doesn’t close the notification when you click on it
  // See: http://crbug.com/463146
  event.notification.close();
  // console.log(event.notification.data.url);

  // This looks to see if the current is already open and
  // focuses if it is
  event.waitUntil(clients.matchAll({
    type: 'window'
  }).then(function (clientList) {
    if (event.notification.data.url) {
      return clients.openWindow(event.notification.data.url);
    }
    else {
      return clients.openWindow('/');
    }
  }));
});
