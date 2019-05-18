(function($, Drupal, drupalSettings) {
  Drupal.behaviors.timeoutnotifier = {
    attach: function () {
      // Set local storage variable for when the session will expire.
        const timeToNotify = parseInt(drupalSettings.timeout_notification.timeout_notifier.time_to_notify);
        const maxLifeTime =  parseInt(drupalSettings.timeout_notification.timeout_notifier.max_lifetime);
        localStorage.setItem('time_to_notify', timeToNotify + Math.floor(new Date().getTime() / 1000));
        localStorage.setItem('max_lifetime', maxLifeTime + Math.floor(new Date().getTime() / 1000));

        // Dismiss any current timeout notifications on all tabs.
        DismissNotification();

        // Remove any existing timeout-notifier divs.
        const previousElements = document.getElementById('timeout-notifier');
        if (previousElements) {
          previousElements.remove();
        }

        // Create notifier and add it to body of DOM.
        const notifier = document.createElement('div');
        notifier.setAttribute('id', 'timeout-notifier');
        notifier.innerHTML =
            `<div class="notifier">
                <div class="notifier-header">
                    <button class="notifier-close">&times;</button>
                    <h2> ${Drupal.t('Session Timeout Notification')} </h2>
                </div>
                <div class="notifier-body">
                    <p> ${Drupal.t('Session will expire in xx seconds')} </p>
                </div>
                <div class="notifier-footer">
                    <button class="notifier-btn"> ${Drupal.t('Extend Session')} </button>
                </div>
            </div>`;
        notifier.style.display = 'none';
        document.body.appendChild(notifier);

        // Grabbing notifier elements.
        const closeBtn = notifier.getElementsByClassName('notifier-close')[0];
        const notifierBtn = notifier.getElementsByClassName('notifier-btn')[0];
        const notifierBody = notifier.getElementsByClassName('notifier-body')[0];

        // Defining default variables for notifier displays.
        let notifierDisplayed = false;
        let expireDisplayed = false;

        // Runs every second. Checks if session has or is about to timeout.
        // Notifies user 1 minute in  advance.
        setInterval(function() {
            // Getting session details.
            const timestamp = Math.floor(new Date().getTime() / 1000);
            const timeout =  localStorage.getItem('max_lifetime');
            const timeTillNotify = localStorage.getItem('time_to_notify');
            const timeLeft = timeout - timestamp;
            const timeoutDismissed =  parseInt(localStorage.getItem('timeout_dismissed'));
            if (timestamp >= timeout) {
                // Session has expired.
                if (!expireDisplayed)  {
                  notifierBody.innerText = Drupal.t('Session Has Expired');
                  notifierBtn.innerText = Drupal.t('OK');
                  document.getElementById('timeout-notifier').style.display = 'block';
                  expireDisplayed = true;
                }
            }
            else if (timestamp >= timeTillNotify) {
                // Session has less than timeTillExpire seconds until expired.
                notifierBody.innerText = Drupal.t(`Session will expire in ${timeLeft} seconds.`);
                notifierBtn.innerText = Drupal.t('Extend Session');
                if (!notifierDisplayed) {
                  document.getElementById('timeout-notifier').style.display = 'block';
                  notifierDisplayed = true;
                }
            }
            else {
                // If session is over the timeTillExpire second mark.
                // Prepare to render notification next time.
                notifierDisplayed = false;
            }

            if (timeoutDismissed) {
                // If any tab has dismissed the notification.
                document.getElementById('timeout-notifier').style.display = 'none';
            }

        }, 1000);

        closeBtn.onclick = () => {
          DismissNotification();
        };

        notifierBtn.onclick = () => {
          if (!expireDisplayed) {
              // Create Iframe to refresh session.
              // TODO: Find better solution to refresh session.
              const iFrameDiv = document.createElement('iframe');
              iFrameDiv.setAttribute('id', 'timeout-iframe');
              iFrameDiv.setAttribute('src', '');
              iFrameDiv.style.display = 'none';
              document.body.appendChild(iFrameDiv);

              iFrameDiv.onload = () => {
                  //  Then remove the Iframe.
                  document.getElementById('timeout-iframe').remove();
                  DismissNotification();
              };

              iFrameDiv.setAttribute('src', `https://${window.location.host}/user`)
          }
          else {
            DismissNotification();
          }
        };

        function DismissNotification () {
            // Set local value showing timeout has been dismissed.
            localStorage.setItem('timeout_dismissed','1');
            setTimeout(() => {
                // After 1 second, show that it is no longer dismissed.
                // Gives the other tabs a chance to catch up.
                localStorage.setItem('timeout_dismissed','0');
            }, 1000);
        }
    }
  };
})(jQuery, Drupal, drupalSettings);
