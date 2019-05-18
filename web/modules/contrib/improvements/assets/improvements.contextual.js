(function ($) {
  // Add query string to "destination" param in contextual links.
  // @see initContextual()
  $(document).on('drupalContextualLinkAdded', function (event, data) {
    if (window.location.search) {
      data.$el.find('.contextual-links a').each(function () {
        var url = this.getAttribute('href');
        this.setAttribute('href', url + Drupal.encodePath(window.location.search));
      });
    }
  });
})(jQuery);
