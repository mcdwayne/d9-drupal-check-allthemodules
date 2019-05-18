(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Creates class to manipulate Analytics.
   */
  var personaAnalyticsClass = function(options) {

    /**
     * Can access this.method
     * inside other methods using
     * root.method()
     */
    var root = this;
    var analyticsEnabled = true;
    var analyticsSent = false;

    /**
     * Constructor
     */
    this.construct = function(options){
      $.extend(vars, options);
    };

    /**
     * Init script.
     */
    this.init = function () {
    }

    /**
     * Is analytics on?
     */
    this.status = function () {
      return analyticsEnabled;
    }

    /**
     * Send log to Analytics only if enabled.
     */
    this.log = function (segmentName) {
      if (analyticsEnabled && !analyticsSent) {
        analyticsSent = true;

        // Sent information to datalayer.
        if (typeof dataLayer != "undefined") {
          var params = {
            event: 'VirtualPageView',
            VirtualSegment: segmentName
          };
          var response2 = dataLayer.push(params);
        }
      }
    }

  }

  window.personaAnalytics = new personaAnalyticsClass();
  window.personaAnalytics.init();

})(jQuery, Drupal, drupalSettings);
