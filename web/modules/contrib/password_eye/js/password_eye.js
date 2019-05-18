/**
 * @file
 * Contains \Drupal\view_password\password.js.
 */

(function ($) {

  Drupal.behaviors.pwd = {
    attach: function (context) {

      $(".pwd-see :password").after("<span class='shwpd eye-close'></span>");
      // To stop repeating of image on rebuild of form.
      $('span.eye-close').each(function () {
        while ($(this).prop('tagName') == $(this).next().prop('tagName')) {
          $(this).next().remove();
        }
      });

      $(".shwpd").on('click', function () {

        var pwd_clk_nme = $(this).prev(':password').attr('name');

        // If two password fields are there, then stop both from acting.
        if ($(this).prev(':password').attr('name') == pwd_clk_nme) {

          // To toggle the images.
          $(this).toggleClass("eye-close eye-open");

          // Get the classnames of clicked element.
          var classNames = $(this).attr("class").toString().split(' ');
          $.each(classNames, function (i, className) {

            if (className == 'eye-open') {
              $('.eye-open').prev(':password').prop('type', 'text');
            }
            else if (className == 'eye-close') {
              $('.eye-close').prev(':text').prop('type', 'password');
            }
          });
        }
      });

    }
  };
})(jQuery);
