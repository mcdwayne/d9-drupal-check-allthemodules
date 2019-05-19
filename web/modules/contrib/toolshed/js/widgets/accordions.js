'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Drupal.Toolshed = Drupal.Toolshed || {};

(function ($, Toolshed) {
  /**
   * Accordion Object definition.
   */
  Toolshed.Accordion = function () {
    /**
     * Create an accordion of expandable and collapsible elements.
     *
     * @param {jQuery} $elem
     *   jQuery wrapped element to turn into an accordion item.
     * @param {Object} configs
     *   A configuration options which determine the options:
     *   {
     *     // The selector to use when locating each of the accordion items.
     *     itemSelector: '.accordion-item'
     *     // The selector to find the element to toggle the collapse and expand.
     *     toggleSelector: '.accordion-item__toggle'
     *     // The selector to use to find the item body within the item context.
     *     bodySelector: '.accordion-item__body'
     *     // Accordion only has one item open at a time.
     *     exclusive: false,
     *     // Should accordion panels start of initially open.
     *     initOpen: false,
     *   }
     */
    function _class($elem, configs) {
      _classCallCheck(this, _class);

      this.config = configs;
      this.elem = $elem;
      this.items = [];

      this.onClickItem = Toolshed.Accordion[configs.exclusive ? 'onClickExclusiveOpen' : 'onClickToggleOpen'];

      var children = $(configs.itemSelector, $elem);
      for (var i = 0; i < children.length; ++i) {
        var $childElem = $(children[i]);
        var $childBody = $(configs.bodySelector, $childElem);

        var item = new Toolshed.AccordionItem($childElem, $childBody, this);
        this.items.push(item);
        $(configs.toggleSelector, $childElem).click(this.onClickItem.bind(item));
      }

      // In either of these cases, most accordion items should appear as empty.
      if (configs.exclusive || !configs.initOpen) {
        // If exclusive and initally open, only open the first accordion item.
        for (var _i = configs.initOpen ? 1 : 0; _i < this.items.length; ++_i) {
          this.items[_i].hide();
        }
      }
    }

    _createClass(_class, null, [{
      key: 'onClickToggleOpen',
      value: function onClickToggleOpen(event) {
        event.preventDefault();

        if (this.isActive()) {
          this.deactivate();
        } else {
          this.activate();
        }
      }

      // Handle the click event

    }, {
      key: 'onClickExclusiveOpen',
      value: function onClickExclusiveOpen(event) {
        event.preventDefault();

        if (this.isActive()) {
          this.deactivate();
        } else {
          var parentItems = this.accordion.items;

          // Only activate after the currently active item is closed.
          for (var i = 0; i < parentItems.length; ++i) {
            if (parentItems[i].isActive()) {
              parentItems[i].deactivate();
              break;
            }
          }

          this.activate();
        }
      }
    }]);

    return _class;
  }();

  /**
   * Accordion Item object definition.
   */
  Toolshed.AccordionItem = function () {
    /**
     * Class representing a single expandable and collapsible item of
     * an accordion. This item maintains its components and the state
     * of the open and close states.
     *
     * @param {jQuery} $elem
     *   The whole accordion item.
     * @param {jQuery} $body
     *   The content area of the accordion.
     * @param {Drupal.Toolshed.Accordion} accordion
     *   Parent accordion instance.
     */
    function _class2($elem, $body, accordion) {
      _classCallCheck(this, _class2);

      this.elem = $elem;
      this.body = $body;
      this.accordion = accordion;
    }

    _createClass(_class2, [{
      key: 'isActive',
      value: function isActive() {
        return !this.elem.hasClass('accordion-item--collapsed');
      }
    }, {
      key: 'hide',
      value: function hide() {
        this.elem.addClass('accordion-item--collapsed');
        this.body.hide();
      }
    }, {
      key: 'activate',
      value: function activate() {
        this.elem.removeClass('accordion-item--collapsed');
        this.body.slideDown(300);
      }
    }, {
      key: 'deactivate',
      value: function deactivate() {
        this.elem.addClass('accordion-item--collapsed');
        this.body.slideUp(300);
      }
    }, {
      key: 'destroy',
      value: function destroy() {
        this.elem.removeClass('accordion-item--collapsed');
        this.body.show();
      }
    }]);

    return _class2;
  }();
})(jQuery, Drupal.Toolshed);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndpZGdldHMvYWNjb3JkaW9ucy5lczYuanMiXSwibmFtZXMiOlsiRHJ1cGFsIiwiVG9vbHNoZWQiLCIkIiwiQWNjb3JkaW9uIiwiJGVsZW0iLCJjb25maWdzIiwiY29uZmlnIiwiZWxlbSIsIml0ZW1zIiwib25DbGlja0l0ZW0iLCJleGNsdXNpdmUiLCJjaGlsZHJlbiIsIml0ZW1TZWxlY3RvciIsImkiLCJsZW5ndGgiLCIkY2hpbGRFbGVtIiwiJGNoaWxkQm9keSIsImJvZHlTZWxlY3RvciIsIml0ZW0iLCJBY2NvcmRpb25JdGVtIiwicHVzaCIsInRvZ2dsZVNlbGVjdG9yIiwiY2xpY2siLCJiaW5kIiwiaW5pdE9wZW4iLCJoaWRlIiwiZXZlbnQiLCJwcmV2ZW50RGVmYXVsdCIsImlzQWN0aXZlIiwiZGVhY3RpdmF0ZSIsImFjdGl2YXRlIiwicGFyZW50SXRlbXMiLCJhY2NvcmRpb24iLCIkYm9keSIsImJvZHkiLCJoYXNDbGFzcyIsImFkZENsYXNzIiwicmVtb3ZlQ2xhc3MiLCJzbGlkZURvd24iLCJzbGlkZVVwIiwic2hvdyIsImpRdWVyeSJdLCJtYXBwaW5ncyI6Ijs7Ozs7O0FBQ0FBLE9BQU9DLFFBQVAsR0FBa0JELE9BQU9DLFFBQVAsSUFBbUIsRUFBckM7O0FBRUEsQ0FBQyxVQUFDQyxDQUFELEVBQUlELFFBQUosRUFBaUI7QUFDaEI7OztBQUdBQSxXQUFTRSxTQUFUO0FBQ0U7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBb0JBLG9CQUFZQyxLQUFaLEVBQW1CQyxPQUFuQixFQUE0QjtBQUFBOztBQUMxQixXQUFLQyxNQUFMLEdBQWNELE9BQWQ7QUFDQSxXQUFLRSxJQUFMLEdBQVlILEtBQVo7QUFDQSxXQUFLSSxLQUFMLEdBQWEsRUFBYjs7QUFFQSxXQUFLQyxXQUFMLEdBQW1CUixTQUFTRSxTQUFULENBQW1CRSxRQUFRSyxTQUFSLEdBQW9CLHNCQUFwQixHQUE2QyxtQkFBaEUsQ0FBbkI7O0FBRUEsVUFBTUMsV0FBV1QsRUFBRUcsUUFBUU8sWUFBVixF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Z0NBc0NZO0FBQ1IsYUFBS2hDLElBQUwsQ0FBVThCLFdBQVYsQ0FBc0IsMkJBQXRCO0FBQ0EsYUFBS0gsSUFBTCxDQUFVTSxJQUFWO0FBQ0Q7QUF6Q0g7O0FBQUE7QUFBQTtBQTJDRCxDQW5JRCxFQW1JR0MsTUFuSUgsRUFtSVd6QyxPQUFPQyxRQW5JbEIiLCJmaWxlIjoid2lkZ2V0cy9hY2NvcmRpb25zLmpzIiwic291cmNlc0NvbnRlbnQiOlsiXG5EcnVwYWwuVG9vbHNoZWQgPSBEcnVwYWwuVG9vbHNoZWQgfHwge307XG5cbigoJCwgVG9vbHNoZWQpID0+IHtcbiAgLyoqXG4gICAqIEFjY29yZGlvbiBPYmplY3QgZGVmaW5pdGlvbi5cbiAgICovXG4gIFRvb2xzaGVkLkFjY29yZGlvbiA9IGNsYXNzIHtcbiAgICAvKipcbiAgICAgKiBDcmVhdGUgYW4gYWNjb3JkaW9uIG9mIGV4cGFuZGFibGUgYW5kIGNvbGxhcHNpYmxlIGVsZW1lbnRzLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtqUXVlcnl9ICRlbGVtXG4gICAgICogICBqUXVlcnkgd3JhcHBlZCBlbGVtZW50IHRvIHR1cm4gaW50byBhbiBhY2NvcmRpb24gaXRlbS5cbiAgICAgKiBAcGFyYW0ge09iamVjdH0gY29uZmlnc1xuICAgICAqICAgQSBjb25maWd1cmF0aW9uIG9wdGlvbnMgd2hpY2ggZGV0ZXJtaW5lIHRoZSBvcHRpb25zOlxuICAgICAqICAge1xuICAgICAqICAgICAvLyBUaGUgc2VsZWN0b3IgdG8gdXNlIHdoZW4gbG9jYXRpbmcgZWFjaCBvZiB0aGUgYWNjb3JkaW9uIGl0ZW1zLlxuICAgICAqICAgICBpdGVtU2VsZWN0b3I6ICcuYWNjb3JkaW9uLWl0ZW0nXG4gICAgICogICAgIC8vIFRoZSBzZWxlY3RvciB0byBmaW5kIHRoZSBlbGVtZW50IHRvIHRvZ2dsZSB0aGUgY29sbGFwc2UgYW5kIGV4cGFuZC5cbiAgICAgKiAgICAgdG9nZ2xlU2VsZWN0b3I6ICcuYWNjb3JkaW9uLWl0ZW1fX3RvZ2dsZSdcbiAgICAgKiAgICAgLy8gVGhlIHNlbGVjdG9yIHRvIHVzZSB0byBmaW5kIHRoZSBpdGVtIGJvZHkgd2l0aGluIHRoZSBpdGVtIGNvbnRleHQuXG4gICAgICogICAgIGJvZHlTZWxlY3RvcjogJy5hY2NvcmRpb24taXRlbV9fYm9keSdcbiAgICAgKiAgICAgLy8gQWNjb3JkaW9uIG9ubHkgaGFzIG9uZSBpdGVtIG9wZW4gYXQgYSB0aW1lLlxuICAgICAqICAgICBleGNsdXNpdmU6IGZhbHNlLFxuICAgICAqICAgICAvLyBTaG91bGQgYWNjb3JkaW9uIHBhbmVscyBzdGFydCBvZiBpbml0aWFsbHkgb3Blbi5cbiAgICAgKiAgICAgaW5pdE9wZW46IGZhbHNlLFxuICAgICAqICAgfVxuICAgICAqL1xuICAgIGNvbnN0cnVjdG9yKCRlbGVtLCBjb25maWdzKSB7XG4gICAgICB0aGlzLmNvbmZpZyA9IGNvbmZpZ3M7XG4gICAgICB0aGlzLmVsZW0gPSAkZWxlbTtcbiAgICAgIHRoaXMuaXRlbXMgPSBbXTtcblxuICAgICAgdGhpcy5vbkNsaWNrSXRlbSA9IFRvb2xzaGVkLkFjY29yZGlvbltjb25maWdzLmV4Y2x1c2l2ZSA/ICdvbkNsaWNrRXhjbHVzaXZlT3BlbicgOiAnb25DbGlja1RvZ2dsZU9wZW4nXTtcblxuICAgICAgY29uc3QgY2hpbGRyZW4gPSAkKGNvbmZpZ3MuaXRlbVNlbGVjdG9yLCAkZWxlbSk7XG4gICAgICBmb3IgKGxldCBpID0gMDsgaSA8IGNoaWxkcmVuLmxlbmd0aDsgKytpKSB7XG4gICAgICAgIGNvbnN0ICRjaGlsZEVsZW0gPSAkKGNoaWxkcmVuW2ldKTtcbiAgICAgICAgY29uc3QgJGNoaWxkQm9keSA9ICQoY29uZmlncy5ib2R5U2VsZWN0b3IsICRjaGlsZEVsZW0pO1xuXG4gICAgICAgIGNvbnN0IGl0ZW0gPSBuZXcgVG9vbHNoZWQuQWNjb3JkaW9uSXRlbSgkY2hpbGRFbGVtLCAkY2hpbGRCb2R5LCB0aGlzKTtcbiAgICAgICAgdGhpcy5pdGVtcy5wdXNoKGl0ZW0pO1xuICAgICAgICAkKGNvbmZpZ3MudG9nZ2xlU2VsZWN0b3IsICRjaGlsZEVsZW0pLmNsaWNrKHRoaXMub25DbGlja0l0ZW0uYmluZChpdGVtKSk7XG4gICAgICB9XG5cbiAgICAgIC8vIEluIGVpdGhlciBvZiB0aGVzZSBjYXNlcywgbW9zdCBhY2NvcmRpb24gaXRlbXMgc2hvdWxkIGFwcGVhciBhcyBlbXB0eS5cbiAgICAgIGlmIChjb25maWdzLmV4Y2x1c2l2ZSB8fCAhY29uZmlncy5pbml0T3Blbikge1xuICAgICAgICAvLyBJZiBleGNsdXNpdmUgYW5kIGluaXRhbGx5IG9wZW4sIG9ubHkgb3BlbiB0aGUgZmlyc3QgYWNjb3JkaW9uIGl0ZW0uXG4gICAgICAgIGZvciAobGV0IGkgPSAoY29uZmlncy5pbml0T3BlbikgPyAxIDogMDsgaSA8IHRoaXMuaXRlbXMubGVuZ3RoOyArK2kpIHtcbiAgICAgICAgICB0aGlzLml0ZW1zW2ldLmhpZGUoKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH1cblxuICAgIHN0YXRpYyBvbkNsaWNrVG9nZ2xlT3BlbihldmVudCkge1xuICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcblxuICAgICAgaWYgKHRoaXMuaXNBY3RpdmUoKSkge1xuICAgICAgICB0aGlzLmRlYWN0aXZhdGUoKTtcbiAgICAgIH1cbiAgICAgIGVsc2Uge1xuICAgICAgICB0aGlzLmFjdGl2YXRlKCk7XG4gICAgICB9XG4gICAgfVxuXG4gICAgLy8gSGFuZGxlIHRoZSBjbGljayBldmVudFxuICAgIHN0YXRpYyBvbkNsaWNrRXhjbHVzaXZlT3BlbihldmVudCkge1xuICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcblxuICAgICAgaWYgKHRoaXMuaXNBY3RpdmUoKSkge1xuICAgICAgICB0aGlzLmRlYWN0aXZhdGUoKTtcbiAgICAgIH1cbiAgICAgIGVsc2Uge1xuICAgICAgICBjb25zdCBwYXJlbnRJdGVtcyA9IHRoaXMuYWNjb3JkaW9uLml0ZW1zO1xuXG4gICAgICAgIC8vIE9ubHkgYWN0aXZhdGUgYWZ0ZXIgdGhlIGN1cnJlbnRseSBhY3RpdmUgaXRlbSBpcyBjbG9zZWQuXG4gICAgICAgIGZvciAobGV0IGkgPSAwOyBpIDwgcGFyZW50SXRlbXMubGVuZ3RoOyArK2kpIHtcbiAgICAgICAgICBpZiAocGFyZW50SXRlbXNbaV0uaXNBY3RpdmUoKSkge1xuICAgICAgICAgICAgcGFyZW50SXRlbXNbaV0uZGVhY3RpdmF0ZSgpO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgdGhpcy5hY3RpdmF0ZSgpO1xuICAgICAgfVxuICAgIH1cbiAgfTtcblxuICAvKipcbiAgICogQWNjb3JkaW9uIEl0ZW0gb2JqZWN0IGRlZmluaXRpb24uXG4gICAqL1xuICBUb29sc2hlZC5BY2NvcmRpb25JdGVtID0gY2xhc3Mge1xuICAgIC8qKlxuICAgICAqIENsYXNzIHJlcHJlc2VudGluZyBhIHNpbmdsZSBleHBhbmRhYmxlIGFuZCBjb2xsYXBzaWJsZSBpdGVtIG9mXG4gICAgICogYW4gYWNjb3JkaW9uLiBUaGlzIGl0ZW0gbWFpbnRhaW5zIGl0cyBjb21wb25lbnRzIGFuZCB0aGUgc3RhdGVcbiAgICAgKiBvZiB0aGUgb3BlbiBhbmQgY2xvc2Ugc3RhdGVzLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtqUXVlcnl9ICRlbGVtXG4gICAgICogICBUaGUgd2hvbGUgYWNjb3JkaW9uIGl0ZW0uXG4gICAgICogQHBhcmFtIHtqUXVlcnl9ICRib2R5XG4gICAgICogICBUaGUgY29udGVudCBhcmVhIG9mIHRoZSBhY2NvcmRpb24uXG4gICAgICogQHBhcmFtIHtEcnVwYWwuVG9vbHNoZWQuQWNjb3JkaW9ufSBhY2NvcmRpb25cbiAgICAgKiAgIFBhcmVudCBhY2NvcmRpb24gaW5zdGFuY2UuXG4gICAgICovXG4gICAgY29uc3RydWN0b3IoJGVsZW0sICRib2R5LCBhY2NvcmRpb24pIHtcbiAgICAgIHRoaXMuZWxlbSA9ICRlbGVtO1xuICAgICAgdGhpcy5ib2R5ID0gJGJvZHk7XG4gICAgICB0aGlzLmFjY29yZGlvbiA9IGFjY29yZGlvbjtcbiAgICB9XG5cbiAgICBpc0FjdGl2ZSgpIHtcbiAgICAgIHJldHVybiAhdGhpcy5lbGVtLmhhc0NsYXNzKCdhY2NvcmRpb24taXRlbS0tY29sbGFwc2VkJyk7XG4gICAgfVxuXG4gICAgaGlkZSgpIHtcbiAgICAgIHRoaXMuZWxlbS5hZGRDbGFzcygnYWNjb3JkaW9uLWl0ZW0tLWNvbGxhcHNlZCcpO1xuICAgICAgdGhpcy5ib2R5LmhpZGUoKTtcbiAgICB9XG5cbiAgICBhY3RpdmF0ZSgpIHtcbiAgICAgIHRoaXMuZWxlbS5yZW1vdmVDbGFzcygnYWNjb3JkaW9uLWl0ZW0tLWNvbGxhcHNlZCcpO1xuICAgICAgdGhpcy5ib2R5LnNsaWRlRG93bigzMDApO1xuICAgIH1cblxuICAgIGRlYWN0aXZhdGUoKSB7XG4gICAgICB0aGlzLmVsZW0uYWRkQ2xhc3MoJ2FjY29yZGlvbi1pdGVtLS1jb2xsYXBzZWQnKTtcbiAgICAgIHRoaXMuYm9keS5zbGlkZVVwKDMwMCk7XG4gICAgfVxuXG4gICAgZGVzdHJveSgpIHtcbiAgICAgIHRoaXMuZWxlbS5yZW1vdmVDbGFzcygnYWNjb3JkaW9uLWl0ZW0tLWNvbGxhcHNlZCcpO1xuICAgICAgdGhpcy5ib2R5LnNob3coKTtcbiAgICB9XG4gIH07XG59KShqUXVlcnksIERydXBhbC5Ub29sc2hlZCk7XG4iXX0=
