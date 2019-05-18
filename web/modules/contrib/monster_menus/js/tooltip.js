(function ($) {
  $('.tooltip-link').tooltip({content: function() {
    return this.title;
  }});
})(jQuery);