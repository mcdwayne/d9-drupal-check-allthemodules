/**
 * @file
 * Provides GridStack admin UI utilities.
 */

(function ($, Drupal, drupalSettings, Backbone, _, window) {

  'use strict';

  Drupal.gridstack = Drupal.gridstack || {};
  Drupal.gridstack.models = {};
  Drupal.gridstack.collections = {};
  Drupal.gridstack.views = {};
  Drupal.gridstack.ui = {};
  Drupal.gridstack.icon = {};
  Drupal.gridstack.imageStyle = {};

  var GRIDSTACK_DEFAULT_GRIDS = {
    x: 0,
    y: 0,
    width: 2,
    height: 2
  };

  /**
   * The GridStack box models.
   */
  Drupal.gridstack.models.Box = Backbone.Model.extend({
    defaults: _.extend(GRIDSTACK_DEFAULT_GRIDS, {
      index: 1,
      nested: []
    }),

    getDataToSave: function (o) {
      var me = this;

      o = o || me.attributes;

      return Drupal.gridstack.ui.getCurrentNode(o);
    },

    getDataNestedToSave: function () {
      var me = this;
      var nested = [];
      var nodes = me.get('nested');

      if (nodes.length) {
        nested = _.map(nodes, function (node) {
          return me.getDataToSave(node) || [];
        }, this);
      }

      return nested;
    },

    initialize: function (o) {
      var me = this;

      o = o || {};

      if (_.isUndefined(o.index)) {
        o.index = me.get('index');
      }

      var attributes = Drupal.gridstack.ui.buildId(o);

      me.set(attributes, {
        silent: true
      });
    }
  });

  /**
   * The GridStack box collections.
   */
  Drupal.gridstack.collections.Boxes = Backbone.Collection.extend({
    model: Drupal.gridstack.models.Box,

    initialize: function () {
      var me = this;

      me.on('add remove', me.updateIndex);
    },

    updateIndex: function () {
      var me = this;

      me.each(function (box, i) {
        box.set('ordinal', (i + 1));
      });
    }
  });

  /**
   * The GridStack box views.
   */
  Drupal.gridstack.views.Box = Backbone.View.extend({
    className: 'gridstack__box box box--root',
    tagName: 'div',

    events: {
      change: 'render'
    },

    initialize: function () {
      var me = this;

      me.listenTo(me.model, 'change', me.render);
    },

    render: function () {
      var me = this;
      var box = me.model;
      var data = box.attributes;
      var $box = me.$el;

      me.isNested = box.collection.length ? box.collection._isNested : false;
      me.useCss = box.collection.length ? box.collection._useCss : false;

      if (!$('> .box__content', $box).length) {
        $box.append(Drupal.theme('gridStackContent', {
          nested: me.isNested,
          useCss: me.useCss,
          type: 'root'
        }));
      }

      $box.attr('data-dimension', data.width + 'x' + data.height);
      _.each(['id', 'index', 'ordinal'], function (key) {
        if (!_.isUndefined(data[key])) {
          $box.attr('data-' + key, data[key]);
        }
      });

      $('.btn', $box).attr('data-id', data.id);
      // @todo use me.isNested when js-driven supports nested.
      if (me.useCss && $('.gridstack--nested', $box).length) {
        $('.gridstack--nested', $box).attr('data-gid', data.index).attr('data-index', data.index).attr('data-id', data.id);
      }

      if (!_.isUndefined(data.image_style)) {
        $box.attr('data-image-style', data.image_style);
      }

      me.trigger('gridstack:view:render');

      return me;
    }
  });

  /**
   * The GridStack views.
   */
  Drupal.gridstack.views.GridStack = Backbone.View.extend({
    gridStack: null,
    nodes: [],
    nestedNodes: [],
    wrapper: '.gridstack-wrapper',

    events: {
      resizestop: 'onResizeStop'
    },

    initialize: function (o) {
      var me = this;
      var el = me.$el;
      var ui = Drupal.gridstack.ui;
      var defaults = drupalSettings.gridstack || {};

      // Merge all options.
      o = _.extend({
        options: {
          breakpoint: null,
          currentColumn: 12,
          isNested: false,
          storage: null,
          useCss: false
        },
        saveDataCallback: null
      }, o);

      me.options = o.options;
      me.saveDataCallback = o.saveDataCallback;
      me.useCss = o.options.useCss;

      ui.config = el.data('config') || {};
      var dataConfig = ui.config;

      var currentColumn = me.options.currentColumn;

      // @todo MUST isNested for now:
      if (me.options.isNested) {
        currentColumn = 12;
        ui.config = el.data('configFramework') || {};
        dataConfig = _.extend(ui.config, ui.nestedOptions);
      }

      if (currentColumn < 12) {
        dataConfig.width = me.options.useCss ? 12 : currentColumn;
      }

      ui.opts = _.extend(defaults, ui.baseOptions, dataConfig);

      me.gridStackOptions = ui.opts;
      me.nestedOptions = _.extend(ui.baseOptions, dataConfig);
      me.options.currentColumn = currentColumn;

      me.rendered = false;

      if (me.collection) {
        me.listenTo(me.collection, 'add', me.onAdd);
        me.listenTo(me.collection, 'change', me.onChange);
        me.listenTo(me.collection, 'remove', me.onRemove);

        // Main buttons.
        me.listenTo(me.collection, 'gridstack:main:save', me.onSave);

        // Root box buttons.
        me.listenTo(me.collection, 'gridstack:root:remove', me.onRootRemoveMultiple);

        // Nested grids are css-driven: Bootstrap + Foundation.
        if (me.useCss) {
          me.listenTo(me.collection, 'gridstack:root:add', me.onRootAddMultiple);

          // Content has no .btn--add to avoid complication with deep nesting.
          me.listenTo(me.collection, 'gridstack:content:remove', me.onContentRemoveMultiple);
        }

        // Provides context to collection.
        me.collection._isNested = me.options.isNested;
        me.collection._useCss = me.options.useCss;
      }
    },

    init: function () {
      var me = this;
      var el = me.$el;

      // Initialize GridStack instance.
      el.gridstack(me.gridStackOptions);

      me.gridStack = el.data('gridstack');
      me.nodes = window.GridStackUI.Utils.sort(me.getSerializedData(el));
      me.nestedNodes = window.GridStackUI.Utils.sort(me.getSerializedData(el, true));
      me.$wrapper = el.parent(me.wrapper);

      // Update grid column.
      me.gridStack.setGridWidth(me.options.currentColumn);
    },

    _onWidgetChange: function () {
      var me = this;

      me.$el.on('change', _.bind(me.onWidgetChange, me));
    },

    _offWidgetChange: function () {
      var me = this;

      me.$el.off('change');
    },

    onChange: function () {
      this.saveData();
    },

    onWidgetChange: function () {
      this.updateBoxesAttributes();
    },

    onSave: function () {
      var me = this;

      me.options.updateIcon = true;
      me.saveData();
    },

    onResizeStop: function (e) {
      var me = this;
      var box;
      var $box = $(e.target);

      me.updateBoxDimensions($box);

      me.options.updateStorage = false;

      if (me.el === e.delegateTarget) {
        var id = me.getBoxId($box);
        box = me.getCurrentBox(id);

        me.updateBoxAttributes(box, true);
        me.options.updateStorage = $(e.delegateTarget).data('storage') || false;
      }

      me.saveData();
    },

    onRootAddMultiple: function (e, box, $box) {
      var me = this;
      var delta = $box.index();
      var node = {};

      if (!me.isValidBox(box)) {
        box = me.collection.at(delta);
      }

      var index = box.get('index');
      var el = $box.find('.gridstack:first');

      // Initiliaze the newly added gridstack.
      el.gridstack(me.nestedOptions);
      el.attr('data-gid', index);

      var gridStack = me.getCurrentGrid(el);
      var len = gridStack.grid.nodes.length;
      var data = box.defaults;

      data.index = index;
      data.indexNested = len + 1;

      node = me.getRunTimeNode(data);

      me.addWidget(node, el);

      me.saveData(true);
    },

    onAdd: function (box) {
      var me = this;

      me.addBox(box);
    },

    addBox: function (box) {
      var me = this;

      me._offWidgetChange();
      me.addWidget(box);

      if (me.rendered) {
        me._onWidgetChange();
      }
    },

    addWidget: function (box, el) {
      var me = this;
      var node = box;
      var widget;
      var isCustomBox = false;

      el = el || me.$el;
      var gridStack = me.getCurrentGrid(el);

      if (me.isValidBox(box)) {
        var view = me.getCurrentView(box);
        var $view = view.render().$el;

        node = box.attributes;
        widget = $view[0].outerHTML;
      }
      else {
        isCustomBox = true;

        // @todo: Make nested boxes as nested models if you can.
        widget = Drupal.theme('gridStackBox', {
          // @todo use me.isNested when js-driven supports nested.
          nested: me.options.useCss
        });
      }

      if (!gridStack.willItFit(node.x, node.y, node.width, node.height, true)) {
        return;
      }

      var $box = gridStack.addWidget(
        $(widget),
        node.x,
        node.y,
        node.width,
        node.height,
        true
      );

      if (!_.isUndefined(el) && _.isUndefined($box.context)) {
        $box.context = el[0];
      }

      me.updateBoxDimensions($box);

      if (isCustomBox) {
        me.setBoxNestedAttributes($box, node);
      }
      else {
        // Disable movable, as too complex too handle with breakpoints.
        if (me.useCss) {
          gridStack.movable($box, false);
        }
      }

      me.saveData(true);

      return $box;
    },

    onRemove: function (box) {
      var me = this;
      var view = me.getCurrentView(box);

      view.remove();

      me.updateBoxesAttributes(true);
      me.saveData(true);
    },

    onRootRemoveMultiple: function (e, box, $box) {
      var me = this;

      if (_.isUndefined(box)) {
        return;
      }

      me.removeWidget($box);
      me.collection.remove(box);
    },

    onContentRemoveMultiple: function (e, box, $box) {
      var me = this;

      if (_.isUndefined(box)) {
        return;
      }

      // Remove nested attributes by its index.
      var index = $box.index();
      var nested = box.get('nested');

      if (nested.length) {
        nested.splice(index, 1);
      }

      me.removeWidget($box);

      me.saveData(true);
    },

    removeWidget: function ($box) {
      var me = this;
      var el = $box.parent('.gridstack--nested');
      var gridStack = me.getCurrentGrid(el);

      if (!_.isUndefined(gridStack)) {
        gridStack.removeWidget($box);
      }
    },

    updateBoxesAttributes: function (updateIndex) {
      var me = this;

      me.collection.each(function (box) {
        me.updateBoxAttributes(box, updateIndex);
      }, me);
    },

    updateBoxAttributes: function (box, updateIndex) {
      var me = this;

      if (!me.isValidBox(box)) {
        return;
      }

      var id = box.get('id');
      var $box = $('> .box[data-id="' + id + '"]', me.$el);
      var node = $box.data('_gridstack_node');
      var data = me.getCurrentNode(node);

      if (!_.isEmpty(data)) {
        data.rect = $box[0].firstElementChild.getBoundingClientRect();
        box.set(data);
      }

      // Provides consistent ordinal across multiple breakpoints.
      if (updateIndex) {
        $box.attr('data-ordinal', ($box.index() + 1));
      }

      // Bail out if not nested.
      if (!$box.find('.gridstack').children().length) {
        box.set('nested', []);

        return;
      }

      // Update nested boxes if required.
      // @todo use me.isNested when js-driven supports nested.
      if (me.options.useCss) {
        me.updateBoxNestedAttributes(box, $box);
      }
    },

    updateBoxNestedAttributes: function (box, $box) {
      var me = this;
      var index = box.get('index');
      var nested = _.clone(box.get('nested'));
      var $nested = $('.gridstack--nested:first', $box);
      var $boxes = $('> .gridstack__box', $nested);

      if (!$boxes.length) {
        return;
      }

      _.each($boxes, function (el, i) {
        var $el = $(el);
        var indexNested = i + 1;
        var node = $el.data('_gridstack_node');

        if (!_.isUndefined(node)) {
          node.index = index;
          node.indexNested = indexNested;

          var data = me.getRunTimeNode(node);

          data.rect = el.firstElementChild.getBoundingClientRect();

          nested[i] = _.isUndefined(nested[i]) ? data : _.extend({}, nested[i], data);

          me.setBoxNestedAttributes($el, data);
        }
      });

      if (nested.length > 0) {
        box.set('nested', nested);
      }
    },

    setBoxNestedAttributes: function ($box, node) {
      $box.attr('data-index', node.index);
      $box.attr('data-id', node.id);
      $box.attr('data-ordinal', ($box.index() + 1));
      $box.find('.btn').attr('data-id', node.id);

      if (!_.isUndefined(node.pid)) {
        $box.find('.btn').attr('data-pid', node.pid);
      }
    },

    updateBoxDimensions: function ($box) {
      var node = $box.data('_gridstack_node');

      $box.attr('data-dimension', node.width + 'x' + node.height);
    },

    getBoxId: function ($box) {
      return $box.parent('.gridstack--nested').length ? $box.parent('.gridstack--nested').attr('data-id') : $box.attr('data-id');
    },

    getCurrentBox: function (id) {
      return this.collection.findWhere({
        id: id
      });
    },

    getCurrentNode: function (node) {
      return Drupal.gridstack.ui.getCurrentNode(node);
    },

    getRunTimeNode: function (node) {
      var me = this;

      return _.extend(me.getCurrentNode(node), Drupal.gridstack.ui.buildId(node));
    },

    getCurrentGrid: function (el) {
      return el && el.length ? el.data('gridstack') : this.gridStack;
    },

    getCurrentView: function (box) {
      return new Drupal.gridstack.views.Box({
        model: box
      });
    },

    getStoredImageStyle: function (i) {
      var me = this;
      var nodes = me.nodes;
      return !_.isUndefined(nodes[i]) && !_.isUndefined(nodes[i].image_style) ? nodes[i].image_style : '';
    },

    populateImageStyle: function (i, el) {
      var $el = $(el);
      var $box = $el.closest('.box');
      var v = $box.data('imageStyle');
      var $selected = '<option value="' + v + '">' + v + '</option>';

      if (v && v !== '' && !$el.children().length) {
        $el.html($selected);
      }
    },

    onChangeImageStyle: function (e) {
      var me = this;
      var $el = $(e.currentTarget);
      var $box = $el.closest('.box');
      var $gs = $(e.delegateTarget);
      var i = $box.index();
      var box = me.collection.at(i);
      var stored = me.getStoredImageStyle(i);
      var v = $el.val() || stored || $box.data('imageStyle');

      $el.val(v).attr('data-imageid', v);
      $el.find('option:selected').prop('selected', true).siblings('option').prop('selected', false);

      // Pass it to Backbone model.
      box.set('image_style', v);

      if (me.rendered) {
        me.collection.trigger('change');

        me.options.updateStorage = $gs.data('storage');
        me.saveData();
      }
    },

    onClickImageStyle: function (e) {
      var $el = $(e.currentTarget);
      var $box = $el.closest('.box');
      var v = $box.data('imageStyle');

      if ($el.children().length < 2) {
        $el.html(Drupal.gridstack.imageStyle.getOptions());

        if (v !== '') {
          $el.val(v).attr('data-imageid', v);
        }
      }
    },

    getSerializedData: function (el, useCss) {
      el = el || this.$el;

      return Drupal.gridstack.ui.getSerializedData(el, useCss);
    },

    buildNested: function (box, i) {
      var me = this;
      var nodes = me.nestedNodes[i];
      var index = i + 1;
      var nested = [];

      if (!_.isUndefined(nodes) && nodes.length > 0) {
        nested = _.clone(box.get('nested'));

        _.each(nodes, function (node, j) {
          node.index = index;
          node.indexNested = j + 1;

          nested[j] = me.getRunTimeNode(node);
        }, this);

        box.set('nested', nested, {
          silent: true
        });
      }

      return box.get('nested');
    },

    build: function () {
      var me = this;
      var data = [];
      // @todo use me.isNested when js-driven supports nested.
      var useCss = me.options.useCss;
      var hasNested = false;

      if (!me.collection.length) {
        return;
      }

      // Build the main boxes for each breakpoint: xs, sm, md, lg, xl.
      // The hustle is because we have clean optionsets, excluding runtime data.
      me.collection.each(function (box, i) {

        // Set nested data before the main box is being added.
        if (useCss) {
          data = me.buildNested(box, i);
          hasNested = data.length > 0;
        }

        // Build the main boxes.
        me.addBox(box);

        // Pass nodes data to individual box.
        if (hasNested) {
          var id = box.get('id');
          var el = $('> .box[data-id="' + id + '"] .gridstack:first', me.$el);

          if (el.length > 0) {

            // Initialize the nested gridstack element.
            el.gridstack(me.nestedOptions);

            // Add nested boxes.
            _.each(data, function (node) {
              me.addWidget(node, el);
            }, this);
          }
        }
      }, me);

      if (!me.useCss) {
        me.$el.on('change.gsimg', '.form-select--image-style', _.bind(me.onChangeImageStyle, me));
        me.$el.on('click.gsimg', '.form-select--image-style', _.bind(me.onClickImageStyle, me));
        $('.form-select--image-style', me.$el).each(_.bind(me.populateImageStyle, me));
      }
    },

    render: function () {
      var me = this;

      // Initialize GridStack instance.
      me.init();
      me.build();

      me.rendered = true;

      me._onWidgetChange();

      return me;
    },

    isValidBox: function (box) {
      return box instanceof Drupal.gridstack.models.Box;
    },

    saveData: function (isAll) {
      var me = this;

      if (!_.isEmpty(me.saveDataCallback) && me.rendered) {
        var o = me.options || {};

        if (!o.updateStorage && isAll) {
          o.updateStorage = me.options.storage;
        }

        me.saveDataCallback.callback(me.collection, o);
      }

      me.options.updateIcon = false;
      me.options.updateStorage = false;
    }
  });

  /**
   * GridStack UI public methods.
   *
   * @namespace
   */
  Drupal.gridstack.ui = {
    config: {},
    opts: {},
    baseOptions: {
      itemClass: 'gridstack__box',
      handle: '.box__content',
      alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
      resizable: {
        handles: 'e, se, s, sw, w'
      },
      placeholderClass: 'gridstack__box--placeholder box'
    },
    nestedOptions: {
      isNested: true,
      minWidth: 1,
      verticalMargin: 15,
      width: 12
    },
    nodesData: [],
    nestedData: [],
    domData: [],
    defaultGrids: [GRIDSTACK_DEFAULT_GRIDS],
    buildId: function (o) {
      var attributes = {};

      o = o || {};

      if (_.isUndefined(o.index)) {
        return attributes;
      }

      var index = o.index;
      attributes.id = 'root' + index;

      if (!_.isUndefined(o.indexNested)) {
        var indexNested = o.indexNested;

        attributes.pindex = index;
        attributes.pid = attributes.id;
        attributes.id = 'nested' + index + '_' + indexNested;
        attributes.index = indexNested;
      }
      else {
        attributes.index = index;
      }

      attributes.ordinal = _.isUndefined(o.ordinal) ? attributes.index : o.ordinal;

      return attributes;
    },

    getCurrentNode: function (node) {
      var box = {};

      if (_.isUndefined(node)) {
        return box;
      }

      if (_.isUndefined(node.x)) {
        node = node.data('_gridstack_node');
      }

      box = {
        x: parseInt(node.x),
        y: parseInt(node.y),
        width: parseInt(node.width),
        height: parseInt(node.height)
      };

      if (!_.isUndefined(node.image_style)) {
        box.image_style = node.image_style;
      }

      return box;
    },

    getSerializedData: function (el, useCss) {
      var me = this;
      var dataStorage = el.data('storage');
      var dataGrids = el.data('previewGrids');

      if (useCss) {
        dataStorage = el.data('nestedStorage');
        dataGrids = el.data('nestedGrids');
      }

      var $storage = $('[data-drupal-selector="' + dataStorage + '"]');
      var val = $storage.length ? $storage.val() : '';
      var dataStored = (val === '' || val === '[]') ? '' : val;

      if (dataStored) {
        dataStored = JSON.parse(dataStored);
      }

      var data = dataStored || dataGrids || [];

      if (!data.length) {
        data = me.defaultGrids;
      }

      if (useCss) {
        data = dataStored || dataGrids || [];
      }

      return data;
    },

    loadCollection: function (el) {
      var me = this;
      var $el = $(el);
      var collections = [];
      var nodes = window.GridStackUI.Utils.sort(me.getSerializedData($el));

      if (!nodes.length) {
        return collections;
      }

      var gridStack = Drupal.gridstack.collections.Boxes.extend({
        model: Drupal.gridstack.models.Box
      });

      collections = _.map(nodes, function (node, i) {
        node.index = (i + 1);

        return new Drupal.gridstack.models.Box(node);
      }, this);

      return new gridStack(collections);
    },

    loadGridStack: function (o) {
      var me = this;

      o = o || {};
      o.saveDataCallback = o.saveDataCallback || {
        callback: me.saveData.bind(me)
      };

      return new Drupal.gridstack.views.GridStack(o);
    },

    saveData: function (collection, options) {
      var me = this;
      var breakpoint = options.breakpoint || null;
      var icon = options.icon || null;
      var mergedData = [];
      var nodesData = [];
      var nestedData = [];
      var storage = options.storage || null;
      var updateIcon = options.updateIcon || false;

      collection.each(function (box, i) {
        if (box instanceof Drupal.gridstack.models.Box) {
          var nodes = box.getDataToSave();
          var nested = box.get('nested');
          var nestedNodes = box.getDataNestedToSave();

          nodesData.push(nodes);

          nestedData[i] = nested.length ? nestedNodes : [];

          // Flatten main and nested boxes with complete data for icon.
          if (updateIcon) {
            var boxAttributes = box.attributes;
            mergedData.push(_.omit(boxAttributes, 'nested'));

            if (nested.length) {
              _.omit(nested, 'nested');
              mergedData = mergedData.concat(nested);
            }
          }
        }
      });

      me.nodesData = nodesData;
      me.nestedData = nestedData;
      me.mergedData = mergedData;

      // Collect data needed to generate icon.
      if (updateIcon && icon === breakpoint) {
        me.domData = me.saveIcon(options);
      }

      if (!options.updateStorage) {
        return;
      }

      // Only needed to save during runtime/ update, not at the end of saving.
      if (!updateIcon && options.updateStorage === storage) {
        // Ensures empty value is respected to add and remove existing grids.
        var storedValue = me.nodesData.length ? JSON.stringify(me.nodesData) : '';
        $('[data-drupal-selector="' + storage + '"]').val(storedValue);

        var nestedValue = me.nestedData.length ? JSON.stringify(me.nestedData) : '';
        $('[data-drupal-selector="' + options.nestedStorage + '"]').val(nestedValue);
      }
    },

    saveIcon: function (options) {
      var me = this;
      var $main = $('#gridstack-' + options.icon);
      var grids = {};
      var margin = me.opts.verticalMargin > 2 ? me.opts.verticalMargin : 15;
      var halfMargin = margin / 2;
      var spacing = me.opts.verticalMargin > 5 ? 0 : 5;
      var noMargin = options.noMargin;

      var domData = _.map(me.mergedData, function (node) {
        if (_.isUndefined(node.id)) {
          return grids;
        }

        var $box = $('.box[data-id="' + node.id + '"]', $main);

        if (_.isUndefined($box) || _.isUndefined($box[0])) {
          return grids;
        }

        var rect = $box[0].firstElementChild.getBoundingClientRect();
        var currentIndex = $box.index() + 1;
        var left = parseInt($box.css('left')) + halfMargin;
        var top = parseInt($box.css('top'));
        var $nested = $('.gridstack--nested:first', $box);
        var $nestedBox = $('> .box', $nested);
        var nested = false;
        var title = $nestedBox.length ? '' : currentIndex;
        var color = '#18bc9c';
        var width = rect.width;

        if ($nestedBox.length) {
          color = 'transparent';
          width += halfMargin;
        }

        if ($box.parent('.gridstack--nested').length) {
          var $gsNested = $box.parent('.gridstack--nested');
          var $parentBox = $gsNested.closest('.box');
          var parentBoxIndex = $parentBox.index() + 1;

          currentIndex = $box.index() + 1;

          left += parseInt($parentBox.css('left'));
          top += parseInt($parentBox.css('top'));
          nested = true;

          if (!_.isUndefined(parentBoxIndex) && !_.isUndefined(currentIndex)) {
            title = parentBoxIndex + ':' + currentIndex;
          }
          else {
            title = '';
          }

          color = 'rgba(24, 288, 156, .4)';

          if ($nestedBox.length) {
            color = '#18bc9c';
          }
        }
        else {
          // Check is using CSS framework, and so reduce with by its margins.
          // Also if nomargin is enabled, icon still needs some margins.
          if (options.useCss || noMargin) {
            width -= margin;
          }
        }

        if (me.opts.verticalMargin === 0 && $box.data('gsWidth') !== 12) {
          width -= halfMargin;
        }

        grids = {
          left: left,
          top: top,
          width: width,
          height: rect.height - spacing,
          margin: margin,
          id: !_.isUndefined(node) ? node._id : 0,
          index: currentIndex,
          nested: nested,
          title: title,
          color: color
        };

        return grids;

      }, this);

      return domData;
    }
  };

  /**
   * GridStack icon public methods.
   *
   * @namespace
   */
  Drupal.gridstack.icon = {

    build: function (form, rebuild) {
      var me = this;
      var $form = $(form);
      var iconBreakpoint = $form.data('icon') || 'lg';
      var $main = $('#gridstack-' + iconBreakpoint, form);

      if (!$main.length) {
        return;
      }

      // Sets canvas dimensions.
      var canvas = $('#gridstack-canvas', form)[0];
      var cw = parseInt($main.css('width'));
      var ch = parseInt($main.css('height'));
      var url = '';

      canvas.width = cw;
      canvas.height = ch;
      canvas.style.width = cw + 'px';
      canvas.style.height = ch + 'px';

      // Rebuild icon if required.
      if (rebuild) {
        url = me.draw(canvas);
      }

      return url;
    },

    draw: function (canvas) {
      var me = this;
      var url;
      var data = Drupal.gridstack.ui.domData || {};

      if (!data.length) {
        return;
      }

      _.each(data, function (node) {
        var ctx = canvas.getContext('2d');
        var x = node.left;
        var y = node.top;
        var id = node.title;

        ctx.beginPath();

        ctx.fillStyle = node.color;

        // Uniform colors.
        if (node.color !== 'transparent') {
          ctx.fillStyle = '#18bc9c';
        }

        ctx.fillRect(x, y, node.width, node.height);
        ctx.restore();

        // Text.
        me.drawBoxById(ctx, id, node);
      });

      // https://developer.mozilla.org/en-US/docs/Web/API/HTMLCanvasElement.
      url = canvas.toDataURL('image/png');
      $('#gridstack-icon').val(url);

      // Update icon preview.
      $('#gridstack-screenshot').empty().css('background-image', 'url(' + url + ')');

      return url;
    },

    drawBoxById: function (ctx, id, node) {
      var h = node.height;
      var x = node.left + 20;
      var y = h > 120 ? (node.top + 70) : (node.top + 45);

      ctx.beginPath();
      ctx.font = '92px sans-serif';
      ctx.textBaseline = 'middle';
      ctx.textAlign = 'left';
      ctx.fillStyle = 'white';
      ctx.fillText(id, x, y);
    }
  };

  /**
   * GridStack image style public methods.
   *
   * @namespace
   */
  Drupal.gridstack.imageStyle = {

    getOptions: function () {
      var img = $('#gridstack-box-template .form-item--image-style').html();
      var clone = $(img).clone();

      return clone[0].innerHTML;
    }

  };

  /**
   * Theme function for a GridStack box.
   *
   * @param {Object} settings
   *   An object with the following keys: nested, type.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.gridStackBox = function (settings) {
    var type = settings.type || 'content';
    var tpl = '';
    tpl += '<div class="gridstack__box box' + (type === 'content' && settings.nested ? ' box--nested' : '') + '">';
    tpl += Drupal.theme('gridStackContent', settings);
    tpl += '</div>';

    return tpl;
  };

  /**
   * Theme function for a GridStack box content.
   *
   * @param {Object} settings
   *   An object with the following keys: nested, type.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.gridStackContent = function (settings) {
    var type = settings.type || 'content';
    var tpl = '';

    tpl += '<div class="box__content">';
    tpl += '<div class="btn-group btn-group--dummy">';
    tpl += '<button class="button btn btn--box btn--' + type + ' btn--remove" data-message="remove" data-type="' + type + '">&times;</button>';

    if (type === 'root') {
      if (settings.useCss) {
        tpl += '<button class="button btn btn--box btn--' + type + ' btn--add" data-message="add" data-type="' + type + '">+</button>';
      }
      else {
        tpl += '<select class="form-select form-select--image-style" data-imageid="" id="" />';
      }
    }

    tpl += '</div>';

    if (settings.nested) {
      tpl += '<div class="gridstack ungridstack gridstack--ui gridstack--js gridstack--nested gridstack--enabled"></div>';
    }

    tpl += '</div>';

    return tpl;
  };

})(jQuery, Drupal, drupalSettings, Backbone, _, this);
