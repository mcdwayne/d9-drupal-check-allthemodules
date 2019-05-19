/**
 * @file
 * Provides the summary information for the views vertical tabs.
 */

(function ($) {

"use strict";

Drupal.behaviors.viewsVerticalTabsSummary = {
  attach: function (context, settings) {
    // The drupalSetSummary method required for this behavior so we need to make
    // sure this behavior is processed only if drupalSetSummary is defined.
    if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
      return;
    }

    $.each(settings.views_vertical_tabs, function(){
      $.each(this, function(id, summary){
        $('details#' + id, context).drupalSetSummary(function(context) {
          return summary;
        });
      });
    });

  }
};

})(jQuery);
