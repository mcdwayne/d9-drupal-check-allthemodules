(function ($) {
  'use strict';
  Drupal.behaviors.twitterFeedTimeAgo = {
    attach: function (context, settings) {
      $(context).find('time.timeago').timeago();
    }
  };
})(jQuery);
