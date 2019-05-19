
(($, Toolshed, settings) => {
  const defaultOpts = {
    autoListen: true, // Register and deregister the event automatically.
    debounce: settings.eventDebounce,
    passive: true,
  };

  // Initialize the Toolshed global listeners and window objects.
  Toolshed.winOffset = { top: 0, left: 0 };
  Toolshed.winRect = new Toolshed.Geom.Rect(0, 0, window.innerWidth, window.innerHeight);
  Toolshed.events = {};

  /**
   * Create a global event listener for window scroll events.
   *
   * Toolshed JS scroll event does additional calculations to maintain the
   * visibile window space still available to listener items. This helps for
   * layouts, dockers and other screen elements that need to know their
   * workable space taken by other Toolshed JS libraries.
   *
   * @type {EventListener}
   */
  Toolshed.events.scroll = new Toolshed.EventListener(window, 'scroll', defaultOpts);
  Toolshed.events.scroll._run = function _run(e) {
    const rect = new Toolshed.Geom.Rect(Toolshed.winRect);
    const scroll = {
      left: document.documentElement.scrollLeft || document.body.scrollLeft,
      top: document.documentElement.scrollTop || document.body.scrollTop,
    };

    this.listeners.forEach(listener => listener.onScroll(e, rect, scroll));
  };

  /**
   * Create a global event listener for window resize events.
   *
   * The global resize event listener is overridden to pass the available
   * window size and position to
   *
   * @type {EventListener}
   */
  Toolshed.events.resize = new Toolshed.EventListener(window, 'resize', defaultOpts);
  Toolshed.events.resize._run = function _run(e, force = false) {
    const right = window.innerWidth || window.clientWidth;
    const bottom = window.innerHeight || window.clientHeight;

    // Only trigger if the size has changed in some way.
    if (Toolshed.winRect.bottom !== bottom
      || Toolshed.winRect.top !== Toolshed.winOffset.top
      || Toolshed.winRect.right !== right
      || Toolshed.winRect.left !== Toolshed.winOffset.left
      || force
    ) {
      Toolshed.winRect.top = Toolshed.winOffset.top;
      Toolshed.winRect.left = Toolshed.winOffset.left;
      Toolshed.winRect.right = right;
      Toolshed.winRect.bottom = bottom;

      const rect = new Toolshed.Geom.Rect(Toolshed.winRect);
      this.listeners.forEach(listener => listener.onResize(e, rect));
    }
  };

  // Defer creating and using this mql until after the document is ready.
  if (settings && settings.breakpoints) {
    Toolshed.events.mediaQueries = new Toolshed.MediaQueryListener(
      settings.breakpoints,
      defaultOpts,
    );
  }

  /**
   * When the DOM is ready, start listening for the MediaQuery events
   * and keep track of the offset created by the admin too bar.
   */
  $(() => {
    $(document).on('drupalViewportOffsetChange.toolbar', () => {
      const toolbar = $('#toolbar-bar');
      const tooltray = $('.toolbar-tray.is-active', toolbar);

      Toolshed.winOffset.top = $('body').hasClass('toolbar-fixed') ? toolbar.height() : 0;

      if (tooltray.length) {
        if (tooltray.hasClass('toolbar-tray-horizontal')) Toolshed.winOffset.top += tooltray.height();
        else if (tooltray.hasClass('toolbar-tray-vertical')) Toolshed.winOffset.left = tooltray.width();
      }

      Toolshed.events.resize.trigger();
    });

    // Run the initial resize callback if the toolbar has already loaded.
    if ($('#toolbar-bar').height()) {
      $(document).trigger('drupalViewportOffsetChange.toolbar');
    }
  });
})(jQuery, Drupal.Toolshed, drupalSettings.Toolshed);
