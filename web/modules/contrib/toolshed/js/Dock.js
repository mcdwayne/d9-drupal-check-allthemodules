'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/* eslint no-bitwise: ["error", { "allow": ["^"] }] */
(function ($, Toolshed) {
  /**
   * Define the namespace for defining docking libraries & tools.
   */
  Toolshed.Dock = {
    /**
     * Creates a new instance of a docker for the edge and parses
     * options from CSS class attributes.
     *
     * @param {jQuery} $elem
     *   HTML element that is being docked.
     * @param {jQuery} $bounds
     *   HTML element which defines the bounds.
     * @param {Object} settings
     *   Object containing the docker settings.
     *   {
     *     edge: {string} ['top'|'left'|'bottom'|'right'],
     *     offset: {int} 0
     *     collapsible: {bool} false,
     *     trackMutations: {bool} false,
     *     animate: {Object|bool} {
     *       type: {string} [slide],
     *       // Animation will last for 200 milliseconds.
     *       duration: {int} 200,
     *       // Animation starts after 250% of the element dimension.
     *       // This value is ignored of no animatable options are enabled.
     *       // NOTE: can be also be a constant pixel value.
     *     }
     *   }
     */
    createItem: function createItem($elem, $bounds) {
      var settings = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

      var config = { edge: 'TOP', offset: 0 };

      /*
       * Determine the set of active docker settings by parsing CSS class
       * information. Options are classes that start with "tsdock-opt-{{option}}"
       * or "tsdock-edge-[top|left|bottom|right]".
       *
       * Options can only get activated here, and will get applied with the
       * current defaults for that option. For instance, "tsdock-opt-sticky"
       * will make the docker, sticky using the default animation configurations.
       */
      if (!settings || settings.detectOpts) {
        var match = null;
        var optRegex = /(?:^|\s)tsdock--(opt|edge)-([-\w]+)(?:\s|$)/g;
        var elClasses = $elem.attr('class');

        // eslint-disable-next-line no-cond-assign
        while ((match = optRegex.exec(elClasses)) !== null) {
          if (match[1] === 'opt') {
            config[match[2]] = true;
          } else if (match[1] === 'edge') {
            var _match = match;

            var _match2 = _slicedToArray(_match, 3);

            config.edge = _match2[2];
          }
        }
      }

      // Build the docker now that all settings have been applied to it.
      var docker = new Toolshed.Dock.DockItem($elem, $bounds, _extends({}, config, settings));
      Toolshed.Dock.addDocker(config.edge.toUpperCase() || 'TOP', docker);
    },


    /**
     * Add docker items into a docked container.
     *
     * @param {string} edge
     *   The edge to add the docking content to.
     * @param {Drupal.Toolshed.Dock.DockItem} item
     *   The dockable item to place into the container.
     */
    addDocker: function addDocker(edge, item) {
      if (Toolshed.Dock.containers[edge]) {
        Toolshed.Dock.containers[edge].addItem(item);
      }
    }
  };

  /**
   * Containers for holding items that are docked to them. DockContainers
   * will listen to Window events and manage the items that they wrap.
   */
  Toolshed.Dock.DockContainer = function () {
    function _class() {
      _classCallCheck(this, _class);

      this.active = false;
      this.container = null;
      this.items = [];
    }

    _createClass(_class, [{
      key: 'isActive',
      value: function isActive() {
        return this.active;
      }

      /**
       * Add a new docking item to this docking container.
       *
       * @param {Drupal.Toolshed.Dock.DockItem} item
       *   The DockItem to add to this container.
       */

    }, {
      key: 'addItem',
      value: function addItem(item) {
        item.dockTo = this;
        this.items.push(item);

        // Defer building and listening to events until a dockable item is added.
        if (!this.active) {
          this.init();
        }
      }

      /**
       * Remove the DockItem from this container.
       *
       * @param {Drupal.Toolshed.Dock.DockItem} item
       *   The DockItem to find and remove from the container.
       */

    }, {
      key: 'removeItem',
      value: function removeItem(item) {
        this.items = this.items.filter(function (cmp) {
          return cmp !== item;
        });
        delete item.dockTo;

        if (!this.items.length && this.container) {
          this.container.hide();
        }
      }

      /**
       * Register events that may make changes to docking, and init positioning.
       */

    }, {
      key: 'init',
      value: function init() {
        this.container = $('<div class="tsdock-container"/>').appendTo($('body'));
        this.initContainer();
        this.active = true;

        Toolshed.events.scroll.add(this);
        Toolshed.events.resize.add(this);

        // Initialize the positioning of the dock.
        this.onResize(new Event('resize'), Toolshed.winRect);
      }

      /**
       * Event handler for the window scroll change events.
       *
       * @param {Event} e
       *   The scroll event object for this event.
       * @param {Drupal.Toolshed.Geom.Rect} win
       *   The current bounds of the window.
       * @param {Object} scroll
       *   Object containing a top and left item to represent the current
       *   scroll offsets of the document in relation to the window.
       */

    }, {
      key: 'onScroll',
      value: function onScroll(e, win, scroll) {
        var _this = this;

        var viewable = new Toolshed.Geom.Rect(win);
        viewable.offset(scroll.left, scroll.top);

        this.items.forEach(function (item) {
          if (item.isDocked ^ _this.isDocking(item, viewable)) {
            return item.isDocked ? item.deactivateDock() : item.activateDock(_this.container);
          }
        }, this);
      }
    }, {
      key: 'onResize',
      value: function onResize(e, rect) {
        var offset = {
          top: document.documentElement.scrollTop || document.body.scrollTop,
          left: document.documentElement.scrollLeft || document.body.scrollLeft
        };

        if (rect.top !== this.container.offset().top) {
          this.container.css({ top: rect.top });
        }

        // Window resizes could change the scroll position, but won't trigger a
        // scroll event on their own. Force a calculation of positioning.
        this.onScroll(e, rect, offset);
      }
    }, {
      key: 'destroy',
      value: function destroy() {
        // Unregister these event listeners, so these items are not lingering.
        Toolshed.events.scroll.remove(this);
        Toolshed.events.resize.remove(this);

        if (this.container) {
          this.container.remove();
        }
      }
    }]);

    return _class;
  }();

  Toolshed.Dock.TopDockContainer = function (_Toolshed$Dock$DockCo) {
    _inherits(_class2, _Toolshed$Dock$DockCo);

    function _class2() {
      _classCallCheck(this, _class2);

      return _possibleConstructorReturn(this, (_class2.__proto__ || Object.getPrototypeOf(_class2)).apply(this, arguments));
    }

    _createClass(_class2, [{
      key: 'initContainer',

      /**
       * Docking container specific handling of the docking container.
       */
      value: function initContainer() {
        this.container.css({
          position: 'fixed',
          top: 0,
          width: '100%',
          boxSizing: 'border-box'
        });
      }

      /**
       * Determine if the content fits and is in the viewable window area.
       *
       * @param {Drupal.Toolshed.Geom.Rect} item
       *   Rect of the dockable content.
       * @param {Drupal.Toolshed.Geom.Rect} win
       *   Viewable window space.
       *
       * @return {Boolean}
       *   TRUE if the docking content is outside the viewable window.
       */

    }, {
      key: 'isDocking',
      value: function isDocking(item, win) {
        // eslint-disable-line class-methods-use-this
        var cnt = item.getContainerRect();
        var top = Math.floor(item.placeholder.offset().top + item.config.offset);

        if (item.config.offset < 0) {
          top += item.placeholder.height();
        }

        return top < win.top && cnt.bottom > win.top && item.elem.outerHeight() < cnt.getHeight();
      }
    }]);

    return _class2;
  }(Toolshed.Dock.DockContainer);

  /**
   * A dockable item that goes into a dock container.
   */
  Toolshed.Dock.DockItem = function () {
    /**
     * Create a new instance of a dockable item.
     *
     * @param {jQuery} $elem
     *   The element that is being docked within this docking container.
     * @param {jQuery} $bounds
     *   The DOM element that is used to determine the bounds of when
     *   this item is being docked.
     * @param {Object} settings
     *   Settings that control how this item behaves while docking and
     *   undocking from a dock container.
     */
    function _class3($elem, $bounds, settings) {
      _classCallCheck(this, _class3);

      this.elem = $elem;
      this.bounds = $bounds;
      this.config = settings;

      this.elem.addClass('tsdock-item');
      this.isDocked = false;

      // Apply animation settings, or use the defaults if they are provided.
      if (this.config.animate) {
        this.mode = this.config.animate.type || 'slide';
      }

      this.init();
    }

    /**
     * NULL function, meant to be a placeholder for edges that might
     * need to have custom initialization.
     */


    _createClass(_class3, [{
      key: 'init',
      value: function init() {
        // Create a new placeholder, that will keep track of the space
        // used by the docked element, while it's being docked to the container.
        this.placeholder = this.elem.wrap('<div class="tsdock__placeholder"/>').parent();
        this.placeholder.css({ position: this.elem.css('position') });
        this.height = this.elem.outerHeight();

        // If available, try to track the size of the docked element
        // and make updates to the docking system if dimensions change.
        if (this.config.trackMutations && MutationObserver) {
          this.observer = new MutationObserver(this._mutated.bind(this));
          this.observer.observe(this.elem[0], {
            attributes: true,
            childList: true,
            subtree: true,
            characterData: true
          });
        }
      }

      /**
       * Mutation event listener. Will be registered by relevant docker types
       * and trigger when the docking element is modified in the appropriate ways.
       */

    }, {
      key: '_mutations',
      value: function _mutations() {
        // Disable mutation events while we process the current docking information.
        this.observer.disconnect();

        // In most cases we only care if the height has changed.
        var height = this.elem.outerHeight();
        if (this.height !== height) {
          this.height = height || 0;

          if (this.placeholder) {
            this.placeholder.height(height);
          }

          var win = new Toolshed.Geom.Rect(Toolshed.winRect);
          var scrollPos = document.documentElement.scrollTop || document.body.scrollTop;
          this.scroll(scrollPos, win);
        }

        this.observer.observe(this.elem[0], {
          attributes: true,
          childList: true,
          subtree: true,
          characterData: true
        });
      }
    }, {
      key: 'getContainerRect',
      value: function getContainerRect() {
        var _bounds$offset = this.bounds.offset(),
            top = _bounds$offset.top,
            left = _bounds$offset.left;

        return new Toolshed.Geom.Rect(top, left, top + this.bounds.outerHeight(), left + this.bounds.outerWidth());
      }

      /**
       * Turn on docking for this instance.
       *
       * This should make the element dock to the respective edge and set the
       * correct behaviors for items when they are docked.
       *
       * @param {jQuery} addTo
       *   Element to add the docked item into.
       */

    }, {
      key: 'activateDock',
      value: function activateDock(addTo) {
        if (!this.isDocked) {
          this.isDocked = true;
          this.placeholder.height(this.height);

          addTo.append(this.elem);
          this.elem.addClass('tsdock-item--docked');
          this.elem.trigger('ToolshedDocking.docked');
        }
      }

      /**
       * Turn docking off for this docked item.
       */

    }, {
      key: 'deactivateDock',
      value: function deactivateDock() {
        if (this.isDocked) {
          this.isDocked = false;
          this.placeholder.append(this.elem);
          this.elem.removeClass('tsdock-item--docked');

          // Reset the placeholder to size according to the placeholder.
          this.placeholder.css({ height: '' });
          this.elem.trigger('ToolshedDocking.undocked');
        }
      }
    }, {
      key: 'destroy',
      value: function destroy() {
        if (this.observer) this.observer.disconnect();
        this.deactivateDock();

        if (this.placeholder) {
          this.elem.unwrap('.tsdock__placeholder');
        }
      }
    }]);

    return _class3;
  }();

  Toolshed.Dock.containers = {
    TOP: new Toolshed.Dock.TopDockContainer()
  };
})(jQuery, Drupal.Toolshed);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIkRvY2suZXM2LmpzIl0sIm5hbWVzIjpbIiQiLCJUb29sc2hlZCIsIkRvY2siLCJjcmVhdGVJdGVtIiwiJGVsZW0iLCIkYm91bmRzIiwic2V0dGluZ3MiLCJjb25maWciLCJlZGdlIiwib2Zmc2V0IiwiZGV0ZWN0T3B0cyIsIm1hdGNoIiwib3B0UmVnZXgiLCJlbENsYXNzZXMiLCJhdHRyIiwiZXhlYyIsImRvY2tlciIsIkRvY2tJdGVtIiwiYWRkRG9ja2VyIiwidG9VcHBlckNhc2UiLCJpdGVtIiwiY29udGFpbmVycyIsImFkZEl0ZW0iLCJEb2NrQ29udGFpbmVyIiwiYWN0aXZlIiwiY29udGFpbmVyIiwiaXRlbXMiLCJkb2NrVG8iLCJwdXNoIiwiaW5pdCIsImZpbHRlciIsImNtcCIsImxlbmd0aCIsImhpZGUiLCJhcHBlbmRUbyIsImluaXRDb250YWluZXIiLCJldmVudHMiLCJzY3JvbGwiLCJhZGQiLCJyZXNpemUiLCJvblJlc2l6ZSIsIkV2ZW50Iiwid2luUmVjdCIsImUiLCJ3aW4iLCJ2aWV3YWJsZSIsIkdlb20iLCJSZWN0IiwibGVmdCIsInRvcCIsImZvckVhY2giLCJpc0RvY2tlZCIsImlzRG9ja2luZyIsImRlYWN0aXZhdGVEb2NrIiwiYWN0aXZhdGVEb2NrIiwicmVjdCIsImRvY3VtZW50IiwiZG9jdW1lbnRFbGVtZW50Iiwic2Nyb2xsVG9wIiwiYm9keSIsInNjcm9sbExlZnQiLCJjc3MiLCJvblNjcm9sbCIsInJlbW92ZSIsIlRvcERvY2tDb250YWluZXIiLCJwb3NpdGlvbiIsIndpZHRoIiwiYm94U2l6aW5nIiwiY250IiwiZ2V0Q29udGFpbmVyUmVjdCIsIk1hdGgiLCJmbG9vciIsInBsYWNlaG9sZGVyIiwiaGVpZ2h0IiwiYm90dG9tIiwiZWxlbSIsIm91dGVySGVpZ2h0IiwiZ2V0SGVpZ2h0IiwiYm91bmRzIiwiYWRkQ2xhc3MiLCJhbmltYXRlIiwibW9kZSIsInR5cGUiLCJ3cmFwIiwicGFyZW50IiwidHJhY2tNdXRhdGlvbnMiLCJNdXRhdGlvbk9ic2VydmVyIiwib2JzZXJ2ZXIiLCJfbXV0YXRlZCIsImJpbmQiLCJvYnNlcnZlIiwiYXR0cmlidXRlcyIsImNoaWxkTGlzdCIsInN1YnRyZWUiLCJjaGFyYWN0ZXJEYXRhIiwiZGlzY29ubmVjdCIsInNjcm9sbFBvcyIsIm91dGVyV2lkdGgiLCJhZGRUbyIsImFwcGVuZCIsInRyaWdnZXIiLCJyZW1vdmVDbGFzcyIsInVud3JhcCIsIlRPUCIsImpRdWVyeSIsIkRydXBhbCJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7QUFBQTtBQUNBLENBQUMsVUFBQ0EsQ0FBRCxFQUFJQyxRQUFKLEVBQWlCO0FBQ2hCOzs7QUFHQUEsV0FBU0MsSUFBVCxHQUFnQjtBQUNkOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBeUJBQyxjQTFCYyxzQkEwQkhDLEtBMUJHLEVBMEJJQyxPQTFCSixFQTBCNEI7QUFBQSxVQUFmQyxRQUFlLHVFQUFKLEVBQUk7O0FBQ3hDLFVBQU1DLFNBQVMsRUFBRUMsTUFBTSxLQUFSLEVBQWVDLFFBQVEsQ0FBdkIsRUFBZjs7QUFFQTs7Ozs7Ozs7O0FBU0EsVUFBSSxDQUFDSCxRQUFELElBQWFBLFNBQVNJLFVBQTFCLEVBQXNDO0FBQ3BDLFlBQUlDLFFBQVEsSUFBWjtBQUNBLFlBQU1DLFdBQVcsOENBQWpCO0FBQ0EsWUFBTUMsWUFBWVQsTUFBTVUsSUFBTixDQUFXLE9BQVgsQ0FBbEI7O0FBRUE7QUFDQSxlQUFPLENBQUNILFFBQVFDLFNBQVNHLElBQVQsQ0FBY0YsU0FBZCxDQUFULE1BQXVDLElBQTlDLEVBQW9EO0FBQ2xELGNBQUlGLE1BQU0sQ0FBTixNQUFhLEtBQWpCLEVBQXdCO0FBQ3RCSixtQkFBT0ksTUFBTSxDQUFOLENBQVAsSUFBbUIsSUFBbkI7QUFDRCxXQUZELE1BR0ssSUFBSUEsTUFBTSxDQUFOLE1BQWEsTUFBakIsRUFBeUI7QUFBQSx5QkFDVEEsS0FEUzs7QUFBQTs7QUFDeEJKLG1CQUFPQyxJQURpQjtBQUU3QjtBQUNGO0FBQ0Y7O0FBRUQ7QUFDQSxV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QTtBQUFBLHVDQW9IbUI7QUFDZixZQUFJLEtBQUtqRCxRQUFULEVBQW1CO0FBQ2pCLGVBQUtBLFFBQUwsR0FBZ0IsS0FBaEI7QUFDQSxlQUFLcUIsV0FBTCxDQUFpQjJCLE1BQWpCLENBQXdCLEtBQUt4QixJQUE3QjtBQUNBLGVBQUtBLElBQUwsQ0FBVTBCLFdBQVYsQ0FBc0IscUJBQXRCOztBQUVBO0FBQ0EsZUFBSzdCLFdBQUwsQ0FBaUJYLEdBQWpCLENBQXFCLEVBQUVZLFFBQVEsRUFBVixFQUFyQjtBQUNBLGVBQUtFLElBQUwsQ0FBVXlCLE9BQVYsQ0FBa0IsMEJBQWxCO0FBQ0Q7QUFDRjtBQTlISDtBQUFBO0FBQUEsZ0NBZ0lZO0FBQ1IsWUFBSSxLQUFLYixRQUFULEVBQW1CLEtBQUtBLFFBQUwsQ0FBY1EsVUFBZDtBQUNuQixhQUFLMUMsY0FBTDs7QUFFQSxZQUFJLEtBQUttQixXQUFULEVBQXNCO0FBQ3BCLGVBQUtHLElBQUwsQ0FBVTJCLE1BQVYsQ0FBaUIsc0JBQWpCO0FBQ0Q7QUFDRjtBQXZJSDs7QUFBQTtBQUFBOztBQTBJQXJHLFdBQVNDLElBQVQsQ0FBY21CLFVBQWQsR0FBMkI7QUFDekJrRixTQUFLLElBQUl0RyxTQUFTQyxJQUFULENBQWM4RCxnQkFBbEI7QUFEb0IsR0FBM0I7QUFHRCxDQWpYRCxFQWlYR3dDLE1BalhILEVBaVhXQyxPQUFPeEcsUUFqWGxCIiwiZmlsZSI6IkRvY2suanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiBlc2xpbnQgbm8tYml0d2lzZTogW1wiZXJyb3JcIiwgeyBcImFsbG93XCI6IFtcIl5cIl0gfV0gKi9cbigoJCwgVG9vbHNoZWQpID0+IHtcbiAgLyoqXG4gICAqIERlZmluZSB0aGUgbmFtZXNwYWNlIGZvciBkZWZpbmluZyBkb2NraW5nIGxpYnJhcmllcyAmIHRvb2xzLlxuICAgKi9cbiAgVG9vbHNoZWQuRG9jayA9IHtcbiAgICAvKipcbiAgICAgKiBDcmVhdGVzIGEgbmV3IGluc3RhbmNlIG9mIGEgZG9ja2VyIGZvciB0aGUgZWRnZSBhbmQgcGFyc2VzXG4gICAgICogb3B0aW9ucyBmcm9tIENTUyBjbGFzcyBhdHRyaWJ1dGVzLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtqUXVlcnl9ICRlbGVtXG4gICAgICogICBIVE1MIGVsZW1lbnQgdGhhdCBpcyBiZWluZyBkb2NrZWQuXG4gICAgICogQHBhcmFtIHtqUXVlcnl9ICRib3VuZHNcbiAgICAgKiAgIEhUTUwgZWxlbWVudCB3aGljaCBkZWZpbmVzIHRoZSBib3VuZHMuXG4gICAgICogQHBhcmFtIHtPYmplY3R9IHNldHRpbmdzXG4gICAgICogICBPYmplY3QgY29udGFpbmluZyB0aGUgZG9ja2VyIHNldHRpbmdzLlxuICAgICAqICAge1xuICAgICAqICAgICBlZGdlOiB7c3RyaW5nfSBbJ3RvcCd8J2xlZnQnfCdib3R0b20nfCdyaWdodCddLFxuICAgICAqICAgICBvZmZzZXQ6IHtpbnR9IDBcbiAgICAgKiAgICAgY29sbGFwc2libGU6IHtib29sfSBmYWxzZSxcbiAgICAgKiAgICAgdHJhY2tNdXRhdGlvbnM6IHtib29sfSBmYWxzZSxcbiAgICAgKiAgICAgYW5pbWF0ZToge09iamVjdHxib29sfSB7XG4gICAgICogICAgICAgdHlwZToge3N0cmluZ30gW3NsaWRlXSxcbiAgICAgKiAgICAgICAvLyBBbmltYXRpb24gd2lsbCBsYXN0IGZvciAyMDAgbWlsbGlzZWNvbmRzLlxuICAgICAqICAgICAgIGR1cmF0aW9uOiB7aW50fSAyMDAsXG4gICAgICogICAgICAgLy8gQW5pbWF0aW9uIHN0YXJ0cyBhZnRlciAyNTAlIG9mIHRoZSBlbGVtZW50IGRpbWVuc2lvbi5cbiAgICAgKiAgICAgICAvLyBUaGlzIHZhbHVlIGlzIGlnbm9yZWQgb2Ygbm8gYW5pbWF0YWJsZSBvcHRpb25zIGFyZSBlbmFibGVkLlxuICAgICAqICAgICAgIC8vIE5PVEU6IGNhbiBiZSBhbHNvIGJlIGEgY29uc3RhbnQgcGl4ZWwgdmFsdWUuXG4gICAgICogICAgIH1cbiAgICAgKiAgIH1cbiAgICAgKi9cbiAgICBjcmVhdGVJdGVtKCRlbGVtLCAkYm91bmRzLCBzZXR0aW5ncyA9IHt9KSB7XG4gICAgICBjb25zdCBjb25maWcgPSB7IGVkZ2U6ICdUT1AnLCBvZmZzZXQ6IDAgfTtcblxuICAgICAgLypcbiAgICAgICAqIERldGVybWluZSB0aGUgc2V0IG9mIGFjdGl2ZSBkb2NrZXIgc2V0dGluZ3MgYnkgcGFyc2luZyBDU1MgY2xhc3NcbiAgICAgICAqIGluZm9ybWF0aW9uLiBPcHRpb25zIGFyZSBjbGFzc2VzIHRoYXQgc3RhcnQgd2l0aCBcInRzZG9jay1vcHQte3tvcHRpb259fVwiXG4gICAgICAgKiBvciBcInRzZG9jay1lZGdlLVt0b3B8bGVmdHxib3R0b218cmlnaHRdXCIuXG4gICAgICAgKlxuICAgICAgICogT3B0aW9ucyBjYW4gb25seSBnZXQgYWN0aXZhdGVkIGhlcmUsIGFuZCB3aWxsIGdldCBhcHBsaWVkIHdpdGggdGhlXG4gICAgICAgKiBjdXJyZW50IGRlZmF1bHRzIGZvciB0aGF0IG9wdGlvbi4gRm9yIGluc3RhbmNlLCBcInRzZG9jay1vcHQtc3RpY2t5XCJcbiAgICAgICAqIHdpbGwgbWFrZSB0aGUgZG9ja2VyLCBzdGlja3kgdXNpbmcgdGhlIGRlZmF1bHQgYW5pbWF0aW9uIGNvbmZpZ3VyYXRpb25zLlxuICAgICAgICovXG4gICAgICBpZiAoIXNldHRpbmdzIHx8IHNldHRpbmdzLmRldGVjdE9wdHMpIHtcbiAgICAgICAgbGV0IG1hdGNoID0gbnVsbDtcbiAgICAgICAgY29uc3Qgb3B0UmVnZXggPSAvKD86XnxcXHMpdHNkb2NrLS0ob3B0fGVkZ2UpLShbLVxcd10rKSg/Olxcc3wkKS9nO1xuICAgICAgICBjb25zdCBlbENsYXNzZXMgPSAkZWxlbS5hdHRyKCdjbGFzcycpO1xuXG4gICAgICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1jb25kLWFzc2lnblxuICAgICAgICB3aGlsZSAoKG1hdGNoID0gb3B0UmVnZXguZXhlYyhlbENsYXNzZXMpKSAhPT0gbnVsbCkge1xuICAgICAgICAgIGlmIChtYXRjaFsxXSA9PT0gJ29wdCcpIHtcbiAgICAgICAgICAgIGNvbmZpZ1ttYXRjaFsyXV0gPSB0cnVlO1xuICAgICAgICAgIH1cbiAgICAgICAgICBlbHNlIGlmIChtYXRjaFsxXSA9PT0gJ2VkZ2UnKSB7XG4gICAgICAgICAgICBbLCwgY29uZmlnLmVkZ2VdID0gbWF0Y2g7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIC8vIEJ1aWxkIHRoZSBkb2NrZXIgbm93IHRoYXQgYWxsIHNldHRpbmdzIGhhdmUgYmVlbiBhcHBsaWVkIHRvIGl0LlxuICAgICAgY29uc3QgZG9ja2VyID0gbmV3IFRvb2xzaGVkLkRvY2suRG9ja0l0ZW0oJGVsZW0sICRib3VuZHMsIHsgLi4uY29uZmlnLCAuLi5zZXR0aW5ncyB9KTtcbiAgICAgIFRvb2xzaGVkLkRvY2suYWRkRG9ja2VyKGNvbmZpZy5lZGdlLnRvVXBwZXJDYXNlKCkgfHwgJ1RPUCcsIGRvY2tlcik7XG4gICAgfSxcblxuICAgIC8qKlxuICAgICAqIEFkZCBkb2NrZXIgaXRlbXMgaW50byBhIGRvY2tlZCBjb250YWluZXIuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge3N0cmluZ30gZWRnZVxuICAgICAqICAgVGhlIGVkZ2UgdG8gYWRkIHRoZSBkb2NraW5nIGNvbnRlbnQgdG8uXG4gICAgICogQHBhcmFtIHtEcnVwYWwuVG9vbHNoZWQuRG9jay5Eb2NrSXRlbX0gaXRlbVxuICAgICAqICAgVGhlIGRvY2thYmxlIGl0ZW0gdG8gcGxhY2UgaW50byB0aGUgY29udGFpbmVyLlxuICAgICAqL1xuICAgIGFkZERvY2tlcihlZGdlLCBpdGVtKSB7XG4gICAgICBpZiAoVG9vbHNoZWQuRG9jay5jb250YWluZXJzW2VkZ2VdKSB7XG4gICAgICAgIFRvb2xzaGVkLkRvY2suY29udGFpbmVyc1tlZGdlXS5hZGRJdGVtKGl0ZW0pO1xuICAgICAgfVxuICAgIH0sXG4gIH07XG5cbiAgLyoqXG4gICAqIENvbnRhaW5lcnMgZm9yIGhvbGRpbmcgaXRlbXMgdGhhdCBhcmUgZG9ja2VkIHRvIHRoZW0uIERvY2tDb250YWluZXJzXG4gICAqIHdpbGwgbGlzdGVuIHRvIFdpbmRvdyBldmVudHMgYW5kIG1hbmFnZSB0aGUgaXRlbXMgdGhhdCB0aGV5IHdyYXAuXG4gICAqL1xuICBUb29sc2hlZC5Eb2NrLkRvY2tDb250YWluZXIgPSBjbGFzcyB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICB0aGlzLmFjdGl2ZSA9IGZhbHNlO1xuICAgICAgdGhpcy5jb250YWluZXIgPSBudWxsO1xuICAgICAgdGhpcy5pdGVtcyA9IFtdO1xuICAgIH1cblxuICAgIGlzQWN0aXZlKCkge1xuICAgICAgcmV0dXJuIHRoaXMuYWN0aXZlO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEFkZCBhIG5ldyBkb2NraW5nIGl0ZW0gdG8gdGhpcyBkb2NraW5nIGNvbnRhaW5lci5cbiAgICAgKlxuICAgICAqIEBwYXJhbSB7RHJ1cGFsLlRvb2xzaGVkLkRvY2suRG9ja0l0ZW19IGl0ZW1cbiAgICAgKiAgIFRoZSBEb2NrSXRlbSB0byBhZGQgdG8gdGhpcyBjb250YWluZXIuXG4gICAgICovXG4gICAgYWRkSXRlbShpdGVtKSB7XG4gICAgICBpdGVtLmRvY2tUbyA9IHRoaXM7XG4gICAgICB0aGlzLml0ZW1zLnB1c2goaXRlbSk7XG5cbiAgICAgIC8vIERlZmVyIGJ1aWxkaW5nIGFuZCBsaXN0ZW5pbmcgdG8gZXZlbnRzIHVudGlsIGEgZG9ja2FibGUgaXRlbSBpcyBhZGRlZC5cbiAgICAgIGlmICghdGhpcy5hY3RpdmUpIHtcbiAgICAgICAgdGhpcy5pbml0KCk7XG4gICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUmVtb3ZlIHRoZSBEb2NrSXRlbSBmcm9tIHRoaXMgY29udGFpbmVyLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtEcnVwYWwuVG9vbHNoZWQuRG9jay5Eb2NrSXRlbX0gaXRlbVxuICAgICAqICAgVGhlIERvY2tJdGVtIHRvIGZpbmQgYW5kIHJlbW92ZSBmcm9tIHRoZSBjb250YWluZXIuXG4gICAgICovXG4gICAgcmVtb3ZlSXRlbShpdGVtKSB7XG4gICAgICB0aGlzLml0ZW1zID0gdGhpcy5pdGVtcy5maWx0ZXIoY21wID0+IGNtcCAhPT0gaXRlbSk7XG4gICAgICBkZWxldGUgaXRlbS5kb2NrVG87XG5cbiAgICAgIGlmICghdGhpcy5pdGVtcy5sZW5ndGggJiYgdGhpcy5jb250YWluZXIpIHtcbiAgICAgICAgdGhpcy5jb250YWluZXIuaGlkZSgpO1xuICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFJlZ2lzdGVyIGV2ZW50cyB0aGF0IG1heSBtYWtlIGNoYW5nZXMgdG8gZG9ja2luZywgYW5kIGluaXQgcG9zaXRpb25pbmcuXG4gICAgICovXG4gICAgaW5pdCgpIHtcbiAgICAgIHRoaXMuY29udGFpbmVyID0gJCgnPGRpdiBjbGFzcz1cInRzZG9jay1jb250YWluZXJcIi8+JykuYXBwZW5kVG8oJCgnYm9keScpKTtcbiAgICAgIHRoaXMuaW5pdENvbnRhaW5lcigpO1xuICAgICAgdGhpcy5hY3RpdmUgPSB0cnVlO1xuXG4gICAgICBUb29sc2hlZC5ldmVudHMuc2Nyb2xsLmFkZCh0aGlzKTtcbiAgICAgIFRvb2xzaGVkLmV2ZW50cy5yZXNpemUuYWRkKHRoaXMpO1xuXG4gICAgICAvLyBJbml0aWFsaXplIHRoZSBwb3NpdGlvbmluZyBvZiB0aGUgZG9jay5cbiAgICAgIHRoaXMub25SZXNpemUobmV3IEV2ZW50KCdyZXNpemUnKSwgVG9vbHNoZWQud2luUmVjdCk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogRXZlbnQgaGFuZGxlciBmb3IgdGhlIHdpbmRvdyBzY3JvbGwgY2hhbmdlIGV2ZW50cy5cbiAgICAgKlxuICAgICAqIEBwYXJhbSB7RXZlbnR9IGVcbiAgICAgKiAgIFRoZSBzY3JvbGwgZXZlbnQgb2JqZWN0IGZvciB0aGlzIGV2ZW50LlxuICAgICAqIEBwYXJhbSB7RHJ1cGFsLlRvb2xzaGVkLkdlb20uUmVjdH0gd2luXG4gICAgICogICBUaGUgY3VycmVudCBib3VuZHMgb2YgdGhlIHdpbmRvdy5cbiAgICAgKiBAcGFyYW0ge09iamVjdH0gc2Nyb2xsXG4gICAgICogICBPYmplY3QgY29udGFpbmluZyBhIHRvcCBhbmQgbGVmdCBpdGVtIHRvIHJlcHJlc2VudCB0aGUgY3VycmVudFxuICAgICAqICAgc2Nyb2xsIG9mZnNldHMgb2YgdGhlIGRvY3VtZW50IGluIHJlbGF0aW9uIHRvIHRoZSB3aW5kb3cuXG4gICAgICovXG4gICAgb25TY3JvbGwoZSwgd2luLCBzY3JvbGwpIHtcbiAgICAgIGNvbnN0IHZpZXdhYmxlID0gbmV3IFRvb2xzaGVkLkdlb20uUmVjdCh3aW4pO1xuICAgICAgdmlld2FibGUub2Zmc2V0KHNjcm9sbC5sZWZ0LCBzY3JvbGwudG9wKTtcblxuICAgICAgdGhpcy5pdGVtcy5mb3JFYWNoKChpdGVtKSA9PiB7XG4gICAgICAgIGlmIChpdGVtLmlzRG9ja2VkIF4gdGhpcy5pc0RvY2tpbmcoaXRlbSwgdmlld2FibGUpKSB7XG4gICAgICAgICAgcmV0dXJuIGl0ZW0uaXNEb2NrZWQgPyBpdGVtLmRlYWN0aXZhdGVEb2NrKCkgOiBpdGVtLmFjdGl2YXRlRG9jayh0aGlzLmNvbnRhaW5lcik7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpO1xuICAgIH1cblxuICAgIG9uUmVzaXplKGUsIHJlY3QpIHtcbiAgICAgIGNvbnN0IG9mZnNldCA9IHtcbiAgICAgICAgdG9wOiBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc2Nyb2xsVG9wIHx8IGRvY3VtZW50LmJvZHkuc2Nyb2xsVG9wLFxuICAgICAgICBsZWZ0OiBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuc2Nyb2xsTGVmdCB8fCBkb2N1bWVudC5ib2R5LnNjcm9sbExlZnQsXG4gICAgICB9O1xuXG4gICAgICBpZiAocmVjdC50b3AgIT09IHRoaXMuY29udGFpbmVyLm9mZnNldCgpLnRvcCkge1xuICAgICAgICB0aGlzLmNvbnRhaW5lci5jc3MoeyB0b3A6IHJlY3QudG9wIH0pO1xuICAgICAgfVxuXG4gICAgICAvLyBXaW5kb3cgcmVzaXplcyBjb3VsZCBjaGFuZ2UgdGhlIHNjcm9sbCBwb3NpdGlvbiwgYnV0IHdvbid0IHRyaWdnZXIgYVxuICAgICAgLy8gc2Nyb2xsIGV2ZW50IG9uIHRoZWlyIG93bi4gRm9yY2UgYSBjYWxjdWxhdGlvbiBvZiBwb3NpdGlvbmluZy5cbiAgICAgIHRoaXMub25TY3JvbGwoZSwgcmVjdCwgb2Zmc2V0KTtcbiAgICB9XG5cbiAgICBkZXN0cm95KCkge1xuICAgICAgLy8gVW5yZWdpc3RlciB0aGVzZSBldmVudCBsaXN0ZW5lcnMsIHNvIHRoZXNlIGl0ZW1zIGFyZSBub3QgbGluZ2VyaW5nLlxuICAgICAgVG9vbHNoZWQuZXZlbnRzLnNjcm9sbC5yZW1vdmUodGhpcyk7XG4gICAgICBUb29sc2hlZC5ldmVudHMucmVzaXplLnJlbW92ZSh0aGlzKTtcblxuICAgICAgaWYgKHRoaXMuY29udGFpbmVyKSB7XG4gICAgICAgIHRoaXMuY29udGFpbmVyLnJlbW92ZSgpO1xuICAgICAgfVxuICAgIH1cbiAgfTtcblxuICBUb29sc2hlZC5Eb2NrLlRvcERvY2tDb250YWluZXIgPSBjbGFzcyBleHRlbmRzIFRvb2xzaGVkLkRvY2suRG9ja0NvbnRhaW5lciB7XG4gICAgLyoqXG4gICAgICogRG9ja2luZyBjb250YWluZXIgc3BlY2lmaWMgaGFuZGxpbmcgb2YgdGhlIGRvY2tpbmcgY29udGFpbmVyLlxuICAgICAqL1xuICAgIGluaXRDb250YWluZXIoKSB7XG4gICAgICB0aGlzLmNvbnRhaW5lci5jc3Moe1xuICAgICAgICBwb3NpdGlvbjogJ2ZpeGVkJyxcbiAgICAgICAgdG9wOiAwLFxuICAgICAgICB3aWR0aDogJzEwMCUnLFxuICAgICAgICBib3hTaXppbmc6ICdib3JkZXItYm94JyxcbiAgICAgIH0pO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIERldGVybWluZSBpZiB0aGUgY29udGVudCBmaXRzIGFuZCBpcyBpbiB0aGUgdmlld2FibGUgd2luZG93IGFyZWEuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge0RydXBhbC5Ub29sc2hlZC5HZW9tLlJlY3R9IGl0ZW1cbiAgICAgKiAgIFJlY3Qgb2YgdGhlIGRvY2thYmxlIGNvbnRlbnQuXG4gICAgICogQHBhcmFtIHtEcnVwYWwuVG9vbHNoZWQuR2VvbS5SZWN0fSB3aW5cbiAgICAgKiAgIFZpZXdhYmxlIHdpbmRvdyBzcGFjZS5cbiAgICAgKlxuICAgICAqIEByZXR1cm4ge0Jvb2xlYW59XG4gICAgICogICBUUlVFIGlmIHRoZSBkb2NraW5nIGNvbnRlbnQgaXMgb3V0c2lkZSB0aGUgdmlld2FibGUgd2luZG93LlxuICAgICAqL1xuICAgIGlzRG9ja2luZyhpdGVtLCB3aW4pIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBjbGFzcy1tZXRob2RzLXVzZS10aGlzXG4gICAgICBjb25zdCBjbnQgPSBpdGVtLmdldENvbnRhaW5lclJlY3QoKTtcbiAgICAgIGxldCB0b3AgPSBNYXRoLmZsb29yKGl0ZW0ucGxhY2Vob2xkZXIub2Zmc2V0KCkudG9wICsgaXRlbS5jb25maWcub2Zmc2V0KTtcblxuICAgICAgaWYgKGl0ZW0uY29uZmlnLm9mZnNldCA8IDApIHtcbiAgICAgICAgdG9wICs9IGl0ZW0ucGxhY2Vob2xkZXIuaGVpZ2h0KCk7XG4gICAgICB9XG5cbiAgICAgIHJldHVybiAodG9wIDwgd2luLnRvcClcbiAgICAgICAgJiYgKGNudC5ib3R0b20gPiB3aW4udG9wKVxuICAgICAgICAmJiBpdGVtLmVsZW0ub3V0ZXJIZWlnaHQoKSA8IGNudC5nZXRIZWlnaHQoKTtcbiAgICB9XG4gIH07XG5cbiAgLyoqXG4gICAqIEEgZG9ja2FibGUgaXRlbSB0aGF0IGdvZXMgaW50byBhIGRvY2sgY29udGFpbmVyLlxuICAgKi9cbiAgVG9vbHNoZWQuRG9jay5Eb2NrSXRlbSA9IGNsYXNzIHtcbiAgICAvKipcbiAgICAgKiBDcmVhdGUgYSBuZXcgaW5zdGFuY2Ugb2YgYSBkb2NrYWJsZSBpdGVtLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtqUXVlcnl9ICRlbGVtXG4gICAgICogICBUaGUgZWxlbWVudCB0aGF0IGlzIGJlaW5nIGRvY2tlZCB3aXRoaW4gdGhpcyBkb2NraW5nIGNvbnRhaW5lci5cbiAgICAgKiBAcGFyYW0ge2pRdWVyeX0gJGJvdW5kc1xuICAgICAqICAgVGhlIERPTSBlbGVtZW50IHRoYXQgaXMgdXNlZCB0byBkZXRlcm1pbmUgdGhlIGJvdW5kcyBvZiB3aGVuXG4gICAgICogICB0aGlzIGl0ZW0gaXMgYmVpbmcgZG9ja2VkLlxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSBzZXR0aW5nc1xuICAgICAqICAgU2V0dGluZ3MgdGhhdCBjb250cm9sIGhvdyB0aGlzIGl0ZW0gYmVoYXZlcyB3aGlsZSBkb2NraW5nIGFuZFxuICAgICAqICAgdW5kb2NraW5nIGZyb20gYSBkb2NrIGNvbnRhaW5lci5cbiAgICAgKi9cbiAgICBjb25zdHJ1Y3RvcigkZWxlbSwgJGJvdW5kcywgc2V0dGluZ3MpIHtcbiAgICAgIHRoaXMuZWxlbSA9ICRlbGVtO1xuICAgICAgdGhpcy5ib3VuZHMgPSAkYm91bmRzO1xuICAgICAgdGhpcy5jb25maWcgPSBzZXR0aW5ncztcblxuICAgICAgdGhpcy5lbGVtLmFkZENsYXNzKCd0c2RvY2staXRlbScpO1xuICAgICAgdGhpcy5pc0RvY2tlZCA9IGZhbHNlO1xuXG4gICAgICAvLyBBcHBseSBhbmltYXRpb24gc2V0dGluZ3MsIG9yIHVzZSB0aGUgZGVmYXVsdHMgaWYgdGhleSBhcmUgcHJvdmlkZWQuXG4gICAgICBpZiAodGhpcy5jb25maWcuYW5pbWF0ZSkge1xuICAgICAgICB0aGlzLm1vZGUgPSB0aGlzLmNvbmZpZy5hbmltYXRlLnR5cGUgfHwgJ3NsaWRlJztcbiAgICAgIH1cblxuICAgICAgdGhpcy5pbml0KCk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogTlVMTCBmdW5jdGlvbiwgbWVhbnQgdG8gYmUgYSBwbGFjZWhvbGRlciBmb3IgZWRnZXMgdGhhdCBtaWdodFxuICAgICAqIG5lZWQgdG8gaGF2ZSBjdXN0b20gaW5pdGlhbGl6YXRpb24uXG4gICAgICovXG4gICAgaW5pdCgpIHtcbiAgICAgIC8vIENyZWF0ZSBhIG5ldyBwbGFjZWhvbGRlciwgdGhhdCB3aWxsIGtlZXAgdHJhY2sgb2YgdGhlIHNwYWNlXG4gICAgICAvLyB1c2VkIGJ5IHRoZSBkb2NrZWQgZWxlbWVudCwgd2hpbGUgaXQncyBiZWluZyBkb2NrZWQgdG8gdGhlIGNvbnRhaW5lci5cbiAgICAgIHRoaXMucGxhY2Vob2xkZXIgPSB0aGlzLmVsZW0ud3JhcCgnPGRpdiBjbGFzcz1cInRzZG9ja19fcGxhY2Vob2xkZXJcIi8+JykucGFyZW50KCk7XG4gICAgICB0aGlzLnBsYWNlaG9sZGVyLmNzcyh7IHBvc2l0aW9uOiB0aGlzLmVsZW0uY3NzKCdwb3NpdGlvbicpIH0pO1xuICAgICAgdGhpcy5oZWlnaHQgPSB0aGlzLmVsZW0ub3V0ZXJIZWlnaHQoKTtcblxuICAgICAgLy8gSWYgYXZhaWxhYmxlLCB0cnkgdG8gdHJhY2sgdGhlIHNpemUgb2YgdGhlIGRvY2tlZCBlbGVtZW50XG4gICAgICAvLyBhbmQgbWFrZSB1cGRhdGVzIHRvIHRoZSBkb2NraW5nIHN5c3RlbSBpZiBkaW1lbnNpb25zIGNoYW5nZS5cbiAgICAgIGlmICh0aGlzLmNvbmZpZy50cmFja011dGF0aW9ucyAmJiBNdXRhdGlvbk9ic2VydmVyKSB7XG4gICAgICAgIHRoaXMub2JzZXJ2ZXIgPSBuZXcgTXV0YXRpb25PYnNlcnZlcih0aGlzLl9tdXRhdGVkLmJpbmQodGhpcykpO1xuICAgICAgICB0aGlzLm9ic2VydmVyLm9ic2VydmUodGhpcy5lbGVtWzBdLCB7XG4gICAgICAgICAgYXR0cmlidXRlczogdHJ1ZSxcbiAgICAgICAgICBjaGlsZExpc3Q6IHRydWUsXG4gICAgICAgICAgc3VidHJlZTogdHJ1ZSxcbiAgICAgICAgICBjaGFyYWN0ZXJEYXRhOiB0cnVlLFxuICAgICAgICB9KTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBNdXRhdGlvbiBldmVudCBsaXN0ZW5lci4gV2lsbCBiZSByZWdpc3RlcmVkIGJ5IHJlbGV2YW50IGRvY2tlciB0eXBlc1xuICAgICAqIGFuZCB0cmlnZ2VyIHdoZW4gdGhlIGRvY2tpbmcgZWxlbWVudCBpcyBtb2RpZmllZCBpbiB0aGUgYXBwcm9wcmlhdGUgd2F5cy5cbiAgICAgKi9cbiAgICBfbXV0YXRpb25zKCkge1xuICAgICAgLy8gRGlzYWJsZSBtdXRhdGlvbiBldmVudHMgd2hpbGUgd2UgcHJvY2VzcyB0aGUgY3VycmVudCBkb2NraW5nIGluZm9ybWF0aW9uLlxuICAgICAgdGhpcy5vYnNlcnZlci5kaXNjb25uZWN0KCk7XG5cbiAgICAgIC8vIEluIG1vc3QgY2FzZXMgd2Ugb25seSBjYXJlIGlmIHRoZSBoZWlnaHQgaGFzIGNoYW5nZWQuXG4gICAgICBjb25zdCBoZWlnaHQgPSB0aGlzLmVsZW0ub3V0ZXJIZWlnaHQoKTtcbiAgICAgIGlmICh0aGlzLmhlaWdodCAhPT0gaGVpZ2h0KSB7XG4gICAgICAgIHRoaXMuaGVpZ2h0ID0gaGVpZ2h0IHx8IDA7XG5cbiAgICAgICAgaWYgKHRoaXMucGxhY2Vob2xkZXIpIHtcbiAgICAgICAgICB0aGlzLnBsYWNlaG9sZGVyLmhlaWdodChoZWlnaHQpO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3Qgd2luID0gbmV3IFRvb2xzaGVkLkdlb20uUmVjdChUb29sc2hlZC53aW5SZWN0KTtcbiAgICAgICAgY29uc3Qgc2Nyb2xsUG9zID0gKGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zY3JvbGxUb3AgfHwgZG9jdW1lbnQuYm9keS5zY3JvbGxUb3ApO1xuICAgICAgICB0aGlzLnNjcm9sbChzY3JvbGxQb3MsIHdpbik7XG4gICAgICB9XG5cbiAgICAgIHRoaXMub2JzZXJ2ZXIub2JzZXJ2ZSh0aGlzLmVsZW1bMF0sIHtcbiAgICAgICAgYXR0cmlidXRlczogdHJ1ZSxcbiAgICAgICAgY2hpbGRMaXN0OiB0cnVlLFxuICAgICAgICBzdWJ0cmVlOiB0cnVlLFxuICAgICAgICBjaGFyYWN0ZXJEYXRhOiB0cnVlLFxuICAgICAgfSk7XG4gICAgfVxuXG4gICAgZ2V0Q29udGFpbmVyUmVjdCgpIHtcbiAgICAgIGNvbnN0IHsgdG9wLCBsZWZ0IH0gPSB0aGlzLmJvdW5kcy5vZmZzZXQoKTtcbiAgICAgIHJldHVybiBuZXcgVG9vbHNoZWQuR2VvbS5SZWN0KFxuICAgICAgICB0b3AsXG4gICAgICAgIGxlZnQsXG4gICAgICAgIHRvcCArIHRoaXMuYm91bmRzLm91dGVySGVpZ2h0KCksXG4gICAgICAgIGxlZnQgKyB0aGlzLmJvdW5kcy5vdXRlcldpZHRoKCksXG4gICAgICApO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFR1cm4gb24gZG9ja2luZyBmb3IgdGhpcyBpbnN0YW5jZS5cbiAgICAgKlxuICAgICAqIFRoaXMgc2hvdWxkIG1ha2UgdGhlIGVsZW1lbnQgZG9jayB0byB0aGUgcmVzcGVjdGl2ZSBlZGdlIGFuZCBzZXQgdGhlXG4gICAgICogY29ycmVjdCBiZWhhdmlvcnMgZm9yIGl0ZW1zIHdoZW4gdGhleSBhcmUgZG9ja2VkLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtqUXVlcnl9IGFkZFRvXG4gICAgICogICBFbGVtZW50IHRvIGFkZCB0aGUgZG9ja2VkIGl0ZW0gaW50by5cbiAgICAgKi9cbiAgICBhY3RpdmF0ZURvY2soYWRkVG8pIHtcbiAgICAgIGlmICghdGhpcy5pc0RvY2tlZCkge1xuICAgICAgICB0aGlzLmlzRG9ja2VkID0gdHJ1ZTtcbiAgICAgICAgdGhpcy5wbGFjZWhvbGRlci5oZWlnaHQodGhpcy5oZWlnaHQpO1xuXG4gICAgICAgIGFkZFRvLmFwcGVuZCh0aGlzLmVsZW0pO1xuICAgICAgICB0aGlzLmVsZW0uYWRkQ2xhc3MoJ3RzZG9jay1pdGVtLS1kb2NrZWQnKTtcbiAgICAgICAgdGhpcy5lbGVtLnRyaWdnZXIoJ1Rvb2xzaGVkRG9ja2luZy5kb2NrZWQnKTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBUdXJuIGRvY2tpbmcgb2ZmIGZvciB0aGlzIGRvY2tlZCBpdGVtLlxuICAgICAqL1xuICAgIGRlYWN0aXZhdGVEb2NrKCkge1xuICAgICAgaWYgKHRoaXMuaXNEb2NrZWQpIHtcbiAgICAgICAgdGhpcy5pc0RvY2tlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnBsYWNlaG9sZGVyLmFwcGVuZCh0aGlzLmVsZW0pO1xuICAgICAgICB0aGlzLmVsZW0ucmVtb3ZlQ2xhc3MoJ3RzZG9jay1pdGVtLS1kb2NrZWQnKTtcblxuICAgICAgICAvLyBSZXNldCB0aGUgcGxhY2Vob2xkZXIgdG8gc2l6ZSBhY2NvcmRpbmcgdG8gdGhlIHBsYWNlaG9sZGVyLlxuICAgICAgICB0aGlzLnBsYWNlaG9sZGVyLmNzcyh7IGhlaWdodDogJycgfSk7XG4gICAgICAgIHRoaXMuZWxlbS50cmlnZ2VyKCdUb29sc2hlZERvY2tpbmcudW5kb2NrZWQnKTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICBkZXN0cm95KCkge1xuICAgICAgaWYgKHRoaXMub2JzZXJ2ZXIpIHRoaXMub2JzZXJ2ZXIuZGlzY29ubmVjdCgpO1xuICAgICAgdGhpcy5kZWFjdGl2YXRlRG9jaygpO1xuXG4gICAgICBpZiAodGhpcy5wbGFjZWhvbGRlcikge1xuICAgICAgICB0aGlzLmVsZW0udW53cmFwKCcudHNkb2NrX19wbGFjZWhvbGRlcicpO1xuICAgICAgfVxuICAgIH1cbiAgfTtcblxuICBUb29sc2hlZC5Eb2NrLmNvbnRhaW5lcnMgPSB7XG4gICAgVE9QOiBuZXcgVG9vbHNoZWQuRG9jay5Ub3BEb2NrQ29udGFpbmVyKCksXG4gIH07XG59KShqUXVlcnksIERydXBhbC5Ub29sc2hlZCk7XG4iXX0=
