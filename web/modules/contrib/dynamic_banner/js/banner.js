 (function ($, Drupal) {
    /*  $(window).load(function () {
        // Execute on page load
      });
      $(window).resize(function () {
        // Execute code when the window is resized.
      });
      $(window).scroll(function () {
        // Execute code when the window scrolls.
      });
      
*/
      $(document).ready(function () {
          var selectedItem = $('input[name=image_type]:checked').val();
           if( selectedItem == 'Use Existing Image(s)'){
              $('.form-item-image').css('display', 'none');
              $('.form-item-imgurl').css('display', 'block');
           }
           else{
              $('.form-item-imgurl').css('display', 'none');
              $('.form-item-image').css('display', 'block');
           } 

          $('input[name=image_type]').on('change', function() {
              var selectedItem = $('input[name=image_type]:checked').val();
               if( selectedItem == 'Use Existing Image(s)'){
                  $('.form-item-image').css('display', 'none');
                  $('.form-item-imgurl').css('display', 'block');
               }
               else{
                  $('.form-item-imgurl').css('display', 'none');
                  $('.form-item-image').css('display', 'block');
               }
		      });
      }); 

})(jQuery, Drupal);