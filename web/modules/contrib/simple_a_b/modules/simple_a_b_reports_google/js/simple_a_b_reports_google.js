(function ($) {
  'use strict';

  $(document).ready(function ($a) {


    /**
     * init
     */
    function init()
    {
      getReports();
    }


    /**
     * One the page has load we can
     * request any report data from the reporter
     */
    function getReports()
    {
      // make an ajax call to get any report data
      $a.ajax({
        url: "/request/simple_a_b/get/reports",
        method: 'GET',
        dataType: "json",
        success: function (reports) {
          // if we have some reports & reports has a count of 0 or greater
          if (typeof reports.reports !== 'undefined' && reports.reports.length !== 0) {
            processReportData(reports.reports);
          }
        }
      });
    }

    /**
     * Process report data to send to google analytics
     *
     * @param data
     */
    function processReportData(data)
    {
      // if we can find ga
      // lets look though and send over the data to be sent to google
      if (typeof ga === "function") {
        $.each(data, function (key, report) {
          callGA(report.eventCategory, report.eventAction, report.eventLabel, report.eventValue);
        });
      }
      else {
        console.error('Simple a/b - Cannot find "ga" method');
      }
    }


    /**

     *
     */

    /**
     * Sends the event to google analytics
     *
     * ga('send', 'event', [eventCategory], [eventAction], [eventLabel],
     * [eventValue], [fieldsObject]);
     *
     * @param eventCategory
     * @param eventAction
     * @param eventLabel
     * @param eventValue
     */
    function callGA(eventCategory, eventAction, eventLabel, eventValue)
    {
      // just another check for ga - as maybe someone calls it from outside
      // this methid if found then lets use it! send away to google!
      if (typeof ga === "function") {
        ga("send", "event", eventCategory, eventAction, eventLabel, eventValue);
      }
      else {
        console.error('Simple a/b - Cannot call "ga" method');
      }
    }


    // call init
    init();
  });

}(jQuery));
