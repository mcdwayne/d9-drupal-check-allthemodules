/**
 * @file
 * Provides GridStack Outlayer loader.
 */

(function (Drupal, drupalSettings, Isotope, _db) {

  'use strict';

  Drupal.outLayer = Drupal.outLayer || {};

  /**
   * Outlayer utility functions.
   *
   * @namespace
   */
  Drupal.outLayer.isotope = {

    $instance: null,
    $el: null,
    $empty: null,
    $items: [],
    $filter: null,
    $sorter: null,
    $search: null,
    $input: null,
    isIsotope: false,
    useGridStack: true,
    dataFilter: null,
    searchString: null,
    activeFilters: [],
    activeSorters: [],
    gridHeight: 210 + 'px',

    /**
     * Initializes the Outlayer instance.
     *
     * @param {bool} force
     *   If should be forced such as when not using gridstack layout.
     */
    init: function (force) {
      var me = this;
      var opts = me.$el.getAttribute('data-outlayer-isotope') ? _db.parse(me.$el.getAttribute('data-outlayer-isotope')) : {};

      // If not using gridstack, layout items manually.
      if (!me.useGridStack) {
        Drupal.outLayer.base.updateRatio(me.$items);
      }

      // Only initialize it if not already, or a force for ugridstack.
      if (me.$instance === null || force) {
        me.isIsotope = true;
        me.$instance = new Isotope(me.$el, opts);

        me.$instance.on('arrangeComplete', function (filteredItems) {
          // Add hints about empty results.
          if (me.$empty !== null) {
            var empty = filteredItems.length === 0;
            me.$el.classList[empty ? 'add' : 'remove']('outlayer--empty');
            me.$empty.textContent = empty ? Drupal.t('No data found.') : '';
          }
        });
      }
    },

    /**
     * Destroys the Outlayer instance.
     */
    destroy: function () {
      var me = this;

      // Only destroy it using gridstack to avoid breaking gridstack layouts.
      if (me.$instance !== null && typeof me.$instance !== 'undefined') {
        // Only destroy if using gridstack layout.
        if (me.useGridStack) {
          me.$instance.destroy();

          // Reset to defaults.
          me.isIsotope = false;
          me.$el.style.height = me.gridHeight;
          me.$el.classList.remove('outlayer--empty');

          // We have no onDestroy event, and destroy is unfortunately unclean.
          // This causes unwanted transition, but at least not breaking layout.
          // Isotope is NOT immediately destroyed till transitions ends.
          window.setTimeout(function () {
            _db.forEach(me.$items, function (item) {
              item.removeAttribute('style');
            });
          }, 1000);
        }
        else {
          me.$instance.arrange();
        }
      }
    },

    /**
     * Revalidate items.
     */
    revalidate: function () {
      var me = this;
      var $elms = me.$el.querySelectorAll('.b-lazy:not(.b-loaded)');

      // Revalidate blazy.
      if ($elms !== null && Drupal.blazy) {
        Drupal.blazy.init.load($elms);
      }
    }
  };

  /**
   * Outlayer utility functions.
   *
   * @param {HTMLElement} grid
   *   The Outlayer HTML element.
   */
  function doOutLayerIsotope(grid) {
    var me = Drupal.outLayer.isotope;
    var id = grid.getAttribute('data-instance-id');

    // Pass data to Drupal.outLayer.isotope for easy reference.
    me.$el = grid;
    me.gridHeight = me.$el.style.height;
    me.$filter = document.querySelector('.outlayer-list--filter[data-instance-id="' + id + '"]');
    me.$search = document.querySelector('.outlayer-list--search[data-instance-id="' + id + '"]');
    me.$sorter = document.querySelector('.outlayer-list--sorter[data-instance-id="' + id + '"]');

    /**
     * Filter elements on a button click.
     *
     * @param {Event} e
     *   The event triggering the filter.
     */
    function doFilter(e) {
      var btn = e.target;
      var $active = me.$filter.querySelector('.is-active');

      me.dataFilter = btn.getAttribute('data-filter');

      // Only initialize it once on an event to not mess up with gridstack.
      me.init();

      // Toggle the current active button class.
      if ($active !== null) {
        $active.classList.remove('is-active');
      }

      btn.classList.add('is-active');

      // Filter items.
      me.$instance.arrange({
        filter: me.dataFilter
      });

      // Revalidate items.
      me.revalidate();
    }

    /**
     * Filter elements on a button click.
     *
     * @param {Event} e
     *   The event triggering the filter.
     */
    function doSearch(e) {
      var $target = e.target;
      var searchText = $target.value;

      me.$input = $target;
      if (me.$input.value === '') {
        doDestroy();

        return;
      }

      // Only initialize it once on an event to not destroy gridstack.
      me.init();

      me.searchString = Drupal.checkPlain(searchText.toLowerCase());

      me.$instance.arrange({
        filter: function () {
          var elm = this;
          return elm.textContent.toLowerCase().indexOf(me.searchString) !== -1 ? true : false;
        }
      });

      // Revalidate items.
      me.revalidate();
    }

    /**
     * Sorter elements on a button click.
     *
     * @param {Event} e
     *   The event triggering the filter.
     */
    function doSorter(e) {
      var btn = e.target;
      var value = btn.getAttribute('data-sort-by');
      var $active = me.$sorter.querySelector('.is-active');
      var options;

      // Only initialize it once on an event to not mess up with gridstack.
      me.init();

      // Toggle the current active button class.
      if ($active !== null) {
        $active.classList.remove('is-active');
      }

      btn.classList.add('is-active');

      // Sort items.
      options = {
        sortBy: value
      };

      me.$instance.arrange(options);

      // Revalidate items.
      me.revalidate();
    }

    /**
     * Filter elements on a button click.
     */
    function doDestroy() {
      me.destroy();

      if (me.$input !== null) {
        me.$input.value = '';
      }
    }

    /**
     * Layout elements.
     */
    function outlay() {
      // Outlayer only triggered on events, not on init.
      if (me.$filter !== null) {
        _db.on(me.$filter, 'click', '.button--filter', doFilter);
      }

      if (me.$search !== null) {
        var onSearch = Drupal.debounce(doSearch, 250);
        _db.on(me.$search, 'keyup', '.form-text--search', onSearch);
      }

      if (me.$sorter !== null) {
        me.activeSorters = me.$sorter.hasAttribute('data-sorters') ? _db.parse(me.$sorter.getAttribute('data-sorters')) : {};
        _db.on(me.$sorter, 'click', '.button--sorter', doSorter);
      }

      _db.forEach([me.$filter, me.$search, me.$sorter], function (elm) {
        if (elm !== null) {
          _db.on(elm, 'click', '.button--reset', doDestroy);
        }
      });

      // Only initialize Isotope if not using gridstack grids.
      me.$empty = me.$el.querySelector('.outlayer__empty');
      me.$items = me.$el.querySelectorAll('.gridstack__box');
      if (me.$el.classList.contains('ungridstack')) {
        me.useGridStack = false;
        me.init(true);
      }

      // Add a class that we are laid out.
      me.$el.classList.add('outlayer--isotope--on');
    }

    outlay();
  }

  /**
   * Attaches Outlayer behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.outLayerIsotope = {
    attach: function (context) {
      var galleries = context.querySelectorAll('.outlayer--isotope:not(.outlayer--isotope--on)');
      _db.once(_db.forEach(galleries, doOutLayerIsotope, context));
    }
  };

}(Drupal, drupalSettings, Isotope, dBlazy));
