/**
 * @file
 * Navbar filter js file.
 */

(function ($) {
  Drupal.behaviors.navbar_filter = {
    attach: function () {
      $('.navbar-filter').keyup(function () {
        var thisvalue = $(this).val().toLowerCase();
        var menu_ul = $(this).parent('.form-item').siblings('.toolbar-menu');

        if (thisvalue.length === 0) {
          menu_ul.find('ul, li').removeClass('open');
          menu_ul.find('a').show();
        }
        else {
          menu_ul.find('li').removeClass('open');
          menu_ul.find('a').each(function () {
            // Close all submenu.
            var a_text = $(this).text().toLowerCase();
            var match = a_text.match(thisvalue);
            if (match !== null && match.length > 0) {
              $(this).show();
              $(this).parents('li.level-1').find('a').eq(0).show();
              $(this).parents('li.level-2').find('a').eq(0).show();
              $(this).parents('li.level-1').addClass('open');
              $(this).parents('li.level-2').addClass('open');
            }
            else {
              $(this).hide();
            }
          });
        }
      });
    }
  };
})(jQuery);
