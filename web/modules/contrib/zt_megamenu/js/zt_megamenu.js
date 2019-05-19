/**
 * @file
 */

(function ($, Drupal) {
  $('ul.ztmmenu-list li.has_submenu_li > a').click(function (ev) {
    ev.preventDefault();
    var parent_li = $(this).parent('li');
    parent_li.find(".wsmenu-submenu-level-one > li a").first().trigger('mouseenter');
    $('li.has_submenu_li').not(parent_li).removeClass('active');
    $('.wsmenu-submenu').hide();
    if (!(parent_li.hasClass('active'))) {
      parent_li.addClass('active');
      parent_li.find('.wsmenu-submenu').fadeIn('3000');
    }
    else {
      parent_li.removeClass('active');
      parent_li.find('.wsmenu-submenu').fadeOut('3000');
    }
  });

  $('ul.multi-submenu > li ul.multi-submenu').parent().addClass('has_child');

  $(".wsmenu-submenu-level-one > li").on({
    mouseenter: function () {
      $('.wsmenu-submenu-level-one li').removeClass('liactive');
      $(this).addClass('liactive');
    }
  });

  $(".wsmenu-submenu-level-two > li").on({
    mouseenter: function () {
      $('.wsmenu-submenu-level-two li').removeClass('liactive');
      $(this).addClass('liactive');
    }
  });

  $(".wsmenu-submenu-level-three > li").on({
    mouseenter: function () {
      $('.wsmenu-submenu-level-three li').removeClass('liactive');
      $(this).addClass('liactive');
    }
  });

  $('ul.multi-submenu li a').hover(function () {
    var href = $(this).attr('rel');
    var node = href.split("/");
    var nid = node[node.length - 1];
    if (nid != '') {
      $('.wsmenu-submenu-list-content .wsmenu-content').hide();
      $('#' + nid + '.wsmenu-content').show();
    }
    else {
      $('.wsmenu-submenu-list-content .wsmenu-content').hide();
      $('.wsmenu-submenu-list-content .wsmenu-content').first().show();
    }
  });

  // Handling the closing of the menu when a click is detected outside of the menu.
  $.fn.outside = function (ename, cb) {
    return this.each(function () {
      var $this = $(this),
        self = this;

      $(document).bind(ename, function tempo(e) {
        if (e.target !== self && !$.contains(self, e.target)) {
          cb.apply(self, [e]);
          if (!self.parentNode) {
            $(document.body).unbind(ename, tempo);
          }
        }
      });
    });
  };

  $('nav.ztmmenu').outside('click', function (e) {
    $('ul.wsmenu-submenu').hide('3000');
  });

})(jQuery, Drupal);
