(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.siteSearch360Suggestions = {

    attach: function (context, settings) {

      var minChars = drupalSettings.sitesearch_360.suggests_min_chars;

      $.each(drupalSettings.sitesearch_360.suggests_forms, function(index, item) {

        $('input[type="search"]', item.trim()).once('ss360Suggestions').each(function () {
          var awesomeInstance = new Awesomplete(this, {
            minChars: minChars,
            filter: function () {
              return true;
            }
          });

          $(this).on('input', $.debounce(200, function (e) {
            if (this.value.length < minChars) {
              return false;
            }

            $.ajax({
              url: '/api/search/suggests/' + this.value,
              success: function (data) {
                awesomeInstance._list = data;
                awesomeInstance.evaluate();
              }
            });
          }));

          $(this).on('awesomplete-selectcomplete', function (e) {

            // prevent the field to be filled with the url
            this.value = '';

            window.location = e.originalEvent.text.value;
          });

        });

      });
    }
  };
})(jQuery, Drupal);
