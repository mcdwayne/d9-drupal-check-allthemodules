/**
 * @File
 * Javascript for the Simple Feedback module.
 */

(function ($, Drupal, window) {
  'use strict';

  Drupal.behaviors.simpleFeedback = {
    attach: function (context, settings) {
      var nid = drupalSettings.path.currentPath.replace('node/','');
      $.get('/ajax/simple_feedback/get_values/' + nid, function (data) {
        $('#yes-vote').html('(' + data.count.yes + ')');
        $('#no-vote').html('(' + data.count.no + ')');
      });
    }
  };
})(jQuery, Drupal, window);
