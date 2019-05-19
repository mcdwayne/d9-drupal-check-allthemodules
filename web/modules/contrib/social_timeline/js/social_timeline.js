(function ($, window) {

  "use strict";

  /**
   * Generate the output for the social timeline.
   */
  Drupal.behaviors.socialTimeline = {
    attach: function (context, settings) {
      var feeds = drupalSettings.social_timeline.feeds;
      var instanceId = drupalSettings.social_timeline.instance_id;
      jQuery('#' + instanceId, context).dpSocialTimeline(feeds);
    }
  };

})(jQuery, window);
