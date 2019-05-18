/**
 * @file
 * Cheeseburger Menu JavaScript file.
 */

(function ($, drupalSettings) {

  'use strict';

  $(document).ready(function () {

    var block_id = drupalSettings.block_id;
    var headerHeight = drupalSettings.headerHeight;
    var headerSize = parseInt(headerHeight);
    var breakpoints = drupalSettings.breakpoints;
    var activateMenu;
    var instantShow = drupalSettings.instant_show;
    var currentRoute = drupalSettings.current_route;

    if (instantShow) {
      includeCode();
    }
    else {
      activateMenu = true;
      if (typeof breakpoints === typeof undefined) {
        return;
      }
      if (breakpoints['from'] !== '0') {
        if (!window.matchMedia(breakpoints['from']).matches) {
          activateMenu = false;
        }
      }
      if (window.matchMedia(breakpoints['to']).matches) {
        activateMenu = false;
      }
      if (activateMenu !== false) {
        $.ajax({
          url: '/cheeseburger-menu-render-request',
          dataType: 'html',
          type: 'post',
          data: {
            block_id: block_id,
            current_route: currentRoute
          },
          contentType: 'application/x-www-form-urlencoded',
          success: function (data, textStatus, jQxhr) {
            $('.cheeseburger-menu__wrapper').html(data);
            includeCode();
          }
        });
      }
    }

    function includeCode() {

      var $active = $('.cheeseburger-menu__menu-list-item-link.active');
      var hasActive = $active.length > 0;
      $('.cheeseburger-menu__trigger').on('click touchstart', function (e) {
        $('body, .cheeseburger-menu__wrapper').toggleClass('menu-is-visible');
        $('.cheeseburger-menu__trigger').toggleClass('is-open');
        e.preventDefault();
      });
      var scrollTo = function (element, to, duration) {

        var start = element.scrollTop,
            change = to,
            currentTime = 0,
            increment = 20;

        var animateScroll = function () {
          currentTime += increment;
          var val = Math.easeInOutQuad(currentTime, start, change, duration);
          element.scrollTop = val;
          if (currentTime < duration) {
            setTimeout(animateScroll, increment);
          }
        };
        animateScroll();
      };

      Math.easeInOutQuad = function (t, b, c, d) {
        t /= d / 2;
        if (t < 1) {
          return c / 2 * t * t + b;
        }
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
      };

      $('.cheeseburger-menu__navigation-list-item a, .cheeseburger-menu__navigation-list-item img, .cheeseburger-menu__navigation-list-item span').on('click touchstart', function (e) {
        var selectedMenu = $(this).parent().attr('data-drupal-selector');
        if ((selectedMenu !== 'cheeseburger-menu--cart') && (selectedMenu !== 'cheeseburger-menu--phone')) {
          $('.cheeseburger-menu__navigation-list-item').removeClass('cheeseburger-menu__navigation-list-item--active');
          $(this).parent().addClass('cheeseburger-menu__navigation-list-item--active');

          $('.cheeseburger-menu__menu').removeClass('cheeseburger-menu__menu--active');
          $('.cheeseburger-menu__menu[data-drupal-selector="' + selectedMenu + '"]').addClass('cheeseburger-menu__menu--active');

          var elem = $('.cheeseburger-menu__menu--active .cheeseburger-menu__menu-list-trigger');
          var topPosEl = elem.offset();
          var topPos = topPosEl.top;

          scrollTo(document.getElementsByClassName('cheeseburger-menu__menus')[0], topPos - headerSize, 600);
          e.preventDefault();
        }
      });

      var checkScroll = false;
      var scrollStart = 0;
      var scrollEnd = 0;

      $('.cheeseburger-menu__menu-list-item--expanded > a, .cheeseburger-menu__menu-list-item--expanded > img, .cheeseburger-menu__menu-list-item--expanded > span').on('touchstart', function (event) {
        scrollStart = $('.cheeseburger-menu__menus').scrollTop();
      });

      $('.cheeseburger-menu__menu-list-item--expanded > a, .cheeseburger-menu__menu-list-item--expanded > img, .cheeseburger-menu__menu-list-item--expanded > span').on('touchend', function (event) {
        scrollEnd = $('.cheeseburger-menu__menus').scrollTop();
        if (scrollStart !== scrollEnd) {
          checkScroll = true;
        }
        else {
          checkScroll = false;
        }
      });

      $('.cheeseburger-menu__menu-list-item--expanded > a, .cheeseburger-menu__menu-list-item--expanded > img, .cheeseburger-menu__menu-list-item--expanded span').bind('mouseup touchend', function (event) {
        if (checkScroll === false) {
          if ($(this).parent().attr('class') === 'cheeseburger-menu__menu-list') {
            $('.cheeseburger-menu__menu-list-item--parent > ul.open-parent').toggleClass('open-parent');
          }
          else {
            $(this).next('ul').toggleClass('open-child');
          }
          $(this).next('ul').toggleClass('open-parent');
          $(this).toggleClass('is-opened');
        }

        if (event.handled === false) {
          return;
        }
        event.preventDefault();
        event.handled = true;
        event.stopPropagation();
      });

      if (hasActive) {
        $active
            .first()
            .parents('ul')
            .each(function (index, element) {
              var $el = $(element);

              if ($el.hasClass('.cheeseburger-menu__menu-list')) {
                return false;
              }

              $el.toggleClass('open-parent');
              $el.prev('.cheeseburger-menu__menu-list-item-link').toggleClass('is-opened');

            });

        return;
      }

      $('.cheeseburger-menu__menu:first-of-type').addClass('cheeseburger-menu__menu--active');
    }
  });
})(jQuery, drupalSettings);