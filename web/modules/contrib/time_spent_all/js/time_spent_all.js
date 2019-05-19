(function ($, Drupal) {
  Drupal.behaviors.time_spent_all = {
    attach: function(context, settings) {
      // Ajax callback url.
 /*  $.getJSON("http://jsonip.com?callback=?", function (data) {
     //alert("Your ip: " + data.ip);
ipaddr = data.ip;
});*/

      var callbackUrl = drupalSettings.path.baseUrl + 'js/time_spent_all/ajax/' + drupalSettings.time_spent_all.nid + '/' + drupalSettings.time_spent_all.sectoken;

      // Maximum time to run timer and callbacks (in minutes).
      var timeLimit = drupalSettings.time_spent_all.limit * 1000 * 60;

      // Detect if is in an iframe, like overlay module.
      var isInIFrame = (window.location != window.parent.location) ? true : false;

      // Initialize the timer.
       
      var timer = setInterval( time_spent_all_ajax, (drupalSettings.time_spent_all.timer * 1000));

      // If configured to do so, track time on client-side, which is
      // sent to the backend on page unload.
      if (drupalSettings.time_spent_all.clientTiming && !isInIFrame) {
        // Update client-side timer every second.
        var unsentTimeOnPage = 0;
        var clientTimer = setInterval( timeSpentAllClientTimer, 1000, 1);
        // Register onunload event.
        $(window).bind('unload', timeSpentSendTimer);

        // Since unload can be unreliable, also send on link click.
        $('a').bind('click', timeSpentAllSendTimer);

        // Stop client timer and remove the unload handler at the time limit.
        setTimeout(function () { clearInterval(clientTimer); }, timeLimit);
        setTimeout(function () {
          $(window).off('unload', timeSpentAllSendTimer);
          $('a').unbind('click', timeSpentAllSendTimer);
        }, timeLimit);
      }
      else {
        // Clear the ajax callback timer after limit is reached.
        setTimeout(function() {clearInterval(timer);}, timeLimit);
        window.parent.enabled = true;
        if(isInIFrame){
          // If a page is loaded into a overlay iframe
          // cancel the timespent from the page under overlay.
          window.parent.enabled = false;
        }
        $('#overlay-close').click(function() {
          window.parent.enabled = true;
          if (isInIFrame) {
            // If a page is loaded into a overlay iframe
            // cancel the timespent from the page under overlay.
            window.parent.enabled = false;
          }
        });
      }

      /**
       * Send pings to the backend for to update time spent.
       */
      function time_spent_all_ajax() {
   
       
        if(isInIFrame || window.parent.enabled){
          $.ajax({
            type: 'get',
            url: callbackUrl,
            dataType: 'json',
            data: 'js=1'
          });

          // Zero-out client-side timer.
          if (drupalSettings.time_spent_all.clientTiming) {
            unsentTimeOnPage = 0;
          }
        }


      }

      /**
       * Track time on page between ajax callbacks.
       *
       * @param interval
       *   Time in seconds between when this function is called.
       */
      function timeSpentAllClientTimer(interval) {
        // Increment by 1 second since this is called
        unsentTimeOnPage += interval;

        // Remove the unload event in case this was triggered by a link click.
        $(window).unbind('unload', timeSpentSendTimer);

        // Zero out unsent time just in case this link click did not exit the page.
        unsetTimeOnPage = 0;
      }

      /**
       * Send client side timer to backend.
       */
      function timeSpentAllSendTimer () {
        $.ajax({
          type: 'get',
          url: callbackUrl,
          dataType: 'json',
          data: 'js=1&timer=' + unsentTimeOnPage
        });
      }
    }
  };
})(jQuery, Drupal);