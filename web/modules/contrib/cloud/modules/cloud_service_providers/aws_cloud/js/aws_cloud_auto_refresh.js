(function ($) {
  // The interval to update a view's content.
  var refresh_in_sec = drupalSettings.aws_cloud_view_refresh_interval || 10;

  // The function to get a view's content in an Ajax way.
  var auto_update = function() {

    // The url of the callback for a view.
    var url = window.location.pathname + "/callback";

    // The query string of current request.
    // It is necessary because query parameters of a view, such as sorting and
    // paging, should be passed to callback in order to get the content with the
    // same sorting and paging as the initial view's content.
    var query_str = window.location.search;

    // Update .view-content element, which is the content of view, excluding
    // exposed filter and pager.
    $.get(url + query_str, function(data) {
      $(".views-element-container .view-content")
        .replaceWith($(data).find(".view-content"));

      // Fix for the theme Bartik, which need to initialize drop buttons.
      if (Drupal.behaviors.dropButton) {
        Drupal.behaviors.dropButton.attach(document, drupalSettings);
      }
    });
  }

  // Update a view's content every "refresh_in_sec" seconds.
  setInterval(auto_update, refresh_in_sec * 1000);
})(jQuery);
