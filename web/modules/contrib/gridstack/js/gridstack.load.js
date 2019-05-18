/**
 * @file
 * Provides GridStack loader.
 */

(function ($, Drupal, drupalSettings, _, _db, window) {

  'use strict';

  Drupal.gridstack = Drupal.gridstack || {};

  /**
   * GridStack front-end public methods.
   *
   * @namespace
   */
  Drupal.gridstack.fe = {
    $el: null,
    $instance: null,
    horizontalMargin: 0,
    config: {},
    breakpoints: null,
    windowWidth: 0,
    options: {},
    serializedData: null,
    smGrids: {},
    mdGrids: {},
    lgGrids: {},
    xlGrids: {},
    xsWidth: 0,
    smWidth: 0,
    mdWidth: 0,
    lgWidth: 0,
    xlWidth: 0,

    /**
     * Returns the grid nodes from each visible box.
     *
     * @param {HTMLElement} box
     *   The .gridstack__box:visible HTML element.
     *
     * @return {array}
     *   The serialized box data.
     */
    serialized: function (box) {
      var me = this;
      var data = _.map(box, function (grid) {
        var node = $(grid).data('_gridstack_node');
        // Bail out, if disabled by disabling Auto option, else error.
        if (typeof node === 'undefined') {
          return {};
        }
        var grids = {
          x: node.x,
          y: node.y,
          width: node.width,
          height: node.height
        };

        return grids;
      }, me);

      return data;
    },

    updateClasses: function () {
      var me = this;
      var minWidth = me.config.minWidth;

      if (me.windowWidth <= minWidth) {
        me.onDisabled();
      }
      else {
        me.onEnabled();
      }

      if (!me.config.auto) {
        me.$el.addClass('gridstack--uninit');
      }
    },

    onEnabled: function () {
      var me = this;
      me.$el.addClass('gridstack--enabled');
    },

    onDisabled: function () {
      var me = this;
      me.$el.removeClass('gridstack--enabled');
      me.$el.removeAttr('style');
    },

    /**
     * Updates the current box aspect ratio.
     *
     * @param {HTMLElement} $box
     *   The gridstack item.
     * @param {object} node
     *   The node object containing data: width, height, etc.
     */
    updateRatio: function ($box, node) {
      // @todo https://github.com/gridstack/gridstack.js/issues/304
      // @todo var width = (node.width * me.$instance.cellWidth()) + me.horizontalMargin;
      // @todo var height = ((node.height * me.options.cellHeight) + (me.options.verticalMargin));
      var width = node.width;
      var height = node.height;
      var pad = Math.round(((height / width) * 100), 2);
      var $media = $('.media--ratio', $box);

      if ($media.length && width > 0 && height > 0) {
        $media.removeAttr('data-ratio');
        $media.css({paddingBottom: pad + '%'});
        // @todo $('.box__content', $box).css('min-height', $media.outerHeight());
      }
    },

    updateGrid: function () {
      var me = this;
      var activeGrid = null;
      var keys = _.keys(me.breakpoints);
      var max = parseInt(_.last(keys));
      var tColumn = null;
      var tWidth = null;

      var breakpoints = keys.sort(function (a, b) {
        return (me.options.mobileFirst) ? a - b : b - a;
      });

      _.each(breakpoints, function (width, i) {
        if (me.windowWidth <= width) {
          tColumn = me.breakpoints[width];
          tWidth = width;
        }
        else if (me.windowWidth >= max) {
          tColumn = me.breakpoints[max];
          tWidth = max;
        }
      });

      if (!_.isNull(tColumn)) {
        me.$instance.setGridWidth(tColumn, true);

        // {"480":1,"767":2,"1024":3,"1400":12}.
        activeGrid = me.activeGrid(tWidth, max);
        if (!_.isNull(activeGrid)) {
          _.each(activeGrid, function (item, i) {
            var $box = $('> .gridstack__box:visible', me.$el).eq(i);

            me.updateRatio($box, item);

            item = _.isObject(item) ? _.values(item) : item;

            // Params: el, x, y, width, height.
            me.$instance.update($box, item[0], item[1], item[2], item[3]);
          });
        }
      }
    },

    /**
     * Returns the active grid based on the window width.
     *
     * @param {int} width
     *   The expected window width to activate the current grid.
     * @param {int} max
     *   The last breakpoint key, hence the default desktop window width.
     *
     * @return {object|null}
     *   The current active grid or null.
     */
    activeGrid: function (width, max) {
      var me = this;

      // Do not do anything if no responsive grids defined.
      if (me.smWidth || me.mdWidth || me.lgWidth || me.xlWidth) {
        if (max <= width) {
          return me.serializedData;
        }
        else if (me.xlWidth <= width) {
          return me.xlGrids;
        }
        else if (me.lgWidth <= width) {
          return me.lgGrids;
        }
        else if (me.mdWidth <= width) {
          return me.mdGrids;
        }
        else if (me.smWidth <= width) {
          return me.smGrids;
        }
      }

      return null;
    },

    buildOutAndUpdate: function () {
      var me = this;

      me.updateClasses();

      if (!_.isNull(me.breakpoints)) {
        me.updateGrid();
      }
    },

    buildOutAndResize: function () {
      var me = this;

      var doResize = function () {
        me.windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        me.buildOutAndUpdate();
      };

      // Two tasks: load the active grid per breakpoint, and update on resizing.
      _db.resize(doResize)();
    },

    cleanUp: function () {
      var me = this;

      me.$el.removeClass('grid-stack-12 gridstack--destroyed');
      me.$el.removeAttr('data-breakpoints data-config');
      me.$el.removeAttr('data-xs-grids data-sm-grids data-md-grids data-lg-grids data-xl-grids');
      me.$el.removeAttr('data-xs-width data-sm-width data-md-width data-lg-width data-xl-width');
      window.setTimeout(function () {
        me.$el.removeClass('gridstack--packing');
      }, 300);
    },

    destroy: function () {
      var me = this;

      if (me.$instance !== null) {
        me.$instance.destroy(false);

        me.$el.addClass('gridstack--destroyed');
        me.$el.removeAttr('style');
      }
    },

    /**
     * Initializes the GridStack.
     *
     * @param {HTMLElement} elm
     *   The .gridstack--js HTML element.
     */
    init: function (elm) {
      var me = this;
      var defaults = drupalSettings.gridstack || {};
      var $elm = $(elm);
      var box = $('> .gridstack__box:visible', elm);
      var base = {
        mobileFirst: false,
        itemClass: 'gridstack__box',
        handle: '.box__content',
        oneColumnModeClass: 'gridstack--disabled'
      };

      me.config = $elm.data('config') || {};
      me.options = $.extend({}, defaults, base, me.config);

      $elm.gridstack(me.options);

      me.$el = $elm;

      me.$instance = $elm.data('gridstack');
      me.breakpoints = $elm.data('breakpoints') || null;
      me.serializedData = me.serialized(box);
      me.horizontalMargin = me.options.horizontalMargin ? 16 : 0;
      me.smGrids = $elm.data('smGrids') || {};
      me.mdGrids = $elm.data('mdGrids') || {};
      me.lgGrids = $elm.data('lgGrids') || {};
      me.xlGrids = $elm.data('xlGrids') || {};
      me.xsWidth = $elm.data('xsWidth') || 0;
      me.smWidth = $elm.data('smWidth') || 0;
      me.mdWidth = $elm.data('mdWidth') || 0;
      me.lgWidth = $elm.data('lgWidth') || 0;
      me.xlWidth = $elm.data('xlWidth') || 0;

      me.buildOutAndResize();
      me.cleanUp();
    }

  };

  /**
   * GridStack utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The GridStack HTML element.
   */
  function doGridStack(i, elm) {
    Drupal.gridstack.fe.init(elm);
  }

  /**
   * Attaches gridstack behavior to HTML element identified by .gridstack--js.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.gridstack = {
    attach: function (context) {
      $('.gridstack--js:not(.ungridstack)', context).once('gridstack').each(doGridStack);
    }
  };

})(jQuery, Drupal, drupalSettings, _, dBlazy, this);
