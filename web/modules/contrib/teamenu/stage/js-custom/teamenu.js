(function ($, Drupal, window, document, undefined) {

  "use strict";

  Drupal.behaviors.teaMenu = {
    attach: function (context, settings) {
      // Focus on menu.
      $('nav.block-menu', context).on('focus', 'ul.menu', function (e) {
        $(this).blur();
      });
      // Focus on menu link.
      $('nav.block-menu ul.menu li', context).on('focus', '> a', function (e) {
        $(this).addClass('focus').removeClass('blur');
      });
      $('nav.menu-teamenu ul.menu li', context).on('focus', '> a', function (e) {
        $(this).siblings('.menu-toggle').addClass('menu-shown');
        $(this).parents().siblings('.menu-toggle').addClass('menu-shown');
      });
      // Blur on menu link.
      $('nav.block-menu ul.menu li', context).on('blur', '> a', function (e) {
        $(this).addClass('blur').removeClass('focus');
      });
      $('nav.menu-teamenu ul.menu li', context).on('blur', '> a', function (e) {
        $(this).siblings('.menu-toggle').removeClass('menu-shown');
        $(this).parents().not('.menu-root').siblings('.menu-toggle').removeClass('menu-shown');
      });
      // Hover on unshown desktop or widescreen L1 menu item.
      $(document, context).on({
        mouseenter: function () {
          $(this).children('.menu-toggle').addClass('menu-hovered');
        },
        mouseleave: function () {
          $(this).children('.menu-toggle').removeClass('menu-hovered');
        }
      }, "nav.block-menu ul.menu-root > li.menu-item--expanded:not(.menu-shown)");

      // Click on menu toggle.
      $('nav.menu-teamenu', context).on('click', '.menu-toggle', function () {
        if ($(this).is('.menu-shown')) {
          $(this).html('<span class="visually-hidden">show this menu</span>');
          $(this).removeClass('menu-shown');
        } else {
          $(this).html('<span class="visually-hidden">hide this menu</span>');
          $(this).addClass('menu-shown');
        }
      });
      $('nav.menu-not-teamenu', context).on('click', '> .menu-toggle', function () {
        if ($(this).is('.menu-shown')) {
          $(this).html('<span class="visually-hidden">show this menu</span>');
          $(this).removeClass('menu-shown');
        } else {
          $(this).html('<span class="visually-hidden">hide this menu</span>');
          $(this).addClass('menu-shown');
        }
      });
    }
  };

  Drupal.behaviors.keyNav = {
    attach: function (context, settings) {

      // Manipulate the DOM to add classes and other attributes and contents.
      $('nav.block-menu ul.menu-root', context).each(function (e) {
        $(this).addClass('open').attr('role', 'menubar').attr('tabindex', '-1');
        $(this).find('ul').attr('role', 'menu').attr('tabindex', '-1');
        $(this).find('li').attr('role', 'presentation').attr('tabindex', '-1');
        $(this).find('> li > a').attr('tabindex', '0').attr('role', 'menuitemradio');
        $(this).find('ul li > a').attr('tabindex', '-1').attr('role', 'menuitemradio');
        $(this).find('li.menu-item--expanded').attr('aria-haspopup', 'true');
        $(this).find('li.active a.active').attr('aria-selected', 'true');
        $(this).not('.menu-root-processed').addClass('menu-root-processed');
      });
      $('nav.menu-not-teamenu ul.menu-root', context).each(function (e) {
        $(this).find('.menu-toggle').addClass('menu-shown');
        $(this).find('.menu-toggle').html('<span class="visually-hidden">hide this menu</span>');
      });

      // Activate the menubar.
      $('nav.block-menu', context).each(function (e) {
        var thisId = $(this).attr('id');
        var thisMenu = new menubar(thisId, e);
      });
    }
  };

  /**
   * Function menubar() is the constructor of a menu widget.
   *
   * @param(id string) id is the HTML id of the aria_menus block to bind to.
   *
   * @return N/A.
   */
  function menubar(id, e) {
    this.$id = $('#' + id);
    this.$rootList = this.$id.find('ul.menu-root');
    this.$nonRootLists = this.$id.find('ul.menu-root > li ul');
    this.$items = this.$id.find('a');
    this.$activeItem = null;
    this.keys = {
      tab: 9,
      enter: 13,
      esc: 27,
      space: 32,
      left: 37,
      up: 38,
      right: 39,
      down: 40
    };
    this.addMarkup(e);
    this.bindHandlers(e);
  }

  /**
   * Function addMarkup() adds markup for the widget.
   *
   * @return N/A.
   */
  menubar.prototype.addMarkup = function (menucount) {
    // Add markup to Anchor tags.
    this.$rootList.find('a').each(function (e) {
      $(this).attr('id', 'm' + menucount + 'a' + e);
      return true;
    });
    // Add markup to non-root ULs.
    this.$nonRootLists.each(function (e) {
      var parentId = $(this).siblings('a').attr('id');
      $(this).attr('aria-labelledby', parentId);
      return true;
    });
  };

  /**
   * Function bindHandlers() binds event handlers for the widget.
   *
   * @return N/A.
   */
  menubar.prototype.bindHandlers = function () {
    var thisObj = this;
    // Bind a keydown handler.
    this.$items.keydown(function (e) {
      return thisObj.handleKeyDown($(this), e);
    });
  };

  /**
   * Function handleKeyDown() is a member function to process keydown.
   *
   * @param($item object) $item is the jquery object of the item firing the event.
   *
   * @param(e object) is the associated event object.
   *
   * @return(boolean) Returns false if consuming; true if propagating.
   */
  menubar.prototype.handleKeyDown = function ($item, e) {
    switch (e.keyCode) {
        // Handle the SPACE key.
      case this.keys.space:
        var theURL = $item.attr('href');
        window.location.href = theURL;
        e.stopPropagation();
        return false;
        break;
    // Handle the ENTER key.
      case this.keys.enter:
        var theURL = $item.attr('href');
        window.location.href = theURL;
        e.stopPropagation();
        return false;
        break;
    // Handle the TAB key.
      case this.keys.tab:
        // TAB with ALT key.
        if (e.altKey) {
          e.stopPropagation();
          return false;
        }
        // TAB with SHIFT key.
        if (e.shiftKey) {
          if ($item.parent().parent().is('.menu-root')) {
            if ($item.parent().is(':first-child')) {
              return true;
            }
          }
          if (!$item.parent().is(':first-child')) {
            this.$activeItem = $item.parent().prev().children('a');
            this.$activeItem.focus();
          }
          e.stopPropagation();
          return false;
        }
        // TAB without SHIFT key.
        else {
          // Skip from last link in menu to next focussable element via propogation.
          if ($item.parent().parent().is('.menu-root')) {
            if ($item.closest('li').is(':last-child')) {
              return true;
            }
          }
          if ($item.parent().not(':last-child')) {
            this.$activeItem = $item.parent().next().children('a');
            this.$activeItem.focus();
          }
          e.stopPropagation();
          return false;
        }
        return true;
        break;
    // Handle the ESCAPE key.
      case this.keys.esc:
        if ($item.parent().parent().is('.menu-root')) {
          e.stopPropagation();
          return true;
        } else {
          this.$activeItem = $item.parent().parent().siblings('a');
          this.$activeItem.focus();
          e.stopPropagation();
          return false;
        }
        return true;
        break;
    // Handle the LEFT ARROW key.
      case this.keys.left:
        if (!$item.parent().is(':first-child')) {
          this.$activeItem = $item.parent().prev().children('a');
          this.$activeItem.focus();
        }
        e.stopPropagation();
        return false;
        break;
    // Handle the RIGHT ARROW key.
      case this.keys.right:
        if (!$item.parent().is(':last-child')) {
          this.$activeItem = $item.parent().next().children('a');
          this.$activeItem.focus();
        }
        e.stopPropagation();
        return false;
        break;
    // Handle the UP ARROW key.
      case this.keys.up:
        if (!$item.parent().parent().is('.menu-root')) {
          this.$activeItem = $item.parent().parent().siblings('a');
          this.$activeItem.focus();
        }
        e.stopPropagation();
        return false;
        break;
    // Handle the DOWN ARROW key.
      case this.keys.down:
        if ($item.parent().is('.menu-item--expanded')) {
          // Jump down.
          this.$activeItem = $item.siblings('ul').children('li:first-child').children('a');
          this.$activeItem.focus();
          e.stopPropagation();
          return false;
        }
        break;
    // Handle the default case.
      default:
        return true;
        break;
    }
  };

})(jQuery, Drupal, this, this.document);
