/* global jQuery, drupalSettings*/
(function ($) {
  'use strict';
  function tweetThis() {
    var selector = 'a.tweetthis:not(processed), a[href*="twitter.com/intent/tweet"]';
    $(selector).each(function () {
      var via = drupalSettings.twitterprofiler;
      var tweetUrl = $(this).attr('href');
      if (!via) {
        via = drupalSettings.sitename;
      }
      tweetUrl = encodeURI(tweetUrl + '&url=' + location.href + '&via=' + via);
      $(this).attr('href', tweetUrl).addClass('processed').attr('target', '_blank');
    });
  }
  $(document).ready(function () {
    tweetThis();
  });
}(jQuery));
