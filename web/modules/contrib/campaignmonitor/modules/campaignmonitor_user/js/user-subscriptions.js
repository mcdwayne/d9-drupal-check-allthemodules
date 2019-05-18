/**
 * @file
 * Javascript functionality for Campaign Monitor User Subscriptions
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.campaignmonitorUserSubscriptions = {
    attach: function (context, settings) {

      // Refresh page after an interval so updated values show
      if ($('.page-refresh').length > 0) {
        console.log('refreshing');
        setTimeout( function(){
          // reload page after 5 seconds giving plenty of time for API to update
          location.reload();
        }  , 2000 );
      }

    }
  };

})(jQuery, Drupal);
