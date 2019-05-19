/* eslint no-bitwise: ["error", { "allow": ["^"] }] */
(($, Toolshed) => {
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
    createItem($elem, $bounds, settings = {}) {
      const config = { edge: 'TOP', offset: 0 };

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
        let match = null;
        const optRegex = /(?:^|\s)tsdock--(opt|edge)-([-\w]+)(?:\s|$)/g;
        const elClasses = $elem.attr('class');

        // eslint-disable-next-line no-cond-assign
        while ((match = optRegex.exec(elClasses)) !== null) {
          if (match[1] === 'opt') {
            config[match[2]] = true;
          }
          else if (match[1] === 'edge') {
            [,, config.edge] = match;
          }
        }
      }

      // Build the docker now that all settings have been applied to it.
      const docker = new Toolshed.Dock.DockItem($elem, $bounds, { ...config, ...settings });
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
    addDocker(edge, item) {
      if (Toolshed.Dock.containers[edge]) {
        Toolshed.Dock.containers[edge].addItem(item);
      }
    },
  };

  /**
   * Containers for holding items that are docked to them. DockContainers
   * will listen to Window events and manage the items that they wrap.
   */
  Toolshed.Dock.DockContainer = class {
    constructor() {
      this.active = false;
      this.container = null;
      this.items = [];
    }

    isActive() {
      return this.active;
    }

    /**
     * Add a new docking item to this docking container.
     *
     * @param {Drupal.Toolshed.Dock.DockItem} item
     *   The DockItem to add to this container.
     */
    addItem(item) {
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
    removeItem(item) {
      this.items = this.items.filter(cmp => cmp !== item);
      delete item.dockTo;

      if (!this.items.length && this.container) {
        this.container.hide();
      }
    }

    /**
     * Register events that may make changes to docking, and init positioning.
     */
    init() {
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
    onScroll(e, win, scroll) {
      const viewable = new Toolshed.Geom.Rect(win);
      viewable.offset(scroll.left, scroll.top);

      this.items.forEach((item) => {
        if (item.isDocked ^ this.isDocking(item, viewable)) {
          return item.isDocked ? item.deactivateDock() : item.activateDock(this.container);
        }
      }, this);
    }

    onResize(e, rect) {
      const offset = {
        top: document.documentElement.scrollTop || document.body.scrollTop,
        left: document.documentElement.scrollLeft || document.body.scrollLeft,
      };

      if (rect.top !== this.container.offset().top) {
        this.container.css({ top: rect.top });
      }

      // Window resizes could change the scroll position, but won't trigger a
      // scroll event on their own. Force a calculation of positioning.
      this.onScroll(e, rect, offset);
    }

    destroy() {
      // Unregister these event listeners, so these items are not lingering.
      Toolshed.events.scroll.remove(this);
      Toolshed.events.resize.remove(this);

      if (this.container) {
        this.container.remove();
      }
    }
  };

  Toolshed.Dock.TopDockContainer = class extends Toolshed.Dock.DockContainer {
    /**
     * Docking container specific handling of the docking container.
     */
    initContainer() {
      this.container.css({
        position: 'fixed',
        top: 0,
        width: '100%',
        boxSizing: 'border-box',
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
    isDocking(item, win) { // eslint-disable-line class-methods-use-this
      const cnt = item.getContainerRect();
      let top = Math.floor(item.placeholder.offset().top + item.config.offset);

      if (item.config.offset < 0) {
        top += item.placeholder.height();
      }

      return (top < win.top)
        && (cnt.bottom > win.top)
        && item.elem.outerHeight() < cnt.getHeight();
    }
  };

  /**
   * A dockable item that goes into a dock container.
   */
  Toolshed.Dock.DockItem = class {
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
    constructor($elem, $bounds, settings) {
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
    init() {
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
          characterData: true,
        });
      }
    }

    /**
     * Mutation event listener. Will be registered by relevant docker types
     * and trigger when the docking element is modified in the appropriate ways.
     */
    _mutations() {
      // Disable mutation events while we process the current docking information.
      this.observer.disconnect();

      // In most cases we only care if the height has changed.
      const height = this.elem.outerHeight();
      if (this.height !== height) {
        this.height = height || 0;

        if (this.placeholder) {
          this.placeholder.height(height);
        }

        const win = new Toolshed.Geom.Rect(Toolshed.winRect);
        const scrollPos = (document.documentElement.scrollTop || document.body.scrollTop);
        this.scroll(scrollPos, win);
      }

      this.observer.observe(this.elem[0], {
        attributes: true,
        childList: true,
        subtree: true,
        characterData: true,
      });
    }

    getContainerRect() {
      const { top, left } = this.bounds.offset();
      return new Toolshed.Geom.Rect(
        top,
        left,
        top + this.bounds.outerHeight(),
        left + this.bounds.outerWidth(),
      );
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
    activateDock(addTo) {
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
    deactivateDock() {
      if (this.isDocked) {
        this.isDocked = false;
        this.placeholder.append(this.elem);
        this.elem.removeClass('tsdock-item--docked');

        // Reset the placeholder to size according to the placeholder.
        this.placeholder.css({ height: '' });
        this.elem.trigger('ToolshedDocking.undocked');
      }
    }

    destroy() {
      if (this.observer) this.observer.disconnect();
      this.deactivateDock();

      if (this.placeholder) {
        this.elem.unwrap('.tsdock__placeholder');
      }
    }
  };

  Toolshed.Dock.containers = {
    TOP: new Toolshed.Dock.TopDockContainer(),
  };
})(jQuery, Drupal.Toolshed);
