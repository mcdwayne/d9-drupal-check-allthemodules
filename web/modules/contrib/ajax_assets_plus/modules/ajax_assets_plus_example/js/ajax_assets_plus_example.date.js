(function($, Drupal) {

  $(document).ready(function() {
    var link = $('.ajax-assets-plus-example-date__link')[0];
    var url = '/ajax-assets-plus-example/date?_format=json';

    var self_settings = {
      url: url,
      event: 'click',
      element: link
    };
    Drupal.ajaxAssetsPlusAjax(self_settings);

    $(document).on('ajax_assets_plus_success', function(e, element, response) {
      if (element === link) {
        $(link).replaceWith(response.content);
      }
    });
  });

})(jQuery, Drupal);
