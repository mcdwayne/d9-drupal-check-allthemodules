(function ($, Drupal, window, document, undefined) {

  // https://enginpost.com/category/technology/posts/2015/12/drupal-8-restful-views-and-jquery

  // https://enginpost.com/experiment/drupal-8-restful-autocomplete

  'use strict';

  Drupal.behaviors.teaLeaf = {
    attach: function (context, settings) {

      $('#tealeaf-container', context).on('click', '#tealeaf-finder', function (tf) {
        var theNid = $('#tealeaf-nid').val();
        $.ajax({
          url: '/teatree/node/' + theNid + '?_format=hal_json',
          method: 'GET',
          success: function(data, status, xhr) {
            var dataTitle = data.title[0].value;
            var dataBody = data.field_tea_body[0].value;
            $('#tealeaf-results').html('<p><strong>' + status + ':</strong></p>');
            $('#tealeaf-results').append('<p>' + dataTitle + '</p>');
            $('#tealeaf-results').append('<p>' + dataBody + '</p>');
            // console.log(data);
            // console.log(status);
            // console.log(xhr);
            // debugger
          }
        })
      });

      $('#tealeaf-container', context).on('click', '#tealeaf-loader', function (tl) {

        $.ajax({
          url: '/teatree/node/1?_format=hal_json',
          method: 'GET',
          success: function(data, status, xhr) {
            var dataTitle = data.title[0].value;
            var dataBody = data.field_tea_body[0].value;
            $('#tealeaf-results').html('<p><strong>' + status + ':</strong></p>');
            $('#tealeaf-results').append('<p>' + dataTitle + '</p>');
            $('#tealeaf-results').append('<p>' + dataBody + '</p>');
            // console.log(data);
            // console.log(status);
            // console.log(xhr);
            // debugger
          }
        })
      });

    }

  }


})(jQuery, Drupal, this, this.document);
