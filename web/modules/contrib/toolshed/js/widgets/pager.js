'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/* eslint no-bitwise: ["error", { "allow": ["^", ">>"] }] */
Drupal.Toolshed = Drupal.Toolshed || {};

(function ($) {
  /**
   * A pager that is meant to flip through a list of items without refreshing
   * the page. This can be something like slider navigation or a gallery.
   *
   * Loading and unloading content is beyond the scope of this pager.
   *
   * TODO: Add events for when a page change is triggered. This could allow
   * for more enhanced interactions and dynamic loading of content.
   */
  Drupal.Toolshed.InplacePager = function () {
    /**
     * Creates a new instance of a pager
     *
     * @param {JQuery} container - a JQuery wrapped element to contain the pager.
     * @param {Mixed} items - Items to page through. Either an array or a callback
     *   for generating the navigational items.
     * @param {Object} settings - Setting that determine callbacks and how pager
     *   items are supposed to display on the page.
     */
    function _class(container, items, settings) {
      var _this = this;

      _classCallCheck(this, _class);

      this.container = container;
      this.settings = _extends({ show: 8 }, settings);
      this.element = $('<ul class="pager pager--inplace inline"></ul>').appendTo(this.container);
      this.element.wrap('<div class="pager-wrapper">');

      // Keeps track of the items currently being displayed by the pager.
      this.displayed = {
        startAt: 1, // Starting index of items being displayed.
        endAt: 0, // Ending index of items being displayed.
        items: [] // Current set of items that are being displayed.
      };

      // Keeps track of all the current pager items, creating / loaded.
      this.items = [];

      if (settings.onNavClick) {
        this.onNavClick = settings.onNavClick;
      }

      if (items.theme) {
        for (var i = 0; i < items.count; ++i) {
          this.items.push($(Drupal.theme[items.theme](i, i + 1)));
        }
      } else {
        this.items = items;
      }

      items.forEach(function (item, i) {
        item.on('click', _this, _this.onNavClick).data('index', i);
        item.wrap('<li class="pager__item">');
        item = item.parent();
      });

      // Set this pager to display the first item.
      if (this.settings.show < this.items.length) {
        this.ellipsisFront = $('<span class="pager-ellipsis pager-ellipsis--front">...</span>').hide().insertBefore(this.element);
        this.ellipsisEnd = $('<span class="pager-ellipsis pager-ellipsis--end">...</span>').hide().insertAfter(this.element);
      } else {
        this._displayItems(0, this.items.length - 1);
      }

      this.setActive(0);
    }

    _createClass(_class, [{
      key: 'get',
      value: function get(index) {
        return index < this.items.length ? this.items[index] : this.items[this.items.length - 1];
      }
    }, {
      key: 'setActive',
      value: function setActive(setTo) {
        var index = void 0;
        var item = void 0;

        $('.pager__item--active', this.elem).removeClass('pager__item--active');

        if ($.isNumeric(setTo)) {
          index = setTo;
          item = this.get(setTo);
        } else {
          item = setTo;
          index = setTo.data('index');
        }

        // If able to find the item, update the display and its state.
        if (item) {
          var numVisible = this.settings.show;

          if (numVisible < this.items.length) {
            var start = Math.max(index - (numVisible >> 1), 0);
            var end = start + numVisible - 1;

            // If at the end of the list, then offset the display from the back.
            if (end >= this.items.length) {
              end = this.items.length - 1;
              start = Math.max(end - numVisible + 1, 0);
            }
            this._displayItems(start, end);
          }

          item.addClass('pager__item--active');
        }
      }
    }, {
      key: '_displayItems',
      value: function _displayItems(start, end) {
        var cur = void 0;
        var display = this.displayed;
        var items = display.items;

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
        if (this.ellipsisFront && display.startAt !== 0 ^ this.ellipsisFront.is(':visible')) {
          this.ellipsisFront.toggle();
        }
        if (this.ellipsisEnd && display.endAt !== this.items.length - 1 ^ this.ellipsisEnd.is(':visible')) {
          this.ellipsisEnd.toggle();
        }

        items[0].addClass('pager__item--first');
        items[items.length - 1].addClass('pager__item--last');
      }

      /**
       * Remove the items added to the DOM.
       */

    }, {
      key: 'destroy',
      value: function destroy() {
        this.items.forEach(function (item) {
          return item.remove();
        });
        this.element.remove();
      }
    }]);

    return _class;
  }();
})(jQuery);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndpZGdldHMvcGFnZXIuZXM2LmpzIl0sIm5hbWVzIjpbIkRydXBhbCIsIlRvb2xzaGVkIiwiJCIsIklucGxhY2VQYWdlciIsImNvbnRhaW5lciIsIml0ZW1zIiwic2V0dGluZ3MiLCJzaG93IiwiZWxlbWVudCIsImFwcGVuZFRvIiwid3JhcCIsImRpc3BsYXllZCIsInN0YXJ0QXQiLCJlbmRBdCIsIm9uTmF2Q2xpY2siLCJ0aGVtZSIsImkiLCJjb3VudCIsInB1c2giLCJmb3JFYWNoIiwiaXRlbSIsIm9uIiwiZGF0YSIsInBhcmVudCIsImxlbmd0aCIsImVsbGlwc2lzRnJvbnQiLCJoaWRlIiwiaW5zZXJ0QmVmb3JlIiwiZWxsaXBzaXNFbmQiLCJpbnNlcnRBZnRlciIsIl9kaXNwbGF5SXRlbXMiLCJzZXRBY3RpdmUiLCJpbmRleCIsInNldFRvIiwiZWxlbSIsInJlbW92ZUNsYXNzIiwiaXNOdW1lcmljIiwiZ2V0IiwibnVtVmlzaWJsZSIsInN0YXJ0IiwiTWF0aCIsIm1heCIsImVuZCIsImFkZENsYXNzIiwiY3VyIiwiZGlzcGxheSIsInNoaWZ0IiwiZGV0YWNoIiwidW5zaGlmdCIsInByZXBlbmQiLCJwb3AiLCJhcHBlbmQiLCJpcyIsInRvZ2dsZSIsInJlbW92ZSIsImpRdWVyeSJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7QUFBQTtBQUNBQSxPQUFPQyxRQUFQLEdBQWtCRCxPQUFPQyxRQUFQLElBQW1CLEVBQXJDOztBQUVBLENBQUMsVUFBQ0MsQ0FBRCxFQUFPO0FBQ047Ozs7Ozs7OztBQVNBRixTQUFPQyxRQUFQLENBQWdCRSxZQUFoQjtBQUNFOzs7Ozs7Ozs7QUFTQSxvQkFBWUMsU0FBWixFQUF1QkMsS0FBdkIsRUFBOEJDLFFBQTlCLEVBQXdDO0FBQUE7O0FBQUE7O0FBQ3RDLFdBQUtGLFNBQUwsR0FBaUJBLFNBQWpCO0FBQ0EsV0FBS0UsUUFBTCxjQUFrQkMsTUFBTSxDQUF4QixJQUE4QkQsUUFBOUI7QUFDQSxXQUFLRSxPQUFMLEdBQWVOLEVBQUUs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Q0F3Slk7QUFDUixhQUFLdEMsS0FBTCxDQUFXYyxPQUFYLENBQW1CO0FBQUEsaUJBQVFDLEtBQUtrQyxNQUFMLEVBQVI7QUFBQSxTQUFuQjtBQUNBLGFBQUs5QyxPQUFMLENBQWE4QyxNQUFiO0FBQ0Q7QUEzSkg7O0FBQUE7QUFBQTtBQTZKRCxDQXZLRCxFQXVLR0MsTUF2S0giLCJmaWxlIjoid2lkZ2V0cy9wYWdlci5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qIGVzbGludCBuby1iaXR3aXNlOiBbXCJlcnJvclwiLCB7IFwiYWxsb3dcIjogW1wiXlwiLCBcIj4+XCJdIH1dICovXG5EcnVwYWwuVG9vbHNoZWQgPSBEcnVwYWwuVG9vbHNoZWQgfHwge307XG5cbigoJCkgPT4ge1xuICAvKipcbiAgICogQSBwYWdlciB0aGF0IGlzIG1lYW50IHRvIGZsaXAgdGhyb3VnaCBhIGxpc3Qgb2YgaXRlbXMgd2l0aG91dCByZWZyZXNoaW5nXG4gICAqIHRoZSBwYWdlLiBUaGlzIGNhbiBiZSBzb21ldGhpbmcgbGlrZSBzbGlkZXIgbmF2aWdhdGlvbiBvciBhIGdhbGxlcnkuXG4gICAqXG4gICAqIExvYWRpbmcgYW5kIHVubG9hZGluZyBjb250ZW50IGlzIGJleW9uZCB0aGUgc2NvcGUgb2YgdGhpcyBwYWdlci5cbiAgICpcbiAgICogVE9ETzogQWRkIGV2ZW50cyBmb3Igd2hlbiBhIHBhZ2UgY2hhbmdlIGlzIHRyaWdnZXJlZC4gVGhpcyBjb3VsZCBhbGxvd1xuICAgKiBmb3IgbW9yZSBlbmhhbmNlZCBpbnRlcmFjdGlvbnMgYW5kIGR5bmFtaWMgbG9hZGluZyBvZiBjb250ZW50LlxuICAgKi9cbiAgRHJ1cGFsLlRvb2xzaGVkLklucGxhY2VQYWdlciA9IGNsYXNzIHtcbiAgICAvKipcbiAgICAgKiBDcmVhdGVzIGEgbmV3IGluc3RhbmNlIG9mIGEgcGFnZXJcbiAgICAgKlxuICAgICAqIEBwYXJhbSB7SlF1ZXJ5fSBjb250YWluZXIgLSBhIEpRdWVyeSB3cmFwcGVkIGVsZW1lbnQgdG8gY29udGFpbiB0aGUgcGFnZXIuXG4gICAgICogQHBhcmFtIHtNaXhlZH0gaXRlbXMgLSBJdGVtcyB0byBwYWdlIHRocm91Z2guIEVpdGhlciBhbiBhcnJheSBvciBhIGNhbGxiYWNrXG4gICAgICogICBmb3IgZ2VuZXJhdGluZyB0aGUgbmF2aWdhdGlvbmFsIGl0ZW1zLlxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSBzZXR0aW5ncyAtIFNldHRpbmcgdGhhdCBkZXRlcm1pbmUgY2FsbGJhY2tzIGFuZCBob3cgcGFnZXJcbiAgICAgKiAgIGl0ZW1zIGFyZSBzdXBwb3NlZCB0byBkaXNwbGF5IG9uIHRoZSBwYWdlLlxuICAgICAqL1xuICAgIGNvbnN0cnVjdG9yKGNvbnRhaW5lciwgaXRlbXMsIHNldHRpbmdzKSB7XG4gICAgICB0aGlzLmNvbnRhaW5lciA9IGNvbnRhaW5lcjtcbiAgICAgIHRoaXMuc2V0dGluZ3MgPSB7IHNob3c6IDgsIC4uLnNldHRpbmdzIH07XG4gICAgICB0aGlzLmVsZW1lbnQgPSAkKCc8dWwgY2xhc3M9XCJwYWdlciBwYWdlci0taW5wbGFjZSBpbmxpbmVcIj48L3VsPicpLmFwcGVuZFRvKHRoaXMuY29udGFpbmVyKTtcbiAgICAgIHRoaXMuZWxlbWVudC53cmFwKCc8ZGl2IGNsYXNzPVwicGFnZXItd3JhcHBlclwiPicpO1xuXG4gICAgICAvLyBLZWVwcyB0cmFjayBvZiB0aGUgaXRlbXMgY3VycmVudGx5IGJlaW5nIGRpc3BsYXllZCBieSB0aGUgcGFnZXIuXG4gICAgICB0aGlzLmRpc3BsYXllZCA9IHtcbiAgICAgICAgc3RhcnRBdDogMSwgLy8gU3RhcnRpbmcgaW5kZXggb2YgaXRlbXMgYmVpbmcgZGlzcGxheWVkLlxuICAgICAgICBlbmRBdDogMCwgLy8gRW5kaW5nIGluZGV4IG9mIGl0ZW1zIGJlaW5nIGRpc3BsYXllZC5cbiAgICAgICAgaXRlbXM6IFtdLCAvLyBDdXJyZW50IHNldCBvZiBpdGVtcyB0aGF0IGFyZSBiZWluZyBkaXNwbGF5ZWQuXG4gICAgICB9O1xuXG4gICAgICAvLyBLZWVwcyB0cmFjayBvZiBhbGwgdGhlIGN1cnJlbnQgcGFnZXIgaXRlbXMsIGNyZWF0aW5nIC8gbG9hZGVkLlxuICAgICAgdGhpcy5pdGVtcyA9IFtdO1xuXG4gICAgICBpZiAoc2V0dGluZ3Mub25OYXZDbGljaykge1xuICAgICAgICB0aGlzLm9uTmF2Q2xpY2sgPSBzZXR0aW5ncy5vbk5hdkNsaWNrO1xuICAgICAgfVxuXG4gICAgICBpZiAoaXRlbXMudGhlbWUpIHtcbiAgICAgICAgZm9yIChsZXQgaSA9IDA7IGkgPCBpdGVtcy5jb3VudDsgKytpKSB7XG4gICAgICAgICAgdGhpcy5pdGVtcy5wdXNoKCQoRHJ1cGFsLnRoZW1lW2l0ZW1zLnRoZW1lXShpLCBpICsgMSkpKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgZWxzZSB7XG4gICAgICAgIHRoaXMuaXRlbXMgPSBpdGVtcztcbiAgICAgIH1cblxuICAgICAgaXRlbXMuZm9yRWFjaCgoaXRlbSwgaSkgPT4ge1xuICAgICAgICBpdGVtLm9uKCdjbGljaycsIHRoaXMsIHRoaXMub25OYXZDbGljaykuZGF0YSgnaW5kZXgnLCBpKTtcbiAgICAgICAgaXRlbS53cmFwKCc8bGkgY2xhc3M9XCJwYWdlcl9faXRlbVwiPicpO1xuICAgICAgICBpdGVtID0gaXRlbS5wYXJlbnQoKTtcbiAgICAgIH0pO1xuXG4gICAgICAvLyBTZXQgdGhpcyBwYWdlciB0byBkaXNwbGF5IHRoZSBmaXJzdCBpdGVtLlxuICAgICAgaWYgKHRoaXMuc2V0dGluZ3Muc2hvdyA8IHRoaXMuaXRlbXMubGVuZ3RoKSB7XG4gICAgICAgIHRoaXMuZWxsaXBzaXNGcm9udCA9ICQoJzxzcGFuIGNsYXNzPVwicGFnZXItZWxsaXBzaXMgcGFnZXItZWxsaXBzaXMtLWZyb250XCI+Li4uPC9zcGFuPicpLmhpZGUoKS5pbnNlcnRCZWZvcmUodGhpcy5lbGVtZW50KTtcbiAgICAgICAgdGhpcy5lbGxpcHNpc0VuZCA9ICQoJzxzcGFuIGNsYXNzPVwicGFnZXItZWxsaXBzaXMgcGFnZXItZWxsaXBzaXMtLWVuZFwiPi4uLjwvc3Bhbj4nKS5oaWRlKCkuaW5zZXJ0QWZ0ZXIodGhpcy5lbGVtZW50KTtcbiAgICAgIH1cbiAgICAgIGVsc2Uge1xuICAgICAgICB0aGlzLl9kaXNwbGF5SXRlbXMoMCwgdGhpcy5pdGVtcy5sZW5ndGggLSAxKTtcbiAgICAgIH1cblxuICAgICAgdGhpcy5zZXRBY3RpdmUoMCk7XG4gICAgfVxuXG4gICAgZ2V0KGluZGV4KSB7XG4gICAgICByZXR1cm4gaW5kZXggPCB0aGlzLml0ZW1zLmxlbmd0aCA/IHRoaXMuaXRlbXNbaW5kZXhdIDogdGhpcy5pdGVtc1t0aGlzLml0ZW1zLmxlbmd0aCAtIDFdO1xuICAgIH1cblxuICAgIHNldEFjdGl2ZShzZXRUbykge1xuICAgICAgbGV0IGluZGV4O1xuICAgICAgbGV0IGl0ZW07XG5cbiAgICAgICQoJy5wYWdlcl9faXRlbS0tYWN0aXZlJywgdGhpcy5lbGVtKS5yZW1vdmVDbGFzcygncGFnZXJfX2l0ZW0tLWFjdGl2ZScpO1xuXG4gICAgICBpZiAoJC5pc051bWVyaWMoc2V0VG8pKSB7XG4gICAgICAgIGluZGV4ID0gc2V0VG87XG4gICAgICAgIGl0ZW0gPSB0aGlzLmdldChzZXRUbyk7XG4gICAgICB9XG4gICAgICBlbHNlIHtcbiAgICAgICAgaXRlbSA9IHNldFRvO1xuICAgICAgICBpbmRleCA9IHNldFRvLmRhdGEoJ2luZGV4Jyk7XG4gICAgICB9XG5cbiAgICAgIC8vIElmIGFibGUgdG8gZmluZCB0aGUgaXRlbSwgdXBkYXRlIHRoZSBkaXNwbGF5IGFuZCBpdHMgc3RhdGUuXG4gICAgICBpZiAoaXRlbSkge1xuICAgICAgICBjb25zdCBudW1WaXNpYmxlID0gdGhpcy5zZXR0aW5ncy5zaG93O1xuXG4gICAgICAgIGlmIChudW1WaXNpYmxlIDwgdGhpcy5pdGVtcy5sZW5ndGgpIHtcbiAgICAgICAgICBsZXQgc3RhcnQgPSBNYXRoLm1heChpbmRleCAtIChudW1WaXNpYmxlID4+IDEpLCAwKTtcbiAgICAgICAgICBsZXQgZW5kID0gKHN0YXJ0ICsgbnVtVmlzaWJsZSkgLSAxO1xuXG4gICAgICAgICAgLy8gSWYgYXQgdGhlIGVuZCBvZiB0aGUgbGlzdCwgdGhlbiBvZmZzZXQgdGhlIGRpc3BsYXkgZnJvbSB0aGUgYmFjay5cbiAgICAgICAgICBpZiAoZW5kID49IHRoaXMuaXRlbXMubGVuZ3RoKSB7XG4gICAgICAgICAgICBlbmQgPSB0aGlzLml0ZW1zLmxlbmd0aCAtIDE7XG4gICAgICAgICAgICBzdGFydCA9IE1hdGgubWF4KChlbmQgLSBudW1WaXNpYmxlKSArIDEsIDApO1xuICAgICAgICAgIH1cbiAgICAgICAgICB0aGlzLl9kaXNwbGF5SXRlbXMoc3RhcnQsIGVuZCk7XG4gICAgICAgIH1cblxuICAgICAgICBpdGVtLmFkZENsYXNzKCdwYWdlcl9faXRlbS0tYWN0aXZlJyk7XG4gICAgICB9XG4gICAgfVxuXG4gICAgX2Rpc3BsYXlJdGVtcyhzdGFydCwgZW5kKSB7XG4gICAgICBsZXQgY3VyO1xuICAgICAgY29uc3QgZGlzcGxheSA9IHRoaXMuZGlzcGxheWVkO1xuICAgICAgY29uc3QgeyBpdGVtcyB9ID0gZGlzcGxheTtcblxuICAgICAgLy8gSWYgdGhlcmUgd2VyZSBwcmV2aW91cyBpdGVtcywgcmVtb3ZlIHRoZSBmaXJzdCBhbmQgbGFzdCBjbGFzc2VzLlxuICAgICAgaWYgKGl0ZW1zLmxlbmd0aCkge1xuICAgICAgICBpdGVtc1swXS5yZW1vdmVDbGFzcygncGFnZXJfX2l0ZW0tLWZpcnN0Jyk7XG4gICAgICAgIGl0ZW1zW2l0ZW1zLmxlbmd0aCAtIDFdLnJlbW92ZUNsYXNzKCdwYWdlcl9faXRlbS0tbGFzdCcpO1xuICAgICAgfVxuXG4gICAgICAvLyBSZW1vdmUgaXRlbXMgZnJvbSB0aGUgZnJvbnQgb2YgdGhlIGxpc3QuXG4gICAgICB3aGlsZSAoZGlzcGxheS5zdGFydEF0IDwgc3RhcnQpIHtcbiAgICAgICAgaXRlbXMuc2hpZnQoKS5kZXRhY2goKTtcbiAgICAgICAgZGlzcGxheS5zdGFydEF0ICs9IDE7XG4gICAgICB9XG5cbiAgICAgIC8vIEFkZCBpdGVtcyB0byB0aGUgZnJvbnQgb2YgdGhlIGxpc3QuXG4gICAgICB3aGlsZSAoZGlzcGxheS5zdGFydEF0ID4gc3RhcnQpIHtcbiAgICAgICAgZGlzcGxheS5zdGFydEF0IC09IDE7XG4gICAgICAgIGN1ciA9IHRoaXMuaXRlbXNbZGlzcGxheS5zdGFydEF0XTtcbiAgICAgICAgaWYgKGN1cikge1xuICAgICAgICAgIGl0ZW1zLnVuc2hpZnQoY3VyKTtcbiAgICAgICAgICB0aGlzLmVsZW1lbnQucHJlcGVuZChjdXIpO1xuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIHdoaWxlIChkaXNwbGF5LmVuZEF0ID4gZW5kKSB7XG4gICAgICAgIGl0ZW1zLnBvcCgpLmRldGFjaCgpO1xuICAgICAgICBkaXNwbGF5LmVuZEF0IC09IDE7XG4gICAgICB9XG5cbiAgICAgIHdoaWxlIChkaXNwbGF5LmVuZEF0IDwgZW5kKSB7XG4gICAgICAgIGRpc3BsYXkuZW5kQXQgKz0gMTtcbiAgICAgICAgY3VyID0gdGhpcy5pdGVtc1tkaXNwbGF5LmVuZEF0XTtcbiAgICAgICAgaWYgKGN1cikge1xuICAgICAgICAgIGl0ZW1zLnB1c2goY3VyKTtcbiAgICAgICAgICB0aGlzLmVsZW1lbnQuYXBwZW5kKGN1cik7XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgLy8gRGV0ZXJtaW5lIHdoaWNoIGVsbGlwc2lzIGFyZSB2aXNpYmxlLlxuICAgICAgaWYgKHRoaXMuZWxsaXBzaXNGcm9udCAmJiAoKGRpc3BsYXkuc3RhcnRBdCAhPT0gMCkgXiB0aGlzLmVsbGlwc2lzRnJvbnQuaXMoJzp2aXNpYmxlJykpKSB7XG4gICAgICAgIHRoaXMuZWxsaXBzaXNGcm9udC50b2dnbGUoKTtcbiAgICAgIH1cbiAgICAgIGlmICh0aGlzLmVsbGlwc2lzRW5kICYmICgoZGlzcGxheS5lbmRBdCAhPT0gdGhpcy5pdGVtcy5sZW5ndGggLSAxKSBeIHRoaXMuZWxsaXBzaXNFbmQuaXMoJzp2aXNpYmxlJykpKSB7XG4gICAgICAgIHRoaXMuZWxsaXBzaXNFbmQudG9nZ2xlKCk7XG4gICAgICB9XG5cbiAgICAgIGl0ZW1zWzBdLmFkZENsYXNzKCdwYWdlcl9faXRlbS0tZmlyc3QnKTtcbiAgICAgIGl0ZW1zW2l0ZW1zLmxlbmd0aCAtIDFdLmFkZENsYXNzKCdwYWdlcl9faXRlbS0tbGFzdCcpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFJlbW92ZSB0aGUgaXRlbXMgYWRkZWQgdG8gdGhlIERPTS5cbiAgICAgKi9cbiAgICBkZXN0cm95KCkge1xuICAgICAgdGhpcy5pdGVtcy5mb3JFYWNoKGl0ZW0gPT4gaXRlbS5yZW1vdmUoKSk7XG4gICAgICB0aGlzLmVsZW1lbnQucmVtb3ZlKCk7XG4gICAgfVxuICB9O1xufSkoalF1ZXJ5KTtcbiJdfQ==
