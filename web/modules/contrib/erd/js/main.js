(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Contains behaviors for the Entity Relationship diagram module.
   */
  Drupal.behaviors.erd = {
    attach: function (context, settings) {
      $('.erd-container', context).once('erd-init').each(erdInit);
    }
  };

  /**
   * Initializes an Entity Relationship diagram.
   */
  function erdInit () {
    var data = createGraph(this);

    var $actions = $(this).siblings('.erd-actions');
    initAutocomplete($actions.find('.erd-search input'), data.graph, data.paper);
    $actions.on('click', '.erd-label', data, addLabelClick);
    $actions.on('click', '.erd-save', data, saveClick);
    $actions.on('click', '.erd-machine-name', data, machineNameClick);
    $actions.on('click', '.erd-line-style', data, lineStyleClick);
    $actions.on('click', '.erd-zoom', data, zoomInClick);
    $actions.on('click', '.erd-unzoom', data, zoomOutClick);

    $(this).on('click', '.erd-label-cell .label', data, labelClick);
    $(this).on('click', '.remove-entity', data, removeClick);

    var originalDimensions = { width: $(this).width(), height: $(this).height() };
    var actions_padding = $actions.innerWidth() - $actions.width();
    data.paper.setDimensions(originalDimensions.width, originalDimensions.height);
    $actions.css('width', originalDimensions.width - actions_padding);
    data.paper.originOffsetX = 0;
    data.paper.originOffsetY = 0;

    $(this).resizable({
      resize: function (event, ui) {
        $actions.css('width', ui.size.width - actions_padding);
        data.paper.setDimensions(ui.size.width, ui.size.height);
        data.paper.originOffsetX = (ui.size.width - originalDimensions.width) / 2;
        data.paper.originOffsetY = (ui.size.height - originalDimensions.height) / 2;
      }
    });
  }

  /**
   * Defines a custom autocomplete to select entity bundles and types.
   */
  $.widget('custom.erdautocomplete', $.ui.autocomplete, {
    _create: function () {
      this._super();
      this.widget().menu('option', 'items', '> :not(.erd-autocomplete-category)');
    },
    _renderMenu: function (ul, items) {
      var that = this,
        current_category = '';
      items.sort(function (a, b) {
        if (b.type === a.type) {
          if (a.originalData.label.localeCompare(b.originalData.label) === 0 && a.entity_type_label && b.entity_type_label) {
            a.label = a.originalData.label + ' (' + a.entity_type_label + ')';
            b.label = a.originalData.label + ' (' + b.entity_type_label + ')';
          }
          return a.label.localeCompare(b.label);
        }
        else if (a.type === 'bundle') {
          return 1;
        }
        return -1;
      });
      $.each(items, function (index, item) {
        var li;
        if (item.type_label !== current_category) {
          ul.append("<li class='erd-autocomplete-category'>" + item.type_label + 's</li>');
          current_category = item.type_label;
        }
        li = that._renderItemData(ul, item);
        if (item.type_label) {
          li.attr('aria-label', item.type_label + 's : ' + item.label );
        }
      });
    }
  });

  /**
   * Saves the SVG on screen to a file.
   */
  function saveClick (event) {
    var $container = $(this).parent().siblings('.erd-container');
    var svg_clone = event.data.paper.svg.cloneNode(true);
    $(svg_clone).find('.port, .remove-entity, .link-tools, .marker-vertices, .marker-arrowheads, .connection-wrap').remove();
    $(svg_clone).attr('width', $container.outerWidth());
    $(svg_clone).attr('height', $container.outerHeight());

    var serializer = new XMLSerializer();
    var svg = serializer.serializeToString(svg_clone);

    var data = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svg)));

    var image = new Image();
    image.src = data;
    image.onload = function () {
      var canvas = document.createElement('canvas');
      canvas.width = image.width;
      canvas.height = image.height;
      var context = canvas.getContext('2d');
      context.drawImage(image, 0, 0);

      var png = canvas.toDataURL('image/png');

      var a = document.createElement('a');
      a.href = png;
      a.download = 'erd.png';
      a.click();
    };
  }

  /**
   * Allows custom label text to be edited.
   */
  function labelClick (event) {
    var model_id = $(this).closest('[model-id]').attr('model-id');
    var model = event.data.graph.get('cells').get(model_id);
    var text = prompt(Drupal.t('Please enter new label text'), model.attr('.label/text'));
    if (text && text.length > 0) {
      model.attr('.label/text', Drupal.checkPlain(text));
      reAdjustWidths(event.data.graph);
    }
  }

  /**
   * Toggles friendly labels and machine names.
   */
  function machineNameClick (event) {
    $(this).toggleClass('active');
    var display = $(this).hasClass('active') ? 'id' : 'label';
    event.data.graph.labelDisplay = display;

    event.data.graph.get('cells').each(function (cell) {
      if (cell.has('erd.bundle')) {
        var bundle = cell.get('erd.bundle');
        cell.attr('.label/text', Drupal.checkPlain(bundle[display]));

        // Change the display of fields based on our current active state.
        if (bundle.fields) {
          var field, text_class;
          for (var field_name in bundle.fields) {
            field = bundle.fields[field_name];
            text_class = 'attribute-' + field_name;

            cell.attr('.' + text_class + '/text', Drupal.checkPlain(field[display]));
          }
        }
      }
      else if (cell.has('erd.type')) {
        var type = cell.get('erd.type');
        cell.attr('.label/text', Drupal.checkPlain(type[display]));
      }
    });

    reAdjustWidths(event.data.graph);
  }

  /**
   * Cycles through various line styles.
   */
  function lineStyleClick (event) {
    var line_styles = [null, 'orthogonal', 'manhattan', 'metro'];
    var index = line_styles.indexOf(event.data.graph.lineStyle);

    if (index < line_styles.length - 1) {
      ++index;
    }
    else {
      index = 0;
    }

    var style = line_styles[index];
    event.data.graph.lineStyle = style;

    // Change the line style for the default paper link.
    if (style) {
      event.data.paper.options.defaultLink.set('router', { name: style });
    }
    else {
      event.data.paper.options.defaultLink.unset('router');
    }

    // Change line styles for all on-screen links.
    event.data.graph.get('cells').each(function (cell) {
      if (cell.get('type') == 'erd.Line' || cell.get('type') == 'link') {
        if (style) {
          cell.set('router', { name: style });
        }
        else {
          cell.unset('router');
        }

        // Hotfix for badly rendered metro/manhattan links.
        cell.attr('.connection/fill', 'none');
      }
    });
  }

  /**
   * Re-adjusts the widths of all cell graphs based on their text content.
   */
  function reAdjustWidths (graph) {
    graph.get('cells').each(function (cell) {
      if (cell.get('type') == 'erd.Entity') {
        // Calculate our max string length.
        var title_length = 0;
        var attribute_length = 0;
        var attrs = cell.get('attrs');
        for (var i in attrs) {
          if (attrs[i].text) {
            var length = attrs[i].text.length;
            if (i.indexOf('.attribute') === 0 && length > attribute_length) {
              attribute_length = length;
            }
            else if (i == '.label' && length > title_length)  {
              title_length = length;
            }
          }
        }

        // Determine our necessary width based on known font sizes.
        title_length = title_length * 9;
        attribute_length = attribute_length * 7.2;
        var width = title_length > attribute_length ? title_length : attribute_length;
        if (width < 150) {
          width = 150;
        }

        // Resize our rectangles and re-position the right magnet link.
        var sizes = cell.get('size');
        sizes.width = width;
        cell.set('size', sizes);
        cell.attr('.outer/points', '0,0 ' + width + ',0 ' + width + ',60 0,60');
        cell.attr('.attribute-background/points', '0,0 ' + width + ',0 ' + width + ',25 0,25');
        cell.attr('.port-right/ref-x', width - 7);
      }
    });
  }

  /**
   * Initializes the auto complete functionality.
   */
  function initAutocomplete (input_element, graph, paper) {
    var source = [];
    $.each(drupalSettings.erd.entities, function (index, item) {
      item.originalData = item;
      source.push(item);
    });
    input_element.erdautocomplete({
      source: source,
      select: function (event, ui) {
        if (ui.item.type === 'type') {
          addType(ui.item.originalData, graph, paper);
        }
        else {
          addBundle(ui.item.originalData, graph, paper);
        }

        reAdjustWidths(graph);

        $(this).val('');
        return false;
      }
    });
  }

  /**
   * Creates a new graph for a given element.
   */
  function createGraph (element) {
    var graph = new joint.dia.Graph();
    graph.lineStyle = null;
    graph.labelDisplay = 'label';

    var $paper_el = $(element);
    var paper;

    paper = new joint.dia.Paper({
      el: $paper_el,
      width: $paper_el.width(),
      height: $paper_el.height(),
      gridSize: 1,
      model: graph,
      linkPinning: false,
      linkConnectionPoint: joint.util.shapePerimeterConnectionPoint,
      defaultLink: new joint.dia.Link({
        attrs: {
          '.marker-target': { fill: '#000000', stroke: '#000000', d: 'M 10 0 L 0 5 L 10 10 z' },
          '.connection': { fill: 'none' }
        }
      })
    });

    var panAndZoom = svgPanZoom($paper_el[0].childNodes[0],
      {
        viewportSelector: $paper_el[0].childNodes[0].childNodes[0],
        fit: false,
        zoomScaleSensitivity: 0.1,
        mouseWheelZoomEnabled: false,
        panEnabled: false
      });

    paper.on('blank:pointerdown', function (evt, x, y) {
      panAndZoom.enablePan();
    });

    paper.on('cell:pointerup blank:pointerup', function (cellView, event) {
      panAndZoom.disablePan();
    });

    var $prompt = $('<div class="erd-start-prompt">' + Drupal.t('Use the search box above to add entities to the diagram.') + '</div>');
    $paper_el.append($prompt);
    graph.on('add remove', function () {
      $prompt.toggle(this.get('cells').length === 0);
    });

    return { graph: graph, paper: paper, panAndZoom: panAndZoom };
  }

  /**
   * Gets a default Joint JS entity, used by all cells.
   */
  function getDefaultJointEntity () {
    return new joint.shapes.erd.Entity({
      markup:
      '<g class="rotatable">' +
        '<polygon class="outer"/>' +
        '<text class="label"/>' +
        '<circle class="port port-left"/>' +
        '<circle class="port port-right"/>' +
      '</g>' +
      '<a class="remove-entity">' +
        '<svg fill="#F7F7F7" height="20" width="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"> <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/> <path d="M0 0h24v24H0z" fill="none"/></svg>' +
      '</a>',
      inPorts: ['foo','bar'],
      attrs: {
        text: {
          text: '',
          fill: '#ffffff',
          'letter-spacing': 0,
        },
        '.connection': {
          fill: 'none'
        },
        '.label': {
          'font-size': 16,
          'text-anchor': 'middle',
          ref: '.outer', 'ref-y': .4, 'ref-x': .5
        },
        '.outer': {
          fill: '#0097a7', stroke: '#0097a7', 'stroke-width': 1
        },
        '.port': {
          magnet: 'active',
          r: 7,
          fill: '#F7F7F7',
          'stroke-width': 1,
          stroke: '#CCCCCC',
          ref: '.outer', 'ref-x': 7, 'ref-y': .5
        },
        '.port-right': {
          'ref-y': .5
        },
        '.attribute': {
          text: '',
          'text-anchor': 'start',
          'font-size': 14,
          ref: '.outer', 'ref-x': .5, 'ref-y': 66,
          'x-alignment': 'middle', 'y-alignment': 'middle'
        },
        '.attribute-background': {
          fill: '#00acc1', stroke: '#0097a7',
          'ref-x': 0, 'ref-y': 60
        }
      }
    });
  }

  /**
   * Adds a custom label cell to the graph.
   */
  function addLabelClick (event) {
    var cell = getDefaultJointEntity().clone()
      .attr('.outer, .attribute-background/fill', '#f57f17')
      .attr('.outer, .attribute-background/stroke', '#f57f17')
      .attr('.label/text', 'Change me');

    var markup = cell.get('markup');
    markup = '<g class="erd-label-cell">' + markup + '</g>';
    cell.set('markup', markup);

    event.data.graph.addCell(cell);

    reAdjustWidths(event.data.graph);

    cell.set('position', { x: event.data.paper.originOffsetX - (cell.get('size').width / 2), y: event.data.paper.originOffsetY })
  }

  /**
   * Adds an entity type to the graph.
   */
  function addType (type, graph, paper) {
    var display = graph.labelDisplay;
    var cell = getDefaultJointEntity().clone()
      .attr('position', { x: paper.options.width / 2, y: paper.options.height / 2})
      .attr('.outer, .attribute-background/fill', '#0288d1')
      .attr('.outer, .attribute-background/stroke', '#0288d1')
      .attr('.label/text', Drupal.checkPlain(type[display]));

    cell.set({ identifier: type.identifier }, { silent: true });

    graph.addCell(cell);

    cell.set('erd.type', type);

    reAdjustWidths(graph);
    refreshLinks(graph);

    cell.set('position', { x: paper.originOffsetX - (cell.get('size').width / 2), y: paper.originOffsetY })
  }

  /**
   * Adds an entity bundle to the graph.
   */
  function addBundle (bundle, graph, paper) {
    var display = graph.labelDisplay;
    var cell = getDefaultJointEntity().clone()
    .attr('position', { x: paper.options.width / 2, y: paper.options.height / 2})
    .attr('.label/text', Drupal.checkPlain(bundle[display]));

    cell.set({ identifier: bundle.identifier }, { silent: true });
    var markup = cell.get('markup');

    // Add elements to our markup.
    if (bundle.fields) {
      var field, text_class, background_class, background_y, text_y;
      var i = 0;
      for (var field_name in bundle.fields) {
        field = bundle.fields[field_name];
        text_class = 'attribute-' + Drupal.checkPlain(field_name);
        background_class = 'attribute-background-' + Drupal.checkPlain(field_name);
        background_y = cell.attr('.attribute-background/ref-y') + (i * 25);
        text_y = cell.attr('.attribute/ref-y') + (i * 25);

        markup += '<polygon class="attribute-background ' + background_class + '"/>';
        markup += '<text class="attribute ' + text_class + '"/>';

        cell.attr('.' + text_class + '/text', Drupal.checkPlain(field[display]));
        cell.attr('.' + text_class + '/ref-y', text_y);
        cell.attr('.' + text_class + '/ref-x', 5);
        cell.attr('.' + background_class + '/ref-y', background_y);

        ++i;
      }

      cell.set({ markup: markup });
    }

    graph.addCell(cell);

    cell.set('erd.bundle', bundle);

    reAdjustWidths(graph);
    refreshLinks(graph);

    cell.set('position', { x: paper.originOffsetX - (cell.get('size').width / 2), y: paper.originOffsetY })
  }

  /**
   * Creates a link between two cells.
   */
  function createLink (source, target, label, graph) {
    var identifier = label + ':' + source.id + '=>' + target.id;

    // Avoid duplicating links.
    if (graph.get('cells').findWhere({ identifier: identifier })) {
      return;
    }

    var settings = {
      source: source,
      target: target,
      attrs: {
        '.marker-target': { fill: '#000000', stroke: '#000000', d: 'M 10 0 L 0 5 L 10 10 z' },
        '.connection': { fill: 'none' }
      }
    };

    if (graph.lineStyle) {
      settings.router = { name: graph.lineStyle };
    }

    var link = new joint.shapes.erd.Line(settings);
    link.set('identifier', identifier);

    link.addTo(graph).set('labels', [{
      position: 0.5,
      attrs: {
        text: {
          text: label, fill: '#f6f6f6',
          'font-family': 'sans-serif', 'font-size': 12
        },
        rect: { stroke: '#43a047', 'stroke-width': 20, rx: 1, ry: 1 } }
    }]);
  }

  /**
   * Refreshes on screen links - useful when new cells are added.
   */
  function refreshLinks (graph) {
    for (var i in drupalSettings.erd.links) {
      var link = drupalSettings.erd.links[i];
      var from = graph.get('cells').findWhere({ identifier: link.from });
      // This may not be on-screen.
      if (from) {
        for (var j in link.targets) {
          var to = graph.get('cells').findWhere({ identifier: link.targets[j] });
          if (to && from !== to) {
            createLink({ id: from.id, selector: link.from_selector }, { id: to.id }, link.label, graph);
          }
        }
      }
    }
  }

  /**
   * Zooms the graph in.
   */
  function zoomInClick (event) {
    event.data.panAndZoom.zoomIn();
  }

  /**
   * Zooms the graph out.
   */
  function zoomOutClick (event) {
    event.data.panAndZoom.zoomOut();
  }

  /**
   * Removes the target cell from the graph.
   */
  function removeClick (event) {
    var model_id = $(this).closest('[model-id]').attr('model-id');
    event.data.graph.get('cells').get(model_id).remove();
  }

}(jQuery, Drupal, drupalSettings));
