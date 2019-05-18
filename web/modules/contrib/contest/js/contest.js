/**
 * @file
 * A JavaScript file for the contest.
 */
(function ($, Drupal, window, document) {
  'use strict';

  Drupal.behaviors.contest = {
    attach: function (context, settings) {
      $(window).on('load',(function() {

        // T&C toggle.
        $('#contest-tnc').css('display', 'none');
        $('#contest-tnc-toggle').click(function () { $('#contest-tnc').slideToggle('slow'); });

        // Profile toggle.
        if ($('#contest-profile').hasClass('complete-profile')) {
          $('#contest-profile').css('display', 'none');
        }
        $('#contest-profile-toggle').click(function () { $('#contest-profile').slideToggle('slow'); });
      }));
    }
  };
}(jQuery, Drupal, this, this.document));
