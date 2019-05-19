/**
 * Simple analytics live updator.
 */
(function ($, Drupal, drupalSettings, window) {

    //Get settings from Drupal
    var time = drupalSettings.simple_analytics.time;
    var path = drupalSettings.simple_analytics.path;
    var period = drupalSettings.simple_analytics.period * 1000;
    var loop = true;

    //For Chat and DrupalBehavior Test
    Drupal.simpleAnalytics = {};

    // Loop.
    Drupal.simpleAnalytics.loop = function () {
        if (loop) {
            Drupal.simpleAnalytics.update();
            setTimeout(Drupal.simpleAnalytics.loop, period);
        }
    };

    // Get data and update.
    Drupal.simpleAnalytics.update = function () {
        $.post(path, {time: time}, function (data) {
            var listFields = ["rasult", "time", "visits", "visitors", "mobiles"];
            var arrayLength = listFields.length;
            for (var i = 0; i < arrayLength; i++) {
                var field = listFields[i];
                $(".sa-live-value-" + field).text(data[field]);
            }
        }, "json")
                .fail(function () {
                    loop = false;
                });
    };

    // Starter.
    Drupal.behaviors.simpleAnalyticsStart = {
        attach: function (context, settings) {
            Drupal.simpleAnalytics.loop();
        }
    };

}(jQuery, Drupal, drupalSettings, this));
