/**
 * @file
 * @Todo
 */

(function ($, Backbone, Drupal) {

  'use strict';

  /**
   * Define namespace for our objects.
   */
  Drupal.esmtoc = {};

  /**
   * Table of content object.
   *
   * @param item
   *  HTML DOM Element pointing to table of content.
   */
  Drupal.esmtoc.toc = function (item) {
    this.$item = $(item);
    this.$trigger = this.$item.find('.esm-toc__toggle');
    this.areas = [];
    this.links = [];
    this.items = [];

    /**
     * Initialize function.
     */
    this.initialize = function () {
      var $this = this;

      // Set body class for esm-toc.
      $('body').addClass('esm-toc-tray');

      this.$trigger.on('click', function (e) {
        $this.toggleExpand();
      });

      this.$item.find('.esm-toc__link').each(function (index, item) {
        $this.links.push(new Drupal.esmtoc.tocLink(item, $this));
      });

      $('.esm-area').each(function () {
        $this.areas.push(new Drupal.esmtoc.area(this));
      });

      $('.esm-toc__item').each(function (e) {
        $this.items.push(new Drupal.esmtoc.tocItem(this, $this));
      });
    };

    /**
     * Helper function to disable all areas for this table of content.
     */
    this.disableAreas = function () {
      this.areas.forEach(function (area) {
        area.deactivate();
      });
    };

    /**
     * Helper function to disable all links for this table of content.
     */
    this.disableLinks = function () {
      this.links.forEach(function (link) {
        link.deactivate();
      });
    };

    /**
     * Helper function to disable all items for this table of content.
     */
    this.disableItems = function () {
      this.items.forEach(function (item) {
        item.deactivate();
      });
    };

    /**
     * Helper function to expand/collapse sidebar menu.
     */
    this.toggleExpand = function () {
      var item = this.$item;
      if (!item.hasClass('esm-toc--collapsed')) {
        this.collapse();
      } else {
        this.expand();
      }
    };

    /**
     * Helper function to expand sidebar menu.
     */
    this.expand = function () {
      this.$item.removeClass('esm-toc--collapsed');
      this.$item.addClass('esm-toc--expanded');
      $('body').removeClass('esm-toc-tray--collapsed').addClass('esm-toc-tray--expanded');
    };

    /**
     * Helper function to expand sidebar menu.
     */
    this.collapse = function () {
      this.$item.removeClass('esm-toc--expanded');
      this.$item.addClass('esm-toc--collapsed');
      $('body').removeClass('esm-toc-tray--expanded').addClass('esm-toc-tray--collapsed');
    };

    this.initialize();
  };


  /**
   * Table of content item object.
   *
   * @param item
   *  HTML DOM Element pointing to table of content item.
   */
  Drupal.esmtoc.tocItem = function (item, toc) {
    this.$item = $(item);
    this.toc = toc;
    this.hoverDelay = 100;
    this.hoverStatus = false;

    var $this = this;

    /**
     * Helper function to disable all items and set hover class for this item.
     */
    this.hover = function () {
      $this.toc.disableItems();

      $this.$item.addClass('esm-toc__item--hover');
    };

    /**
     * Helper function to remove class from this item.
     */
    this.deactivate = function () {
      $this.$item.removeClass('esm-toc__item--hover');
    };

    this.$item.mouseenter(function (e) {
      $this.hoverStatus = true;

      setTimeout($this.hover, $this.hoverDelay);
    });
    this.$item.mouseleave(function (e) {
      $this.hoverStatus = false;
    });
  };





  /**
   * Table of content link object.
   *
   * @param item
   *  HTML DOM Element pointing to table of content link.
   */
  Drupal.esmtoc.tocLink = function (item, toc) {
    this.$item = $(item);
    this.active = false;
    this.toc = toc;

    /**
     * Helper function to get area jquery Object.
     *
     * @returns {*|HTMLElement}
     */
    this.getArea = function () {
      return $('#' + this.$item.data('area'));
    };

    /**
     * Helper function to get field jquery Object.
     *
     * @returns {*|HTMLElement}
     */
    this.getField = function () {
      var field;
      /**
       * Helper function that is only needed in this context.
       * @param $item
       */
      function findFormElement($item) {
        var selectors = new Array('input', 'textarea', 'select', 'button', 'option');
        var formElement;

        selectors.some(function (selector) {
          if ($item.find(selector).length) {
            formElement = $item.find(selector).first();
            return formElement;
          }
        });

        return formElement;
      }

      // Check if field data attribute is set, if not return first input field.
      if (!this.$item.data('field')) {
        field = findFormElement(this.area.$item);
      } else {
        field = findFormElement($(this.$item.data('field')));
      }

      return field;
    };

    /**
     * Helper function to set class on active element.
     */
    this.activate = function () {
      this.$item.addClass('esm-toc__link--active');
    };

    /**
     * Helper function to remove class on deactivated element.
     */
    this.deactivate = function () {
      this.$item.removeClass('esm-toc__link--active');
    };

    this.area = new Drupal.esmtoc.area(this.getArea());
    this.field = new Drupal.esmtoc.field(this.getField());

    var $this = this;
    this.$item.on('click', function(e) {
      // First disabled all areas and than set focus on area matching this item.
      $this.toc.disableAreas();
      $this.toc.disableLinks();

      // Activate link item, activate area, scroll to field, activate field.
      $this.activate();
      $this.area.activate();
      $this.field.scrollTo();
      $this.field.focus();
    });
  };

  /**
   * Area object.
   *
   * @param item
   *  HTML DOM Element pointing to area element.
   */
  Drupal.esmtoc.area = function (item) {
    this.$item = $(item);

    /**
     * Helper function to activate this area.
     *
     * @param {boolean} value
     * @returns {shoestring|string|*}
     */
    this.setState = function (value) {
      return this.$item.attr('data-active', value);
    };

    /**
     * Helper function to set this area element into focus.
     */
    this.activate = function () {
      this.setState(true);
    };

    /**
     * Helper function to disabled this area.
     */
    this.deactivate = function () {
      this.setState(false);
    };
  };

  /**
   * Field object.
   *
   * @param item
   *  HTML DOM Element pointing to area element.
   */
  Drupal.esmtoc.field = function (item) {
    this.$item = $(item);

    /**
     * Helper function to get element position from top.
     *
     * @returns {number}
     *  Pixel value where this element is position from top minus admin menu.
     */
    this.scrollTop = function () {
      return this.$item.parents('.form-item').offset().top - 80;
    };

    /**
     * Helper function to scroll this element into viewport.
     */
    this.scrollTo = function () {
      // Scroll to matching area.
      $([document.documentElement, document.body]).animate({
        scrollTop: this.scrollTop(),
      }, 300);
    };

    /**
     * Helper function to set focus on this element.
     */
    this.focus = function () {
      this.$item.focus();
    };
  };

  /**
   * Set active navigation item based on active waypoint.
   *
   * @param toc
   *  Complete toc item used on page.
   * @param active
   *  Active toc area id name.
   */
  Drupal.esmtoc.setActiveTocItem = function (toc, active) {
    // Remove all active markers by default.
    $(toc).find('.esm-toc__item').removeClass('esm-toc__item--active');
    // Set active toc item by active waypoint.
    var toc_item = $(toc).find("[data-area=" + active + "]");
    $(toc_item).parent().not('.esm-toc__item-child').addClass('esm-toc__item--active');
  };

  /**
   * @Todo
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour snippet form tabs.
   *
   *   Specifically, it updates summaries to the revision information and the
   *   translation options.
   */
  Drupal.behaviors.esmtoc = {
    attach: function (context) {
      var $context = $(context);

      $context.find('.esm-toc').each(function(index, item) {
        new Drupal.esmtoc.toc(item);

        // Waypoints - down scrolling.
        $('.esm-area').waypoint(function(direction) {
          if (direction === 'down') {
            Drupal.esmtoc.setActiveTocItem(item, this.element.id);
          }
        },
        {
          offset: '10%'
        });

        // Waypoints - up scrolling.
        $('.esm-area').waypoint(function(direction) {
            if (direction === 'up') {
              Drupal.esmtoc.setActiveTocItem(item, this.element.id);
            }
          },
          {
            offset: '-10%'
          });
      });
    }
  };

})(jQuery, Backbone, Drupal);
