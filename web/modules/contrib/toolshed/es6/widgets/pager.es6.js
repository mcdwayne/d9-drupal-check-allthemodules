/* eslint no-bitwise: ["error", { "allow": ["^", ">>"] }] */
Drupal.Toolshed = Drupal.Toolshed || {};

(($) => {
  /**
   * A pager that is meant to flip through a list of items without refreshing
   * the page. This can be something like slider navigation or a gallery.
   *
   * Loading and unloading content is beyond the scope of this pager.
   *
   * TODO: Add events for when a page change is triggered. This could allow
   * for more enhanced interactions and dynamic loading of content.
   */
  Drupal.Toolshed.InplacePager = class {
    /**
     * Creates a new instance of a pager
     *
     * @param {JQuery} container - a JQuery wrapped element to contain the pager.
     * @param {Mixed} items - Items to page through. Either an array or a callback
     *   for generating the navigational items.
     * @param {Object} settings - Setting that determine callbacks and how pager
     *   items are supposed to display on the page.
     */
    constructor(container, items, settings) {
      this.container = container;
      this.settings = { show: 8, ...settings };
      this.element = $('<ul class="pager pager--inplace inline"></ul>').appendTo(this.container);
      this.element.wrap('<div class="pager-wrapper">');

      // Keeps track of the items currently being displayed by the pager.
      this.displayed = {
        startAt: 1, // Starting index of items being displayed.
        endAt: 0, // Ending index of items being displayed.
        items: [], // Current set of items that are being displayed.
      };

      // Keeps track of all the current pager items, creating / loaded.
      this.items = [];

      if (settings.onNavClick) {
        this.onNavClick = settings.onNavClick;
      }

      if (items.theme) {
        for (let i = 0; i < items.count; ++i) {
          this.items.push($(Drupal.theme[items.theme](i, i + 1)));
        }
      }
      else {
        this.items = items;
      }

      items.forEach((item, i) => {
        item.on('click', this, this.onNavClick).data('index', i);
        item.wrap('<li class="pager__item">');
        item = item.parent();
      });

      // Set this pager to display the first item.
      if (this.settings.show < this.items.length) {
        this.ellipsisFront = $('<span class="pager-ellipsis pager-ellipsis--front">...</span>').hide().insertBefore(this.element);
        this.ellipsisEnd = $('<span class="pager-ellipsis pager-ellipsis--end">...</span>').hide().insertAfter(this.element);
      }
      else {
        this._displayItems(0, this.items.length - 1);
      }

      this.setActive(0);
    }

    get(index) {
      return index < this.items.length ? this.items[index] : this.items[this.items.length - 1];
    }

    setActive(setTo) {
      let index;
      let item;

      $('.pager__item--active', this.elem).removeClass('pager__item--active');

      if ($.isNumeric(setTo)) {
        index = setTo;
        item = this.get(setTo);
      }
      else {
        item = setTo;
        index = setTo.data('index');
      }

      // If able to find the item, update the display and its state.
      if (item) {
        const numVisible = this.settings.show;

        if (numVisible < this.items.length) {
          let start = Math.max(index - (numVisible >> 1), 0);
          let end = (start + numVisible) - 1;

          // If at the end of the list, then offset the display from the back.
          if (end >= this.items.length) {
            end = this.items.length - 1;
            start = Math.max((end - numVisible) + 1, 0);
          }
          this._displayItems(start, end);
        }

        item.addClass('pager__item--active');
      }
    }

    _displayItems(start, end) {
      let cur;
      const display = this.displayed;
      const { items } = display;

      // If there were previous items, remove the first and last classes.
      if (items.length) {
        items[0].removeClass('pager__item--first');
        items[items.length - 1].removeClass('pager__item--last');
      }

      // Remove items from the front of the list.
      while (display.startAt < start) {
        items.shift().detach();
        display.startAt += 1;
      }

      // Add items to the front of the list.
      while (display.startAt > start) {
        display.startAt -= 1;
        cur = this.items[display.startAt];
        if (cur) {
          items.unshift(cur);
          this.element.prepend(cur);
        }
      }

      while (display.endAt > end) {
        items.pop().detach();
        display.endAt -= 1;
      }

      while (display.endAt < end) {
        display.endAt += 1;
        cur = this.items[display.endAt];
        if (cur) {
          items.push(cur);
          this.element.append(cur);
        }
      }

      // Determine which ellipsis are visible.
      if (this.ellipsisFront && ((display.startAt !== 0) ^ this.ellipsisFront.is(':visible'))) {
        this.ellipsisFront.toggle();
      }
      if (this.ellipsisEnd && ((display.endAt !== this.items.length - 1) ^ this.ellipsisEnd.is(':visible'))) {
        this.ellipsisEnd.toggle();
      }

      items[0].addClass('pager__item--first');
      items[items.length - 1].addClass('pager__item--last');
    }

    /**
     * Remove the items added to the DOM.
     */
    destroy() {
      this.items.forEach(item => item.remove());
      this.element.remove();
    }
  };
})(jQuery);
