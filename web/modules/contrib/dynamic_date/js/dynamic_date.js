;(function($) {
  'use strict';

  // Update every minute.
  // @todo: Make configurable?
  var interval = 60000;
  // @todo: Make configurable?
  var maxTimeAgo = (7 * 24 * 60 * 60 * 1000);

  setInterval(function() {
    updateContext($(document.body));
  }, interval);

  function updateContext(context) {
    $(context).find('[data-is-timeago]').each(function(i, n) {
      // Convert the date into time ago. If it is more than 25 days, we
      // display the actual date. Also, this is php, so the timestamp is
      // seconds, not milliseconds.
      var date = parseInt($(n).attr('data-timestamp'), 10) * 1000;
      // If we are supposed to ensure this is in the past, we set it to
      // maximum current time.
      var ensurePast = $(n).attr('data-ensure-past');
      if (ensurePast && date > Date.now()) {
        date = Date.now();
      }
      var m = moment(date);
      if (date < (Date.now() - maxTimeAgo)) {
        $(n).text($(n).attr('title'));
      }
      else {
        $(n).text(m.fromNow());
      }
      // Also add an attribute, so we can know that this is provided by
      // javascript.
      $(n).attr('data-js-date', 1);
    });
  }

  Drupal.behaviors.dynamicDate = {
    attach: function(context) {
      updateContext(context);
    }
  }

})(jQuery, Drupal);
