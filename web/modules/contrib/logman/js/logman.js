(function ($) {
  Drupal.behaviors.logman = {
    attach: function (context, settings) {
      // Initialize display.
      $('#logman_icon_close').css('display', 'none');

      // Make sure logman UI sticks on the page
      // even if the page is scrolled.
      stickLogmanOnPage();
      $(window)
        .scroll(stickLogmanOnPage)
        .resize(stickLogmanOnPage);

      // Bind click event to logman icon and close button
      // for display and closing on page UI.
      $('#logman_icon').click(function() {
        toggleLogmanContainer($('#logman_container').hasClass('active'));
      });
      $('#logman_icon_close').click(function() {
        toggleLogmanContainer($('#logman_container').hasClass('active'));
      });

      // Close the UI on pressing esc key.
      $(document).keydown(function (event) {
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if (keycode == 27) {
          toggleLogmanContainer(true);
        }
      });

      // Function for making the on page UI to stick to the middle of the browser.
      function stickLogmanOnPage() {
        var footer = $("#logman_container");
        var windowH = $(window).height();
        var footerHeight = footer.height() + 15;
        var footerTop = ($(window).scrollTop() + windowH - footerHeight) + "px";
        footer.css({'top' : footerTop});
      }

      function toggleLogmanContainer(hide) {
        if (hide == true) {
          $('#logman_container').removeClass('active');
          $('#logman_statistics').removeClass('active');
          $('#logman_statistics').addClass('inactive');
          $('#logman_icon').css('display', '');
          $('#logman_icon_close').css('display', 'none');
        }
        else {
          $('#logman_container').addClass('active');
          $('#logman_statistics').removeClass('inactive');
          $('#logman_statistics').addClass('active');
          $('#logman_icon').css('display', 'none');
          $('#logman_icon_close').css('display', '');
        }
      }
    }
  };
}(jQuery));
