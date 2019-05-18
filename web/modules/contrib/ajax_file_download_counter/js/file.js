//  (function ($, Drupal) {
//    Drupal.behaviors.myModuleBehavior = {
//      attach: function (context, settings) {
//      $('.file-download-count').on('click', function() {alert();
//       var fid = $(this).attr('data-fid');
//       var url = '/file/' + fid + '/dlcounter';
//        $.ajax({
//          url: url,
//          success: function(data, textStatus, jqXHR) {
//            console.log(data);
//            $('span#dlcount-' + fid).text(data.dlcount);
//          },
//          error:function (xhr, ajaxOptions, thrownError){
//            console.log('yes');
//           // console.log(thrownError);
//           // console.log(xhr);
//          }
//        });
//        return true;
//      });
//      }
//    };
//  })(jQuery, Drupal);

(function($, Drupal, drupalSettings) {
  jQuery(document).ready(function ($) {
    $('.file-download-count').on('click', function() {
      var fid = $(this).attr('data-fid');
      var url = '/drupal-8.4.4/file/' + fid + '/dlcounter';
      $.ajax({
        url: url,
        success: function(data, textStatus, jqXHR) {
          console.log(data);
          $('span#dlcount-' + fid).text(data.dlcount);
        },
        error:function (xhr, ajaxOptions, thrownError){
          console.log('yes');
         // console.log(thrownError);
         // console.log(xhr);

        }
      });
      return true;
    });
  });
})(jQuery, Drupal, drupalSettings);
