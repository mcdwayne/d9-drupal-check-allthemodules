(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.AjaxLinkChange = {
    attach: function (context) {
      $('a.data-switcher', context).once().bind('click', function (event) {
        event.preventDefault();
        var href = $(this).attr('href');
        var ajax_settings = {
          url: href,
          element: this
        };
        Drupal.ajax(ajax_settings).execute();
      });
    }
  };

  Drupal.AjaxCommands.prototype.AjaxLinkChangeCommand = function (ajax, response, status) {
    var status_content = '';
    if (response.current_value !== null && typeof (response.current_value) != 'undefined') {
      var element = $(ajax.element);
      element.attr('data-init', response.current_value);
      var true_value = element.attr('data-true');
      status_content = element.attr('data-label-off');
      if (response.current_value === true_value) {
        status_content = element.attr('data-label-on');
      }
      element.toggleClass('active');
      element.find('span.status').html(status_content);
    }
  };

})(jQuery, Drupal);
