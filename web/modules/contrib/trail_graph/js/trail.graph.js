(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

/**
 * @file
 * Trail Graph.
 *
 * Displays nodes within trails.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Resubmit Trail / Node filter form (exposed filter)
   * This function is called from _modal_edit_form_ajax_submit via InvokeCommand.
   */
  $.fn.resubmitTrailGraphFilterForm = function () {
    var formId = drupalSettings.exposed_form_id;
    var $form = $('form#' + formId);

    // In case we have form submit button trigger click event (ajax submit will happen)
    if ($form.find('.button.js-form-submit')) {
      $form.find('.button.js-form-submit').trigger('click');
      return;
    }

    // In case we dont have form submit button, fallback to full page reload.
    $form.submit();
  };

  /**
   * Trail graph Behaviour
   * TODO: Write some documentation about this behaviour.
   */
  Drupal.behaviors.trailGraph = {
    styles: {
      node: {
        color: {
          background: {
            initial: '#d4edfc',
            selected: '#9BDFFC',
            highlighted: 'rgba(212, 237, 252, 0.3)'
          },
          border: {
            initial: '#009fe3',
            highlighted: 'rgba(0, 159, 227, 0.1)'
          }
        },
        borderWidth: {
          initial: 1,
          selected: 2
        },
        font: {
          color: {
            initial: '#343434',
            highlighted: 'rgba(0, 0, 0, 0.1)'
          }
        },
        boxMaxWidth: 150
      },
      header: {
        color: {
          background: '#ffffff'
        },
        borderWidth: 3,
        margin: 10
      },
      link: {
        width: 3,
        color: {
          opacity: {
            initial: 1,
            highlighted: 0
          }
        }
      },
      modal: {
        width: '80%'
      }
    },
    colors: ["#00ffff", "#000000", "#0000ff", "#a52a2a", "#00ffff", "#00008b", "#008b8b", "#006400", "#bdb76b", "#8b008b", "#556b2f", "#ff8c00", "#9932cc", "#8b0000", "#e9967a", "#9400d3", "#ff00ff", "#ffd700", "#008000", "#4b0082", "#f0e68c", "#add8e6", "#90ee90", "#00ff00", "#ff00ff", "#800000", "#000080", "#808000", "#ffa500", "#ffc0cb", "#800080", "#800080", "#ff0000", "#c0c0c0", "#ffff00"],
    attach: function attach(context, settings) {
      $('.trail-graph__content', context).once().each(function () {
        var keys = Object.keys(settings.trail_graph.data);
        var trailGraphSettings = settings.trail_graph.data[keys[keys.length - 1]];
        var trails = trailGraphSettings.trails,
            nodes = trailGraphSettings.nodes,
            filterInputs = trailGraphSettings.filterInputs;

        Drupal.behaviors.trailGraph.prepareData(this, nodes, trails, filterInputs);
      });
    },

    /**
     * Prepare data for Network.
     *
     * @param context
     * @param nodes
     * @param trails
     * @param filterInputs
     */
    prepareData: function prepareData(context, nodes, trails, filterInputs) {
      var _this = this;

      var dataSets = {
        nodes: new vis.DataSet(),
        links: new vis.DataSet()
      };
      var positionStep = { x: 200, y: 100 };
      var trailHeadersQueue = [];
      var nodesQueue = [];
      var focusToFirstTrail = trails.length > 1;

      // Create data sets for nodes.
      if (nodes.length) {
        nodes.forEach(function (node) {
          return dataSets.nodes.add(_this.prepareNodeData(node));
        });
      }

      // Create data sets for links.
      if (trails.length) {
        trails.forEach(function (trail, trailIndex) {
          if (trail.links.length) {

            // Assign trail color.
            var trailColor = trail.color == null ? _this.generateTrailColor(trail.tid) : trail.color;

            // Update Trail Header Border to be in same color as trail.
            var trailHeader = dataSets.nodes.get(trail.id);
            trailHeader.color.border = trailColor;
            trailHeader.originalOptions.color.border = trailColor;
            trailHeadersQueue.push(trailHeader);

            var position = {
              x: positionStep.x * trailIndex,
              y: 0
            };

            // Create links between nodes.
            trail.links.forEach(function (link, linkIndex) {
              var linkOptions = {
                trail: trail,
                link: link,
                trailColor: trailColor,
                chosen: linkIndex === 0 ? { edge: false } : true
              };

              dataSets.links.add(_this.prepareLinkData(linkOptions));

              // Set x/y position for trail which is directly connected to nodes.
              position.y += positionStep.y;

              // Set position for first node of edge(link)
              nodesQueue.push({
                id: link.from,
                x: position.x,
                y: position.y
              });

              // Update second node in a edge(link)
              if (linkIndex === trail.links.length - 1 // Last node in a trail.
              || trail.links.length === 1 // Case for single sink in a trail.
              ) {
                  nodesQueue.push({
                    id: link.to,
                    x: position.x,
                    y: position.y + positionStep.y
                  });
                }
            });
          }

          // Update dataSets & Re-Draw canvas.
          dataSets.nodes.update(trailHeadersQueue);
          dataSets.nodes.update(nodesQueue);
        });
      }

      this.enableContextualMenu();
      this.prepareNetwork(context, dataSets);

      // Sync data (store data globally in drupal setting - mostly for debugging purposes)
      drupalSettings.trail_graph.dataSets = dataSets;
      drupalSettings.trail_graph.trails = trails;
      drupalSettings.trail_graph.focusToFirstTrail = focusToFirstTrail;
      drupalSettings.trail_graph.filterInputs = filterInputs;
    },


    /**
     * Prepare Network.
     *
     * @param context
     * @param dataSets
     */
    prepareNetwork: function prepareNetwork(context, dataSets) {
      var styles = this.styles;
      var data = {
        nodes: dataSets.nodes,
        edges: dataSets.links
      };
      var options = {
        autoResize: true,
        width: '100%',
        height: '700px',
        physics: false,
        layout: {
          hierarchical: false
        },
        nodes: {
          shape: 'box',
          widthConstraint: {
            maximum: styles.node.boxMaxWidth
          },
          font: {
            multi: true
          },
          color: {
            border: styles.node.color.border.initial,
            background: styles.node.color.background.initial,
            highlight: {
              border: styles.node.color.border.highlighted,
              background: styles.node.color.background.selected
            }
          },
          fixed: false
        },
        edges: {
          width: styles.link.width,
          smooth: false
        }
      };

      this.displayNetwork(context, data, options);
    },


    /**
     * Display Network.
     *
     * @param context
     * @param data
     * @param options
     */
    displayNetwork: function displayNetwork(context, data, options) {
      var _this2 = this;

      var network = new vis.Network($('#trail-graph-canvas', context)[0], data, options);

      // Storing network data globally.
      drupalSettings.trail_graph.network = network;
      drupalSettings.trail_graph.networkIsDrawn = false;

      // Network events
      // TODO: Link creation method.
      network.on("oncontext", function (params) {
        var selectedNodeId = network.getNodeAt(params.pointer.DOM);
        var dataSets = drupalSettings.trail_graph.dataSets;
        var $menuContent = $('<ul>');

        if (selectedNodeId) {
          var node = dataSets.nodes.get(selectedNodeId);

          if (node.isHeader) {
            // Trail header.
            $menuContent.append($('<li>').append(_this2.createFocusOnThisTrailLink(Drupal.t('Focus on this trail'), selectedNodeId)), $('<hr>'), $('<li>').append(_this2.createShowTrailLink(Drupal.t('Edit trail order'), '/trail_graph/node_order/' + node.tid, context)));
          } else {
            // Node.
            $menuContent.append($('<li>').append(node.contentPreview), $('<hr>'), $('<li>').append(_this2.createModalLink(Drupal.t('Edit node'), '/node/' + selectedNodeId + '/edit?mim=trail_graph', { width: '80%' })));
          }

          _this2.showContextualMenu({
            x: params.event.x,
            y: params.event.y
          }, context, $menuContent);
        }
      });
      network.on("selectNode", function (params) {
        var nodeId = params.nodes.length > 0 ? params.nodes[0] : null;

        if (nodeId !== null && _this2.isTrailHeader(nodeId)) {
          _this2.highlightTrail(nodeId).then(function () {
            return _this2.lockNodes();
          }).catch(function (err) {
            console.error(err);
          });
        }
      });
      network.on("deselectNode", function () {
        _this2.resetTrailHighlight().then(function () {
          return _this2.unlockNodes();
        });
      });
      network.on("dragStart", function () {
        $('#trail-graph-canvas', context).css('cursor', 'move');
      });
      network.on("dragEnd", function () {
        $('#trail-graph-canvas', context).css('cursor', 'default');
      });
      network.on("afterDrawing", function () {

        // If more than trail is displayed, set focus to first one.
        if (drupalSettings.trail_graph.focusToFirstTrail && !drupalSettings.trail_graph.networkIsDrawn) {
          // Its important to set networkIsDrawn to TRUE, otherwise there will be infinite loop!!!
          drupalSettings.trail_graph.networkIsDrawn = true;

          if (drupalSettings.trail_graph.filterInputs['trail_id']) {
            _this2.updateTrailVerticalArrangement(drupalSettings.trail_graph.filterInputs['trail_id'][0]);
          }
        }
      });
    },


    /**
     * Contextual menu functions.
     */
    enableContextualMenu: function enableContextualMenu() {
      $(document).bind("contextmenu", function (e) {
        return e.preventDefault();
      });
    },
    showContextualMenu: function showContextualMenu(position, context, content) {
      $(".trail-graph__contextual-menu", context).empty().append(content).css({
        "left": position.x,
        "top": position.y
      }).fadeIn(200, this.startFocusOut(context));

      Drupal.attachBehaviors();
    },
    startFocusOut: function startFocusOut(context) {
      $(document).on("click", function () {
        $(".trail-graph__contextual-menu", context).hide();
        $(document).off("click");
      });
    },
    createModalLink: function createModalLink(title, url, options) {
      return $('<a>').attr('href', url).text(title).addClass('use-ajax').data({
        'dialog-type': 'modal',
        'dialog-options': options
      });
    },
    createInlineModalLink: function createInlineModalLink(modalTitle, linkTitle, $modalContent) {
      var _this3 = this;

      if (typeof $modalContent === 'string') {
        $modalContent = $($modalContent);
      }

      return $('<a>').attr('href', '#').text(linkTitle).on('click', function () {
        return _this3.showInlineModal(modalTitle, $modalContent);
      });
    },
    createFocusOnThisTrailLink: function createFocusOnThisTrailLink(linkTitle, nodeId) {
      var _this4 = this;

      return $('<a>').attr('href', '#').text(linkTitle).on('click', function () {
        return _this4.updateTrailVerticalArrangement(nodeId, true);
      });
    },
    createShowTrailLink: function createShowTrailLink(linkTitle, endpoint, context) {
      var _this5 = this;

      return $('<a>').attr('href', '#').text(linkTitle).on('click', function () {
        return _this5.showTrailLink(endpoint, context);
      });
    },


    /**
     * Modal window.
     */
    showInlineModal: function showInlineModal(title, content) {
      var modal = $('<div>').html(content);
      var options = {
        title: title,
        width: this.styles.modal.width
      };

      Drupal.dialog(modal, options).showModal();
    },


    /**
     * Trail / Link functions.
     */
    showTrailLink: function showTrailLink(endpoint) {
      Drupal.ajax({
        url: endpoint
      }).execute();

      // Show Trails tab content.
      Drupal.behaviors.trailGraphSidebarTabs.switchTab('trail-graph-tab-2');
    },
    highlightTrail: function highlightTrail() {
      var _this6 = this;

      var termId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

      return new Promise(function (resolve, reject) {
        if (termId === null) {
          reject('Missing termId for highlightTrail()');
        }

        var dataSets = drupalSettings.trail_graph.dataSets;
        var links = dataSets.links.get();
        var nodes = dataSets.nodes.get();
        var nodesQueue = [];
        var linksQueue = [];

        links.forEach(function (link) {
          if (link.tid !== termId) {
            linksQueue.push({
              id: link.id,
              color: {
                opacity: _this6.styles.link.color.opacity.highlighted
              }
            });
          }
        });
        nodes.forEach(function (node) {
          var updateNeeded = false;
          var data = {
            id: node.id,
            font: { color: _this6.styles.node.font.color.highlighted }
          };

          if (node.isHeader) {
            if (node.id !== termId) {
              data.color = {
                background: _this6.hexToRGB(node.color.background, 0.2),
                border: _this6.hexToRGB(node.color.border, 0.2)
              };
              updateNeeded = true;
            }
          } else {
            var relatedTrails = node.relatedTrails.map(function (trail) {
              return 'T' + trail;
            });
            if (!relatedTrails.includes(termId)) {
              data.color = {
                background: _this6.styles.node.color.background.highlighted,
                border: _this6.styles.node.color.border.highlighted
              };
              updateNeeded = true;
            }
          }

          // Proceed with update (some cases we don't need to update)
          if (updateNeeded) {
            nodesQueue.push(data);
          }
        });

        dataSets.links.update(linksQueue);
        dataSets.nodes.update(nodesQueue);

        resolve('Trail Highlighted');
      });
    },
    resetTrailHighlight: function resetTrailHighlight() {
      var _this7 = this;

      return new Promise(function (resolve, reject) {
        var dataSets = drupalSettings.trail_graph.dataSets;
        var links = dataSets.links.get();
        var nodes = dataSets.nodes.get();
        var nodeColorStyle = _this7.styles.node.color;
        var linksQueue = [];
        var nodesQueue = [];

        links.forEach(function (link) {
          linksQueue.push({
            id: link.id,
            color: {
              color: link.originalOptions.color.color,
              opacity: _this7.styles.link.color.opacity.initial
            }
          });
        });
        nodes.forEach(function (node) {
          nodesQueue.push({
            id: node.id,
            font: { color: _this7.styles.node.font.color.initial },
            color: {
              background: node.originalOptions ? node.originalOptions.color.background : nodeColorStyle.background.initial,
              border: node.originalOptions ? node.originalOptions.color.border : nodeColorStyle.border.initial
            }
          });
        });

        dataSets.links.update(linksQueue);
        dataSets.nodes.update(nodesQueue);

        resolve('Reset Trail Highlight Successfully');
      });
    },
    updateTrailVerticalArrangement: function updateTrailVerticalArrangement() {
      var trailId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      var rememberTrailLastPosition = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

      if (trailId === null) {
        console.error('Missing trailId');
        return;
      }

      var trails = new vis.DataSet(drupalSettings.trail_graph.trails);
      var nodePosition = { x: 0, y: 0 };
      var positionStep = { x: 200, y: 100 };
      var trailIndex = this.findIndexOfObject(trails.get(), 'id', trailId);
      var dataSets = drupalSettings.trail_graph.dataSets;
      var network = drupalSettings.trail_graph.network;
      var trail = trails.get(trailId);
      var nodesQueue = [];

      if (trail.links.length) {
        nodePosition.x = positionStep.x * trailIndex;
        nodePosition.y = 0;

        // Get trail header current position and use it for nodes.
        if (rememberTrailLastPosition) {
          var trailHeaderPosition = network.getPositions([trailId])[trailId];
          nodePosition.x = trailHeaderPosition.x;
          nodePosition.y = trailHeaderPosition.y - positionStep.y;
        }

        // Create links between nodes.
        trail.links.forEach(function (link, linkIndex) {
          nodePosition.y += positionStep.y;
          nodesQueue.push({
            id: link.from,
            x: nodePosition.x,
            y: nodePosition.y
          });

          // Update second node in a link.
          if (linkIndex === trail.links.length - 1 // Last node in a trail.
          || trail.links.length === 1 // Case for single link in a trail.
          ) {
              nodesQueue.push({
                id: link.to,
                x: nodePosition.x,
                y: nodePosition.y + positionStep.y
              });
            }
        });

        // Update dataSet & Re-draw canvas.
        dataSets.nodes.update(nodesQueue);
      } else {
        console.error('No links found in trail #' + trailId);
      }
    },

    /**
     * Generate trail color based on trail id.
     *
     * @param trailId
     *
     * @returns {string}
     */
    generateTrailColor: function generateTrailColor() {
      var trailId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;

      var colorIndex = trailId % this.colors.length;

      return this.colors[colorIndex];
    },


    /**
     * Node functions.
     */
    isTrailHeader: function isTrailHeader() {
      var nodeId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

      if (nodeId === null) {
        return false;
      }

      return drupalSettings.trail_graph.dataSets.nodes.get(nodeId).isHeader;
    },
    lockNodes: function lockNodes() {
      drupalSettings.trail_graph.network.setOptions({
        interaction: {
          dragNodes: false
        }
      });
    },
    unlockNodes: function unlockNodes() {
      drupalSettings.trail_graph.network.setOptions({
        interaction: {
          dragNodes: true
        }
      });
    },


    /**
     * Prepare data for Vis.js Data Sets.
     */
    prepareNodeData: function prepareNodeData(node) {
      if (typeof node === "undefined") {
        return;
      }

      var data = {};

      // Trail Header.
      if (node.isHeader) {
        data = {
          id: node.id,
          tid: node.tid,
          label: '<b>' + node.title + '</b>',
          title: '<b>' + node.title + '</b>',
          borderWidth: this.styles.header.borderWidth,
          originalOptions: {
            color: {
              background: this.styles.header.color.background
            }
          },
          color: {
            background: this.styles.header.color.background
          },
          margin: this.styles.header.margin,
          chosen: false,
          isHeader: true
        };
      }

      // Node.
      else {
          data = {
            id: node.id,
            label: node.title.substring(0, 15) + '...',
            title: node.title,
            relatedTrails: node.trail_field,
            contentPreview: node.content_preview,
            borderWidth: this.styles.node.borderWidth.initial,
            shapeProperties: {
              borderDashes: !node.published
            }
          };

          // Update node properties for Selected node.
          if (node.selected) {
            data.label = '<b>' + node.title.substring(0, 15) + '...</b>';
            data.borderWidth = this.styles.node.borderWidth.selected;
            data.color = {
              border: this.styles.node.color.border.initial,
              background: this.styles.node.color.background.selected
            };
            data.originalOptions = {
              color: {
                border: this.styles.node.color.border.initial,
                background: this.styles.node.color.background.selected
              }
            };
          }
        }

      return data;
    },
    prepareLinkData: function prepareLinkData(options) {
      var trail = options.trail,
          link = options.link,
          trailColor = options.trailColor,
          chosen = options.chosen;


      return {
        tid: trail.id,
        from: link.from,
        to: link.to,
        originalOptions: {
          color: {
            color: trailColor
          }
        },
        color: {
          color: trailColor
        },
        dashes: link.bothNodesHaveSameWeight,
        chosen: chosen
      };
    },


    /**
     * Helper functions.
     */
    findIndexOfObject: function findIndexOfObject(object, key, value) {
      var index = 0;
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = object.entries()[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var _step$value = _slicedToArray(_step.value, 2),
              i = _step$value[0],
              obj = _step$value[1];

          if (obj[key] === value) {
            index = i;
            break;
          }
        }
      } catch (err) {
        _didIteratorError = true;
        _iteratorError = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion && _iterator.return) {
            _iterator.return();
          }
        } finally {
          if (_didIteratorError) {
            throw _iteratorError;
          }
        }
      }

      return index;
    },
    hexToRGB: function hexToRGB(hex, opacity) {
      hex = hex.replace('#', '');
      var r = parseInt(hex.substring(0, 2), 16);
      var g = parseInt(hex.substring(2, 4), 16);
      var b = parseInt(hex.substring(4, 6), 16);

      return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + opacity + ')';
    }
  };

  /**
   * Trail Graph Sidebar Tabs Behaviour.
   */
  Drupal.behaviors.trailGraphSidebarTabs = {
    attach: function attach(context, settings) {
      $('#trail-graph-sidebar', context).once().each(function () {

        // Listening on Events.
        $(this, context).on('click', '.views-trail-graph__sidebar__tabs > input', function (e) {
          Drupal.behaviors.trailGraphSidebarTabs.showTabContent($(e.currentTarget).attr('id'), context);
        });
      });
    },
    switchTab: function switchTab(id) {
      $('#' + id).click();
    },
    showTabContent: function showTabContent(id, context) {
      $('.views-trail-graph__sidebar__section', context).hide();
      $('.views-trail-graph__sidebar__section[data-tab-id=' + id + ']', context).show();
    }
  };
})(jQuery, Drupal, drupalSettings);

},{}]},{},[1])

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJqcy9zcmMvYXBwLmpzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBOzs7OztBQ0FBOzs7Ozs7O0FBT0EsQ0FBQyxVQUFVLENBQVYsRUFBYSxNQUFiLEVBQXFCLGNBQXJCLEVBQXFDOztBQUVwQzs7OztBQUlBLElBQUUsRUFBRixDQUFLLDRCQUFMLEdBQW9DLFlBQU07QUFDeEMsUUFBTSxTQUFTLGVBQWUsZUFBOUI7QUFDQSxRQUFNLFFBQVEsWUFBVSxNQUFWLENBQWQ7O0FBRUE7QUFDQSxRQUFJLE1BQU0sSUFBTixDQUFXLHdCQUFYLENBQUosRUFBMEM7QUFDeEMsWUFBTSxJQUFOLENBQVcsd0JBQVgsRUFBcUMsT0FBckMsQ0FBNkMsT0FBN0M7QUFDQTtBQUNEOztBQUVEO0FBQ0EsVUFBTSxNQUFOO0FBQ0QsR0FaRDs7QUFjQTs7OztBQUlBLFNBQU8sU0FBUCxDQUFpQixVQUFqQixHQUE4QjtBQUM1QixZQUFRO0FBQ04sWUFBTTtBQUNKLGVBQU87QUFDTCxzQkFBWTtBQUNWLHFCQUFTLFNBREM7QUFFVixzQkFBVSxTQUZBO0FBR1YseUJBQWE7QUFISCxXQURQO0FBTUwsa0JBQVE7QUFDTixxQkFBUyxTQURIO0FBRU4seUJBQWE7QUFGUDtBQU5ILFNBREg7QUFZSixxQkFBYTtBQUNYLG1CQUFTLENBREU7QUFFWCxvQkFBVTtBQUZDLFNBWlQ7QUFnQkosY0FBTTtBQUNKLGlCQUFPO0FBQ0wscUJBQVMsU0FESjtBQUVMLHlCQUFhO0FBRlI7QUFESCxTQWhCRjtBQXNCSixxQkFBYTtBQXRCVCxPQURBO0FBeUJOLGNBQVE7QUFDTixlQUFPO0FBQ0wsc0JBQVk7QUFEUCxTQUREO0FBSU4scUJBQWEsQ0FKUDtBQUtOLGdCQUFRO0FBTEYsT0F6QkY7QUFnQ04sWUFBTTtBQUNKLGVBQU8sQ0FESDtBQUVKLGVBQU87QUFDTCxtQkFBUztBQUNQLHFCQUFTLENBREY7QUFFUCx5QkFBYTtBQUZO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RUE4d0JXLE1BOXdCWCxFQTh3Qm1CLGNBOXdCbkIiLCJmaWxlIjoidHJhaWwuZ3JhcGguanMiLCJzb3VyY2VzQ29udGVudCI6WyIoZnVuY3Rpb24gZSh0LG4scil7ZnVuY3Rpb24gcyhvLHUpe2lmKCFuW29dKXtpZighdFtvXSl7dmFyIGE9dHlwZW9mIHJlcXVpcmU9PVwiZnVuY3Rpb25cIiYmcmVxdWlyZTtpZighdSYmYSlyZXR1cm4gYShvLCEwKTtpZihpKXJldHVybiBpKG8sITApO3ZhciBmPW5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIrbytcIidcIik7dGhyb3cgZi5jb2RlPVwiTU9EVUxFX05PVF9GT1VORFwiLGZ9dmFyIGw9bltvXT17ZXhwb3J0czp7fX07dFtvXVswXS5jYWxsKGwuZXhwb3J0cyxmdW5jdGlvbihlKXt2YXIgbj10W29dWzFdW2VdO3JldHVybiBzKG4/bjplKX0sbCxsLmV4cG9ydHMsZSx0LG4scil9cmV0dXJuIG5bb10uZXhwb3J0c312YXIgaT10eXBlb2YgcmVxdWlyZT09XCJmdW5jdGlvblwiJiZyZXF1aXJlO2Zvcih2YXIgbz0wO288ci5sZW5ndGg7bysrKXMocltvXSk7cmV0dXJuIHN9KSIsIi8qKlxuICogQGZpbGVcbiAqIFRyYWlsIEdyYXBoLlxuICpcbiAqIERpc3BsYXlzIG5vZGVzIHdpdGhpbiB0cmFpbHMuXG4gKi9cblxuKGZ1bmN0aW9uICgkLCBEcnVwYWwsIGRydXBhbFNldHRpbmdzKSB7XG5cbiAgLyoqXG4gICAqIFJlc3VibWl0IFRyYWlsIC8gTm9kZSBmaWx0ZXIgZm9ybSAoZXhwb3NlZCBmaWx0ZXIpXG4gICAqIFRoaXMgZnVuY3Rpb24gaXMgY2FsbGVkIGZyb20gX21vZGFsX2VkaXRfZm9ybV9hamF4X3N1Ym1pdCB2aWEgSW52b2tlQ29tbWFuZC5cbiAgICovXG4gICQuZm4ucmVzdWJtaXRUcmFpbEdyYXBoRmlsdGVyRm9ybSA9ICgpID0+IHtcbiAgICBjb25zdCBmb3JtSWQgPSBkcnVwYWxTZXR0aW5ncy5leHBvc2VkX2Zvcm1faWQ7XG4gICAgY29uc3QgJGZvcm0gPSAkKGBmb3JtIyR7Zm9ybUlkfWApO1xuXG4gICAgLy8gSW4gY2FzZSB3ZSBoYXZlIGZvcm0gc3VibWl0IGJ1dHRvbiB0cmlnZ2VyIGNsaWNrIGV2ZW50IChhamF4IHN1Ym1pdCB3aWxsIGhhcHBlbilcbiAgICBpZiAoJGZvcm0uZmluZCgnLmJ1dHRvbi5qcy1mb3JtLXN1Ym1pdCcpKSB7XG4gICAgICAkZm9ybS5maW5kKCcuYnV0dG9uLmpzLWZvcm0tc3VibWl0JykudHJpZ2dlcignY2xpY2snKTtcbiAgICAgIHJldHVybjtcbiAgICB9XG5cbiAgICAvLyBJbiBjYXNlIHdlIGRvbnQgaGF2ZSBmb3JtIHN1Ym1pdCBidXR0b24sIGZhbGxiYWNrIHRvIGZ1bGwgcGFnZSByZWxvYWQuXG4gICAgJGZvcm0uc3VibWl0KCk7XG4gIH07XG5cbiAgLyoqXG4gICAqIFRyYWlsIGdyYXBoIEJlaGF2aW91clxuICAgKiBUT0RPOiBXcml0ZSBzb21lIGRvY3VtZW50YXRpb24gYWJvdXQgdGhpcyBiZWhhdmlvdXIuXG4gICAqL1xuICBEcnVwYWwuYmVoYXZpb3JzLnRyYWlsR3JhcGggPSB7XG4gICAgc3R5bGVzOiB7XG4gICAgICBub2RlOiB7XG4gICAgICAgIGNvbG9yOiB7XG4gICAgICAgICAgYmFja2dyb3VuZDoge1xuICAgICAgICAgICAgaW5pdGlhbDogJyNkNGVkZmMnLFxuICAgICAgICAgICAgc2VsZWN0ZWQ6ICcjOUJERkZDJyxcbiAgICAgICAgICAgIGhpZ2hsaWdodGVkOiAncmdiYSgyMTIsIDIzNywgMjUyLCAwLjMpJyxcbiAgICAgICAgICB9LFxuICAgICAgICAgIGJvcmRlcjoge1xuICAgICAgICAgICAgaW5pdGlhbDogJyMwMDlmZTMnLFxuICAgICAgICAgICAgaGlnaGxpZ2h0ZWQ6ICdyZ2JhKDAsIDE1OSwgMjI3LCAwLjEpJyxcbiAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBib3JkZXJXaWR0aDoge1xuICAgICAgICAgIGluaXRpYWw6IDEsXG4gICAgICAgICAgc2VsZWN0ZWQ6IDIsXG4gICAgICAgIH0sXG4gICAgICAgIGZvbnQ6IHtcbiAgICAgICAgICBjb2xvcjoge1xuICAgICAgICAgICAgaW5pdGlhbDogJyMzNDM0MzQnLFxuICAgICAgICAgICAgaGlnaGxpZ2h0ZWQ6ICdyZ2JhKDAsIDAsIDAsIDAuMSknLFxuICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgICAgIGJveE1heFdpZHRoOiAxNTAsXG4gICAgICB9LFxuICAgICAgaGVhZGVyOiB7XG4gICAgICAgIGNvbG9yOiB7XG4gICAgICAgICAgYmFja2dyb3VuZDogJyNmZmZmZmYnLFxuICAgICAgICB9LFxuICAgICAgICBib3JkZXJXaWR0aDogMyxcbiAgICAgICAgbWFyZ2luOiAxMCxcbiAgICAgIH0sXG4gICAgICBsaW5rOiB7XG4gICAgICAgIHdpZHRoOiAzLFxuICAgICAgICBjb2xvcjoge1xuICAgICAgICAgIG9wYWNpdHk6IHtcbiAgICAgICAgICAgIGluaXRpYWw6IDEsXG4gICAgICAgICAgICBoaWdobGlnaHRlZDogMCxcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgICBtb2RhbDoge1xuICAgICAgICB3aWR0aDogJzgwJScsXG4gICAgICB9LFxuICAgIH0sXG4gICAgY29sb3JzOiBbXG4gICAgICBcIiMwMGZmZmZcIixcbiAgICAgIFwiIzAwMDAwMFwiLFxuICAgICAgXCIjMDAwMGZmXCIsXG4gICAgICBcIiNhNTJhMmFcIixcbiAgICAgIFwiIzAwZmZmZlwiLFxuICAgICAgXCIjMDAwMDhiXCIsXG4gICAgICBcIiMwMDhiOGJcIixcbiAgICAgIFwiIzAwNjQwMFwiLFxuICAgICAgXCIjYmRiNzZiXCIsXG4gICAgICBcIiM4YjAwOGJcIixcbiAgICAgIFwiIzU1NmIyZlwiLFxuICAgICAgXCIjZmY4YzAwXCIsXG4gICAgICBcIiM5OTMyY2NcIixcbiAgICAgIFwiIzhiMDAwMFwiLFxuICAgICAgXCIjZTk5NjdhXCIsXG4gICAgICBcIiM5NDAwZDNcIixcbiAgICAgIFwiI2ZmMDBmZlwiLFxuICAgICAgXCIjZmZkNzAwXCIsXG4gICAgICBcIiMwMDgwMDBcIixcbiAgICAgIFwiIzRiMDA4MlwiLFxuICAgICAgXCIjZjBlNjhjXCIsXG4gICAgICBcIiNhZGQ4ZTZcIixcbiAgICAgIFwiIzkwZWU5MFwiLFxuICAgICAgXCIjMDBmZjAwXCIsXG4gICAgICBcIiNmZjAwZmZcIixcbiAgICAgIFwiIzgwMDAwMFwiLFxuICAgICAgXCIjMDAwMDgwXCIsXG4gICAgICBcIiM4MDgwMDBcIixcbiAgICAgIFwiI2ZmYTUwMFwiLFxuICAgICAgXCIjZmZjMGNiXCIsXG4gICAgICBcIiM4MDAwODBcIixcbiAgICAgIFwiIzgwMDA4MFwiLFxuICAgICAgXCIjZmYwMDAwXCIsXG4gICAgICBcIiNjMGMwYzBcIixcbiAgICAgIFwiI2ZmZmYwMFwiLFxuICAgIF0sXG4gICAgYXR0YWNoOiBmdW5jdGlvbiAoY29udGV4dCwgc2V0dGluZ3MpIHtcbiAgICAgICQoJy50cmFpbC1ncmFwaF9fY29udGVudCcsIGNvbnRleHQpLm9uY2UoKS5lYWNoKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgY29uc3Qga2V5cyA9IE9iamVjdC5rZXlzKHNldHRpbmdzLnRyYWlsX2dyYXBoLmRhdGEpO1xuICAgICAgICBjb25zdCB0cmFpbEdyYXBoU2V0dGluZ3MgPSBzZXR0aW5ncy50cmFpbF9ncmFwaC5kYXRhW2tleXNba2V5cy5sZW5ndGggLSAxXV07XG4gICAgICAgIGNvbnN0IHsgdHJhaWxzLCBub2RlcywgZmlsdGVySW5wdXRzIH0gPSB0cmFpbEdyYXBoU2V0dGluZ3M7XG4gICAgICAgIERydXBhbC5iZWhhdmlvcnMudHJhaWxHcmFwaC5wcmVwYXJlRGF0YSh0aGlzLCBub2RlcywgdHJhaWxzLCBmaWx0ZXJJbnB1dHMpO1xuICAgICAgfSk7XG4gICAgfSxcblxuICAgIC8qKlxuICAgICAqIFByZXBhcmUgZGF0YSBmb3IgTmV0d29yay5cbiAgICAgKlxuICAgICAqIEBwYXJhbSBjb250ZXh0XG4gICAgICogQHBhcmFtIG5vZGVzXG4gICAgICogQHBhcmFtIHRyYWlsc1xuICAgICAqIEBwYXJhbSBmaWx0ZXJJbnB1dHNcbiAgICAgKi9cbiAgICBwcmVwYXJlRGF0YShjb250ZXh0LCBub2RlcywgdHJhaWxzLCBmaWx0ZXJJbnB1dHMpIHtcbiAgICAgIGNvbnN0IGRhdGFTZXRzID0ge1xuICAgICAgICBub2RlczogbmV3IHZpcy5EYXRhU2V0KCksXG4gICAgICAgIGxpbmtzOiBuZXcgdmlzLkRhdGFTZXQoKSxcbiAgICAgIH07XG4gICAgICBjb25zdCBwb3NpdGlvblN0ZXAgPSB7IHg6IDIwMCwgeTogMTAwIH07XG4gICAgICBjb25zdCB0cmFpbEhlYWRlcnNRdWV1ZSA9IFtdO1xuICAgICAgY29uc3Qgbm9kZXNRdWV1ZSA9IFtdO1xuICAgICAgY29uc3QgZm9jdXNUb0ZpcnN0VHJhaWwgPSAodHJhaWxzLmxlbmd0aCA+IDEpO1xuXG4gICAgICAvLyBDcmVhdGUgZGF0YSBzZXRzIGZvciBub2Rlcy5cbiAgICAgIGlmIChub2Rlcy5sZW5ndGgpIHtcbiAgICAgICAgbm9kZXMuZm9yRWFjaChub2RlID0+IGRhdGFTZXRzLm5vZGVzLmFkZChcbiAgICAgICAgICB0aGlzLnByZXBhcmVOb2RlRGF0YShub2RlKVxuICAgICAgICApKTtcbiAgICAgIH1cblxuICAgICAgLy8gQ3JlYXRlIGRhdGEgc2V0cyBmb3IgbGlua3MuXG4gICAgICBpZiAodHJhaWxzLmxlbmd0aCkge1xuICAgICAgICB0cmFpbHMuZm9yRWFjaCgodHJhaWwsIHRyYWlsSW5kZXgpID0+IHtcbiAgICAgICAgICBpZiAodHJhaWwubGlua3MubGVuZ3RoKSB7XG5cbiAgICAgICAgICAgIC8vIEFzc2lnbiB0cmFpbCBjb2xvci5cbiAgICAgICAgICAgIGNvbnN0IHRyYWlsQ29sb3IgPSAoXG4gICAgICAgICAgICAgIHRyYWlsLmNvbG9yID09IG51bGwgPyB0aGlzLmdlbmVyYXRlVHJhaWxDb2xvcih0cmFpbC50aWQpIDogdHJhaWwuY29sb3JcbiAgICAgICAgICAgICk7XG5cbiAgICAgICAgICAgIC8vIFVwZGF0ZSBUcmFpbCBIZWFkZXIgQm9yZGVyIHRvIGJlIGluIHNhbWUgY29sb3IgYXMgdHJhaWwuXG4gICAgICAgICAgICBjb25zdCB0cmFpbEhlYWRlciA9IGRhdGFTZXRzLm5vZGVzLmdldCh0cmFpbC5pZCk7XG4gICAgICAgICAgICB0cmFpbEhlYWRlci5jb2xvci5ib3JkZXIgPSB0cmFpbENvbG9yO1xuICAgICAgICAgICAgdHJhaWxIZWFkZXIub3JpZ2luYWxPcHRpb25zLmNvbG9yLmJvcmRlciA9IHRyYWlsQ29sb3I7XG4gICAgICAgICAgICB0cmFpbEhlYWRlcnNRdWV1ZS5wdXNoKHRyYWlsSGVhZGVyKTtcblxuICAgICAgICAgICAgY29uc3QgcG9zaXRpb24gPSB7XG4gICAgICAgICAgICAgIHg6IHBvc2l0aW9uU3RlcC54ICogdHJhaWxJbmRleCxcbiAgICAgICAgICAgICAgeTogMFxuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgLy8gQ3JlYXRlIGxpbmtzIGJldHdlZW4gbm9kZXMuXG4gICAgICAgICAgICB0cmFpbC5saW5rcy5mb3JFYWNoKChsaW5rLCBsaW5rSW5kZXgpID0+IHtcbiAgICAgICAgICAgICAgY29uc3QgbGlua09wdGlvbnMgPSB7XG4gICAgICAgICAgICAgICAgdHJhaWwsXG4gICAgICAgICAgICAgICAgbGluayxcbiAgICAgICAgICAgICAgICB0cmFpbENvbG9yLFxuICAgICAgICAgICAgICAgIGNob3NlbjogbGlua0luZGV4ID09PSAwID8geyBlZGdlOiBmYWxzZSB9IDogdHJ1ZVxuICAgICAgICAgICAgICB9O1xuXG4gICAgICAgICAgICAgIGRhdGFTZXRzLmxpbmtzLmFkZChcbiAgICAgICAgICAgICAgICB0aGlzLnByZXBhcmVMaW5rRGF0YShsaW5rT3B0aW9ucylcbiAgICAgICAgICAgICAgKTtcblxuICAgICAgICAgICAgICAvLyBTZXQgeC95IHBvc2l0aW9uIGZvciB0cmFpbCB3aGljaCBpcyBkaXJlY3RseSBjb25uZWN0ZWQgdG8gbm9kZXMuXG4gICAgICAgICAgICAgIHBvc2l0aW9uLnkgKz0gcG9zaXRpb25TdGVwLnk7XG5cbiAgICAgICAgICAgICAgLy8gU2V0IHBvc2l0aW9uIGZvciBmaXJzdCBub2RlIG9mIGVkZ2UobGluaylcbiAgICAgICAgICAgICAgbm9kZXNRdWV1ZS5wdXNoKHtcbiAgICAgICAgICAgICAgICBpZDogbGluay5mcm9tLFxuICAgICAgICAgICAgICAgIHg6IHBvc2l0aW9uLngsXG4gICAgICAgICAgICAgICAgeTogcG9zaXRpb24ueVxuICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAvLyBVcGRhdGUgc2Vjb25kIG5vZGUgaW4gYSBlZGdlKGxpbmspXG4gICAgICAgICAgICAgIGlmIChcbiAgICAgICAgICAgICAgICAgIGxpbmtJbmRleCA9PT0gdHJhaWwubGlua3MubGVuZ3RoIC0gMSAvLyBMYXN0IG5vZGUgaW4gYSB0cmFpbC5cbiAgICAgICAgICAgICAgICAgIHx8XG4gICAgICAgICAgICAgICAgICB0cmFpbC5saW5rcy5sZW5ndGggPT09IDEgLy8gQ2FzZSBmb3Igc2luZ2xlIHNpbmsgaW4gYSB0cmFpbC5cbiAgICAgICAgICAgICAgICApIHtcbiAgICAgICAgICAgICAgbm9kZXNRdWV1ZS5wdXNoKHtcbiAgICAgICAgICAgICAgICAgIGlkOiBsaW5rLnRvLFxuICAgICAgICAgICAgICAgICAgeDogcG9zaXRpb24ueCxcbiAgICAgICAgICAgICAgICAgIHk6IHBvc2l0aW9uLnkgKyBwb3NpdGlvblN0ZXAueVxuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgICAvLyBVcGRhdGUgZGF0YVNldHMgJiBSZS1EcmF3IGNhbnZhcy5cbiAgICAgICAgICBkYXRhU2V0cy5ub2Rlcy51cGRhdGUodHJhaWxIZWFkZXJzUXVldWUpO1xuICAgICAgICAgIGRhdGFTZXRzLm5vZGVzLnVwZGF0ZShub2Rlc1F1ZXVlKTtcbiAgICAgICAgfSk7XG4gICAgICB9XG5cbiAgICAgIHRoaXMuZW5hYmxlQ29udGV4dHVhbE1lbnUoKTtcbiAgICAgIHRoaXMucHJlcGFyZU5ldHdvcmsoY29udGV4dCwgZGF0YVNldHMpO1xuXG4gICAgICAvLyBTeW5jIGRhdGEgKHN0b3JlIGRhdGEgZ2xvYmFsbHkgaW4gZHJ1cGFsIHNldHRpbmcgLSBtb3N0bHkgZm9yIGRlYnVnZ2luZyBwdXJwb3NlcylcbiAgICAgIGRydXBhbFNldHRpbmdzLnRyYWlsX2dyYXBoLmRhdGFTZXRzID0gZGF0YVNldHM7XG4gICAgICBkcnVwYWxTZXR0aW5ncy50cmFpbF9ncmFwaC50cmFpbHMgPSB0cmFpbHM7XG4gICAgICBkcnVwYWxTZXR0aW5ncy50cmFpbF9ncmFwaC5mb2N1c1RvRmlyc3RUcmFpbCA9IGZvY3VzVG9GaXJzdFRyYWlsO1xuICAgICAgZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGguZmlsdGVySW5wdXRzID0gZmlsdGVySW5wdXRzO1xuICAgIH0sXG5cbiAgICAvKipcbiAgICAgKiBQcmVwYXJlIE5ldHdvcmsuXG4gICAgICpcbiAgICAgKiBAcGFyYW0gY29udGV4dFxuICAgICAqIEBwYXJhbSBkYXRhU2V0c1xuICAgICAqL1xuICAgIHByZXBhcmVOZXR3b3JrKGNvbnRleHQsIGRhdGFTZXRzKSB7XG4gICAgICBjb25zdCBzdHlsZXMgPSB0aGlzLnN0eWxlcztcbiAgICAgIGNvbnN0IGRhdGEgPSB7XG4gICAgICAgIG5vZGVzOiBkYXRhU2V0cy5ub2RlcyxcbiAgICAgICAgZWRnZXM6IGRhdGFTZXRzLmxpbmtzXG4gICAgICB9O1xuICAgICAgY29uc3Qgb3B0aW9ucyA9IHtcbiAgICAgICAgYXV0b1Jlc2l6ZTogdHJ1ZSxcbiAgICAgICAgd2lkdGg6ICcxMDAlJyxcbiAgICAgICAgaGVpZ2h0OiAnNzAwcHgnLFxuICAgICAgICBwaHlzaWNzOiBmYWxzZSxcbiAgICAgICAgbGF5b3V0OiB7XG4gICAgICAgICAgaGllcmFyY2hpY2FsOiBmYWxzZSxcbiAgICAgICAgfSxcbiAgICAgICAgbm9kZXM6IHtcbiAgICAgICAgICBzaGFwZTogJ2JveCcsXG4gICAgICAgICAgd2lkdGhDb25zdHJhaW50OiB7XG4gICAgICAgICAgICBtYXhpbXVtOiBzdHlsZXMubm9kZS5ib3hNYXhXaWR0aCxcbiAgICAgICAgICB9LFxuICAgICAgICAgIGZvbnQ6IHtcbiAgICAgICAgICAgIG11bHRpOiB0cnVlLFxuICAgICAgICAgIH0sXG4gICAgICAgICAgY29sb3I6IHtcbiAgICAgICAgICAgIGJvcmRlcjogc3R5bGVzLm5vZGUuY29sb3IuYm9yZGVyLmluaXRpYWwsXG4gICAgICAgICAgICBiYWNrZ3JvdW5kOiBzdHlsZXMubm9kZS5jb2xvci5iYWNrZ3JvdW5kLmluaXRpYWwsXG4gICAgICAgICAgICBoaWdobGlnaHQ6IHtcbiAgICAgICAgICAgICAgYm9yZGVyOiBzdHlsZXMubm9kZS5jb2xvci5ib3JkZXIuaGlnaGxpZ2h0ZWQsXG4gICAgICAgICAgICAgIGJhY2tncm91bmQ6IHN0eWxlcy5ub2RlLmNvbG9yLmJhY2tncm91bmQuc2VsZWN0ZWQsXG4gICAgICAgICAgICB9LFxuICAgICAgICAgIH0sXG4gICAgICAgICAgZml4ZWQ6IGZhbHNlXG4gICAgICAgIH0sXG4gICAgICAgIGVkZ2VzOiB7XG4gICAgICAgICAgd2lkdGg6IHN0eWxlcy5saW5rLndpZHRoLFxuICAgICAgICAgIHNtb290aDogZmFsc2UsXG4gICAgICAgIH0sXG4gICAgICB9O1xuXG4gICAgICB0aGlzLmRpc3BsYXlOZXR3b3JrKGNvbnRleHQsIGRhdGEsIG9wdGlvbnMpO1xuICAgIH0sXG5cbiAgICAvKipcbiAgICAgKiBEaXNwbGF5IE5ldHdvcmsuXG4gICAgICpcbiAgICAgKiBAcGFyYW0gY29udGV4dFxuICAgICAqIEBwYXJhbSBkYXRhXG4gICAgICogQHBhcmFtIG9wdGlvbnNcbiAgICAgKi9cbiAgICBkaXNwbGF5TmV0d29yayhjb250ZXh0LCBkYXRhLCBvcHRpb25zKSB7XG4gICAgICBjb25zdCBuZXR3b3JrID0gbmV3IHZpcy5OZXR3b3JrKFxuICAgICAgICAkKCcjdHJhaWwtZ3JhcGgtY2FudmFzJywgY29udGV4dClbMF0sXG4gICAgICAgIGRhdGEsXG4gICAgICAgIG9wdGlvbnMsXG4gICAgICApO1xuXG4gICAgICAvLyBTdG9yaW5nIG5ldHdvcmsgZGF0YSBnbG9iYWxseS5cbiAgICAgIGRydXBhbFNldHRpbmdzLnRyYWlsX2dyYXBoLm5ldHdvcmsgPSBuZXR3b3JrO1xuICAgICAgZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGgubmV0d29ya0lzRHJhd24gPSBmYWxzZTtcblxuICAgICAgLy8gTmV0d29yayBldmVudHNcbiAgICAgIC8vIFRPRE86IExpbmsgY3JlYXRpb24gbWV0aG9kLlxuICAgICAgbmV0d29yay5vbihcIm9uY29udGV4dFwiLCBwYXJhbXMgPT4ge1xuICAgICAgICBjb25zdCBzZWxlY3RlZE5vZGVJZCA9IG5ldHdvcmsuZ2V0Tm9kZUF0KHBhcmFtcy5wb2ludGVyLkRPTSk7XG4gICAgICAgIGNvbnN0IGRhdGFTZXRzID0gZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGguZGF0YVNldHM7XG4gICAgICAgIGNvbnN0ICRtZW51Q29udGVudCA9ICQoJzx1bD4nKTtcblxuICAgICAgICBpZiAoc2VsZWN0ZWROb2RlSWQpIHtcbiAgICAgICAgICBjb25zdCBub2RlID0gZGF0YVNldHMubm9kZXMuZ2V0KHNlbGVjdGVkTm9kZUlkKTtcblxuICAgICAgICAgIGlmIChub2RlLmlzSGVhZGVyKSB7IC8vIFRyYWlsIGhlYWRlci5cbiAgICAgICAgICAgICRtZW51Q29udGVudC5hcHBlbmQoXG4gICAgICAgICAgICAgICQoJzxsaT4nKS5hcHBlbmQoXG4gICAgICAgICAgICAgICAgdGhpcy5jcmVhdGVGb2N1c09uVGhpc1RyYWlsTGluayhcbiAgICAgICAgICAgICAgICAgIERydXBhbC50KCdGb2N1cyBvbiB0aGlzIHRyYWlsJyksXG4gICAgICAgICAgICAgICAgICBzZWxlY3RlZE5vZGVJZFxuICAgICAgICAgICAgICAgIClcbiAgICAgICAgICAgICAgKSxcbiAgICAgICAgICAgICAgJCgnPGhyPicpLFxuICAgICAgICAgICAgICAkKCc8bGk+JykuYXBwZW5kKFxuICAgICAgICAgICAgICAgIHRoaXMuY3JlYXRlU2hvd1RyYWlsTGluayhcbiAgICAgICAgICAgICAgICAgIERydXBhbC50KCdFZGl0IHRyYWlsIG9yZGVyJyksXG4gICAgICAgICAgICAgICAgICBgL3RyYWlsX2dyYXBoL25vZGVfb3JkZXIvJHtub2RlLnRpZH1gLFxuICAgICAgICAgICAgICAgICAgY29udGV4dFxuICAgICAgICAgICAgICAgIClcbiAgICAgICAgICAgICAgKSxcbiAgICAgICAgICAgICk7XG4gICAgICAgICAgfVxuICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgLy8gTm9kZS5cbiAgICAgICAgICAgICRtZW51Q29udGVudC5hcHBlbmQoXG4gICAgICAgICAgICAgICQoJzxsaT4nKS5hcHBlbmQobm9kZS5jb250ZW50UHJldmlldyksXG4gICAgICAgICAgICAgICQoJzxocj4nKSxcbiAgICAgICAgICAgICAgJCgnPGxpPicpLmFwcGVuZChcbiAgICAgICAgICAgICAgICB0aGlzLmNyZWF0ZU1vZGFsTGluayhcbiAgICAgICAgICAgICAgICAgIERydXBhbC50KCdFZGl0IG5vZGUnKSxcbiAgICAgICAgICAgICAgICAgIGAvbm9kZS8ke3NlbGVjdGVkTm9kZUlkfS9lZGl0P21pbT10cmFpbF9ncmFwaGAsXG4gICAgICAgICAgICAgICAgICB7IHdpZHRoOiAnODAlJyB9XG4gICAgICAgICAgICAgICAgKVxuICAgICAgICAgICAgICApLFxuICAgICAgICAgICAgKTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgICB0aGlzLnNob3dDb250ZXh0dWFsTWVudShcbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgeDogcGFyYW1zLmV2ZW50LngsXG4gICAgICAgICAgICAgIHk6IHBhcmFtcy5ldmVudC55LFxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGNvbnRleHQsXG4gICAgICAgICAgICAkbWVudUNvbnRlbnRcbiAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgICB9KTtcbiAgICAgIG5ldHdvcmsub24oXCJzZWxlY3ROb2RlXCIsIHBhcmFtcyA9PiB7XG4gICAgICAgIGNvbnN0IG5vZGVJZCA9IChwYXJhbXMubm9kZXMubGVuZ3RoID4gMCA/IHBhcmFtcy5ub2Rlc1swXSA6IG51bGwpO1xuXG4gICAgICAgIGlmIChub2RlSWQgIT09IG51bGwgJiYgdGhpcy5pc1RyYWlsSGVhZGVyKG5vZGVJZCkpIHtcbiAgICAgICAgICB0aGlzLmhpZ2hsaWdodFRyYWlsKG5vZGVJZClcbiAgICAgICAgICAudGhlbigoKSA9PiB0aGlzLmxvY2tOb2RlcygpKVxuICAgICAgICAgIC5jYXRjaChlcnIgPT4ge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcihlcnIpO1xuICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICB9KTtcbiAgICAgIG5ldHdvcmsub24oXCJkZXNlbGVjdE5vZGVcIiwgKCkgPT4ge1xuICAgICAgICB0aGlzLnJlc2V0VHJhaWxIaWdobGlnaHQoKS50aGVuKFxuICAgICAgICAgICgpID0+IHRoaXMudW5sb2NrTm9kZXMoKVxuICAgICAgICApO1xuICAgICAgfSk7XG4gICAgICBuZXR3b3JrLm9uKFwiZHJhZ1N0YXJ0XCIsICgpID0+IHtcbiAgICAgICAgJCgnI3RyYWlsLWdyYXBoLWNhbnZhcycsIGNvbnRleHQpLmNzcygnY3Vyc29yJywgJ21vdmUnKTtcbiAgICAgIH0pO1xuICAgICAgbmV0d29yay5vbihcImRyYWdFbmRcIiwgKCkgPT4ge1xuICAgICAgICAkKCcjdHJhaWwtZ3JhcGgtY2FudmFzJywgY29udGV4dCkuY3NzKCdjdXJzb3InLCAnZGVmYXVsdCcpO1xuICAgICAgfSk7XG4gICAgICBuZXR3b3JrLm9uKFwiYWZ0ZXJEcmF3aW5nXCIsICgpID0+IHtcblxuICAgICAgICAvLyBJZiBtb3JlIHRoYW4gdHJhaWwgaXMgZGlzcGxheWVkLCBzZXQgZm9jdXMgdG8gZmlyc3Qgb25lLlxuICAgICAgICBpZiAoXG4gICAgICAgICAgICBkcnVwYWxTZXR0aW5ncy50cmFpbF9ncmFwaC5mb2N1c1RvRmlyc3RUcmFpbCAmJlxuICAgICAgICAgICAgIWRydXBhbFNldHRpbmdzLnRyYWlsX2dyYXBoLm5ldHdvcmtJc0RyYXduXG4gICAgICAgICAgKSB7XG4gICAgICAgIC8vIEl0cyBpbXBvcnRhbnQgdG8gc2V0IG5ldHdvcmtJc0RyYXduIHRvIFRSVUUsIG90aGVyd2lzZSB0aGVyZSB3aWxsIGJlIGluZmluaXRlIGxvb3AhISFcbiAgICAgICAgICBkcnVwYWxTZXR0aW5ncy50cmFpbF9ncmFwaC5uZXR3b3JrSXNEcmF3biA9IHRydWU7XG5cbiAgICAgICAgICBpZiAoZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGguZmlsdGVySW5wdXRzWyd0cmFpbF9pZCddKSB7XG4gICAgICAgICAgICB0aGlzLnVwZGF0ZVRyYWlsVmVydGljYWxBcnJhbmdlbWVudChkcnVwYWxTZXR0aW5ncy50cmFpbF9ncmFwaC5maWx0ZXJJbnB1dHNbJ3RyYWlsX2lkJ11bMF0pO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgfSxcblxuICAgIC8qKlxuICAgICAqIENvbnRleHR1YWwgbWVudSBmdW5jdGlvbnMuXG4gICAgICovXG4gICAgZW5hYmxlQ29udGV4dHVhbE1lbnUoKSB7XG4gICAgICAkKGRvY3VtZW50KS5iaW5kKFwiY29udGV4dG1lbnVcIiwgZSA9PiBlLnByZXZlbnREZWZhdWx0KCkpO1xuICAgIH0sXG4gICAgc2hvd0NvbnRleHR1YWxNZW51KHBvc2l0aW9uLCBjb250ZXh0LCBjb250ZW50KSB7XG4gICAgICAkKFwiLnRyYWlsLWdyYXBoX19jb250ZXh0dWFsLW1lbnVcIiwgY29udGV4dClcbiAgICAgICAgLmVtcHR5KClcbiAgICAgICAgLmFwcGVuZChjb250ZW50KVxuICAgICAgICAuY3NzKHtcbiAgICAgICAgICBcImxlZnRcIjogcG9zaXRpb24ueCxcbiAgICAgICAgICBcInRvcFwiOiBwb3NpdGlvbi55XG4gICAgICAgIH0pXG4gICAgICAgIC5mYWRlSW4oMjAwLCB0aGlzLnN0YXJ0Rm9jdXNPdXQoY29udGV4dCkpO1xuXG4gICAgICBEcnVwYWwuYXR0YWNoQmVoYXZpb3JzKCk7XG4gICAgfSxcbiAgICBzdGFydEZvY3VzT3V0KGNvbnRleHQpIHtcbiAgICAgICQoZG9jdW1lbnQpLm9uKFwiY2xpY2tcIiwgKCkgPT4ge1xuICAgICAgICAkKFwiLnRyYWlsLWdyYXBoX19jb250ZXh0dWFsLW1lbnVcIiwgY29udGV4dCkuaGlkZSgpO1xuICAgICAgICAkKGRvY3VtZW50KS5vZmYoXCJjbGlja1wiKTtcbiAgICAgIH0pO1xuICAgIH0sXG4gICAgY3JlYXRlTW9kYWxMaW5rKHRpdGxlLCB1cmwsIG9wdGlvbnMpIHtcbiAgICAgIHJldHVybiAkKCc8YT4nKVxuICAgICAgLmF0dHIoJ2hyZWYnLCB1cmwpXG4gICAgICAudGV4dCh0aXRsZSlcbiAgICAgIC5hZGRDbGFzcygndXNlLWFqYXgnKVxuICAgICAgLmRhdGEoe1xuICAgICAgICAnZGlhbG9nLXR5cGUnOiAnbW9kYWwnLFxuICAgICAgICAnZGlhbG9nLW9wdGlvbnMnOiBvcHRpb25zLFxuICAgICAgfSk7XG4gICAgfSxcbiAgICBjcmVhdGVJbmxpbmVNb2RhbExpbmsobW9kYWxUaXRsZSwgbGlua1RpdGxlLCAkbW9kYWxDb250ZW50KSB7XG4gICAgICBpZiAodHlwZW9mICRtb2RhbENvbnRlbnQgPT09ICdzdHJpbmcnKSB7XG4gICAgICAgICRtb2RhbENvbnRlbnQgPSAkKCRtb2RhbENvbnRlbnQpO1xuICAgICAgfVxuXG4gICAgICByZXR1cm4gJCgnPGE+JylcbiAgICAgIC5hdHRyKCdocmVmJywgJyMnKVxuICAgICAgLnRleHQobGlua1RpdGxlKVxuICAgICAgLm9uKCdjbGljaycsICgpID0+IHRoaXMuc2hvd0lubGluZU1vZGFsKG1vZGFsVGl0bGUsICRtb2RhbENvbnRlbnQpKTtcbiAgICB9LFxuICAgIGNyZWF0ZUZvY3VzT25UaGlzVHJhaWxMaW5rKGxpbmtUaXRsZSwgbm9kZUlkKSB7XG4gICAgICByZXR1cm4gJCgnPGE+JylcbiAgICAgIC5hdHRyKCdocmVmJywgJyMnKVxuICAgICAgLnRleHQobGlua1RpdGxlKVxuICAgICAgLm9uKCdjbGljaycsICgpID0+IHRoaXMudXBkYXRlVHJhaWxWZXJ0aWNhbEFycmFuZ2VtZW50KG5vZGVJZCwgdHJ1ZSkpO1xuICAgIH0sXG4gICAgY3JlYXRlU2hvd1RyYWlsTGluayhsaW5rVGl0bGUsIGVuZHBvaW50LCBjb250ZXh0KSB7XG4gICAgICByZXR1cm4gJCgnPGE+JylcbiAgICAgIC5hdHRyKCdocmVmJywgJyMnKVxuICAgICAgLnRleHQobGlua1RpdGxlKVxuICAgICAgLm9uKCdjbGljaycsICgpID0+IHRoaXMuc2hvd1RyYWlsTGluayhlbmRwb2ludCwgY29udGV4dCkpO1xuICAgIH0sXG5cbiAgICAvKipcbiAgICAgKiBNb2RhbCB3aW5kb3cuXG4gICAgICovXG4gICAgc2hvd0lubGluZU1vZGFsKHRpdGxlLCBjb250ZW50KSB7XG4gICAgICBjb25zdCBtb2RhbCA9ICQoJzxkaXY+JykuaHRtbChjb250ZW50KTtcbiAgICAgIGNvbnN0IG9wdGlvbnMgPSB7XG4gICAgICAgIHRpdGxlOiB0aXRsZSxcbiAgICAgICAgd2lkdGg6IHRoaXMuc3R5bGVzLm1vZGFsLndpZHRoLFxuICAgICAgfTtcblxuICAgICAgRHJ1cGFsLmRpYWxvZyhtb2RhbCwgb3B0aW9ucykuc2hvd01vZGFsKCk7XG4gICAgfSxcblxuICAgIC8qKlxuICAgICAqIFRyYWlsIC8gTGluayBmdW5jdGlvbnMuXG4gICAgICovXG4gICAgc2hvd1RyYWlsTGluayhlbmRwb2ludCkge1xuICAgICAgRHJ1cGFsLmFqYXgoe1xuICAgICAgICB1cmw6IGVuZHBvaW50XG4gICAgICB9KS5leGVjdXRlKCk7XG5cbiAgICAgIC8vIFNob3cgVHJhaWxzIHRhYiBjb250ZW50LlxuICAgICAgRHJ1cGFsLmJlaGF2aW9ycy50cmFpbEdyYXBoU2lkZWJhclRhYnMuc3dpdGNoVGFiKCd0cmFpbC1ncmFwaC10YWItMicpO1xuICAgIH0sXG4gICAgaGlnaGxpZ2h0VHJhaWwodGVybUlkID0gbnVsbCkge1xuICAgICAgcmV0dXJuIG5ldyBQcm9taXNlKChyZXNvbHZlLCByZWplY3QpID0+IHtcbiAgICAgICAgaWYgKHRlcm1JZCA9PT0gbnVsbCkge1xuICAgICAgICAgIHJlamVjdCgnTWlzc2luZyB0ZXJtSWQgZm9yIGhpZ2hsaWdodFRyYWlsKCknKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IGRhdGFTZXRzID0gZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGguZGF0YVNldHM7XG4gICAgICAgIGNvbnN0IGxpbmtzID0gZGF0YVNldHMubGlua3MuZ2V0KCk7XG4gICAgICAgIGNvbnN0IG5vZGVzID0gZGF0YVNldHMubm9kZXMuZ2V0KCk7XG4gICAgICAgIGNvbnN0IG5vZGVzUXVldWUgPSBbXTtcbiAgICAgICAgY29uc3QgbGlua3NRdWV1ZSA9IFtdO1xuXG4gICAgICAgIGxpbmtzLmZvckVhY2gobGluayA9PiB7XG4gICAgICAgICAgaWYgKGxpbmsudGlkICE9PSB0ZXJtSWQpIHtcbiAgICAgICAgICAgIGxpbmtzUXVldWUucHVzaCh7XG4gICAgICAgICAgICAgIGlkOiBsaW5rLmlkLFxuICAgICAgICAgICAgICBjb2xvcjoge1xuICAgICAgICAgICAgICAgIG9wYWNpdHk6IHRoaXMuc3R5bGVzLmxpbmsuY29sb3Iub3BhY2l0eS5oaWdobGlnaHRlZFxuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgICBub2Rlcy5mb3JFYWNoKG5vZGUgPT4ge1xuICAgICAgICAgIGxldCB1cGRhdGVOZWVkZWQgPSBmYWxzZTtcbiAgICAgICAgICBjb25zdCBkYXRhID0ge1xuICAgICAgICAgICAgaWQ6IG5vZGUuaWQsXG4gICAgICAgICAgICBmb250OiB7IGNvbG9yOiB0aGlzLnN0eWxlcy5ub2RlLmZvbnQuY29sb3IuaGlnaGxpZ2h0ZWQgfVxuICAgICAgICAgIH07XG5cbiAgICAgICAgICBpZiAobm9kZS5pc0hlYWRlcikge1xuICAgICAgICAgICAgaWYgKG5vZGUuaWQgIT09IHRlcm1JZCkge1xuICAgICAgICAgICAgICBkYXRhLmNvbG9yID0ge1xuICAgICAgICAgICAgICAgIGJhY2tncm91bmQ6IHRoaXMuaGV4VG9SR0Iobm9kZS5jb2xvci5iYWNrZ3JvdW5kLCAwLjIpLFxuICAgICAgICAgICAgICAgIGJvcmRlcjogdGhpcy5oZXhUb1JHQihub2RlLmNvbG9yLmJvcmRlciwgMC4yKSxcbiAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgdXBkYXRlTmVlZGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICBjb25zdCByZWxhdGVkVHJhaWxzID0gbm9kZS5yZWxhdGVkVHJhaWxzLm1hcCh0cmFpbCA9PiAnVCcgKyB0cmFpbCk7XG4gICAgICAgICAgICBpZiAoIXJlbGF0ZWRUcmFpbHMuaW5jbHVkZXModGVybUlkKSkge1xuICAgICAgICAgICAgICBkYXRhLmNvbG9yID0ge1xuICAgICAgICAgICAgICAgIGJhY2tncm91bmQ6IHRoaXMuc3R5bGVzLm5vZGUuY29sb3IuYmFja2dyb3VuZC5oaWdobGlnaHRlZCxcbiAgICAgICAgICAgICAgICBib3JkZXI6IHRoaXMuc3R5bGVzLm5vZGUuY29sb3IuYm9yZGVyLmhpZ2hsaWdodGVkLFxuICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICB1cGRhdGVOZWVkZWQgPSB0cnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cblxuICAgICAgICAgIC8vIFByb2NlZWQgd2l0aCB1cGRhdGUgKHNvbWUgY2FzZXMgd2UgZG9uJ3QgbmVlZCB0byB1cGRhdGUpXG4gICAgICAgICAgaWYgKHVwZGF0ZU5lZWRlZCkge1xuICAgICAgICAgICAgbm9kZXNRdWV1ZS5wdXNoKGRhdGEpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgZGF0YVNldHMubGlua3MudXBkYXRlKGxpbmtzUXVldWUpO1xuICAgICAgICBkYXRhU2V0cy5ub2Rlcy51cGRhdGUobm9kZXNRdWV1ZSk7XG5cbiAgICAgICAgcmVzb2x2ZSgnVHJhaWwgSGlnaGxpZ2h0ZWQnKTtcbiAgICAgIH0pO1xuICAgIH0sXG4gICAgcmVzZXRUcmFpbEhpZ2hsaWdodCgpIHtcbiAgICAgIHJldHVybiBuZXcgUHJvbWlzZSgocmVzb2x2ZSwgcmVqZWN0KSA9PiB7XG4gICAgICAgIGNvbnN0IGRhdGFTZXRzID0gZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGguZGF0YVNldHM7XG4gICAgICAgIGNvbnN0IGxpbmtzID0gZGF0YVNldHMubGlua3MuZ2V0KCk7XG4gICAgICAgIGNvbnN0IG5vZGVzID0gZGF0YVNldHMubm9kZXMuZ2V0KCk7XG4gICAgICAgIGNvbnN0IG5vZGVDb2xvclN0eWxlID0gdGhpcy5zdHlsZXMubm9kZS5jb2xvcjtcbiAgICAgICAgY29uc3QgbGlua3NRdWV1ZSA9IFtdO1xuICAgICAgICBjb25zdCBub2Rlc1F1ZXVlID0gW107XG5cbiAgICAgICAgbGlua3MuZm9yRWFjaChsaW5rID0+IHtcbiAgICAgICAgICBsaW5rc1F1ZXVlLnB1c2goe1xuICAgICAgICAgICAgaWQ6IGxpbmsuaWQsXG4gICAgICAgICAgICBjb2xvcjoge1xuICAgICAgICAgICAgICBjb2xvcjogbGluay5vcmlnaW5hbE9wdGlvbnMuY29sb3IuY29sb3IsXG4gICAgICAgICAgICAgIG9wYWNpdHk6IHRoaXMuc3R5bGVzLmxpbmsuY29sb3Iub3BhY2l0eS5pbml0aWFsLFxuICAgICAgICAgICAgfSxcbiAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG4gICAgICAgIG5vZGVzLmZvckVhY2gobm9kZSA9PiB7XG4gICAgICAgICAgbm9kZXNRdWV1ZS5wdXNoKHtcbiAgICAgICAgICAgIGlkOiBub2RlLmlkLFxuICAgICAgICAgICAgZm9udDogeyBjb2xvcjogdGhpcy5zdHlsZXMubm9kZS5mb250LmNvbG9yLmluaXRpYWwgfSxcbiAgICAgICAgICAgIGNvbG9yOiB7XG4gICAgICAgICAgICAgIGJhY2tncm91bmQ6IChcbiAgICAgICAgICAgICAgICBub2RlLm9yaWdpbmFsT3B0aW9ucyA/IG5vZGUub3JpZ2luYWxPcHRpb25zLmNvbG9yLmJhY2tncm91bmQgOiBub2RlQ29sb3JTdHlsZS5iYWNrZ3JvdW5kLmluaXRpYWxcbiAgICAgICAgICAgICAgKSxcbiAgICAgICAgICAgICAgYm9yZGVyOiAoXG4gICAgICAgICAgICAgICAgbm9kZS5vcmlnaW5hbE9wdGlvbnMgPyBub2RlLm9yaWdpbmFsT3B0aW9ucy5jb2xvci5ib3JkZXIgOiBub2RlQ29sb3JTdHlsZS5ib3JkZXIuaW5pdGlhbFxuICAgICAgICAgICAgICApXG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIGRhdGFTZXRzLmxpbmtzLnVwZGF0ZShsaW5rc1F1ZXVlKTtcbiAgICAgICAgZGF0YVNldHMubm9kZXMudXBkYXRlKG5vZGVzUXVldWUpO1xuXG4gICAgICAgIHJlc29sdmUoJ1Jlc2V0IFRyYWlsIEhpZ2hsaWdodCBTdWNjZXNzZnVsbHknKTtcbiAgICAgIH0pO1xuICAgIH0sXG4gICAgdXBkYXRlVHJhaWxWZXJ0aWNhbEFycmFuZ2VtZW50KHRyYWlsSWQgPSBudWxsLCByZW1lbWJlclRyYWlsTGFzdFBvc2l0aW9uID0gZmFsc2UpIHtcbiAgICAgICAgaWYgKHRyYWlsSWQgPT09IG51bGwpIHtcbiAgICAgICAgICBjb25zb2xlLmVycm9yKCdNaXNzaW5nIHRyYWlsSWQnKTtcbiAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICBjb25zdCB0cmFpbHMgPSBuZXcgdmlzLkRhdGFTZXQoZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGgudHJhaWxzKTtcbiAgICAgICAgY29uc3Qgbm9kZVBvc2l0aW9uID0geyB4OiAwLCB5OiAwIH07XG4gICAgICAgIGNvbnN0IHBvc2l0aW9uU3RlcCA9IHsgeDogMjAwLCB5OiAxMDAgfTtcbiAgICAgICAgY29uc3QgdHJhaWxJbmRleCA9IHRoaXMuZmluZEluZGV4T2ZPYmplY3QodHJhaWxzLmdldCgpLCAnaWQnLCB0cmFpbElkKTtcbiAgICAgICAgY29uc3QgZGF0YVNldHMgPSBkcnVwYWxTZXR0aW5ncy50cmFpbF9ncmFwaC5kYXRhU2V0cztcbiAgICAgICAgY29uc3QgbmV0d29yayA9IGRydXBhbFNldHRpbmdzLnRyYWlsX2dyYXBoLm5ldHdvcms7XG4gICAgICAgIGNvbnN0IHRyYWlsID0gdHJhaWxzLmdldCh0cmFpbElkKTtcbiAgICAgICAgY29uc3Qgbm9kZXNRdWV1ZSA9IFtdO1xuXG4gICAgICAgIGlmICh0cmFpbC5saW5rcy5sZW5ndGgpIHtcbiAgICAgICAgICBub2RlUG9zaXRpb24ueCA9IHBvc2l0aW9uU3RlcC54ICogdHJhaWxJbmRleDtcbiAgICAgICAgICBub2RlUG9zaXRpb24ueSA9IDA7XG5cbiAgICAgICAgICAvLyBHZXQgdHJhaWwgaGVhZGVyIGN1cnJlbnQgcG9zaXRpb24gYW5kIHVzZSBpdCBmb3Igbm9kZXMuXG4gICAgICAgICAgaWYgKHJlbWVtYmVyVHJhaWxMYXN0UG9zaXRpb24pIHtcbiAgICAgICAgICAgIGNvbnN0IHRyYWlsSGVhZGVyUG9zaXRpb24gPSBuZXR3b3JrLmdldFBvc2l0aW9ucyhbdHJhaWxJZF0pW3RyYWlsSWRdO1xuICAgICAgICAgICAgbm9kZVBvc2l0aW9uLnggPSB0cmFpbEhlYWRlclBvc2l0aW9uLng7XG4gICAgICAgICAgICBub2RlUG9zaXRpb24ueSA9IHRyYWlsSGVhZGVyUG9zaXRpb24ueSAtIHBvc2l0aW9uU3RlcC55O1xuICAgICAgICAgIH1cblxuICAgICAgICAgIC8vIENyZWF0ZSBsaW5rcyBiZXR3ZWVuIG5vZGVzLlxuICAgICAgICAgIHRyYWlsLmxpbmtzLmZvckVhY2goKGxpbmssIGxpbmtJbmRleCkgPT4ge1xuICAgICAgICAgICAgbm9kZVBvc2l0aW9uLnkgKz0gcG9zaXRpb25TdGVwLnk7XG4gICAgICAgICAgICBub2Rlc1F1ZXVlLnB1c2goe1xuICAgICAgICAgICAgICBpZDogbGluay5mcm9tLFxuICAgICAgICAgICAgICB4OiBub2RlUG9zaXRpb24ueCxcbiAgICAgICAgICAgICAgeTogbm9kZVBvc2l0aW9uLnlcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAvLyBVcGRhdGUgc2Vjb25kIG5vZGUgaW4gYSBsaW5rLlxuICAgICAgICAgICAgaWYgKFxuICAgICAgICAgICAgICAgIGxpbmtJbmRleCA9PT0gdHJhaWwubGlua3MubGVuZ3RoIC0gMSAvLyBMYXN0IG5vZGUgaW4gYSB0cmFpbC5cbiAgICAgICAgICAgICAgICB8fFxuICAgICAgICAgICAgICAgIHRyYWlsLmxpbmtzLmxlbmd0aCA9PT0gMSAvLyBDYXNlIGZvciBzaW5nbGUgbGluayBpbiBhIHRyYWlsLlxuICAgICAgICAgICAgICApIHtcbiAgICAgICAgICAgICAgbm9kZXNRdWV1ZS5wdXNoKHtcbiAgICAgICAgICAgICAgICBpZDogbGluay50byxcbiAgICAgICAgICAgICAgICB4OiBub2RlUG9zaXRpb24ueCxcbiAgICAgICAgICAgICAgICB5OiBub2RlUG9zaXRpb24ueSArIHBvc2l0aW9uU3RlcC55XG4gICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgLy8gVXBkYXRlIGRhdGFTZXQgJiBSZS1kcmF3IGNhbnZhcy5cbiAgICAgICAgICBkYXRhU2V0cy5ub2Rlcy51cGRhdGUobm9kZXNRdWV1ZSk7XG5cbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICBjb25zb2xlLmVycm9yKGBObyBsaW5rcyBmb3VuZCBpbiB0cmFpbCAjJHt0cmFpbElkfWApO1xuICAgICAgICB9XG4gICAgfSxcbiAgICAvKipcbiAgICAgKiBHZW5lcmF0ZSB0cmFpbCBjb2xvciBiYXNlZCBvbiB0cmFpbCBpZC5cbiAgICAgKlxuICAgICAqIEBwYXJhbSB0cmFpbElkXG4gICAgICpcbiAgICAgKiBAcmV0dXJucyB7c3RyaW5nfVxuICAgICAqL1xuICAgIGdlbmVyYXRlVHJhaWxDb2xvcih0cmFpbElkID0gMCkge1xuICAgICAgY29uc3QgY29sb3JJbmRleCA9IHRyYWlsSWQgJSB0aGlzLmNvbG9ycy5sZW5ndGg7XG5cbiAgICAgIHJldHVybiB0aGlzLmNvbG9yc1tjb2xvckluZGV4XTtcbiAgICB9LFxuXG4gICAgLyoqXG4gICAgICogTm9kZSBmdW5jdGlvbnMuXG4gICAgICovXG4gICAgaXNUcmFpbEhlYWRlcihub2RlSWQgPSBudWxsKSB7XG4gICAgICBpZiAobm9kZUlkID09PSBudWxsKSB7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgIH1cblxuICAgICAgcmV0dXJuIGRydXBhbFNldHRpbmdzLnRyYWlsX2dyYXBoLmRhdGFTZXRzLm5vZGVzLmdldChub2RlSWQpLmlzSGVhZGVyO1xuICAgIH0sXG4gICAgbG9ja05vZGVzKCkge1xuICAgICAgZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGgubmV0d29yay5zZXRPcHRpb25zKHtcbiAgICAgICAgaW50ZXJhY3Rpb246IHtcbiAgICAgICAgICBkcmFnTm9kZXM6IGZhbHNlLFxuICAgICAgICB9XG4gICAgICB9KTtcbiAgICB9LFxuICAgIHVubG9ja05vZGVzKCkge1xuICAgICAgZHJ1cGFsU2V0dGluZ3MudHJhaWxfZ3JhcGgubmV0d29yay5zZXRPcHRpb25zKHtcbiAgICAgICAgaW50ZXJhY3Rpb246IHtcbiAgICAgICAgICBkcmFnTm9kZXM6IHRydWUsXG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH0sXG5cbiAgICAvKipcbiAgICAgKiBQcmVwYXJlIGRhdGEgZm9yIFZpcy5qcyBEYXRhIFNldHMuXG4gICAgICovXG4gICAgcHJlcGFyZU5vZGVEYXRhKG5vZGUpIHtcbiAgICAgIGlmICh0eXBlb2Ygbm9kZSA9PT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG5cbiAgICAgIGxldCBkYXRhID0ge307XG5cbiAgICAgIC8vIFRyYWlsIEhlYWRlci5cbiAgICAgIGlmIChub2RlLmlzSGVhZGVyKSB7XG4gICAgICAgIGRhdGEgPSB7XG4gICAgICAgICAgaWQ6IG5vZGUuaWQsXG4gICAgICAgICAgdGlkOiBub2RlLnRpZCxcbiAgICAgICAgICBsYWJlbDogYDxiPiR7bm9kZS50aXRsZX08L2I+YCxcbiAgICAgICAgICB0aXRsZTogYDxiPiR7bm9kZS50aXRsZX08L2I+YCxcbiAgICAgICAgICBib3JkZXJXaWR0aDogdGhpcy5zdHlsZXMuaGVhZGVyLmJvcmRlcldpZHRoLFxuICAgICAgICAgIG9yaWdpbmFsT3B0aW9uczoge1xuICAgICAgICAgICAgY29sb3I6IHtcbiAgICAgICAgICAgICAgYmFja2dyb3VuZDogdGhpcy5zdHlsZXMuaGVhZGVyLmNvbG9yLmJhY2tncm91bmQsXG4gICAgICAgICAgICB9LFxuICAgICAgICAgIH0sXG4gICAgICAgICAgY29sb3I6IHtcbiAgICAgICAgICAgIGJhY2tncm91bmQ6IHRoaXMuc3R5bGVzLmhlYWRlci5jb2xvci5iYWNrZ3JvdW5kLFxuICAgICAgICAgIH0sXG4gICAgICAgICAgbWFyZ2luOiB0aGlzLnN0eWxlcy5oZWFkZXIubWFyZ2luLFxuICAgICAgICAgIGNob3NlbjogZmFsc2UsXG4gICAgICAgICAgaXNIZWFkZXI6IHRydWUsXG4gICAgICAgIH07XG4gICAgICB9XG5cbiAgICAgIC8vIE5vZGUuXG4gICAgICBlbHNlIHtcbiAgICAgICAgZGF0YSA9IHtcbiAgICAgICAgICBpZDogbm9kZS5pZCxcbiAgICAgICAgICBsYWJlbDogYCR7bm9kZS50aXRsZS5zdWJzdHJpbmcoMCwgMTUpfS4uLmAsXG4gICAgICAgICAgdGl0bGU6IG5vZGUudGl0bGUsXG4gICAgICAgICAgcmVsYXRlZFRyYWlsczogbm9kZS50cmFpbF9maWVsZCxcbiAgICAgICAgICBjb250ZW50UHJldmlldzogbm9kZS5jb250ZW50X3ByZXZpZXcsXG4gICAgICAgICAgYm9yZGVyV2lkdGg6IHRoaXMuc3R5bGVzLm5vZGUuYm9yZGVyV2lkdGguaW5pdGlhbCxcbiAgICAgICAgICBzaGFwZVByb3BlcnRpZXM6IHtcbiAgICAgICAgICAgIGJvcmRlckRhc2hlczogKCFub2RlLnB1Ymxpc2hlZCksXG4gICAgICAgICAgfVxuICAgICAgICB9O1xuXG4gICAgICAgIC8vIFVwZGF0ZSBub2RlIHByb3BlcnRpZXMgZm9yIFNlbGVjdGVkIG5vZGUuXG4gICAgICAgIGlmIChub2RlLnNlbGVjdGVkKSB7XG4gICAgICAgICAgZGF0YS5sYWJlbCA9IGA8Yj4ke25vZGUudGl0bGUuc3Vic3RyaW5nKDAsIDE1KX0uLi48L2I+YDtcbiAgICAgICAgICBkYXRhLmJvcmRlcldpZHRoID0gdGhpcy5zdHlsZXMubm9kZS5ib3JkZXJXaWR0aC5zZWxlY3RlZDtcbiAgICAgICAgICBkYXRhLmNvbG9yID0ge1xuICAgICAgICAgICAgYm9yZGVyOiB0aGlzLnN0eWxlcy5ub2RlLmNvbG9yLmJvcmRlci5pbml0aWFsLFxuICAgICAgICAgICAgYmFja2dyb3VuZDogdGhpcy5zdHlsZXMubm9kZS5jb2xvci5iYWNrZ3JvdW5kLnNlbGVjdGVkLFxuICAgICAgICAgIH07XG4gICAgICAgICAgZGF0YS5vcmlnaW5hbE9wdGlvbnMgPSB7XG4gICAgICAgICAgICBjb2xvcjoge1xuICAgICAgICAgICAgICBib3JkZXI6IHRoaXMuc3R5bGVzLm5vZGUuY29sb3IuYm9yZGVyLmluaXRpYWwsXG4gICAgICAgICAgICAgIGJhY2tncm91bmQ6IHRoaXMuc3R5bGVzLm5vZGUuY29sb3IuYmFja2dyb3VuZC5zZWxlY3RlZCxcbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgfTtcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICByZXR1cm4gZGF0YTtcbiAgICB9LFxuICAgIHByZXBhcmVMaW5rRGF0YShvcHRpb25zKSB7XG4gICAgICBjb25zdCB7IHRyYWlsLCBsaW5rLCB0cmFpbENvbG9yLCBjaG9zZW4gfSA9IG9wdGlvbnM7XG5cbiAgICAgIHJldHVybiB7XG4gICAgICAgIHRpZDogdHJhaWwuaWQsXG4gICAgICAgIGZyb206IGxpbmsuZnJvbSxcbiAgICAgICAgdG86IGxpbmsudG8sXG4gICAgICAgIG9yaWdpbmFsT3B0aW9uczoge1xuICAgICAgICAgIGNvbG9yOiB7XG4gICAgICAgICAgICBjb2xvcjogdHJhaWxDb2xvcixcbiAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBjb2xvcjoge1xuICAgICAgICAgIGNvbG9yOiB0cmFpbENvbG9yLFxuICAgICAgICB9LFxuICAgICAgICBkYXNoZXM6IGxpbmsuYm90aE5vZGVzSGF2ZVNhbWVXZWlnaHQsXG4gICAgICAgIGNob3NlbjogY2hvc2VuLFxuICAgICAgfTtcbiAgICB9LFxuXG4gICAgLyoqXG4gICAgICogSGVscGVyIGZ1bmN0aW9ucy5cbiAgICAgKi9cbiAgICBmaW5kSW5kZXhPZk9iamVjdChvYmplY3QsIGtleSwgdmFsdWUpIHtcbiAgICAgIGxldCBpbmRleCA9IDA7XG4gICAgICBmb3IgKGxldCBbaSwgb2JqXSBvZiBvYmplY3QuZW50cmllcygpKSB7XG4gICAgICAgIGlmIChvYmpba2V5XSA9PT0gdmFsdWUpIHtcbiAgICAgICAgICBpbmRleCA9IGk7XG4gICAgICAgICAgYnJlYWs7XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgcmV0dXJuIGluZGV4O1xuICAgIH0sXG4gICAgaGV4VG9SR0IoaGV4LCBvcGFjaXR5KSB7XG4gICAgICBoZXggPSBoZXgucmVwbGFjZSgnIycsICcnKTtcbiAgICAgIGNvbnN0IHIgPSBwYXJzZUludChoZXguc3Vic3RyaW5nKDAsMiksIDE2KTtcbiAgICAgIGNvbnN0IGcgPSBwYXJzZUludChoZXguc3Vic3RyaW5nKDIsNCksIDE2KTtcbiAgICAgIGNvbnN0IGIgPSBwYXJzZUludChoZXguc3Vic3RyaW5nKDQsNiksIDE2KTtcblxuICAgICAgcmV0dXJuIGByZ2JhKCR7cn0sICR7Z30sICR7Yn0sICR7b3BhY2l0eX0pYDtcbiAgICB9LFxuICB9O1xuXG4gIC8qKlxuICAgKiBUcmFpbCBHcmFwaCBTaWRlYmFyIFRhYnMgQmVoYXZpb3VyLlxuICAgKi9cbiAgRHJ1cGFsLmJlaGF2aW9ycy50cmFpbEdyYXBoU2lkZWJhclRhYnMgPSB7XG4gICAgYXR0YWNoOiBmdW5jdGlvbiAoY29udGV4dCwgc2V0dGluZ3MpIHtcbiAgICAgICQoJyN0cmFpbC1ncmFwaC1zaWRlYmFyJywgY29udGV4dCkub25jZSgpLmVhY2goZnVuY3Rpb24gKCkge1xuXG4gICAgICAgIC8vIExpc3RlbmluZyBvbiBFdmVudHMuXG4gICAgICAgICQodGhpcywgY29udGV4dCkub24oJ2NsaWNrJywgJy52aWV3cy10cmFpbC1ncmFwaF9fc2lkZWJhcl9fdGFicyA+IGlucHV0JywgZSA9PiB7XG4gICAgICAgICAgRHJ1cGFsLmJlaGF2aW9ycy50cmFpbEdyYXBoU2lkZWJhclRhYnMuc2hvd1RhYkNvbnRlbnQoXG4gICAgICAgICAgICAkKGUuY3VycmVudFRhcmdldCkuYXR0cignaWQnKSxcbiAgICAgICAgICAgIGNvbnRleHRcbiAgICAgICAgICApO1xuICAgICAgICB9KTtcbiAgICAgIH0pO1xuICAgIH0sXG4gICAgc3dpdGNoVGFiKGlkKSB7XG4gICAgICAkKGAjJHtpZH1gKS5jbGljaygpO1xuICAgIH0sXG4gICAgc2hvd1RhYkNvbnRlbnQoaWQsIGNvbnRleHQpIHtcbiAgICAgICQoJy52aWV3cy10cmFpbC1ncmFwaF9fc2lkZWJhcl9fc2VjdGlvbicsIGNvbnRleHQpLmhpZGUoKTtcbiAgICAgICQoYC52aWV3cy10cmFpbC1ncmFwaF9fc2lkZWJhcl9fc2VjdGlvbltkYXRhLXRhYi1pZD0ke2lkfV1gLCBjb250ZXh0KS5zaG93KCk7XG4gICAgfVxuICB9XG59KShqUXVlcnksIERydXBhbCwgZHJ1cGFsU2V0dGluZ3MpO1xuIl0sInByZUV4aXN0aW5nQ29tbWVudCI6Ii8vIyBzb3VyY2VNYXBwaW5nVVJMPWRhdGE6YXBwbGljYXRpb24vanNvbjtjaGFyc2V0PXV0Zi04O2Jhc2U2NCxleUoyWlhKemFXOXVJam96TENKemIzVnlZMlZ6SWpwYkltNXZaR1ZmYlc5a2RXeGxjeTlpY205M2MyVnlMWEJoWTJzdlgzQnlaV3gxWkdVdWFuTWlMQ0pxY3k5emNtTXZZWEJ3TG1weklsMHNJbTVoYldWeklqcGJYU3dpYldGd2NHbHVaM01pT2lKQlFVRkJPenM3T3p0QlEwRkJPenM3T3pzN08wRkJUMEVzUTBGQlF5eFZRVUZWTEVOQlFWWXNSVUZCWVN4TlFVRmlMRVZCUVhGQ0xHTkJRWEpDTEVWQlFYRkRPenRCUVVWd1F6czdPenRCUVVsQkxFbEJRVVVzUlVGQlJpeERRVUZMTERSQ1FVRk1MRWRCUVc5RExGbEJRVTA3UVVGRGVFTXNVVUZCVFN4VFFVRlRMR1ZCUVdVc1pVRkJPVUk3UVVGRFFTeFJRVUZOTEZGQlFWRXNXVUZCVlN4TlFVRldMRU5CUVdRN08wRkJSVUU3UVVGRFFTeFJRVUZKTEUxQlFVMHNTVUZCVGl4RFFVRlhMSGRDUVVGWUxFTkJRVW9zUlVGQk1FTTdRVUZEZUVNc1dVRkJUU3hKUVVGT0xFTkJRVmNzZDBKQlFWZ3NSVUZCY1VNc1QwRkJja01zUTBGQk5rTXNUMEZCTjBNN1FVRkRRVHRCUVVORU96dEJRVVZFTzBGQlEwRXNWVUZCVFN4TlFVRk9PMEZCUTBRc1IwRmFSRHM3UVVGalFUczdPenRCUVVsQkxGTkJRVThzVTBGQlVDeERRVUZwUWl4VlFVRnFRaXhIUVVFNFFqdEJRVU0xUWl4WlFVRlJPMEZCUTA0c1dVRkJUVHRCUVVOS0xHVkJRVTg3UVVGRFRDeHpRa0ZCV1R0QlFVTldMSEZDUVVGVExGTkJSRU03UVVGRlZpeHpRa0ZCVlN4VFFVWkJPMEZCUjFZc2VVSkJRV0U3UVVGSVNDeFhRVVJRTzBGQlRVd3NhMEpCUVZFN1FVRkRUaXh4UWtGQlV5eFRRVVJJTzBGQlJVNHNlVUpCUVdFN1FVRkdVRHRCUVU1SUxGTkJSRWc3UVVGWlNpeHhRa0ZCWVR0QlFVTllMRzFDUVVGVExFTkJSRVU3UVVGRldDeHZRa0ZCVlR0QlFVWkRMRk5CV2xRN1FVRm5Ra29zWTBGQlRUdEJRVU5LTEdsQ1FVRlBPMEZCUTB3c2NVSkJRVk1zVTBGRVNqdEJRVVZNTEhsQ1FVRmhPMEZCUmxJN1FVRkVTQ3hUUVdoQ1JqdEJRWE5DU2l4eFFrRkJZVHRCUVhSQ1ZDeFBRVVJCTzBGQmVVSk9MR05CUVZFN1FVRkRUaXhsUVVGUE8wRkJRMHdzYzBKQlFWazdRVUZFVUN4VFFVUkVPMEZCU1U0c2NVSkJRV0VzUTBGS1VEdEJRVXRPTEdkQ1FVRlJPMEZCVEVZc1QwRjZRa1k3UVVGblEwNHNXVUZCVFR0QlFVTktMR1ZCUVU4c1EwRkVTRHRCUVVWS0xHVkJRVTg3UVVGRFRDeHRRa0ZCVXp0QlFVTlFMSEZDUVVGVExFTkJSRVk3UVVGRlVDeDVRa0ZCWVR0QlFVWk9PMEZCUkVvN1FVRkdTQ3hQUVdoRFFUdEJRWGxEVGl4aFFVRlBPMEZCUTB3c1pVRkJUenRCUVVSR08wRkJla05FTEV0QlJHOUNPMEZCT0VNMVFpeFpRVUZSTEVOQlEwNHNVMEZFVFN4RlFVVk9MRk5CUmswc1JVRkhUaXhUUVVoTkxFVkJTVTRzVTBGS1RTeEZRVXRPTEZOQlRFMHNSVUZOVGl4VFFVNU5MRVZCVDA0c1UwRlFUU3hGUVZGT0xGTkJVazBzUlVGVFRpeFRRVlJOTEVWQlZVNHNVMEZXVFN4RlFWZE9MRk5CV0Uwc1JVRlpUaXhUUVZwTkxFVkJZVTRzVTBGaVRTeEZRV05PTEZOQlpFMHNSVUZsVGl4VFFXWk5MRVZCWjBKT0xGTkJhRUpOTEVWQmFVSk9MRk5CYWtKTkxFVkJhMEpPTEZOQmJFSk5MRVZCYlVKT0xGTkJia0pOTEVWQmIwSk9MRk5CY0VKTkxFVkJjVUpPTEZOQmNrSk5MRVZCYzBKT0xGTkJkRUpOTEVWQmRVSk9MRk5CZGtKTkxFVkJkMEpPTEZOQmVFSk5MRVZCZVVKT0xGTkJla0pOTEVWQk1FSk9MRk5CTVVKTkxFVkJNa0pPTEZOQk0wSk5MRVZCTkVKT0xGTkJOVUpOTEVWQk5rSk9MRk5CTjBKTkxFVkJPRUpPTEZOQk9VSk5MRVZCSzBKT0xGTkJMMEpOTEVWQlowTk9MRk5CYUVOTkxFVkJhVU5PTEZOQmFrTk5MRVZCYTBOT0xGTkJiRU5OTEVWQmJVTk9MRk5CYmtOTkxFTkJPVU52UWp0QlFXMUdOVUlzV1VGQlVTeG5Ra0ZCVlN4UFFVRldMRVZCUVcxQ0xGRkJRVzVDTEVWQlFUWkNPMEZCUTI1RExGRkJRVVVzZFVKQlFVWXNSVUZCTWtJc1QwRkJNMElzUlVGQmIwTXNTVUZCY0VNc1IwRkJNa01zU1VGQk0wTXNRMEZCWjBRc1dVRkJXVHRCUVVNeFJDeFpRVUZOTEU5QlFVOHNUMEZCVHl4SlFVRlFMRU5CUVZrc1UwRkJVeXhYUVVGVUxFTkJRWEZDTEVsQlFXcERMRU5CUVdJN1FVRkRRU3haUVVGTkxIRkNRVUZ4UWl4VFFVRlRMRmRCUVZRc1EwRkJjVUlzU1VGQmNrSXNRMEZCTUVJc1MwRkJTeXhMUVVGTExFMUJRVXdzUjBGQll5eERRVUZ1UWl4RFFVRXhRaXhEUVVFelFqdEJRVVl3UkN4WlFVZHNSQ3hOUVVoclJDeEhRVWRzUWl4clFrRklhMElzUTBGSGJFUXNUVUZJYTBRN1FVRkJRU3haUVVjeFF5eExRVWd3UXl4SFFVZHNRaXhyUWtGSWEwSXNRMEZITVVNc1MwRklNRU03UVVGQlFTeFpRVWR1UXl4WlFVaHRReXhIUVVkc1FpeHJRa0ZJYTBJc1EwRkhia01zV1VGSWJVTTdPMEZCU1RGRUxHVkJRVThzVTBGQlVDeERRVUZwUWl4VlFVRnFRaXhEUVVFMFFpeFhRVUUxUWl4RFFVRjNReXhKUVVGNFF5eEZRVUU0UXl4TFFVRTVReXhGUVVGeFJDeE5RVUZ5UkN4RlFVRTJSQ3haUVVFM1JEdEJRVU5FTEU5QlRFUTdRVUZOUkN4TFFURkdNa0k3TzBGQk5FWTFRanM3T3pzN096czdRVUZSUVN4bFFYQkhORUlzZFVKQmIwZG9RaXhQUVhCSFowSXNSVUZ2UjFBc1MwRndSMDhzUlVGdlIwRXNUVUZ3UjBFc1JVRnZSMUVzV1VGd1IxSXNSVUZ2UjNOQ08wRkJRVUU3TzBGQlEyaEVMRlZCUVUwc1YwRkJWenRCUVVObUxHVkJRVThzU1VGQlNTeEpRVUZKTEU5QlFWSXNSVUZFVVR0QlFVVm1MR1ZCUVU4c1NVRkJTU3hKUVVGSkxFOUJRVkk3UVVGR1VTeFBRVUZxUWp0QlFVbEJMRlZCUVUwc1pVRkJaU3hGUVVGRkxFZEJRVWNzUjBGQlRDeEZRVUZWTEVkQlFVY3NSMEZCWWl4RlFVRnlRanRCUVVOQkxGVkJRVTBzYjBKQlFXOUNMRVZCUVRGQ08wRkJRMEVzVlVGQlRTeGhRVUZoTEVWQlFXNUNPMEZCUTBFc1ZVRkJUU3h2UWtGQmNVSXNUMEZCVHl4TlFVRlFMRWRCUVdkQ0xFTkJRVE5ET3p0QlFVVkJPMEZCUTBFc1ZVRkJTU3hOUVVGTkxFMUJRVllzUlVGQmEwSTdRVUZEYUVJc1kwRkJUU3hQUVVGT0xFTkJRV003UVVGQlFTeHBRa0ZCVVN4VFFVRlRMRXRCUVZRc1EwRkJaU3hIUVVGbUxFTkJRM0JDTEUxQlFVc3NaVUZCVEN4RFFVRnhRaXhKUVVGeVFpeERRVVJ2UWl4RFFVRlNPMEZCUVVFc1UwRkJaRHRCUVVkRU96dEJRVVZFTzBGQlEwRXNWVUZCU1N4UFFVRlBMRTFCUVZnc1JVRkJiVUk3UVVGRGFrSXNaVUZCVHl4UFFVRlFMRU5CUVdVc1ZVRkJReXhMUVVGRUxFVkJRVkVzVlVGQlVpeEZRVUYxUWp0QlFVTndReXhqUVVGSkxFMUJRVTBzUzBGQlRpeERRVUZaTEUxQlFXaENMRVZCUVhkQ096dEJRVVYwUWp0QlFVTkJMR2RDUVVGTkxHRkJRMG9zVFVGQlRTeExRVUZPTEVsQlFXVXNTVUZCWml4SFFVRnpRaXhOUVVGTExHdENRVUZNTEVOQlFYZENMRTFCUVUwc1IwRkJPVUlzUTBGQmRFSXNSMEZCTWtRc1RVRkJUU3hMUVVSdVJUczdRVUZKUVR0QlFVTkJMR2RDUVVGTkxHTkJRV01zVTBGQlV5eExRVUZVTEVOQlFXVXNSMEZCWml4RFFVRnRRaXhOUVVGTkxFVkJRWHBDTEVOQlFYQkNPMEZCUTBFc2QwSkJRVmtzUzBGQldpeERRVUZyUWl4TlFVRnNRaXhIUVVFeVFpeFZRVUV6UWp0QlFVTkJMSGRDUVVGWkxHVkJRVm9zUTBGQk5FSXNTMEZCTlVJc1EwRkJhME1zVFVGQmJFTXNSMEZCTWtNc1ZVRkJNME03UVVGRFFTdzRRa0ZCYTBJc1NVRkJiRUlzUTBGQmRVSXNWMEZCZGtJN08wRkJSVUVzWjBKQlFVMHNWMEZCVnp0QlFVTm1MR2xDUVVGSExHRkJRV0VzUTBGQllpeEhRVUZwUWl4VlFVUk1PMEZCUldZc2FVSkJRVWM3UVVGR1dTeGhRVUZxUWpzN1FVRkxRVHRCUVVOQkxHdENRVUZOTEV0QlFVNHNRMEZCV1N4UFFVRmFMRU5CUVc5Q0xGVkJRVU1zU1VGQlJDeEZRVUZQTEZOQlFWQXNSVUZCY1VJN1FVRkRka01zYTBKQlFVMHNZMEZCWXp0QlFVTnNRaXcwUWtGRWEwSTdRVUZGYkVJc01FSkJSbXRDTzBGQlIyeENMSE5EUVVoclFqdEJRVWxzUWl4M1FrRkJVU3hqUVVGakxFTkJRV1FzUjBGQmEwSXNSVUZCUlN4TlFVRk5MRXRCUVZJc1JVRkJiRUlzUjBGQmIwTTdRVUZLTVVJc1pVRkJjRUk3TzBGQlQwRXNkVUpCUVZNc1MwRkJWQ3hEUVVGbExFZEJRV1lzUTBGRFJTeE5RVUZMTEdWQlFVd3NRMEZCY1VJc1YwRkJja0lzUTBGRVJqczdRVUZKUVR0QlFVTkJMSFZDUVVGVExFTkJRVlFzU1VGQll5eGhRVUZoTEVOQlFUTkNPenRCUVVWQk8wRkJRMEVzZVVKQlFWY3NTVUZCV0N4RFFVRm5RanRCUVVOa0xHOUNRVUZKTEV0QlFVc3NTVUZFU3p0QlFVVmtMRzFDUVVGSExGTkJRVk1zUTBGR1JUdEJRVWRrTEcxQ1FVRkhMRk5CUVZNN1FVRklSU3hsUVVGb1FqczdRVUZOUVR0QlFVTkJMR3RDUVVOSkxHTkJRV01zVFVGQlRTeExRVUZPTEVOQlFWa3NUVUZCV2l4SFFVRnhRaXhEUVVGdVF5eERRVUZ4UXp0QlFVRnlReXhwUWtGRlFTeE5RVUZOTEV0QlFVNHNRMEZCV1N4TlFVRmFMRXRCUVhWQ0xFTkJTRE5DTEVOQlJ6WkNPMEZCU0RkQ0xHZENRVWxKTzBGQlEwb3NOa0pCUVZjc1NVRkJXQ3hEUVVGblFqdEJRVU5hTEhkQ1FVRkpMRXRCUVVzc1JVRkVSenRCUVVWYUxIVkNRVUZITEZOQlFWTXNRMEZHUVR0QlFVZGFMSFZDUVVGSExGTkJRVk1zUTBGQlZDeEhRVUZoTEdGQlFXRTdRVUZJYWtJc2JVSkJRV2hDTzBGQlMwTTdRVUZEUml4aFFXeERSRHRCUVcxRFJEczdRVUZGUkR0QlFVTkJMRzFDUVVGVExFdEJRVlFzUTBGQlpTeE5RVUZtTEVOQlFYTkNMR2xDUVVGMFFqdEJRVU5CTEcxQ1FVRlRMRXRCUVZRc1EwRkJaU3hOUVVGbUxFTkJRWE5DTEZWQlFYUkNPMEZCUTBRc1UwRTFSRVE3UVVFMlJFUTdPMEZCUlVRc1YwRkJTeXh2UWtGQlREdEJRVU5CTEZkQlFVc3NZMEZCVEN4RFFVRnZRaXhQUVVGd1FpeEZRVUUyUWl4UlFVRTNRanM3UVVGRlFUdEJRVU5CTEhGQ1FVRmxMRmRCUVdZc1EwRkJNa0lzVVVGQk0wSXNSMEZCYzBNc1VVRkJkRU03UVVGRFFTeHhRa0ZCWlN4WFFVRm1MRU5CUVRKQ0xFMUJRVE5DTEVkQlFXOURMRTFCUVhCRE8wRkJRMEVzY1VKQlFXVXNWMEZCWml4RFFVRXlRaXhwUWtGQk0wSXNSMEZCSzBNc2FVSkJRUzlETzBGQlEwRXNjVUpCUVdVc1YwRkJaaXhEUVVFeVFpeFpRVUV6UWl4SFFVRXdReXhaUVVFeFF6dEJRVU5FTEV0Qk9Vd3lRanM3TzBGQlowMDFRanM3T3pzN08wRkJUVUVzYTBKQmRFMDBRaXd3UWtGelRXSXNUMEYwVFdFc1JVRnpUVW9zVVVGMFRVa3NSVUZ6VFUwN1FVRkRhRU1zVlVGQlRTeFRRVUZUTEV0QlFVc3NUVUZCY0VJN1FVRkRRU3hWUVVGTkxFOUJRVTg3UVVGRFdDeGxRVUZQTEZOQlFWTXNTMEZFVER0QlFVVllMR1ZCUVU4c1UwRkJVenRCUVVaTUxFOUJRV0k3UVVGSlFTeFZRVUZOTEZWQlFWVTdRVUZEWkN4dlFrRkJXU3hKUVVSRk8wRkJSV1FzWlVGQlR5eE5RVVpQTzBGQlIyUXNaMEpCUVZFc1QwRklUVHRCUVVsa0xHbENRVUZUTEV0QlNrczdRVUZMWkN4blFrRkJVVHRCUVVOT0xIZENRVUZqTzBGQlJGSXNVMEZNVFR0QlFWRmtMR1ZCUVU4N1FVRkRUQ3hwUWtGQlR5eExRVVJHTzBGQlJVd3NNa0pCUVdsQ08wRkJRMllzY1VKQlFWTXNUMEZCVHl4SlFVRlFMRU5CUVZrN1FVRkVUaXhYUVVaYU8wRkJTMHdzWjBKQlFVMDdRVUZEU2l4dFFrRkJUenRCUVVSSUxGZEJURVE3UVVGUlRDeHBRa0ZCVHp0QlFVTk1MRzlDUVVGUkxFOUJRVThzU1VGQlVDeERRVUZaTEV0QlFWb3NRMEZCYTBJc1RVRkJiRUlzUTBGQmVVSXNUMEZFTlVJN1FVRkZUQ3gzUWtGQldTeFBRVUZQTEVsQlFWQXNRMEZCV1N4TFFVRmFMRU5CUVd0Q0xGVkJRV3hDTEVOQlFUWkNMRTlCUm5CRE8wRkJSMHdzZFVKQlFWYzdRVUZEVkN4elFrRkJVU3hQUVVGUExFbEJRVkFzUTBGQldTeExRVUZhTEVOQlFXdENMRTFCUVd4Q0xFTkJRWGxDTEZkQlJIaENPMEZCUlZRc01FSkJRVmtzVDBGQlR5eEpRVUZRTEVOQlFWa3NTMEZCV2l4RFFVRnJRaXhWUVVGc1FpeERRVUUyUWp0QlFVWm9RenRCUVVoT0xGZEJVa1k3UVVGblFrd3NhVUpCUVU4N1FVRm9Ra1lzVTBGU1R6dEJRVEJDWkN4bFFVRlBPMEZCUTB3c2FVSkJRVThzVDBGQlR5eEpRVUZRTEVOQlFWa3NTMEZFWkR0QlFVVk1MR3RDUVVGUk8wRkJSa2c3UVVFeFFrOHNUMEZCYUVJN08wRkJaME5CTEZkQlFVc3NZMEZCVEN4RFFVRnZRaXhQUVVGd1FpeEZRVUUyUWl4SlFVRTNRaXhGUVVGdFF5eFBRVUZ1UXp0QlFVTkVMRXRCTjA4eVFqczdPMEZCSzA4MVFqczdPenM3T3p0QlFVOUJMR3RDUVhSUU5FSXNNRUpCYzFCaUxFOUJkRkJoTEVWQmMxQktMRWxCZEZCSkxFVkJjMUJGTEU5QmRGQkdMRVZCYzFCWE8wRkJRVUU3TzBGQlEzSkRMRlZCUVUwc1ZVRkJWU3hKUVVGSkxFbEJRVWtzVDBGQlVpeERRVU5rTEVWQlFVVXNjVUpCUVVZc1JVRkJlVUlzVDBGQmVrSXNSVUZCYTBNc1EwRkJiRU1zUTBGRVl5eEZRVVZrTEVsQlJtTXNSVUZIWkN4UFFVaGpMRU5CUVdoQ096dEJRVTFCTzBGQlEwRXNjVUpCUVdVc1YwRkJaaXhEUVVFeVFpeFBRVUV6UWl4SFFVRnhReXhQUVVGeVF6dEJRVU5CTEhGQ1FVRmxMRmRCUVdZc1EwRkJNa0lzWTBGQk0wSXNSMEZCTkVNc1MwRkJOVU03TzBGQlJVRTdRVUZEUVR0QlFVTkJMR05CUVZFc1JVRkJVaXhEUVVGWExGZEJRVmdzUlVGQmQwSXNhMEpCUVZVN1FVRkRhRU1zV1VGQlRTeHBRa0ZCYVVJc1VVRkJVU3hUUVVGU0xFTkJRV3RDTEU5QlFVOHNUMEZCVUN4RFFVRmxMRWRCUVdwRExFTkJRWFpDTzBGQlEwRXNXVUZCVFN4WFFVRlhMR1ZCUVdVc1YwRkJaaXhEUVVFeVFpeFJRVUUxUXp0QlFVTkJMRmxCUVUwc1pVRkJaU3hGUVVGRkxFMUJRVVlzUTBGQmNrSTdPMEZCUlVFc1dVRkJTU3hqUVVGS0xFVkJRVzlDTzBGQlEyeENMR05CUVUwc1QwRkJUeXhUUVVGVExFdEJRVlFzUTBGQlpTeEhRVUZtTEVOQlFXMUNMR05CUVc1Q0xFTkJRV0k3TzBGQlJVRXNZMEZCU1N4TFFVRkxMRkZCUVZRc1JVRkJiVUk3UVVGQlJUdEJRVU51UWl4NVFrRkJZU3hOUVVGaUxFTkJRMFVzUlVGQlJTeE5RVUZHTEVWQlFWVXNUVUZCVml4RFFVTkZMRTlCUVVzc01FSkJRVXdzUTBGRFJTeFBRVUZQTEVOQlFWQXNRMEZCVXl4eFFrRkJWQ3hEUVVSR0xFVkJSVVVzWTBGR1JpeERRVVJHTEVOQlJFWXNSVUZQUlN4RlFVRkZMRTFCUVVZc1EwRlFSaXhGUVZGRkxFVkJRVVVzVFVGQlJpeEZRVUZWTEUxQlFWWXNRMEZEUlN4UFFVRkxMRzFDUVVGTUxFTkJRMFVzVDBGQlR5eERRVUZRTEVOQlFWTXNhMEpCUVZRc1EwRkVSaXdyUWtGRk5rSXNTMEZCU3l4SFFVWnNReXhGUVVkRkxFOUJTRVlzUTBGRVJpeERRVkpHTzBGQlowSkVMRmRCYWtKRUxFMUJhMEpMTzBGQlEwZzdRVUZEUVN4NVFrRkJZU3hOUVVGaUxFTkJRMFVzUlVGQlJTeE5RVUZHTEVWQlFWVXNUVUZCVml4RFFVRnBRaXhMUVVGTExHTkJRWFJDTEVOQlJFWXNSVUZGUlN4RlFVRkZMRTFCUVVZc1EwRkdSaXhGUVVkRkxFVkJRVVVzVFVGQlJpeEZRVUZWTEUxQlFWWXNRMEZEUlN4UFFVRkxMR1ZCUVV3c1EwRkRSU3hQUVVGUExFTkJRVkFzUTBGQlV5eFhRVUZVTEVOQlJFWXNZVUZGVnl4alFVWllMRFJDUVVkRkxFVkJRVVVzVDBGQlR5eExRVUZVTEVWQlNFWXNRMEZFUml4RFFVaEdPMEZCVjBRN08wRkJSVVFzYVVKQlFVc3NhMEpCUVV3c1EwRkRSVHRCUVVORkxHVkJRVWNzVDBGQlR5eExRVUZRTEVOQlFXRXNRMEZFYkVJN1FVRkZSU3hsUVVGSExFOUJRVThzUzBGQlVDeERRVUZoTzBGQlJteENMRmRCUkVZc1JVRkxSU3hQUVV4R0xFVkJUVVVzV1VGT1JqdEJRVkZFTzBGQlEwWXNUMEZzUkVRN1FVRnRSRUVzWTBGQlVTeEZRVUZTTEVOQlFWY3NXVUZCV0N4RlFVRjVRaXhyUWtGQlZUdEJRVU5xUXl4WlFVRk5MRk5CUVZVc1QwRkJUeXhMUVVGUUxFTkJRV0VzVFVGQllpeEhRVUZ6UWl4RFFVRjBRaXhIUVVFd1FpeFBRVUZQTEV0QlFWQXNRMEZCWVN4RFFVRmlMRU5CUVRGQ0xFZEJRVFJETEVsQlFUVkVPenRCUVVWQkxGbEJRVWtzVjBGQlZ5eEpRVUZZTEVsQlFXMUNMRTlCUVVzc1lVRkJUQ3hEUVVGdFFpeE5RVUZ1UWl4RFFVRjJRaXhGUVVGdFJEdEJRVU5xUkN4cFFrRkJTeXhqUVVGTUxFTkJRVzlDTEUxQlFYQkNMRVZCUTBNc1NVRkVSQ3hEUVVOTk8wRkJRVUVzYlVKQlFVMHNUMEZCU3l4VFFVRk1MRVZCUVU0N1FVRkJRU3hYUVVST0xFVkJSVU1zUzBGR1JDeERRVVZQTEdWQlFVODdRVUZEV2l4dlFrRkJVU3hMUVVGU0xFTkJRV01zUjBGQlpEdEJRVU5FTEZkQlNrUTdRVUZMUkR0QlFVTkdMRTlCVmtRN1FVRlhRU3hqUVVGUkxFVkJRVklzUTBGQlZ5eGpRVUZZTEVWQlFUSkNMRmxCUVUwN1FVRkRMMElzWlVGQlN5eHRRa0ZCVEN4SFFVRXlRaXhKUVVFelFpeERRVU5GTzBGQlFVRXNhVUpCUVUwc1QwRkJTeXhYUVVGTUxFVkJRVTQ3UVVGQlFTeFRRVVJHTzBGQlIwUXNUMEZLUkR0QlFVdEJMR05CUVZFc1JVRkJVaXhEUVVGWExGZEJRVmdzUlVGQmQwSXNXVUZCVFR0QlFVTTFRaXhWUVVGRkxIRkNRVUZHTEVWQlFYbENMRTlCUVhwQ0xFVkJRV3RETEVkQlFXeERMRU5CUVhORExGRkJRWFJETEVWQlFXZEVMRTFCUVdoRU8wRkJRMFFzVDBGR1JEdEJRVWRCTEdOQlFWRXNSVUZCVWl4RFFVRlhMRk5CUVZnc1JVRkJjMElzV1VGQlRUdEJRVU14UWl4VlFVRkZMSEZDUVVGR0xFVkJRWGxDTEU5QlFYcENMRVZCUVd0RExFZEJRV3hETEVOQlFYTkRMRkZCUVhSRExFVkJRV2RFTEZOQlFXaEVPMEZCUTBRc1QwRkdSRHRCUVVkQkxHTkJRVkVzUlVGQlVpeERRVUZYTEdOQlFWZ3NSVUZCTWtJc1dVRkJUVHM3UVVGRkwwSTdRVUZEUVN4WlFVTkpMR1ZCUVdVc1YwRkJaaXhEUVVFeVFpeHBRa0ZCTTBJc1NVRkRRU3hEUVVGRExHVkJRV1VzVjBGQlppeERRVUV5UWl4alFVWm9ReXhGUVVkSk8wRkJRMG83UVVGRFJTeDVRa0ZCWlN4WFFVRm1MRU5CUVRKQ0xHTkJRVE5DTEVkQlFUUkRMRWxCUVRWRE96dEJRVVZCTEdOQlFVa3NaVUZCWlN4WFFVRm1MRU5CUVRKQ0xGbEJRVE5DTEVOQlFYZERMRlZCUVhoRExFTkJRVW9zUlVGQmVVUTdRVUZEZGtRc2JVSkJRVXNzT0VKQlFVd3NRMEZCYjBNc1pVRkJaU3hYUVVGbUxFTkJRVEpDTEZsQlFUTkNMRU5CUVhkRExGVkJRWGhETEVWQlFXOUVMRU5CUVhCRUxFTkJRWEJETzBGQlEwUTdRVUZEUmp0QlFVTkdMRTlCWkVRN1FVRmxSQ3hMUVROV01rSTdPenRCUVRaV05VSTdPenRCUVVkQkxIZENRV2hYTkVJc2EwTkJaMWRNTzBGQlEzSkNMRkZCUVVVc1VVRkJSaXhGUVVGWkxFbEJRVm9zUTBGQmFVSXNZVUZCYWtJc1JVRkJaME03UVVGQlFTeGxRVUZMTEVWQlFVVXNZMEZCUml4RlFVRk1PMEZCUVVFc1QwRkJhRU03UVVGRFJDeExRV3hYTWtJN1FVRnRWelZDTEhOQ1FXNVhORUlzT0VKQmJWZFVMRkZCYmxkVExFVkJiVmRETEU5QmJsZEVMRVZCYlZkVkxFOUJibGRXTEVWQmJWZHRRanRCUVVNM1F5eFJRVUZGTEN0Q1FVRkdMRVZCUVcxRExFOUJRVzVETEVWQlEwY3NTMEZFU0N4SFFVVkhMRTFCUmtnc1EwRkZWU3hQUVVaV0xFVkJSMGNzUjBGSVNDeERRVWRQTzBGQlEwZ3NaMEpCUVZFc1UwRkJVeXhEUVVSa08wRkJSVWdzWlVGQlR5eFRRVUZUTzBGQlJtSXNUMEZJVUN4RlFVOUhMRTFCVUVnc1EwRlBWU3hIUVZCV0xFVkJUMlVzUzBGQlN5eGhRVUZNTEVOQlFXMUNMRTlCUVc1Q0xFTkJVR1k3TzBGQlUwRXNZVUZCVHl4bFFVRlFPMEZCUTBRc1MwRTVWekpDTzBGQksxYzFRaXhwUWtFdlZ6UkNMSGxDUVN0WFpDeFBRUzlYWXl4RlFTdFhURHRCUVVOeVFpeFJRVUZGTEZGQlFVWXNSVUZCV1N4RlFVRmFMRU5CUVdVc1QwRkJaaXhGUVVGM1FpeFpRVUZOTzBGQlF6VkNMRlZCUVVVc0swSkJRVVlzUlVGQmJVTXNUMEZCYmtNc1JVRkJORU1zU1VGQk5VTTdRVUZEUVN4VlFVRkZMRkZCUVVZc1JVRkJXU3hIUVVGYUxFTkJRV2RDTEU5QlFXaENPMEZCUTBRc1QwRklSRHRCUVVsRUxFdEJjRmd5UWp0QlFYRllOVUlzYlVKQmNsZzBRaXd5UWtGeFdGb3NTMEZ5V0Zrc1JVRnhXRXdzUjBGeVdFc3NSVUZ4V0VFc1QwRnlXRUVzUlVGeFdGTTdRVUZEYmtNc1lVRkJUeXhGUVVGRkxFdEJRVVlzUlVGRFRpeEpRVVJOTEVOQlEwUXNUVUZFUXl4RlFVTlBMRWRCUkZBc1JVRkZUaXhKUVVaTkxFTkJSVVFzUzBGR1F5eEZRVWRPTEZGQlNFMHNRMEZIUnl4VlFVaElMRVZCU1U0c1NVRktUU3hEUVVsRU8wRkJRMG9zZFVKQlFXVXNUMEZFV0R0QlFVVktMREJDUVVGclFqdEJRVVprTEU5QlNrTXNRMEZCVUR0QlFWRkVMRXRCT1ZneVFqdEJRU3RZTlVJc2VVSkJMMWcwUWl4cFEwRXJXRTRzVlVFdldFMHNSVUVyV0Uwc1UwRXZXRTRzUlVFcldHbENMR0ZCTDFocVFpeEZRU3RZWjBNN1FVRkJRVHM3UVVGRE1VUXNWVUZCU1N4UFFVRlBMR0ZCUVZBc1MwRkJlVUlzVVVGQk4wSXNSVUZCZFVNN1FVRkRja01zZDBKQlFXZENMRVZCUVVVc1lVRkJSaXhEUVVGb1FqdEJRVU5FT3p0QlFVVkVMR0ZCUVU4c1JVRkJSU3hMUVVGR0xFVkJRMDRzU1VGRVRTeERRVU5FTEUxQlJFTXNSVUZEVHl4SFFVUlFMRVZCUlU0c1NVRkdUU3hEUVVWRUxGTkJSa01zUlVGSFRpeEZRVWhOTEVOQlIwZ3NUMEZJUnl4RlFVZE5PMEZCUVVFc1pVRkJUU3hQUVVGTExHVkJRVXdzUTBGQmNVSXNWVUZCY2tJc1JVRkJhVU1zWVVGQmFrTXNRMEZCVGp0QlFVRkJMRTlCU0U0c1EwRkJVRHRCUVVsRUxFdEJlRmt5UWp0QlFYbFpOVUlzT0VKQmVsazBRaXh6UTBGNVdVUXNVMEY2V1VNc1JVRjVXVlVzVFVGNldWWXNSVUY1V1d0Q08wRkJRVUU3TzBGQlF6VkRMR0ZCUVU4c1JVRkJSU3hMUVVGR0xFVkJRMDRzU1VGRVRTeERRVU5FTEUxQlJFTXNSVUZEVHl4SFFVUlFMRVZCUlU0c1NVRkdUU3hEUVVWRUxGTkJSa01zUlVGSFRpeEZRVWhOTEVOQlIwZ3NUMEZJUnl4RlFVZE5PMEZCUVVFc1pVRkJUU3hQUVVGTExEaENRVUZNTEVOQlFXOURMRTFCUVhCRExFVkJRVFJETEVsQlFUVkRMRU5CUVU0N1FVRkJRU3hQUVVoT0xFTkJRVkE3UVVGSlJDeExRVGxaTWtJN1FVRXJXVFZDTEhWQ1FTOVpORUlzSzBKQksxbFNMRk5CTDFsUkxFVkJLMWxITEZGQkwxbElMRVZCSzFsaExFOUJMMWxpTEVWQksxbHpRanRCUVVGQk96dEJRVU5vUkN4aFFVRlBMRVZCUVVVc1MwRkJSaXhGUVVOT0xFbEJSRTBzUTBGRFJDeE5RVVJETEVWQlEwOHNSMEZFVUN4RlFVVk9MRWxCUmswc1EwRkZSQ3hUUVVaRExFVkJSMDRzUlVGSVRTeERRVWRJTEU5QlNFY3NSVUZIVFR0QlFVRkJMR1ZCUVUwc1QwRkJTeXhoUVVGTUxFTkJRVzFDTEZGQlFXNUNMRVZCUVRaQ0xFOUJRVGRDTEVOQlFVNDdRVUZCUVN4UFFVaE9MRU5CUVZBN1FVRkpSQ3hMUVhCYU1rSTdPenRCUVhOYU5VSTdPenRCUVVkQkxHMUNRWHBhTkVJc01rSkJlVnBhTEV0QmVscFpMRVZCZVZwTUxFOUJlbHBMTEVWQmVWcEpPMEZCUXpsQ0xGVkJRVTBzVVVGQlVTeEZRVUZGTEU5QlFVWXNSVUZCVnl4SlFVRllMRU5CUVdkQ0xFOUJRV2hDTEVOQlFXUTdRVUZEUVN4VlFVRk5MRlZCUVZVN1FVRkRaQ3hsUVVGUExFdEJSRTg3UVVGRlpDeGxRVUZQTEV0QlFVc3NUVUZCVEN4RFFVRlpMRXRCUVZvc1EwRkJhMEk3UVVGR1dDeFBRVUZvUWpzN1FVRkxRU3hoUVVGUExFMUJRVkFzUTBGQll5eExRVUZrTEVWQlFYRkNMRTlCUVhKQ0xFVkJRVGhDTEZOQlFUbENPMEZCUTBRc1MwRnFZVEpDT3pzN1FVRnRZVFZDT3pzN1FVRkhRU3hwUWtGMFlUUkNMSGxDUVhOaFpDeFJRWFJoWXl4RlFYTmhTanRCUVVOMFFpeGhRVUZQTEVsQlFWQXNRMEZCV1R0QlFVTldMR0ZCUVVzN1FVRkVTeXhQUVVGYUxFVkJSVWNzVDBGR1NEczdRVUZKUVR0QlFVTkJMR0ZCUVU4c1UwRkJVQ3hEUVVGcFFpeHhRa0ZCYWtJc1EwRkJkVU1zVTBGQmRrTXNRMEZCYVVRc2JVSkJRV3BFTzBGQlEwUXNTMEUzWVRKQ08wRkJPR0UxUWl4clFrRTVZVFJDTERSQ1FUaGhSVHRCUVVGQk96dEJRVUZCTEZWQlFXWXNUVUZCWlN4MVJVRkJUaXhKUVVGTk96dEJRVU0xUWl4aFFVRlBMRWxCUVVrc1QwRkJTaXhEUVVGWkxGVkJRVU1zVDBGQlJDeEZRVUZWTEUxQlFWWXNSVUZCY1VJN1FVRkRkRU1zV1VGQlNTeFhRVUZYTEVsQlFXWXNSVUZCY1VJN1FVRkRia0lzYVVKQlFVOHNjVU5CUVZBN1FVRkRSRHM3UVVGRlJDeFpRVUZOTEZkQlFWY3NaVUZCWlN4WFFVRm1MRU5CUVRKQ0xGRkJRVFZETzBGQlEwRXNXVUZCVFN4UlFVRlJMRk5CUVZNc1MwRkJWQ3hEUVVGbExFZEJRV1lzUlVGQlpEdEJRVU5CTEZsQlFVMHNVVUZCVVN4VFFVRlRMRXRCUVZRc1EwRkJaU3hIUVVGbUxFVkJRV1E3UVVGRFFTeFpRVUZOTEdGQlFXRXNSVUZCYmtJN1FVRkRRU3haUVVGTkxHRkJRV0VzUlVGQmJrSTdPMEZCUlVFc1kwRkJUU3hQUVVGT0xFTkJRV01zWjBKQlFWRTdRVUZEY0VJc1kwRkJTU3hMUVVGTExFZEJRVXdzUzBGQllTeE5RVUZxUWl4RlFVRjVRanRCUVVOMlFpeDFRa0ZCVnl4SlFVRllMRU5CUVdkQ08wRkJRMlFzYTBKQlFVa3NTMEZCU3l4RlFVUkxPMEZCUldRc2NVSkJRVTg3UVVGRFRDeDVRa0ZCVXl4UFFVRkxMRTFCUVV3c1EwRkJXU3hKUVVGYUxFTkJRV2xDTEV0QlFXcENMRU5CUVhWQ0xFOUJRWFpDTEVOQlFTdENPMEZCUkc1RE8wRkJSazhzWVVGQmFFSTdRVUZOUkR0QlFVTkdMRk5CVkVRN1FVRlZRU3hqUVVGTkxFOUJRVTRzUTBGQll5eG5Ra0ZCVVR0QlFVTndRaXhqUVVGSkxHVkJRV1VzUzBGQmJrSTdRVUZEUVN4alFVRk5MRTlCUVU4N1FVRkRXQ3huUWtGQlNTeExRVUZMTEVWQlJFVTdRVUZGV0N4clFrRkJUU3hGUVVGRkxFOUJRVThzVDBGQlN5eE5RVUZNTEVOQlFWa3NTVUZCV2l4RFFVRnBRaXhKUVVGcVFpeERRVUZ6UWl4TFFVRjBRaXhEUVVFMFFpeFhRVUZ5UXp0QlFVWkxMRmRCUVdJN08wRkJTMEVzWTBGQlNTeExRVUZMTEZGQlFWUXNSVUZCYlVJN1FVRkRha0lzWjBKQlFVa3NTMEZCU3l4RlFVRk1MRXRCUVZrc1RVRkJhRUlzUlVGQmQwSTdRVUZEZEVJc2JVSkJRVXNzUzBGQlRDeEhRVUZoTzBGQlExZ3NORUpCUVZrc1QwRkJTeXhSUVVGTUxFTkJRV01zUzBGQlN5eExRVUZNTEVOQlFWY3NWVUZCZWtJc1JVRkJjVU1zUjBGQmNrTXNRMEZFUkR0QlFVVllMSGRDUVVGUkxFOUJRVXNzVVVGQlRDeERRVUZqTEV0QlFVc3NTMEZCVEN4RFFVRlhMRTFCUVhwQ0xFVkJRV2xETEVkQlFXcERPMEZCUmtjc1pVRkJZanRCUVVsQkxEWkNRVUZsTEVsQlFXWTdRVUZEUkR0QlFVTkdMRmRCVWtRc1RVRlRTenRCUVVOSUxHZENRVUZOTEdkQ1FVRm5RaXhMUVVGTExHRkJRVXdzUTBGQmJVSXNSMEZCYmtJc1EwRkJkVUk3UVVGQlFTeHhRa0ZCVXl4TlFVRk5MRXRCUVdZN1FVRkJRU3hoUVVGMlFpeERRVUYwUWp0QlFVTkJMR2RDUVVGSkxFTkJRVU1zWTBGQll5eFJRVUZrTEVOQlFYVkNMRTFCUVhaQ0xFTkJRVXdzUlVGQmNVTTdRVUZEYmtNc2JVSkJRVXNzUzBGQlRDeEhRVUZoTzBGQlExZ3NORUpCUVZrc1QwRkJTeXhOUVVGTUxFTkJRVmtzU1VGQldpeERRVUZwUWl4TFFVRnFRaXhEUVVGMVFpeFZRVUYyUWl4RFFVRnJReXhYUVVSdVF6dEJRVVZZTEhkQ1FVRlJMRTlCUVVzc1RVRkJUQ3hEUVVGWkxFbEJRVm9zUTBGQmFVSXNTMEZCYWtJc1EwRkJkVUlzVFVGQmRrSXNRMEZCT0VJN1FVRkdNMElzWlVGQllqdEJRVWxCTERaQ1FVRmxMRWxCUVdZN1FVRkRSRHRCUVVOR096dEJRVVZFTzBGQlEwRXNZMEZCU1N4WlFVRktMRVZCUVd0Q08wRkJRMmhDTEhWQ1FVRlhMRWxCUVZnc1EwRkJaMElzU1VGQmFFSTdRVUZEUkR0QlFVTkdMRk5CTDBKRU96dEJRV2xEUVN4cFFrRkJVeXhMUVVGVUxFTkJRV1VzVFVGQlppeERRVUZ6UWl4VlFVRjBRanRCUVVOQkxHbENRVUZUTEV0QlFWUXNRMEZCWlN4TlFVRm1MRU5CUVhOQ0xGVkJRWFJDT3p0QlFVVkJMR2RDUVVGUkxHMUNRVUZTTzBGQlEwUXNUMEV4UkUwc1EwRkJVRHRCUVRKRVJDeExRVEZsTWtJN1FVRXlaVFZDTEhWQ1FUTmxORUlzYVVOQk1tVk9PMEZCUVVFN08wRkJRM0JDTEdGQlFVOHNTVUZCU1N4UFFVRktMRU5CUVZrc1ZVRkJReXhQUVVGRUxFVkJRVlVzVFVGQlZpeEZRVUZ4UWp0QlFVTjBReXhaUVVGTkxGZEJRVmNzWlVGQlpTeFhRVUZtTEVOQlFUSkNMRkZCUVRWRE8wRkJRMEVzV1VGQlRTeFJRVUZSTEZOQlFWTXNTMEZCVkN4RFFVRmxMRWRCUVdZc1JVRkJaRHRCUVVOQkxGbEJRVTBzVVVGQlVTeFRRVUZUTEV0QlFWUXNRMEZCWlN4SFFVRm1MRVZCUVdRN1FVRkRRU3haUVVGTkxHbENRVUZwUWl4UFFVRkxMRTFCUVV3c1EwRkJXU3hKUVVGYUxFTkJRV2xDTEV0QlFYaERPMEZCUTBFc1dVRkJUU3hoUVVGaExFVkJRVzVDTzBGQlEwRXNXVUZCVFN4aFFVRmhMRVZCUVc1Q096dEJRVVZCTEdOQlFVMHNUMEZCVGl4RFFVRmpMR2RDUVVGUk8wRkJRM0JDTEhGQ1FVRlhMRWxCUVZnc1EwRkJaMEk3UVVGRFpDeG5Ra0ZCU1N4TFFVRkxMRVZCUkVzN1FVRkZaQ3h0UWtGQlR6dEJRVU5NTEhGQ1FVRlBMRXRCUVVzc1pVRkJUQ3hEUVVGeFFpeExRVUZ5UWl4RFFVRXlRaXhMUVVRM1FqdEJRVVZNTEhWQ1FVRlRMRTlCUVVzc1RVRkJUQ3hEUVVGWkxFbEJRVm9zUTBGQmFVSXNTMEZCYWtJc1EwRkJkVUlzVDBGQmRrSXNRMEZCSzBJN1FVRkdia003UVVGR1R5eFhRVUZvUWp0QlFVOUVMRk5CVWtRN1FVRlRRU3hqUVVGTkxFOUJRVTRzUTBGQll5eG5Ra0ZCVVR0QlFVTndRaXh4UWtGQlZ5eEpRVUZZTEVOQlFXZENPMEZCUTJRc1owSkJRVWtzUzBGQlN5eEZRVVJMTzBGQlJXUXNhMEpCUVUwc1JVRkJSU3hQUVVGUExFOUJRVXNzVFVGQlRDeERRVUZaTEVsQlFWb3NRMEZCYVVJc1NVRkJha0lzUTBGQmMwSXNTMEZCZEVJc1EwRkJORUlzVDBGQmNrTXNSVUZHVVR0QlFVZGtMRzFDUVVGUE8wRkJRMHdzTUVKQlEwVXNTMEZCU3l4bFFVRk1MRWRCUVhWQ0xFdEJRVXNzWlVGQlRDeERRVUZ4UWl4TFFVRnlRaXhEUVVFeVFpeFZRVUZzUkN4SFFVRXJSQ3hsUVVGbExGVkJRV1lzUTBGQk1FSXNUMEZHZEVZN1FVRkpUQ3h6UWtGRFJTeExRVUZMTEdWQlFVd3NSMEZCZFVJc1MwRkJTeXhsUVVGTUxFTkJRWEZDTEV0QlFYSkNMRU5CUVRKQ0xFMUJRV3hFTEVkQlFUSkVMR1ZCUVdVc1RVRkJaaXhEUVVGelFqdEJRVXc1UlR0QlFVaFBMRmRCUVdoQ08wRkJXVVFzVTBGaVJEczdRVUZsUVN4cFFrRkJVeXhMUVVGVUxFTkJRV1VzVFVGQlppeERRVUZ6UWl4VlFVRjBRanRCUVVOQkxHbENRVUZUTEV0QlFWUXNRMEZCWlN4TlFVRm1MRU5CUVhOQ0xGVkJRWFJDT3p0QlFVVkJMR2RDUVVGUkxHOURRVUZTTzBGQlEwUXNUMEZ3UTAwc1EwRkJVRHRCUVhGRFJDeExRV3BvUWpKQ08wRkJhMmhDTlVJc2EwTkJiR2hDTkVJc05FTkJhMmhDYzBRN1FVRkJRU3hWUVVGdVJDeFBRVUZ0UkN4MVJVRkJla01zU1VGQmVVTTdRVUZCUVN4VlFVRnVReXg1UWtGQmJVTXNkVVZCUVZBc1MwRkJUenM3UVVGRE9VVXNWVUZCU1N4WlFVRlpMRWxCUVdoQ0xFVkJRWE5DTzBGQlEzQkNMR2RDUVVGUkxFdEJRVklzUTBGQll5eHBRa0ZCWkR0QlFVTkJPMEZCUTBRN08wRkJSVVFzVlVGQlRTeFRRVUZUTEVsQlFVa3NTVUZCU1N4UFFVRlNMRU5CUVdkQ0xHVkJRV1VzVjBGQlppeERRVUV5UWl4TlFVRXpReXhEUVVGbU8wRkJRMEVzVlVGQlRTeGxRVUZsTEVWQlFVVXNSMEZCUnl4RFFVRk1MRVZCUVZFc1IwRkJSeXhEUVVGWUxFVkJRWEpDTzBGQlEwRXNWVUZCVFN4bFFVRmxMRVZCUVVVc1IwRkJSeXhIUVVGTUxFVkJRVlVzUjBGQlJ5eEhRVUZpTEVWQlFYSkNPMEZCUTBFc1ZVRkJUU3hoUVVGaExFdEJRVXNzYVVKQlFVd3NRMEZCZFVJc1QwRkJUeXhIUVVGUUxFVkJRWFpDTEVWQlFYRkRMRWxCUVhKRExFVkJRVEpETEU5QlFUTkRMRU5CUVc1Q08wRkJRMEVzVlVGQlRTeFhRVUZYTEdWQlFXVXNWMEZCWml4RFFVRXlRaXhSUVVFMVF6dEJRVU5CTEZWQlFVMHNWVUZCVlN4bFFVRmxMRmRCUVdZc1EwRkJNa0lzVDBGQk0wTTdRVUZEUVN4VlFVRk5MRkZCUVZFc1QwRkJUeXhIUVVGUUxFTkJRVmNzVDBGQldDeERRVUZrTzBGQlEwRXNWVUZCVFN4aFFVRmhMRVZCUVc1Q096dEJRVVZCTEZWQlFVa3NUVUZCVFN4TFFVRk9MRU5CUVZrc1RVRkJhRUlzUlVGQmQwSTdRVUZEZEVJc2NVSkJRV0VzUTBGQllpeEhRVUZwUWl4aFFVRmhMRU5CUVdJc1IwRkJhVUlzVlVGQmJFTTdRVUZEUVN4eFFrRkJZU3hEUVVGaUxFZEJRV2xDTEVOQlFXcENPenRCUVVWQk8wRkJRMEVzV1VGQlNTeDVRa0ZCU2l4RlFVRXJRanRCUVVNM1FpeGpRVUZOTEhOQ1FVRnpRaXhSUVVGUkxGbEJRVklzUTBGQmNVSXNRMEZCUXl4UFFVRkVMRU5CUVhKQ0xFVkJRV2RETEU5QlFXaERMRU5CUVRWQ08wRkJRMEVzZFVKQlFXRXNRMEZCWWl4SFFVRnBRaXh2UWtGQmIwSXNRMEZCY2tNN1FVRkRRU3gxUWtGQllTeERRVUZpTEVkQlFXbENMRzlDUVVGdlFpeERRVUZ3UWl4SFFVRjNRaXhoUVVGaExFTkJRWFJFTzBGQlEwUTdPMEZCUlVRN1FVRkRRU3hqUVVGTkxFdEJRVTRzUTBGQldTeFBRVUZhTEVOQlFXOUNMRlZCUVVNc1NVRkJSQ3hGUVVGUExGTkJRVkFzUlVGQmNVSTdRVUZEZGtNc2RVSkJRV0VzUTBGQllpeEpRVUZyUWl4aFFVRmhMRU5CUVM5Q08wRkJRMEVzY1VKQlFWY3NTVUZCV0N4RFFVRm5RanRCUVVOa0xHZENRVUZKTEV0QlFVc3NTVUZFU3p0QlFVVmtMR1ZCUVVjc1lVRkJZU3hEUVVaR08wRkJSMlFzWlVGQlJ5eGhRVUZoTzBGQlNFWXNWMEZCYUVJN08wRkJUVUU3UVVGRFFTeGpRVU5KTEdOQlFXTXNUVUZCVFN4TFFVRk9MRU5CUVZrc1RVRkJXaXhIUVVGeFFpeERRVUZ1UXl4RFFVRnhRenRCUVVGeVF5eGhRVVZCTEUxQlFVMHNTMEZCVGl4RFFVRlpMRTFCUVZvc1MwRkJkVUlzUTBGSU0wSXNRMEZITmtJN1FVRklOMElzV1VGSlNUdEJRVU5HTEhsQ1FVRlhMRWxCUVZnc1EwRkJaMEk3UVVGRFpDeHZRa0ZCU1N4TFFVRkxMRVZCUkVzN1FVRkZaQ3h0UWtGQlJ5eGhRVUZoTEVOQlJrWTdRVUZIWkN4dFFrRkJSeXhoUVVGaExFTkJRV0lzUjBGQmFVSXNZVUZCWVR0QlFVaHVRaXhsUVVGb1FqdEJRVXRFTzBGQlEwWXNVMEZ3UWtRN08wRkJjMEpCTzBGQlEwRXNhVUpCUVZNc1MwRkJWQ3hEUVVGbExFMUJRV1lzUTBGQmMwSXNWVUZCZEVJN1FVRkZSQ3hQUVhKRFJDeE5RWE5EU3p0QlFVTklMR2RDUVVGUkxFdEJRVklzSzBKQlFUQkRMRTlCUVRGRE8wRkJRMFE3UVVGRFNpeExRVEZyUWpKQ096dEJRVEpyUWpWQ096czdPenM3TzBGQlQwRXNjMEpCYkd4Q05FSXNaME5CYTJ4Q1NUdEJRVUZCTEZWQlFXSXNUMEZCWVN4MVJVRkJTQ3hEUVVGSE96dEJRVU01UWl4VlFVRk5MR0ZCUVdFc1ZVRkJWU3hMUVVGTExFMUJRVXdzUTBGQldTeE5RVUY2UXpzN1FVRkZRU3hoUVVGUExFdEJRVXNzVFVGQlRDeERRVUZaTEZWQlFWb3NRMEZCVUR0QlFVTkVMRXRCZEd4Q01rSTdPenRCUVhkc1FqVkNPenM3UVVGSFFTeHBRa0V6YkVJMFFpd3lRa0V5YkVKRE8wRkJRVUVzVlVGQlppeE5RVUZsTEhWRlFVRk9MRWxCUVUwN08wRkJRek5DTEZWQlFVa3NWMEZCVnl4SlFVRm1MRVZCUVhGQ08wRkJRMjVDTEdWQlFVOHNTMEZCVUR0QlFVTkVPenRCUVVWRUxHRkJRVThzWlVGQlpTeFhRVUZtTEVOQlFUSkNMRkZCUVROQ0xFTkJRVzlETEV0QlFYQkRMRU5CUVRCRExFZEJRVEZETEVOQlFUaERMRTFCUVRsRExFVkJRWE5FTEZGQlFUZEVPMEZCUTBRc1MwRnFiVUl5UWp0QlFXdHRRalZDTEdGQmJHMUNORUlzZFVKQmEyMUNhRUk3UVVGRFZpeHhRa0ZCWlN4WFFVRm1MRU5CUVRKQ0xFOUJRVE5DTEVOQlFXMURMRlZCUVc1RExFTkJRVGhETzBGQlF6VkRMSEZDUVVGaE8wRkJRMWdzY1VKQlFWYzdRVUZFUVR0QlFVUXJRaXhQUVVFNVF6dEJRVXRFTEV0QmVHMUNNa0k3UVVGNWJVSTFRaXhsUVhwdFFqUkNMSGxDUVhsdFFtUTdRVUZEV2l4eFFrRkJaU3hYUVVGbUxFTkJRVEpDTEU5QlFUTkNMRU5CUVcxRExGVkJRVzVETEVOQlFUaERPMEZCUXpWRExIRkNRVUZoTzBGQlExZ3NjVUpCUVZjN1FVRkVRVHRCUVVRclFpeFBRVUU1UXp0QlFVdEVMRXRCTDIxQ01rSTdPenRCUVdsdVFqVkNPenM3UVVGSFFTeHRRa0Z3YmtJMFFpd3lRa0Z2YmtKYUxFbEJjRzVDV1N4RlFXOXVRazQ3UVVGRGNFSXNWVUZCU1N4UFFVRlBMRWxCUVZBc1MwRkJaMElzVjBGQmNFSXNSVUZCYVVNN1FVRkRMMEk3UVVGRFJEczdRVUZGUkN4VlFVRkpMRTlCUVU4c1JVRkJXRHM3UVVGRlFUdEJRVU5CTEZWQlFVa3NTMEZCU3l4UlFVRlVMRVZCUVcxQ08wRkJRMnBDTEdWQlFVODdRVUZEVEN4alFVRkpMRXRCUVVzc1JVRkVTanRCUVVWTUxHVkJRVXNzUzBGQlN5eEhRVVpNTzBGQlIwd3NlVUpCUVdFc1MwRkJTeXhMUVVGc1FpeFRRVWhMTzBGQlNVd3NlVUpCUVdFc1MwRkJTeXhMUVVGc1FpeFRRVXBMTzBGQlMwd3NkVUpCUVdFc1MwRkJTeXhOUVVGTUxFTkJRVmtzVFVGQldpeERRVUZ0UWl4WFFVd3pRanRCUVUxTUxESkNRVUZwUWp0QlFVTm1MRzFDUVVGUE8wRkJRMHdzTUVKQlFWa3NTMEZCU3l4TlFVRk1MRU5CUVZrc1RVRkJXaXhEUVVGdFFpeExRVUZ1UWl4RFFVRjVRanRCUVVSb1F6dEJRVVJSTEZkQlRsbzdRVUZYVEN4cFFrRkJUenRCUVVOTUxIZENRVUZaTEV0QlFVc3NUVUZCVEN4RFFVRlpMRTFCUVZvc1EwRkJiVUlzUzBGQmJrSXNRMEZCZVVJN1FVRkVhRU1zVjBGWVJqdEJRV05NTEd0Q1FVRlJMRXRCUVVzc1RVRkJUQ3hEUVVGWkxFMUJRVm9zUTBGQmJVSXNUVUZrZEVJN1FVRmxUQ3hyUWtGQlVTeExRV1pJTzBGQlowSk1MRzlDUVVGVk8wRkJhRUpNTEZOQlFWQTdRVUZyUWtRN08wRkJSVVE3UVVGeVFrRXNWMEZ6UWtzN1FVRkRTQ3hwUWtGQlR6dEJRVU5NTEdkQ1FVRkpMRXRCUVVzc1JVRkVTanRCUVVWTUxHMUNRVUZWTEV0QlFVc3NTMEZCVEN4RFFVRlhMRk5CUVZnc1EwRkJjVUlzUTBGQmNrSXNSVUZCZDBJc1JVRkJlRUlzUTBGQlZpeFJRVVpMTzBGQlIwd3NiVUpCUVU4c1MwRkJTeXhMUVVoUU8wRkJTVXdzTWtKQlFXVXNTMEZCU3l4WFFVcG1PMEZCUzB3c05FSkJRV2RDTEV0QlFVc3NaVUZNYUVJN1FVRk5UQ3g1UWtGQllTeExRVUZMTEUxQlFVd3NRMEZCV1N4SlFVRmFMRU5CUVdsQ0xGZEJRV3BDTEVOQlFUWkNMRTlCVG5KRE8wRkJUMHdzTmtKQlFXbENPMEZCUTJZc05FSkJRV1VzUTBGQlF5eExRVUZMTzBGQlJFNDdRVUZRV2l4WFFVRlFPenRCUVZsQk8wRkJRMEVzWTBGQlNTeExRVUZMTEZGQlFWUXNSVUZCYlVJN1FVRkRha0lzYVVKQlFVc3NTMEZCVEN4WFFVRnRRaXhMUVVGTExFdEJRVXdzUTBGQlZ5eFRRVUZZTEVOQlFYRkNMRU5CUVhKQ0xFVkJRWGRDTEVWQlFYaENMRU5CUVc1Q08wRkJRMEVzYVVKQlFVc3NWMEZCVEN4SFFVRnRRaXhMUVVGTExFMUJRVXdzUTBGQldTeEpRVUZhTEVOQlFXbENMRmRCUVdwQ0xFTkJRVFpDTEZGQlFXaEVPMEZCUTBFc2FVSkJRVXNzUzBGQlRDeEhRVUZoTzBGQlExZ3NjMEpCUVZFc1MwRkJTeXhOUVVGTUxFTkJRVmtzU1VGQldpeERRVUZwUWl4TFFVRnFRaXhEUVVGMVFpeE5RVUYyUWl4RFFVRTRRaXhQUVVRelFqdEJRVVZZTERCQ1FVRlpMRXRCUVVzc1RVRkJUQ3hEUVVGWkxFbEJRVm9zUTBGQmFVSXNTMEZCYWtJc1EwRkJkVUlzVlVGQmRrSXNRMEZCYTBNN1FVRkdia01zWVVGQllqdEJRVWxCTEdsQ1FVRkxMR1ZCUVV3c1IwRkJkVUk3UVVGRGNrSXNjVUpCUVU4N1FVRkRUQ3gzUWtGQlVTeExRVUZMTEUxQlFVd3NRMEZCV1N4SlFVRmFMRU5CUVdsQ0xFdEJRV3BDTEVOQlFYVkNMRTFCUVhaQ0xFTkJRVGhDTEU5QlJHcERPMEZCUlV3c05FSkJRVmtzUzBGQlN5eE5RVUZNTEVOQlFWa3NTVUZCV2l4RFFVRnBRaXhMUVVGcVFpeERRVUYxUWl4VlFVRjJRaXhEUVVGclF6dEJRVVo2UXp0QlFVUmpMR0ZCUVhaQ08wRkJUVVE3UVVGRFJqczdRVUZGUkN4aFFVRlBMRWxCUVZBN1FVRkRSQ3hMUVdweVFqSkNPMEZCYTNKQ05VSXNiVUpCYkhKQ05FSXNNa0pCYTNKQ1dpeFBRV3h5UWxrc1JVRnJja0pJTzBGQlFVRXNWVUZEWml4TFFVUmxMRWRCUTNGQ0xFOUJSSEpDTEVOQlEyWXNTMEZFWlR0QlFVRkJMRlZCUTFJc1NVRkVVU3hIUVVOeFFpeFBRVVJ5UWl4RFFVTlNMRWxCUkZFN1FVRkJRU3hWUVVOR0xGVkJSRVVzUjBGRGNVSXNUMEZFY2tJc1EwRkRSaXhWUVVSRk8wRkJRVUVzVlVGRFZTeE5RVVJXTEVkQlEzRkNMRTlCUkhKQ0xFTkJRMVVzVFVGRVZqczdPMEZCUjNaQ0xHRkJRVTg3UVVGRFRDeGhRVUZMTEUxQlFVMHNSVUZFVGp0QlFVVk1MR05CUVUwc1MwRkJTeXhKUVVaT08wRkJSMHdzV1VGQlNTeExRVUZMTEVWQlNFbzdRVUZKVEN4NVFrRkJhVUk3UVVGRFppeHBRa0ZCVHp0QlFVTk1MRzFDUVVGUE8wRkJSRVk3UVVGRVVTeFRRVXBhTzBGQlUwd3NaVUZCVHp0QlFVTk1MR2xDUVVGUE8wRkJSRVlzVTBGVVJqdEJRVmxNTEdkQ1FVRlJMRXRCUVVzc2RVSkJXbEk3UVVGaFRDeG5Ra0ZCVVR0QlFXSklMRTlCUVZBN1FVRmxSQ3hMUVhCelFqSkNPenM3UVVGemMwSTFRanM3TzBGQlIwRXNjVUpCZW5OQ05FSXNOa0pCZVhOQ1ZpeE5RWHB6UWxVc1JVRjVjMEpHTEVkQmVuTkNSU3hGUVhselFrY3NTMEY2YzBKSUxFVkJlWE5DVlR0QlFVTndReXhWUVVGSkxGRkJRVkVzUTBGQldqdEJRVVJ2UXp0QlFVRkJPMEZCUVVFN08wRkJRVUU3UVVGRmNFTXNOa0pCUVhGQ0xFOUJRVThzVDBGQlVDeEZRVUZ5UWl3NFNFRkJkVU03UVVGQlFUdEJRVUZCTEdOQlFUZENMRU5CUVRaQ08wRkJRVUVzWTBGQk1VSXNSMEZCTUVJN08wRkJRM0pETEdOQlFVa3NTVUZCU1N4SFFVRktMRTFCUVdFc1MwRkJha0lzUlVGQmQwSTdRVUZEZEVJc2IwSkJRVkVzUTBGQlVqdEJRVU5CTzBGQlEwUTdRVUZEUmp0QlFWQnRRenRCUVVGQk8wRkJRVUU3UVVGQlFUdEJRVUZCTzBGQlFVRTdRVUZCUVR0QlFVRkJPMEZCUVVFN1FVRkJRVHRCUVVGQk8wRkJRVUU3UVVGQlFUdEJRVUZCT3p0QlFWTndReXhoUVVGUExFdEJRVkE3UVVGRFJDeExRVzUwUWpKQ08wRkJiM1JDTlVJc1dVRndkRUkwUWl4dlFrRnZkRUp1UWl4SFFYQjBRbTFDTEVWQmIzUkNaQ3hQUVhCMFFtTXNSVUZ2ZEVKTU8wRkJRM0pDTEZsQlFVMHNTVUZCU1N4UFFVRktMRU5CUVZrc1IwRkJXaXhGUVVGcFFpeEZRVUZxUWl4RFFVRk9PMEZCUTBFc1ZVRkJUU3hKUVVGSkxGTkJRVk1zU1VGQlNTeFRRVUZLTEVOQlFXTXNRMEZCWkN4RlFVRm5RaXhEUVVGb1FpeERRVUZVTEVWQlFUWkNMRVZCUVRkQ0xFTkJRVlk3UVVGRFFTeFZRVUZOTEVsQlFVa3NVMEZCVXl4SlFVRkpMRk5CUVVvc1EwRkJZeXhEUVVGa0xFVkJRV2RDTEVOQlFXaENMRU5CUVZRc1JVRkJOa0lzUlVGQk4wSXNRMEZCVmp0QlFVTkJMRlZCUVUwc1NVRkJTU3hUUVVGVExFbEJRVWtzVTBGQlNpeERRVUZqTEVOQlFXUXNSVUZCWjBJc1EwRkJhRUlzUTBGQlZDeEZRVUUyUWl4RlFVRTNRaXhEUVVGV096dEJRVVZCTEhWQ1FVRmxMRU5CUVdZc1ZVRkJjVUlzUTBGQmNrSXNWVUZCTWtJc1EwRkJNMElzVlVGQmFVTXNUMEZCYWtNN1FVRkRSRHRCUVROMFFqSkNMRWRCUVRsQ096dEJRVGgwUWtFN096dEJRVWRCTEZOQlFVOHNVMEZCVUN4RFFVRnBRaXh4UWtGQmFrSXNSMEZCZVVNN1FVRkRka01zV1VGQlVTeG5Ra0ZCVlN4UFFVRldMRVZCUVcxQ0xGRkJRVzVDTEVWQlFUWkNPMEZCUTI1RExGRkJRVVVzYzBKQlFVWXNSVUZCTUVJc1QwRkJNVUlzUlVGQmJVTXNTVUZCYmtNc1IwRkJNRU1zU1VGQk1VTXNRMEZCSzBNc1dVRkJXVHM3UVVGRmVrUTdRVUZEUVN4VlFVRkZMRWxCUVVZc1JVRkJVU3hQUVVGU0xFVkJRV2xDTEVWQlFXcENMRU5CUVc5Q0xFOUJRWEJDTEVWQlFUWkNMREpEUVVFM1FpeEZRVUV3UlN4aFFVRkxPMEZCUXpkRkxHbENRVUZQTEZOQlFWQXNRMEZCYVVJc2NVSkJRV3BDTEVOQlFYVkRMR05CUVhaRExFTkJRMFVzUlVGQlJTeEZRVUZGTEdGQlFVb3NSVUZCYlVJc1NVRkJia0lzUTBGQmQwSXNTVUZCZUVJc1EwRkVSaXhGUVVWRkxFOUJSa1k3UVVGSlJDeFRRVXhFTzBGQlRVUXNUMEZVUkR0QlFWVkVMRXRCV25ORE8wRkJZWFpETEdGQlluVkRMSEZDUVdFM1FpeEZRV0kyUWl4RlFXRjZRanRCUVVOYUxHTkJRVTBzUlVGQlRpeEZRVUZaTEV0QlFWbzdRVUZEUkN4TFFXWnpRenRCUVdkQ2RrTXNhMEpCYUVKMVF5d3dRa0ZuUW5oQ0xFVkJhRUozUWl4RlFXZENjRUlzVDBGb1FtOUNMRVZCWjBKWU8wRkJRekZDTEZGQlFVVXNjME5CUVVZc1JVRkJNRU1zVDBGQk1VTXNSVUZCYlVRc1NVRkJia1E3UVVGRFFTdzRSRUZCYzBRc1JVRkJkRVFzVVVGQk5rUXNUMEZCTjBRc1JVRkJjMFVzU1VGQmRFVTdRVUZEUkR0QlFXNUNjME1zUjBGQmVrTTdRVUZ4UWtRc1EwRTVkMEpFTEVWQk9IZENSeXhOUVRsM1FrZ3NSVUU0ZDBKWExFMUJPWGRDV0N4RlFUaDNRbTFDTEdOQk9YZENia0lpTENKbWFXeGxJam9pWjJWdVpYSmhkR1ZrTG1weklpd2ljMjkxY21ObFVtOXZkQ0k2SWlJc0luTnZkWEpqWlhORGIyNTBaVzUwSWpwYklpaG1kVzVqZEdsdmJpQmxLSFFzYml4eUtYdG1kVzVqZEdsdmJpQnpLRzhzZFNsN2FXWW9JVzViYjEwcGUybG1LQ0YwVzI5ZEtYdDJZWElnWVQxMGVYQmxiMllnY21WeGRXbHlaVDA5WENKbWRXNWpkR2x2Ymx3aUppWnlaWEYxYVhKbE8ybG1LQ0YxSmlaaEtYSmxkSFZ5YmlCaEtHOHNJVEFwTzJsbUtHa3BjbVYwZFhKdUlHa29ieXdoTUNrN2RtRnlJR1k5Ym1WM0lFVnljbTl5S0Z3aVEyRnVibTkwSUdacGJtUWdiVzlrZFd4bElDZGNJaXR2SzF3aUoxd2lLVHQwYUhKdmR5Qm1MbU52WkdVOVhDSk5UMFJWVEVWZlRrOVVYMFpQVlU1RVhDSXNabjEyWVhJZ2JEMXVXMjlkUFh0bGVIQnZjblJ6T250OWZUdDBXMjlkV3pCZExtTmhiR3dvYkM1bGVIQnZjblJ6TEdaMWJtTjBhVzl1S0dVcGUzWmhjaUJ1UFhSYmIxMWJNVjFiWlYwN2NtVjBkWEp1SUhNb2JqOXVPbVVwZlN4c0xHd3VaWGh3YjNKMGN5eGxMSFFzYml4eUtYMXlaWFIxY200Z2JsdHZYUzVsZUhCdmNuUnpmWFpoY2lCcFBYUjVjR1Z2WmlCeVpYRjFhWEpsUFQxY0ltWjFibU4wYVc5dVhDSW1KbkpsY1hWcGNtVTdabTl5S0haaGNpQnZQVEE3Ynp4eUxteGxibWQwYUR0dkt5c3BjeWh5VzI5ZEtUdHlaWFIxY200Z2MzMHBJaXdpTHlvcVhHNGdLaUJBWm1sc1pWeHVJQ29nVkhKaGFXd2dSM0poY0dndVhHNGdLbHh1SUNvZ1JHbHpjR3hoZVhNZ2JtOWtaWE1nZDJsMGFHbHVJSFJ5WVdsc2N5NWNiaUFxTDF4dVhHNG9ablZ1WTNScGIyNGdLQ1FzSUVSeWRYQmhiQ3dnWkhKMWNHRnNVMlYwZEdsdVozTXBJSHRjYmx4dUlDQXZLaXBjYmlBZ0lDb2dVbVZ6ZFdKdGFYUWdWSEpoYVd3Z0x5Qk9iMlJsSUdacGJIUmxjaUJtYjNKdElDaGxlSEJ2YzJWa0lHWnBiSFJsY2lsY2JpQWdJQ29nVkdocGN5Qm1kVzVqZEdsdmJpQnBjeUJqWVd4c1pXUWdabkp2YlNCZmJXOWtZV3hmWldScGRGOW1iM0p0WDJGcVlYaGZjM1ZpYldsMElIWnBZU0JKYm5admEyVkRiMjF0WVc1a0xseHVJQ0FnS2k5Y2JpQWdKQzVtYmk1eVpYTjFZbTFwZEZSeVlXbHNSM0poY0doR2FXeDBaWEpHYjNKdElEMGdLQ2tnUFQ0Z2UxeHVJQ0FnSUdOdmJuTjBJR1p2Y20xSlpDQTlJR1J5ZFhCaGJGTmxkSFJwYm1kekxtVjRjRzl6WldSZlptOXliVjlwWkR0Y2JpQWdJQ0JqYjI1emRDQWtabTl5YlNBOUlDUW9ZR1p2Y20wakpIdG1iM0p0U1dSOVlDazdYRzVjYmlBZ0lDQXZMeUJKYmlCallYTmxJSGRsSUdoaGRtVWdabTl5YlNCemRXSnRhWFFnWW5WMGRHOXVJSFJ5YVdkblpYSWdZMnhwWTJzZ1pYWmxiblFnS0dGcVlYZ2djM1ZpYldsMElIZHBiR3dnYUdGd2NHVnVLVnh1SUNBZ0lHbG1JQ2drWm05eWJTNW1hVzVrS0NjdVluVjBkRzl1TG1wekxXWnZjbTB0YzNWaWJXbDBKeWtwSUh0Y2JpQWdJQ0FnSUNSbWIzSnRMbVpwYm1Rb0p5NWlkWFIwYjI0dWFuTXRabTl5YlMxemRXSnRhWFFuS1M1MGNtbG5aMlZ5S0NkamJHbGpheWNwTzF4dUlDQWdJQ0FnY21WMGRYSnVPMXh1SUNBZ0lIMWNibHh1SUNBZ0lDOHZJRWx1SUdOaGMyVWdkMlVnWkc5dWRDQm9ZWFpsSUdadmNtMGdjM1ZpYldsMElHSjFkSFJ2Yml3Z1ptRnNiR0poWTJzZ2RHOGdablZzYkNCd1lXZGxJSEpsYkc5aFpDNWNiaUFnSUNBa1ptOXliUzV6ZFdKdGFYUW9LVHRjYmlBZ2ZUdGNibHh1SUNBdktpcGNiaUFnSUNvZ1ZISmhhV3dnWjNKaGNHZ2dRbVZvWVhacGIzVnlYRzRnSUNBcUlGUlBSRTg2SUZkeWFYUmxJSE52YldVZ1pHOWpkVzFsYm5SaGRHbHZiaUJoWW05MWRDQjBhR2x6SUdKbGFHRjJhVzkxY2k1Y2JpQWdJQ292WEc0Z0lFUnlkWEJoYkM1aVpXaGhkbWx2Y25NdWRISmhhV3hIY21Gd2FDQTlJSHRjYmlBZ0lDQnpkSGxzWlhNNklIdGNiaUFnSUNBZ0lHNXZaR1U2SUh0Y2JpQWdJQ0FnSUNBZ1kyOXNiM0k2SUh0Y2JpQWdJQ0FnSUNBZ0lDQmlZV05yWjNKdmRXNWtPaUI3WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBibWwwYVdGc09pQW5JMlEwWldSbVl5Y3NYRzRnSUNBZ0lDQWdJQ0FnSUNCelpXeGxZM1JsWkRvZ0p5TTVRa1JHUmtNbkxGeHVJQ0FnSUNBZ0lDQWdJQ0FnYUdsbmFHeHBaMmgwWldRNklDZHlaMkpoS0RJeE1pd2dNak0zTENBeU5USXNJREF1TXlrbkxGeHVJQ0FnSUNBZ0lDQWdJSDBzWEc0Z0lDQWdJQ0FnSUNBZ1ltOXlaR1Z5T2lCN1hHNGdJQ0FnSUNBZ0lDQWdJQ0JwYm1sMGFXRnNPaUFuSXpBd09XWmxNeWNzWEc0Z0lDQWdJQ0FnSUNBZ0lDQm9hV2RvYkdsbmFIUmxaRG9nSjNKblltRW9NQ3dnTVRVNUxDQXlNamNzSURBdU1Ta25MRnh1SUNBZ0lDQWdJQ0FnSUgwc1hHNGdJQ0FnSUNBZ0lIMHNYRzRnSUNBZ0lDQWdJR0p2Y21SbGNsZHBaSFJvT2lCN1hHNGdJQ0FnSUNBZ0lDQWdhVzVwZEdsaGJEb2dNU3hjYmlBZ0lDQWdJQ0FnSUNCelpXeGxZM1JsWkRvZ01peGNiaUFnSUNBZ0lDQWdmU3hjYmlBZ0lDQWdJQ0FnWm05dWREb2dlMXh1SUNBZ0lDQWdJQ0FnSUdOdmJHOXlPaUI3WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBibWwwYVdGc09pQW5Jek0wTXpRek5DY3NYRzRnSUNBZ0lDQWdJQ0FnSUNCb2FXZG9iR2xuYUhSbFpEb2dKM0puWW1Fb01Dd2dNQ3dnTUN3Z01DNHhLU2NzWEc0Z0lDQWdJQ0FnSUNBZ2ZTeGNiaUFnSUNBZ0lDQWdmU3hjYmlBZ0lDQWdJQ0FnWW05NFRXRjRWMmxrZEdnNklERTFNQ3hjYmlBZ0lDQWdJSDBzWEc0Z0lDQWdJQ0JvWldGa1pYSTZJSHRjYmlBZ0lDQWdJQ0FnWTI5c2IzSTZJSHRjYmlBZ0lDQWdJQ0FnSUNCaVlXTnJaM0p2ZFc1a09pQW5JMlptWm1abVppY3NYRzRnSUNBZ0lDQWdJSDBzWEc0Z0lDQWdJQ0FnSUdKdmNtUmxjbGRwWkhSb09pQXpMRnh1SUNBZ0lDQWdJQ0J0WVhKbmFXNDZJREV3TEZ4dUlDQWdJQ0FnZlN4Y2JpQWdJQ0FnSUd4cGJtczZJSHRjYmlBZ0lDQWdJQ0FnZDJsa2RHZzZJRE1zWEc0Z0lDQWdJQ0FnSUdOdmJHOXlPaUI3WEc0Z0lDQWdJQ0FnSUNBZ2IzQmhZMmwwZVRvZ2UxeHVJQ0FnSUNBZ0lDQWdJQ0FnYVc1cGRHbGhiRG9nTVN4Y2JpQWdJQ0FnSUNBZ0lDQWdJR2hwWjJoc2FXZG9kR1ZrT2lBd0xGeHVJQ0FnSUNBZ0lDQWdJSDFjYmlBZ0lDQWdJQ0FnZlZ4dUlDQWdJQ0FnZlN4Y2JpQWdJQ0FnSUcxdlpHRnNPaUI3WEc0Z0lDQWdJQ0FnSUhkcFpIUm9PaUFuT0RBbEp5eGNiaUFnSUNBZ0lIMHNYRzRnSUNBZ2ZTeGNiaUFnSUNCamIyeHZjbk02SUZ0Y2JpQWdJQ0FnSUZ3aUl6QXdabVptWmx3aUxGeHVJQ0FnSUNBZ1hDSWpNREF3TURBd1hDSXNYRzRnSUNBZ0lDQmNJaU13TURBd1ptWmNJaXhjYmlBZ0lDQWdJRndpSTJFMU1tRXlZVndpTEZ4dUlDQWdJQ0FnWENJak1EQm1abVptWENJc1hHNGdJQ0FnSUNCY0lpTXdNREF3T0dKY0lpeGNiaUFnSUNBZ0lGd2lJekF3T0dJNFlsd2lMRnh1SUNBZ0lDQWdYQ0lqTURBMk5EQXdYQ0lzWEc0Z0lDQWdJQ0JjSWlOaVpHSTNObUpjSWl4Y2JpQWdJQ0FnSUZ3aUl6aGlNREE0WWx3aUxGeHVJQ0FnSUNBZ1hDSWpOVFUyWWpKbVhDSXNYRzRnSUNBZ0lDQmNJaU5tWmpoak1EQmNJaXhjYmlBZ0lDQWdJRndpSXprNU16SmpZMXdpTEZ4dUlDQWdJQ0FnWENJak9HSXdNREF3WENJc1hHNGdJQ0FnSUNCY0lpTmxPVGsyTjJGY0lpeGNiaUFnSUNBZ0lGd2lJemswTURCa00xd2lMRnh1SUNBZ0lDQWdYQ0lqWm1Zd01HWm1YQ0lzWEc0Z0lDQWdJQ0JjSWlObVptUTNNREJjSWl4Y2JpQWdJQ0FnSUZ3aUl6QXdPREF3TUZ3aUxGeHVJQ0FnSUNBZ1hDSWpOR0l3TURneVhDSXNYRzRnSUNBZ0lDQmNJaU5tTUdVMk9HTmNJaXhjYmlBZ0lDQWdJRndpSTJGa1pEaGxObHdpTEZ4dUlDQWdJQ0FnWENJak9UQmxaVGt3WENJc1hHNGdJQ0FnSUNCY0lpTXdNR1ptTURCY0lpeGNiaUFnSUNBZ0lGd2lJMlptTURCbVpsd2lMRnh1SUNBZ0lDQWdYQ0lqT0RBd01EQXdYQ0lzWEc0Z0lDQWdJQ0JjSWlNd01EQXdPREJjSWl4Y2JpQWdJQ0FnSUZ3aUl6Z3dPREF3TUZ3aUxGeHVJQ0FnSUNBZ1hDSWpabVpoTlRBd1hDSXNYRzRnSUNBZ0lDQmNJaU5tWm1Nd1kySmNJaXhjYmlBZ0lDQWdJRndpSXpnd01EQTRNRndpTEZ4dUlDQWdJQ0FnWENJak9EQXdNRGd3WENJc1hHNGdJQ0FnSUNCY0lpTm1aakF3TURCY0lpeGNiaUFnSUNBZ0lGd2lJMk13WXpCak1Gd2lMRnh1SUNBZ0lDQWdYQ0lqWm1abVpqQXdYQ0lzWEc0Z0lDQWdYU3hjYmlBZ0lDQmhkSFJoWTJnNklHWjFibU4wYVc5dUlDaGpiMjUwWlhoMExDQnpaWFIwYVc1bmN5a2dlMXh1SUNBZ0lDQWdKQ2duTG5SeVlXbHNMV2R5WVhCb1gxOWpiMjUwWlc1MEp5d2dZMjl1ZEdWNGRDa3ViMjVqWlNncExtVmhZMmdvWm5WdVkzUnBiMjRnS0NrZ2UxeHVJQ0FnSUNBZ0lDQmpiMjV6ZENCclpYbHpJRDBnVDJKcVpXTjBMbXRsZVhNb2MyVjBkR2x1WjNNdWRISmhhV3hmWjNKaGNHZ3VaR0YwWVNrN1hHNGdJQ0FnSUNBZ0lHTnZibk4wSUhSeVlXbHNSM0poY0doVFpYUjBhVzVuY3lBOUlITmxkSFJwYm1kekxuUnlZV2xzWDJkeVlYQm9MbVJoZEdGYmEyVjVjMXRyWlhsekxteGxibWQwYUNBdElERmRYVHRjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdleUIwY21GcGJITXNJRzV2WkdWekxDQm1hV3gwWlhKSmJuQjFkSE1nZlNBOUlIUnlZV2xzUjNKaGNHaFRaWFIwYVc1bmN6dGNiaUFnSUNBZ0lDQWdSSEoxY0dGc0xtSmxhR0YyYVc5eWN5NTBjbUZwYkVkeVlYQm9MbkJ5WlhCaGNtVkVZWFJoS0hSb2FYTXNJRzV2WkdWekxDQjBjbUZwYkhNc0lHWnBiSFJsY2tsdWNIVjBjeWs3WEc0Z0lDQWdJQ0I5S1R0Y2JpQWdJQ0I5TEZ4dVhHNGdJQ0FnTHlvcVhHNGdJQ0FnSUNvZ1VISmxjR0Z5WlNCa1lYUmhJR1p2Y2lCT1pYUjNiM0pyTGx4dUlDQWdJQ0FxWEc0Z0lDQWdJQ29nUUhCaGNtRnRJR052Ym5SbGVIUmNiaUFnSUNBZ0tpQkFjR0Z5WVcwZ2JtOWtaWE5jYmlBZ0lDQWdLaUJBY0dGeVlXMGdkSEpoYVd4elhHNGdJQ0FnSUNvZ1FIQmhjbUZ0SUdacGJIUmxja2x1Y0hWMGMxeHVJQ0FnSUNBcUwxeHVJQ0FnSUhCeVpYQmhjbVZFWVhSaEtHTnZiblJsZUhRc0lHNXZaR1Z6TENCMGNtRnBiSE1zSUdacGJIUmxja2x1Y0hWMGN5a2dlMXh1SUNBZ0lDQWdZMjl1YzNRZ1pHRjBZVk5sZEhNZ1BTQjdYRzRnSUNBZ0lDQWdJRzV2WkdWek9pQnVaWGNnZG1sekxrUmhkR0ZUWlhRb0tTeGNiaUFnSUNBZ0lDQWdiR2x1YTNNNklHNWxkeUIyYVhNdVJHRjBZVk5sZENncExGeHVJQ0FnSUNBZ2ZUdGNiaUFnSUNBZ0lHTnZibk4wSUhCdmMybDBhVzl1VTNSbGNDQTlJSHNnZURvZ01qQXdMQ0I1T2lBeE1EQWdmVHRjYmlBZ0lDQWdJR052Ym5OMElIUnlZV2xzU0dWaFpHVnljMUYxWlhWbElEMGdXMTA3WEc0Z0lDQWdJQ0JqYjI1emRDQnViMlJsYzFGMVpYVmxJRDBnVzEwN1hHNGdJQ0FnSUNCamIyNXpkQ0JtYjJOMWMxUnZSbWx5YzNSVWNtRnBiQ0E5SUNoMGNtRnBiSE11YkdWdVozUm9JRDRnTVNrN1hHNWNiaUFnSUNBZ0lDOHZJRU55WldGMFpTQmtZWFJoSUhObGRITWdabTl5SUc1dlpHVnpMbHh1SUNBZ0lDQWdhV1lnS0c1dlpHVnpMbXhsYm1kMGFDa2dlMXh1SUNBZ0lDQWdJQ0J1YjJSbGN5NW1iM0pGWVdOb0tHNXZaR1VnUFQ0Z1pHRjBZVk5sZEhNdWJtOWtaWE11WVdSa0tGeHVJQ0FnSUNBZ0lDQWdJSFJvYVhNdWNISmxjR0Z5WlU1dlpHVkVZWFJoS0c1dlpHVXBYRzRnSUNBZ0lDQWdJQ2twTzF4dUlDQWdJQ0FnZlZ4dVhHNGdJQ0FnSUNBdkx5QkRjbVZoZEdVZ1pHRjBZU0J6WlhSeklHWnZjaUJzYVc1cmN5NWNiaUFnSUNBZ0lHbG1JQ2gwY21GcGJITXViR1Z1WjNSb0tTQjdYRzRnSUNBZ0lDQWdJSFJ5WVdsc2N5NW1iM0pGWVdOb0tDaDBjbUZwYkN3Z2RISmhhV3hKYm1SbGVDa2dQVDRnZTF4dUlDQWdJQ0FnSUNBZ0lHbG1JQ2gwY21GcGJDNXNhVzVyY3k1c1pXNW5kR2dwSUh0Y2JseHVJQ0FnSUNBZ0lDQWdJQ0FnTHk4Z1FYTnphV2R1SUhSeVlXbHNJR052Ykc5eUxseHVJQ0FnSUNBZ0lDQWdJQ0FnWTI5dWMzUWdkSEpoYVd4RGIyeHZjaUE5SUNoY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnZEhKaGFXd3VZMjlzYjNJZ1BUMGdiblZzYkNBL0lIUm9hWE11WjJWdVpYSmhkR1ZVY21GcGJFTnZiRzl5S0hSeVlXbHNMblJwWkNrZ09pQjBjbUZwYkM1amIyeHZjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0tUdGNibHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OGdWWEJrWVhSbElGUnlZV2xzSUVobFlXUmxjaUJDYjNKa1pYSWdkRzhnWW1VZ2FXNGdjMkZ0WlNCamIyeHZjaUJoY3lCMGNtRnBiQzVjYmlBZ0lDQWdJQ0FnSUNBZ0lHTnZibk4wSUhSeVlXbHNTR1ZoWkdWeUlEMGdaR0YwWVZObGRITXVibTlrWlhNdVoyVjBLSFJ5WVdsc0xtbGtLVHRjYmlBZ0lDQWdJQ0FnSUNBZ0lIUnlZV2xzU0dWaFpHVnlMbU52Ykc5eUxtSnZjbVJsY2lBOUlIUnlZV2xzUTI5c2IzSTdYRzRnSUNBZ0lDQWdJQ0FnSUNCMGNtRnBiRWhsWVdSbGNpNXZjbWxuYVc1aGJFOXdkR2x2Ym5NdVkyOXNiM0l1WW05eVpHVnlJRDBnZEhKaGFXeERiMnh2Y2p0Y2JpQWdJQ0FnSUNBZ0lDQWdJSFJ5WVdsc1NHVmhaR1Z5YzFGMVpYVmxMbkIxYzJnb2RISmhhV3hJWldGa1pYSXBPMXh1WEc0Z0lDQWdJQ0FnSUNBZ0lDQmpiMjV6ZENCd2IzTnBkR2x2YmlBOUlIdGNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ2VEb2djRzl6YVhScGIyNVRkR1Z3TG5nZ0tpQjBjbUZwYkVsdVpHVjRMRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQjVPaUF3WEc0Z0lDQWdJQ0FnSUNBZ0lDQjlPMXh1WEc0Z0lDQWdJQ0FnSUNBZ0lDQXZMeUJEY21WaGRHVWdiR2x1YTNNZ1ltVjBkMlZsYmlCdWIyUmxjeTVjYmlBZ0lDQWdJQ0FnSUNBZ0lIUnlZV2xzTG14cGJtdHpMbVp2Y2tWaFkyZ29LR3hwYm1zc0lHeHBibXRKYm1SbGVDa2dQVDRnZTF4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0JqYjI1emRDQnNhVzVyVDNCMGFXOXVjeUE5SUh0Y2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMGNtRnBiQ3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JzYVc1ckxGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIUnlZV2xzUTI5c2IzSXNYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdZMmh2YzJWdU9pQnNhVzVyU1c1a1pYZ2dQVDA5SURBZ1B5QjdJR1ZrWjJVNklHWmhiSE5sSUgwZ09pQjBjblZsWEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJSDA3WEc1Y2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnWkdGMFlWTmxkSE11YkdsdWEzTXVZV1JrS0Z4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhSb2FYTXVjSEpsY0dGeVpVeHBibXRFWVhSaEtHeHBibXRQY0hScGIyNXpLVnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQXBPMXh1WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQzh2SUZObGRDQjRMM2tnY0c5emFYUnBiMjRnWm05eUlIUnlZV2xzSUhkb2FXTm9JR2x6SUdScGNtVmpkR3g1SUdOdmJtNWxZM1JsWkNCMGJ5QnViMlJsY3k1Y2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnY0c5emFYUnBiMjR1ZVNBclBTQndiM05wZEdsdmJsTjBaWEF1ZVR0Y2JseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkx5QlRaWFFnY0c5emFYUnBiMjRnWm05eUlHWnBjbk4wSUc1dlpHVWdiMllnWldSblpTaHNhVzVyS1Z4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0J1YjJSbGMxRjFaWFZsTG5CMWMyZ29lMXh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xrT2lCc2FXNXJMbVp5YjIwc1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2VEb2djRzl6YVhScGIyNHVlQ3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I1T2lCd2IzTnBkR2x2Ymk1NVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUgwcE8xeHVYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDOHZJRlZ3WkdGMFpTQnpaV052Ym1RZ2JtOWtaU0JwYmlCaElHVmtaMlVvYkdsdWF5bGNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYkdsdWEwbHVaR1Y0SUQwOVBTQjBjbUZwYkM1c2FXNXJjeTVzWlc1bmRHZ2dMU0F4SUM4dklFeGhjM1FnYm05a1pTQnBiaUJoSUhSeVlXbHNMbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZkh4Y2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIUnlZV2xzTG14cGJtdHpMbXhsYm1kMGFDQTlQVDBnTVNBdkx5QkRZWE5sSUdadmNpQnphVzVuYkdVZ2MybHVheUJwYmlCaElIUnlZV2xzTGx4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNrZ2UxeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNCdWIyUmxjMUYxWlhWbExuQjFjMmdvZTF4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXUTZJR3hwYm1zdWRHOHNYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I0T2lCd2IzTnBkR2x2Ymk1NExGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlVG9nY0c5emFYUnBiMjR1ZVNBcklIQnZjMmwwYVc5dVUzUmxjQzU1WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlNrN1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2JpQWdJQ0FnSUNBZ0lDQWdJSDBwTzF4dUlDQWdJQ0FnSUNBZ0lIMWNibHh1SUNBZ0lDQWdJQ0FnSUM4dklGVndaR0YwWlNCa1lYUmhVMlYwY3lBbUlGSmxMVVJ5WVhjZ1kyRnVkbUZ6TGx4dUlDQWdJQ0FnSUNBZ0lHUmhkR0ZUWlhSekxtNXZaR1Z6TG5Wd1pHRjBaU2gwY21GcGJFaGxZV1JsY25OUmRXVjFaU2s3WEc0Z0lDQWdJQ0FnSUNBZ1pHRjBZVk5sZEhNdWJtOWtaWE11ZFhCa1lYUmxLRzV2WkdWelVYVmxkV1VwTzF4dUlDQWdJQ0FnSUNCOUtUdGNiaUFnSUNBZ0lIMWNibHh1SUNBZ0lDQWdkR2hwY3k1bGJtRmliR1ZEYjI1MFpYaDBkV0ZzVFdWdWRTZ3BPMXh1SUNBZ0lDQWdkR2hwY3k1d2NtVndZWEpsVG1WMGQyOXlheWhqYjI1MFpYaDBMQ0JrWVhSaFUyVjBjeWs3WEc1Y2JpQWdJQ0FnSUM4dklGTjVibU1nWkdGMFlTQW9jM1J2Y21VZ1pHRjBZU0JuYkc5aVlXeHNlU0JwYmlCa2NuVndZV3dnYzJWMGRHbHVaeUF0SUcxdmMzUnNlU0JtYjNJZ1pHVmlkV2RuYVc1bklIQjFjbkJ2YzJWektWeHVJQ0FnSUNBZ1pISjFjR0ZzVTJWMGRHbHVaM011ZEhKaGFXeGZaM0poY0dndVpHRjBZVk5sZEhNZ1BTQmtZWFJoVTJWMGN6dGNiaUFnSUNBZ0lHUnlkWEJoYkZObGRIUnBibWR6TG5SeVlXbHNYMmR5WVhCb0xuUnlZV2xzY3lBOUlIUnlZV2xzY3p0Y2JpQWdJQ0FnSUdSeWRYQmhiRk5sZEhScGJtZHpMblJ5WVdsc1gyZHlZWEJvTG1adlkzVnpWRzlHYVhKemRGUnlZV2xzSUQwZ1ptOWpkWE5VYjBacGNuTjBWSEpoYVd3N1hHNGdJQ0FnSUNCa2NuVndZV3hUWlhSMGFXNW5jeTUwY21GcGJGOW5jbUZ3YUM1bWFXeDBaWEpKYm5CMWRITWdQU0JtYVd4MFpYSkpibkIxZEhNN1hHNGdJQ0FnZlN4Y2JseHVJQ0FnSUM4cUtseHVJQ0FnSUNBcUlGQnlaWEJoY21VZ1RtVjBkMjl5YXk1Y2JpQWdJQ0FnS2x4dUlDQWdJQ0FxSUVCd1lYSmhiU0JqYjI1MFpYaDBYRzRnSUNBZ0lDb2dRSEJoY21GdElHUmhkR0ZUWlhSelhHNGdJQ0FnSUNvdlhHNGdJQ0FnY0hKbGNHRnlaVTVsZEhkdmNtc29ZMjl1ZEdWNGRDd2daR0YwWVZObGRITXBJSHRjYmlBZ0lDQWdJR052Ym5OMElITjBlV3hsY3lBOUlIUm9hWE11YzNSNWJHVnpPMXh1SUNBZ0lDQWdZMjl1YzNRZ1pHRjBZU0E5SUh0Y2JpQWdJQ0FnSUNBZ2JtOWtaWE02SUdSaGRHRlRaWFJ6TG01dlpHVnpMRnh1SUNBZ0lDQWdJQ0JsWkdkbGN6b2daR0YwWVZObGRITXViR2x1YTNOY2JpQWdJQ0FnSUgwN1hHNGdJQ0FnSUNCamIyNXpkQ0J2Y0hScGIyNXpJRDBnZTF4dUlDQWdJQ0FnSUNCaGRYUnZVbVZ6YVhwbE9pQjBjblZsTEZ4dUlDQWdJQ0FnSUNCM2FXUjBhRG9nSnpFd01DVW5MRnh1SUNBZ0lDQWdJQ0JvWldsbmFIUTZJQ2MzTURCd2VDY3NYRzRnSUNBZ0lDQWdJSEJvZVhOcFkzTTZJR1poYkhObExGeHVJQ0FnSUNBZ0lDQnNZWGx2ZFhRNklIdGNiaUFnSUNBZ0lDQWdJQ0JvYVdWeVlYSmphR2xqWVd3NklHWmhiSE5sTEZ4dUlDQWdJQ0FnSUNCOUxGeHVJQ0FnSUNBZ0lDQnViMlJsY3pvZ2UxeHVJQ0FnSUNBZ0lDQWdJSE5vWVhCbE9pQW5ZbTk0Snl4Y2JpQWdJQ0FnSUNBZ0lDQjNhV1IwYUVOdmJuTjBjbUZwYm5RNklIdGNiaUFnSUNBZ0lDQWdJQ0FnSUcxaGVHbHRkVzA2SUhOMGVXeGxjeTV1YjJSbExtSnZlRTFoZUZkcFpIUm9MRnh1SUNBZ0lDQWdJQ0FnSUgwc1hHNGdJQ0FnSUNBZ0lDQWdabTl1ZERvZ2UxeHVJQ0FnSUNBZ0lDQWdJQ0FnYlhWc2RHazZJSFJ5ZFdVc1hHNGdJQ0FnSUNBZ0lDQWdmU3hjYmlBZ0lDQWdJQ0FnSUNCamIyeHZjam9nZTF4dUlDQWdJQ0FnSUNBZ0lDQWdZbTl5WkdWeU9pQnpkSGxzWlhNdWJtOWtaUzVqYjJ4dmNpNWliM0prWlhJdWFXNXBkR2xoYkN4Y2JpQWdJQ0FnSUNBZ0lDQWdJR0poWTJ0bmNtOTFibVE2SUhOMGVXeGxjeTV1YjJSbExtTnZiRzl5TG1KaFkydG5jbTkxYm1RdWFXNXBkR2xoYkN4Y2JpQWdJQ0FnSUNBZ0lDQWdJR2hwWjJoc2FXZG9kRG9nZTF4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0JpYjNKa1pYSTZJSE4wZVd4bGN5NXViMlJsTG1OdmJHOXlMbUp2Y21SbGNpNW9hV2RvYkdsbmFIUmxaQ3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdZbUZqYTJkeWIzVnVaRG9nYzNSNWJHVnpMbTV2WkdVdVkyOXNiM0l1WW1GamEyZHliM1Z1WkM1elpXeGxZM1JsWkN4Y2JpQWdJQ0FnSUNBZ0lDQWdJSDBzWEc0Z0lDQWdJQ0FnSUNBZ2ZTeGNiaUFnSUNBZ0lDQWdJQ0JtYVhobFpEb2dabUZzYzJWY2JpQWdJQ0FnSUNBZ2ZTeGNiaUFnSUNBZ0lDQWdaV1JuWlhNNklIdGNiaUFnSUNBZ0lDQWdJQ0IzYVdSMGFEb2djM1I1YkdWekxteHBibXN1ZDJsa2RHZ3NYRzRnSUNBZ0lDQWdJQ0FnYzIxdmIzUm9PaUJtWVd4elpTeGNiaUFnSUNBZ0lDQWdmU3hjYmlBZ0lDQWdJSDA3WEc1Y2JpQWdJQ0FnSUhSb2FYTXVaR2x6Y0d4aGVVNWxkSGR2Y21zb1kyOXVkR1Y0ZEN3Z1pHRjBZU3dnYjNCMGFXOXVjeWs3WEc0Z0lDQWdmU3hjYmx4dUlDQWdJQzhxS2x4dUlDQWdJQ0FxSUVScGMzQnNZWGtnVG1WMGQyOXlheTVjYmlBZ0lDQWdLbHh1SUNBZ0lDQXFJRUJ3WVhKaGJTQmpiMjUwWlhoMFhHNGdJQ0FnSUNvZ1FIQmhjbUZ0SUdSaGRHRmNiaUFnSUNBZ0tpQkFjR0Z5WVcwZ2IzQjBhVzl1YzF4dUlDQWdJQ0FxTDF4dUlDQWdJR1JwYzNCc1lYbE9aWFIzYjNKcktHTnZiblJsZUhRc0lHUmhkR0VzSUc5d2RHbHZibk1wSUh0Y2JpQWdJQ0FnSUdOdmJuTjBJRzVsZEhkdmNtc2dQU0J1WlhjZ2RtbHpMazVsZEhkdmNtc29YRzRnSUNBZ0lDQWdJQ1FvSnlOMGNtRnBiQzFuY21Gd2FDMWpZVzUyWVhNbkxDQmpiMjUwWlhoMEtWc3dYU3hjYmlBZ0lDQWdJQ0FnWkdGMFlTeGNiaUFnSUNBZ0lDQWdiM0IwYVc5dWN5eGNiaUFnSUNBZ0lDazdYRzVjYmlBZ0lDQWdJQzh2SUZOMGIzSnBibWNnYm1WMGQyOXlheUJrWVhSaElHZHNiMkpoYkd4NUxseHVJQ0FnSUNBZ1pISjFjR0ZzVTJWMGRHbHVaM011ZEhKaGFXeGZaM0poY0dndWJtVjBkMjl5YXlBOUlHNWxkSGR2Y21zN1hHNGdJQ0FnSUNCa2NuVndZV3hUWlhSMGFXNW5jeTUwY21GcGJGOW5jbUZ3YUM1dVpYUjNiM0pyU1hORWNtRjNiaUE5SUdaaGJITmxPMXh1WEc0Z0lDQWdJQ0F2THlCT1pYUjNiM0pySUdWMlpXNTBjMXh1SUNBZ0lDQWdMeThnVkU5RVR6b2dUR2x1YXlCamNtVmhkR2x2YmlCdFpYUm9iMlF1WEc0Z0lDQWdJQ0J1WlhSM2IzSnJMbTl1S0Z3aWIyNWpiMjUwWlhoMFhDSXNJSEJoY21GdGN5QTlQaUI3WEc0Z0lDQWdJQ0FnSUdOdmJuTjBJSE5sYkdWamRHVmtUbTlrWlVsa0lEMGdibVYwZDI5eWF5NW5aWFJPYjJSbFFYUW9jR0Z5WVcxekxuQnZhVzUwWlhJdVJFOU5LVHRjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdaR0YwWVZObGRITWdQU0JrY25Wd1lXeFRaWFIwYVc1bmN5NTBjbUZwYkY5bmNtRndhQzVrWVhSaFUyVjBjenRjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdKRzFsYm5WRGIyNTBaVzUwSUQwZ0pDZ25QSFZzUGljcE8xeHVYRzRnSUNBZ0lDQWdJR2xtSUNoelpXeGxZM1JsWkU1dlpHVkpaQ2tnZTF4dUlDQWdJQ0FnSUNBZ0lHTnZibk4wSUc1dlpHVWdQU0JrWVhSaFUyVjBjeTV1YjJSbGN5NW5aWFFvYzJWc1pXTjBaV1JPYjJSbFNXUXBPMXh1WEc0Z0lDQWdJQ0FnSUNBZ2FXWWdLRzV2WkdVdWFYTklaV0ZrWlhJcElIc2dMeThnVkhKaGFXd2dhR1ZoWkdWeUxseHVJQ0FnSUNBZ0lDQWdJQ0FnSkcxbGJuVkRiMjUwWlc1MExtRndjR1Z1WkNoY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSkNnblBHeHBQaWNwTG1Gd2NHVnVaQ2hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IwYUdsekxtTnlaV0YwWlVadlkzVnpUMjVVYUdselZISmhhV3hNYVc1cktGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdSSEoxY0dGc0xuUW9KMFp2WTNWeklHOXVJSFJvYVhNZ2RISmhhV3duS1N4Y2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1ZqZEdWa1RtOWtaVWxrWEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnS1Z4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FwTEZ4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FrS0NjOGFISStKeWtzWEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ1FvSnp4c2FUNG5LUzVoY0hCbGJtUW9YRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkR2hwY3k1amNtVmhkR1ZUYUc5M1ZISmhhV3hNYVc1cktGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdSSEoxY0dGc0xuUW9KMFZrYVhRZ2RISmhhV3dnYjNKa1pYSW5LU3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdBdmRISmhhV3hmWjNKaGNHZ3ZibTlrWlY5dmNtUmxjaThrZTI1dlpHVXVkR2xrZldBc1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmpiMjUwWlhoMFhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0tWeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBcExGeHVJQ0FnSUNBZ0lDQWdJQ0FnS1R0Y2JpQWdJQ0FnSUNBZ0lDQjlYRzRnSUNBZ0lDQWdJQ0FnWld4elpTQjdYRzRnSUNBZ0lDQWdJQ0FnSUNBdkx5Qk9iMlJsTGx4dUlDQWdJQ0FnSUNBZ0lDQWdKRzFsYm5WRGIyNTBaVzUwTG1Gd2NHVnVaQ2hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdKQ2duUEd4cFBpY3BMbUZ3Y0dWdVpDaHViMlJsTG1OdmJuUmxiblJRY21WMmFXVjNLU3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdKQ2duUEdoeVBpY3BMRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWtLQ2M4YkdrK0p5a3VZWEJ3Wlc1a0tGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIUm9hWE11WTNKbFlYUmxUVzlrWVd4TWFXNXJLRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnUkhKMWNHRnNMblFvSjBWa2FYUWdibTlrWlNjcExGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdZQzl1YjJSbEx5UjdjMlZzWldOMFpXUk9iMlJsU1dSOUwyVmthWFEvYldsdFBYUnlZV2xzWDJkeVlYQm9ZQ3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhzZ2QybGtkR2c2SUNjNE1DVW5JSDFjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FwWEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ2tzWEc0Z0lDQWdJQ0FnSUNBZ0lDQXBPMXh1SUNBZ0lDQWdJQ0FnSUgxY2JseHVJQ0FnSUNBZ0lDQWdJSFJvYVhNdWMyaHZkME52Ym5SbGVIUjFZV3hOWlc1MUtGeHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0I0T2lCd1lYSmhiWE11WlhabGJuUXVlQ3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdlVG9nY0dGeVlXMXpMbVYyWlc1MExua3NYRzRnSUNBZ0lDQWdJQ0FnSUNCOUxGeHVJQ0FnSUNBZ0lDQWdJQ0FnWTI5dWRHVjRkQ3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDUnRaVzUxUTI5dWRHVnVkRnh1SUNBZ0lDQWdJQ0FnSUNrN1hHNGdJQ0FnSUNBZ0lIMWNiaUFnSUNBZ0lIMHBPMXh1SUNBZ0lDQWdibVYwZDI5eWF5NXZiaWhjSW5ObGJHVmpkRTV2WkdWY0lpd2djR0Z5WVcxeklEMCtJSHRjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdibTlrWlVsa0lEMGdLSEJoY21GdGN5NXViMlJsY3k1c1pXNW5kR2dnUGlBd0lEOGdjR0Z5WVcxekxtNXZaR1Z6V3pCZElEb2diblZzYkNrN1hHNWNiaUFnSUNBZ0lDQWdhV1lnS0c1dlpHVkpaQ0FoUFQwZ2JuVnNiQ0FtSmlCMGFHbHpMbWx6VkhKaGFXeElaV0ZrWlhJb2JtOWtaVWxrS1NrZ2UxeHVJQ0FnSUNBZ0lDQWdJSFJvYVhNdWFHbG5hR3hwWjJoMFZISmhhV3dvYm05a1pVbGtLVnh1SUNBZ0lDQWdJQ0FnSUM1MGFHVnVLQ2dwSUQwK0lIUm9hWE11Ykc5amEwNXZaR1Z6S0NrcFhHNGdJQ0FnSUNBZ0lDQWdMbU5oZEdOb0tHVnljaUE5UGlCN1hHNGdJQ0FnSUNBZ0lDQWdJQ0JqYjI1emIyeGxMbVZ5Y205eUtHVnljaWs3WEc0Z0lDQWdJQ0FnSUNBZ2ZTazdYRzRnSUNBZ0lDQWdJSDFjYmlBZ0lDQWdJSDBwTzF4dUlDQWdJQ0FnYm1WMGQyOXlheTV2YmloY0ltUmxjMlZzWldOMFRtOWtaVndpTENBb0tTQTlQaUI3WEc0Z0lDQWdJQ0FnSUhSb2FYTXVjbVZ6WlhSVWNtRnBiRWhwWjJoc2FXZG9kQ2dwTG5Sb1pXNG9YRzRnSUNBZ0lDQWdJQ0FnS0NrZ1BUNGdkR2hwY3k1MWJteHZZMnRPYjJSbGN5Z3BYRzRnSUNBZ0lDQWdJQ2s3WEc0Z0lDQWdJQ0I5S1R0Y2JpQWdJQ0FnSUc1bGRIZHZjbXN1YjI0b1hDSmtjbUZuVTNSaGNuUmNJaXdnS0NrZ1BUNGdlMXh1SUNBZ0lDQWdJQ0FrS0NjamRISmhhV3d0WjNKaGNHZ3RZMkZ1ZG1Gekp5d2dZMjl1ZEdWNGRDa3VZM056S0NkamRYSnpiM0luTENBbmJXOTJaU2NwTzF4dUlDQWdJQ0FnZlNrN1hHNGdJQ0FnSUNCdVpYUjNiM0pyTG05dUtGd2laSEpoWjBWdVpGd2lMQ0FvS1NBOVBpQjdYRzRnSUNBZ0lDQWdJQ1FvSnlOMGNtRnBiQzFuY21Gd2FDMWpZVzUyWVhNbkxDQmpiMjUwWlhoMEtTNWpjM01vSjJOMWNuTnZjaWNzSUNka1pXWmhkV3gwSnlrN1hHNGdJQ0FnSUNCOUtUdGNiaUFnSUNBZ0lHNWxkSGR2Y21zdWIyNG9YQ0poWm5SbGNrUnlZWGRwYm1kY0lpd2dLQ2tnUFQ0Z2UxeHVYRzRnSUNBZ0lDQWdJQzh2SUVsbUlHMXZjbVVnZEdoaGJpQjBjbUZwYkNCcGN5QmthWE53YkdGNVpXUXNJSE5sZENCbWIyTjFjeUIwYnlCbWFYSnpkQ0J2Ym1VdVhHNGdJQ0FnSUNBZ0lHbG1JQ2hjYmlBZ0lDQWdJQ0FnSUNBZ0lHUnlkWEJoYkZObGRIUnBibWR6TG5SeVlXbHNYMmR5WVhCb0xtWnZZM1Z6Vkc5R2FYSnpkRlJ5WVdsc0lDWW1YRzRnSUNBZ0lDQWdJQ0FnSUNBaFpISjFjR0ZzVTJWMGRHbHVaM011ZEhKaGFXeGZaM0poY0dndWJtVjBkMjl5YTBselJISmhkMjVjYmlBZ0lDQWdJQ0FnSUNBcElIdGNiaUFnSUNBZ0lDQWdMeThnU1hSeklHbHRjRzl5ZEdGdWRDQjBieUJ6WlhRZ2JtVjBkMjl5YTBselJISmhkMjRnZEc4Z1ZGSlZSU3dnYjNSb1pYSjNhWE5sSUhSb1pYSmxJSGRwYkd3Z1ltVWdhVzVtYVc1cGRHVWdiRzl2Y0NFaElWeHVJQ0FnSUNBZ0lDQWdJR1J5ZFhCaGJGTmxkSFJwYm1kekxuUnlZV2xzWDJkeVlYQm9MbTVsZEhkdmNtdEpjMFJ5WVhkdUlEMGdkSEoxWlR0Y2JseHVJQ0FnSUNBZ0lDQWdJR2xtSUNoa2NuVndZV3hUWlhSMGFXNW5jeTUwY21GcGJGOW5jbUZ3YUM1bWFXeDBaWEpKYm5CMWRITmJKM1J5WVdsc1gybGtKMTBwSUh0Y2JpQWdJQ0FnSUNBZ0lDQWdJSFJvYVhNdWRYQmtZWFJsVkhKaGFXeFdaWEowYVdOaGJFRnljbUZ1WjJWdFpXNTBLR1J5ZFhCaGJGTmxkSFJwYm1kekxuUnlZV2xzWDJkeVlYQm9MbVpwYkhSbGNrbHVjSFYwYzFzbmRISmhhV3hmYVdRblhWc3dYU2s3WEc0Z0lDQWdJQ0FnSUNBZ2ZWeHVJQ0FnSUNBZ0lDQjlYRzRnSUNBZ0lDQjlLVHRjYmlBZ0lDQjlMRnh1WEc0Z0lDQWdMeW9xWEc0Z0lDQWdJQ29nUTI5dWRHVjRkSFZoYkNCdFpXNTFJR1oxYm1OMGFXOXVjeTVjYmlBZ0lDQWdLaTljYmlBZ0lDQmxibUZpYkdWRGIyNTBaWGgwZFdGc1RXVnVkU2dwSUh0Y2JpQWdJQ0FnSUNRb1pHOWpkVzFsYm5RcExtSnBibVFvWENKamIyNTBaWGgwYldWdWRWd2lMQ0JsSUQwK0lHVXVjSEpsZG1WdWRFUmxabUYxYkhRb0tTazdYRzRnSUNBZ2ZTeGNiaUFnSUNCemFHOTNRMjl1ZEdWNGRIVmhiRTFsYm5Vb2NHOXphWFJwYjI0c0lHTnZiblJsZUhRc0lHTnZiblJsYm5RcElIdGNiaUFnSUNBZ0lDUW9YQ0l1ZEhKaGFXd3RaM0poY0doZlgyTnZiblJsZUhSMVlXd3RiV1Z1ZFZ3aUxDQmpiMjUwWlhoMEtWeHVJQ0FnSUNBZ0lDQXVaVzF3ZEhrb0tWeHVJQ0FnSUNBZ0lDQXVZWEJ3Wlc1a0tHTnZiblJsYm5RcFhHNGdJQ0FnSUNBZ0lDNWpjM01vZTF4dUlDQWdJQ0FnSUNBZ0lGd2liR1ZtZEZ3aU9pQndiM05wZEdsdmJpNTRMRnh1SUNBZ0lDQWdJQ0FnSUZ3aWRHOXdYQ0k2SUhCdmMybDBhVzl1TG5sY2JpQWdJQ0FnSUNBZ2ZTbGNiaUFnSUNBZ0lDQWdMbVpoWkdWSmJpZ3lNREFzSUhSb2FYTXVjM1JoY25SR2IyTjFjMDkxZENoamIyNTBaWGgwS1NrN1hHNWNiaUFnSUNBZ0lFUnlkWEJoYkM1aGRIUmhZMmhDWldoaGRtbHZjbk1vS1R0Y2JpQWdJQ0I5TEZ4dUlDQWdJSE4wWVhKMFJtOWpkWE5QZFhRb1kyOXVkR1Y0ZENrZ2UxeHVJQ0FnSUNBZ0pDaGtiMk4xYldWdWRDa3ViMjRvWENKamJHbGphMXdpTENBb0tTQTlQaUI3WEc0Z0lDQWdJQ0FnSUNRb1hDSXVkSEpoYVd3dFozSmhjR2hmWDJOdmJuUmxlSFIxWVd3dGJXVnVkVndpTENCamIyNTBaWGgwS1M1b2FXUmxLQ2s3WEc0Z0lDQWdJQ0FnSUNRb1pHOWpkVzFsYm5RcExtOW1aaWhjSW1Oc2FXTnJYQ0lwTzF4dUlDQWdJQ0FnZlNrN1hHNGdJQ0FnZlN4Y2JpQWdJQ0JqY21WaGRHVk5iMlJoYkV4cGJtc29kR2wwYkdVc0lIVnliQ3dnYjNCMGFXOXVjeWtnZTF4dUlDQWdJQ0FnY21WMGRYSnVJQ1FvSnp4aFBpY3BYRzRnSUNBZ0lDQXVZWFIwY2lnbmFISmxaaWNzSUhWeWJDbGNiaUFnSUNBZ0lDNTBaWGgwS0hScGRHeGxLVnh1SUNBZ0lDQWdMbUZrWkVOc1lYTnpLQ2QxYzJVdFlXcGhlQ2NwWEc0Z0lDQWdJQ0F1WkdGMFlTaDdYRzRnSUNBZ0lDQWdJQ2RrYVdGc2IyY3RkSGx3WlNjNklDZHRiMlJoYkNjc1hHNGdJQ0FnSUNBZ0lDZGthV0ZzYjJjdGIzQjBhVzl1Y3ljNklHOXdkR2x2Ym5Nc1hHNGdJQ0FnSUNCOUtUdGNiaUFnSUNCOUxGeHVJQ0FnSUdOeVpXRjBaVWx1YkdsdVpVMXZaR0ZzVEdsdWF5aHRiMlJoYkZScGRHeGxMQ0JzYVc1clZHbDBiR1VzSUNSdGIyUmhiRU52Ym5SbGJuUXBJSHRjYmlBZ0lDQWdJR2xtSUNoMGVYQmxiMllnSkcxdlpHRnNRMjl1ZEdWdWRDQTlQVDBnSjNOMGNtbHVaeWNwSUh0Y2JpQWdJQ0FnSUNBZ0pHMXZaR0ZzUTI5dWRHVnVkQ0E5SUNRb0pHMXZaR0ZzUTI5dWRHVnVkQ2s3WEc0Z0lDQWdJQ0I5WEc1Y2JpQWdJQ0FnSUhKbGRIVnliaUFrS0NjOFlUNG5LVnh1SUNBZ0lDQWdMbUYwZEhJb0oyaHlaV1luTENBbkl5Y3BYRzRnSUNBZ0lDQXVkR1Y0ZENoc2FXNXJWR2wwYkdVcFhHNGdJQ0FnSUNBdWIyNG9KMk5zYVdOckp5d2dLQ2tnUFQ0Z2RHaHBjeTV6YUc5M1NXNXNhVzVsVFc5a1lXd29iVzlrWVd4VWFYUnNaU3dnSkcxdlpHRnNRMjl1ZEdWdWRDa3BPMXh1SUNBZ0lIMHNYRzRnSUNBZ1kzSmxZWFJsUm05amRYTlBibFJvYVhOVWNtRnBiRXhwYm1zb2JHbHVhMVJwZEd4bExDQnViMlJsU1dRcElIdGNiaUFnSUNBZ0lISmxkSFZ5YmlBa0tDYzhZVDRuS1Z4dUlDQWdJQ0FnTG1GMGRISW9KMmh5WldZbkxDQW5JeWNwWEc0Z0lDQWdJQ0F1ZEdWNGRDaHNhVzVyVkdsMGJHVXBYRzRnSUNBZ0lDQXViMjRvSjJOc2FXTnJKeXdnS0NrZ1BUNGdkR2hwY3k1MWNHUmhkR1ZVY21GcGJGWmxjblJwWTJGc1FYSnlZVzVuWlcxbGJuUW9ibTlrWlVsa0xDQjBjblZsS1NrN1hHNGdJQ0FnZlN4Y2JpQWdJQ0JqY21WaGRHVlRhRzkzVkhKaGFXeE1hVzVyS0d4cGJtdFVhWFJzWlN3Z1pXNWtjRzlwYm5Rc0lHTnZiblJsZUhRcElIdGNiaUFnSUNBZ0lISmxkSFZ5YmlBa0tDYzhZVDRuS1Z4dUlDQWdJQ0FnTG1GMGRISW9KMmh5WldZbkxDQW5JeWNwWEc0Z0lDQWdJQ0F1ZEdWNGRDaHNhVzVyVkdsMGJHVXBYRzRnSUNBZ0lDQXViMjRvSjJOc2FXTnJKeXdnS0NrZ1BUNGdkR2hwY3k1emFHOTNWSEpoYVd4TWFXNXJLR1Z1WkhCdmFXNTBMQ0JqYjI1MFpYaDBLU2s3WEc0Z0lDQWdmU3hjYmx4dUlDQWdJQzhxS2x4dUlDQWdJQ0FxSUUxdlpHRnNJSGRwYm1SdmR5NWNiaUFnSUNBZ0tpOWNiaUFnSUNCemFHOTNTVzVzYVc1bFRXOWtZV3dvZEdsMGJHVXNJR052Ym5SbGJuUXBJSHRjYmlBZ0lDQWdJR052Ym5OMElHMXZaR0ZzSUQwZ0pDZ25QR1JwZGo0bktTNW9kRzFzS0dOdmJuUmxiblFwTzF4dUlDQWdJQ0FnWTI5dWMzUWdiM0IwYVc5dWN5QTlJSHRjYmlBZ0lDQWdJQ0FnZEdsMGJHVTZJSFJwZEd4bExGeHVJQ0FnSUNBZ0lDQjNhV1IwYURvZ2RHaHBjeTV6ZEhsc1pYTXViVzlrWVd3dWQybGtkR2dzWEc0Z0lDQWdJQ0I5TzF4dVhHNGdJQ0FnSUNCRWNuVndZV3d1WkdsaGJHOW5LRzF2WkdGc0xDQnZjSFJwYjI1ektTNXphRzkzVFc5a1lXd29LVHRjYmlBZ0lDQjlMRnh1WEc0Z0lDQWdMeW9xWEc0Z0lDQWdJQ29nVkhKaGFXd2dMeUJNYVc1cklHWjFibU4wYVc5dWN5NWNiaUFnSUNBZ0tpOWNiaUFnSUNCemFHOTNWSEpoYVd4TWFXNXJLR1Z1WkhCdmFXNTBLU0I3WEc0Z0lDQWdJQ0JFY25Wd1lXd3VZV3BoZUNoN1hHNGdJQ0FnSUNBZ0lIVnliRG9nWlc1a2NHOXBiblJjYmlBZ0lDQWdJSDBwTG1WNFpXTjFkR1VvS1R0Y2JseHVJQ0FnSUNBZ0x5OGdVMmh2ZHlCVWNtRnBiSE1nZEdGaUlHTnZiblJsYm5RdVhHNGdJQ0FnSUNCRWNuVndZV3d1WW1Wb1lYWnBiM0p6TG5SeVlXbHNSM0poY0doVGFXUmxZbUZ5VkdGaWN5NXpkMmwwWTJoVVlXSW9KM1J5WVdsc0xXZHlZWEJvTFhSaFlpMHlKeWs3WEc0Z0lDQWdmU3hjYmlBZ0lDQm9hV2RvYkdsbmFIUlVjbUZwYkNoMFpYSnRTV1FnUFNCdWRXeHNLU0I3WEc0Z0lDQWdJQ0J5WlhSMWNtNGdibVYzSUZCeWIyMXBjMlVvS0hKbGMyOXNkbVVzSUhKbGFtVmpkQ2tnUFQ0Z2UxeHVJQ0FnSUNBZ0lDQnBaaUFvZEdWeWJVbGtJRDA5UFNCdWRXeHNLU0I3WEc0Z0lDQWdJQ0FnSUNBZ2NtVnFaV04wS0NkTmFYTnphVzVuSUhSbGNtMUpaQ0JtYjNJZ2FHbG5hR3hwWjJoMFZISmhhV3dvS1NjcE8xeHVJQ0FnSUNBZ0lDQjlYRzVjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdaR0YwWVZObGRITWdQU0JrY25Wd1lXeFRaWFIwYVc1bmN5NTBjbUZwYkY5bmNtRndhQzVrWVhSaFUyVjBjenRjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdiR2x1YTNNZ1BTQmtZWFJoVTJWMGN5NXNhVzVyY3k1blpYUW9LVHRjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdibTlrWlhNZ1BTQmtZWFJoVTJWMGN5NXViMlJsY3k1blpYUW9LVHRjYmlBZ0lDQWdJQ0FnWTI5dWMzUWdibTlrWlhOUmRXVjFaU0E5SUZ0ZE8xeHVJQ0FnSUNBZ0lDQmpiMjV6ZENCc2FXNXJjMUYxWlhWbElEMGdXMTA3WEc1Y2JpQWdJQ0FnSUNBZ2JHbHVhM011Wm05eVJXRmphQ2hzYVc1cklEMCtJSHRjYmlBZ0lDQWdJQ0FnSUNCcFppQW9iR2x1YXk1MGFXUWdJVDA5SUhSbGNtMUpaQ2tnZTF4dUlDQWdJQ0FnSUNBZ0lDQWdiR2x1YTNOUmRXVjFaUzV3ZFhOb0tIdGNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ2FXUTZJR3hwYm1zdWFXUXNYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lHTnZiRzl5T2lCN1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2IzQmhZMmwwZVRvZ2RHaHBjeTV6ZEhsc1pYTXViR2x1YXk1amIyeHZjaTV2Y0dGamFYUjVMbWhwWjJoc2FXZG9kR1ZrWEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJSDFjYmlBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh1SUNBZ0lDQWdJQ0FnSUgxY2JpQWdJQ0FnSUNBZ2ZTazdYRzRnSUNBZ0lDQWdJRzV2WkdWekxtWnZja1ZoWTJnb2JtOWtaU0E5UGlCN1hHNGdJQ0FnSUNBZ0lDQWdiR1YwSUhWd1pHRjBaVTVsWldSbFpDQTlJR1poYkhObE8xeHVJQ0FnSUNBZ0lDQWdJR052Ym5OMElHUmhkR0VnUFNCN1hHNGdJQ0FnSUNBZ0lDQWdJQ0JwWkRvZ2JtOWtaUzVwWkN4Y2JpQWdJQ0FnSUNBZ0lDQWdJR1p2Ym5RNklIc2dZMjlzYjNJNklIUm9hWE11YzNSNWJHVnpMbTV2WkdVdVptOXVkQzVqYjJ4dmNpNW9hV2RvYkdsbmFIUmxaQ0I5WEc0Z0lDQWdJQ0FnSUNBZ2ZUdGNibHh1SUNBZ0lDQWdJQ0FnSUdsbUlDaHViMlJsTG1selNHVmhaR1Z5S1NCN1hHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmlBb2JtOWtaUzVwWkNBaFBUMGdkR1Z5YlVsa0tTQjdYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lHUmhkR0V1WTI5c2IzSWdQU0I3WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWW1GamEyZHliM1Z1WkRvZ2RHaHBjeTVvWlhoVWIxSkhRaWh1YjJSbExtTnZiRzl5TG1KaFkydG5jbTkxYm1Rc0lEQXVNaWtzWEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWW05eVpHVnlPaUIwYUdsekxtaGxlRlJ2VWtkQ0tHNXZaR1V1WTI5c2IzSXVZbTl5WkdWeUxDQXdMaklwTEZ4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0I5TzF4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0IxY0dSaGRHVk9aV1ZrWldRZ1BTQjBjblZsTzF4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh1SUNBZ0lDQWdJQ0FnSUgxY2JpQWdJQ0FnSUNBZ0lDQmxiSE5sSUh0Y2JpQWdJQ0FnSUNBZ0lDQWdJR052Ym5OMElISmxiR0YwWldSVWNtRnBiSE1nUFNCdWIyUmxMbkpsYkdGMFpXUlVjbUZwYkhNdWJXRndLSFJ5WVdsc0lEMCtJQ2RVSnlBcklIUnlZV2xzS1R0Y2JpQWdJQ0FnSUNBZ0lDQWdJR2xtSUNnaGNtVnNZWFJsWkZSeVlXbHNjeTVwYm1Oc2RXUmxjeWgwWlhKdFNXUXBLU0I3WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJR1JoZEdFdVkyOXNiM0lnUFNCN1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1ltRmphMmR5YjNWdVpEb2dkR2hwY3k1emRIbHNaWE11Ym05a1pTNWpiMnh2Y2k1aVlXTnJaM0p2ZFc1a0xtaHBaMmhzYVdkb2RHVmtMRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR0p2Y21SbGNqb2dkR2hwY3k1emRIbHNaWE11Ym05a1pTNWpiMnh2Y2k1aWIzSmtaWEl1YUdsbmFHeHBaMmgwWldRc1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUgwN1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUhWd1pHRjBaVTVsWldSbFpDQTlJSFJ5ZFdVN1hHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEc0Z0lDQWdJQ0FnSUNBZ2ZWeHVYRzRnSUNBZ0lDQWdJQ0FnTHk4Z1VISnZZMlZsWkNCM2FYUm9JSFZ3WkdGMFpTQW9jMjl0WlNCallYTmxjeUIzWlNCa2IyNG5kQ0J1WldWa0lIUnZJSFZ3WkdGMFpTbGNiaUFnSUNBZ0lDQWdJQ0JwWmlBb2RYQmtZWFJsVG1WbFpHVmtLU0I3WEc0Z0lDQWdJQ0FnSUNBZ0lDQnViMlJsYzFGMVpYVmxMbkIxYzJnb1pHRjBZU2s3WEc0Z0lDQWdJQ0FnSUNBZ2ZWeHVJQ0FnSUNBZ0lDQjlLVHRjYmx4dUlDQWdJQ0FnSUNCa1lYUmhVMlYwY3k1c2FXNXJjeTUxY0dSaGRHVW9iR2x1YTNOUmRXVjFaU2s3WEc0Z0lDQWdJQ0FnSUdSaGRHRlRaWFJ6TG01dlpHVnpMblZ3WkdGMFpTaHViMlJsYzFGMVpYVmxLVHRjYmx4dUlDQWdJQ0FnSUNCeVpYTnZiSFpsS0NkVWNtRnBiQ0JJYVdkb2JHbG5hSFJsWkNjcE8xeHVJQ0FnSUNBZ2ZTazdYRzRnSUNBZ2ZTeGNiaUFnSUNCeVpYTmxkRlJ5WVdsc1NHbG5hR3hwWjJoMEtDa2dlMXh1SUNBZ0lDQWdjbVYwZFhKdUlHNWxkeUJRY205dGFYTmxLQ2h5WlhOdmJIWmxMQ0J5WldwbFkzUXBJRDArSUh0Y2JpQWdJQ0FnSUNBZ1kyOXVjM1FnWkdGMFlWTmxkSE1nUFNCa2NuVndZV3hUWlhSMGFXNW5jeTUwY21GcGJGOW5jbUZ3YUM1a1lYUmhVMlYwY3p0Y2JpQWdJQ0FnSUNBZ1kyOXVjM1FnYkdsdWEzTWdQU0JrWVhSaFUyVjBjeTVzYVc1cmN5NW5aWFFvS1R0Y2JpQWdJQ0FnSUNBZ1kyOXVjM1FnYm05a1pYTWdQU0JrWVhSaFUyVjBjeTV1YjJSbGN5NW5aWFFvS1R0Y2JpQWdJQ0FnSUNBZ1kyOXVjM1FnYm05a1pVTnZiRzl5VTNSNWJHVWdQU0IwYUdsekxuTjBlV3hsY3k1dWIyUmxMbU52Ykc5eU8xeHVJQ0FnSUNBZ0lDQmpiMjV6ZENCc2FXNXJjMUYxWlhWbElEMGdXMTA3WEc0Z0lDQWdJQ0FnSUdOdmJuTjBJRzV2WkdWelVYVmxkV1VnUFNCYlhUdGNibHh1SUNBZ0lDQWdJQ0JzYVc1cmN5NW1iM0pGWVdOb0tHeHBibXNnUFQ0Z2UxeHVJQ0FnSUNBZ0lDQWdJR3hwYm10elVYVmxkV1V1Y0hWemFDaDdYRzRnSUNBZ0lDQWdJQ0FnSUNCcFpEb2diR2x1YXk1cFpDeGNiaUFnSUNBZ0lDQWdJQ0FnSUdOdmJHOXlPaUI3WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJR052Ykc5eU9pQnNhVzVyTG05eWFXZHBibUZzVDNCMGFXOXVjeTVqYjJ4dmNpNWpiMnh2Y2l4Y2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnYjNCaFkybDBlVG9nZEdocGN5NXpkSGxzWlhNdWJHbHVheTVqYjJ4dmNpNXZjR0ZqYVhSNUxtbHVhWFJwWVd3c1hHNGdJQ0FnSUNBZ0lDQWdJQ0I5TEZ4dUlDQWdJQ0FnSUNBZ0lIMHBPMXh1SUNBZ0lDQWdJQ0I5S1R0Y2JpQWdJQ0FnSUNBZ2JtOWtaWE11Wm05eVJXRmphQ2h1YjJSbElEMCtJSHRjYmlBZ0lDQWdJQ0FnSUNCdWIyUmxjMUYxWlhWbExuQjFjMmdvZTF4dUlDQWdJQ0FnSUNBZ0lDQWdhV1E2SUc1dlpHVXVhV1FzWEc0Z0lDQWdJQ0FnSUNBZ0lDQm1iMjUwT2lCN0lHTnZiRzl5T2lCMGFHbHpMbk4wZVd4bGN5NXViMlJsTG1admJuUXVZMjlzYjNJdWFXNXBkR2xoYkNCOUxGeHVJQ0FnSUNBZ0lDQWdJQ0FnWTI5c2IzSTZJSHRjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdZbUZqYTJkeWIzVnVaRG9nS0Z4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUc1dlpHVXViM0pwWjJsdVlXeFBjSFJwYjI1eklEOGdibTlrWlM1dmNtbG5hVzVoYkU5d2RHbHZibk11WTI5c2IzSXVZbUZqYTJkeWIzVnVaQ0E2SUc1dlpHVkRiMnh2Y2xOMGVXeGxMbUpoWTJ0bmNtOTFibVF1YVc1cGRHbGhiRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQXBMRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQmliM0prWlhJNklDaGNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnViMlJsTG05eWFXZHBibUZzVDNCMGFXOXVjeUEvSUc1dlpHVXViM0pwWjJsdVlXeFBjSFJwYjI1ekxtTnZiRzl5TG1KdmNtUmxjaUE2SUc1dlpHVkRiMnh2Y2xOMGVXeGxMbUp2Y21SbGNpNXBibWwwYVdGc1hHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNsY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjYmlBZ0lDQWdJQ0FnSUNCOUtUdGNiaUFnSUNBZ0lDQWdmU2s3WEc1Y2JpQWdJQ0FnSUNBZ1pHRjBZVk5sZEhNdWJHbHVhM011ZFhCa1lYUmxLR3hwYm10elVYVmxkV1VwTzF4dUlDQWdJQ0FnSUNCa1lYUmhVMlYwY3k1dWIyUmxjeTUxY0dSaGRHVW9ibTlrWlhOUmRXVjFaU2s3WEc1Y2JpQWdJQ0FnSUNBZ2NtVnpiMngyWlNnblVtVnpaWFFnVkhKaGFXd2dTR2xuYUd4cFoyaDBJRk4xWTJObGMzTm1kV3hzZVNjcE8xeHVJQ0FnSUNBZ2ZTazdYRzRnSUNBZ2ZTeGNiaUFnSUNCMWNHUmhkR1ZVY21GcGJGWmxjblJwWTJGc1FYSnlZVzVuWlcxbGJuUW9kSEpoYVd4SlpDQTlJRzUxYkd3c0lISmxiV1Z0WW1WeVZISmhhV3hNWVhOMFVHOXphWFJwYjI0Z1BTQm1ZV3h6WlNrZ2UxeHVJQ0FnSUNBZ0lDQnBaaUFvZEhKaGFXeEpaQ0E5UFQwZ2JuVnNiQ2tnZTF4dUlDQWdJQ0FnSUNBZ0lHTnZibk52YkdVdVpYSnliM0lvSjAxcGMzTnBibWNnZEhKaGFXeEpaQ2NwTzF4dUlDQWdJQ0FnSUNBZ0lISmxkSFZ5Ymp0Y2JpQWdJQ0FnSUNBZ2ZWeHVYRzRnSUNBZ0lDQWdJR052Ym5OMElIUnlZV2xzY3lBOUlHNWxkeUIyYVhNdVJHRjBZVk5sZENoa2NuVndZV3hUWlhSMGFXNW5jeTUwY21GcGJGOW5jbUZ3YUM1MGNtRnBiSE1wTzF4dUlDQWdJQ0FnSUNCamIyNXpkQ0J1YjJSbFVHOXphWFJwYjI0Z1BTQjdJSGc2SURBc0lIazZJREFnZlR0Y2JpQWdJQ0FnSUNBZ1kyOXVjM1FnY0c5emFYUnBiMjVUZEdWd0lEMGdleUI0T2lBeU1EQXNJSGs2SURFd01DQjlPMXh1SUNBZ0lDQWdJQ0JqYjI1emRDQjBjbUZwYkVsdVpHVjRJRDBnZEdocGN5NW1hVzVrU1c1a1pYaFBaazlpYW1WamRDaDBjbUZwYkhNdVoyVjBLQ2tzSUNkcFpDY3NJSFJ5WVdsc1NXUXBPMXh1SUNBZ0lDQWdJQ0JqYjI1emRDQmtZWFJoVTJWMGN5QTlJR1J5ZFhCaGJGTmxkSFJwYm1kekxuUnlZV2xzWDJkeVlYQm9MbVJoZEdGVFpYUnpPMXh1SUNBZ0lDQWdJQ0JqYjI1emRDQnVaWFIzYjNKcklEMGdaSEoxY0dGc1UyVjBkR2x1WjNNdWRISmhhV3hmWjNKaGNHZ3VibVYwZDI5eWF6dGNiaUFnSUNBZ0lDQWdZMjl1YzNRZ2RISmhhV3dnUFNCMGNtRnBiSE11WjJWMEtIUnlZV2xzU1dRcE8xeHVJQ0FnSUNBZ0lDQmpiMjV6ZENCdWIyUmxjMUYxWlhWbElEMGdXMTA3WEc1Y2JpQWdJQ0FnSUNBZ2FXWWdLSFJ5WVdsc0xteHBibXR6TG14bGJtZDBhQ2tnZTF4dUlDQWdJQ0FnSUNBZ0lHNXZaR1ZRYjNOcGRHbHZiaTU0SUQwZ2NHOXphWFJwYjI1VGRHVndMbmdnS2lCMGNtRnBiRWx1WkdWNE8xeHVJQ0FnSUNBZ0lDQWdJRzV2WkdWUWIzTnBkR2x2Ymk1NUlEMGdNRHRjYmx4dUlDQWdJQ0FnSUNBZ0lDOHZJRWRsZENCMGNtRnBiQ0JvWldGa1pYSWdZM1Z5Y21WdWRDQndiM05wZEdsdmJpQmhibVFnZFhObElHbDBJR1p2Y2lCdWIyUmxjeTVjYmlBZ0lDQWdJQ0FnSUNCcFppQW9jbVZ0WlcxaVpYSlVjbUZwYkV4aGMzUlFiM05wZEdsdmJpa2dlMXh1SUNBZ0lDQWdJQ0FnSUNBZ1kyOXVjM1FnZEhKaGFXeElaV0ZrWlhKUWIzTnBkR2x2YmlBOUlHNWxkSGR2Y21zdVoyVjBVRzl6YVhScGIyNXpLRnQwY21GcGJFbGtYU2xiZEhKaGFXeEpaRjA3WEc0Z0lDQWdJQ0FnSUNBZ0lDQnViMlJsVUc5emFYUnBiMjR1ZUNBOUlIUnlZV2xzU0dWaFpHVnlVRzl6YVhScGIyNHVlRHRjYmlBZ0lDQWdJQ0FnSUNBZ0lHNXZaR1ZRYjNOcGRHbHZiaTU1SUQwZ2RISmhhV3hJWldGa1pYSlFiM05wZEdsdmJpNTVJQzBnY0c5emFYUnBiMjVUZEdWd0xuazdYRzRnSUNBZ0lDQWdJQ0FnZlZ4dVhHNGdJQ0FnSUNBZ0lDQWdMeThnUTNKbFlYUmxJR3hwYm10eklHSmxkSGRsWlc0Z2JtOWtaWE11WEc0Z0lDQWdJQ0FnSUNBZ2RISmhhV3d1YkdsdWEzTXVabTl5UldGamFDZ29iR2x1YXl3Z2JHbHVhMGx1WkdWNEtTQTlQaUI3WEc0Z0lDQWdJQ0FnSUNBZ0lDQnViMlJsVUc5emFYUnBiMjR1ZVNBclBTQndiM05wZEdsdmJsTjBaWEF1ZVR0Y2JpQWdJQ0FnSUNBZ0lDQWdJRzV2WkdWelVYVmxkV1V1Y0hWemFDaDdYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lHbGtPaUJzYVc1ckxtWnliMjBzWEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJSGc2SUc1dlpHVlFiM05wZEdsdmJpNTRMRnh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQjVPaUJ1YjJSbFVHOXphWFJwYjI0dWVWeHVJQ0FnSUNBZ0lDQWdJQ0FnZlNrN1hHNWNiaUFnSUNBZ0lDQWdJQ0FnSUM4dklGVndaR0YwWlNCelpXTnZibVFnYm05a1pTQnBiaUJoSUd4cGJtc3VYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppQW9YRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdiR2x1YTBsdVpHVjRJRDA5UFNCMGNtRnBiQzVzYVc1cmN5NXNaVzVuZEdnZ0xTQXhJQzh2SUV4aGMzUWdibTlrWlNCcGJpQmhJSFJ5WVdsc0xseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIeDhYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkSEpoYVd3dWJHbHVhM011YkdWdVozUm9JRDA5UFNBeElDOHZJRU5oYzJVZ1ptOXlJSE5wYm1kc1pTQnNhVzVySUdsdUlHRWdkSEpoYVd3dVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNrZ2UxeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNCdWIyUmxjMUYxWlhWbExuQjFjMmdvZTF4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsa09pQnNhVzVyTG5SdkxGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIZzZJRzV2WkdWUWIzTnBkR2x2Ymk1NExGeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIazZJRzV2WkdWUWIzTnBkR2x2Ymk1NUlDc2djRzl6YVhScGIyNVRkR1Z3TG5sY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnZlNrN1hHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEc0Z0lDQWdJQ0FnSUNBZ2ZTazdYRzVjYmlBZ0lDQWdJQ0FnSUNBdkx5QlZjR1JoZEdVZ1pHRjBZVk5sZENBbUlGSmxMV1J5WVhjZ1kyRnVkbUZ6TGx4dUlDQWdJQ0FnSUNBZ0lHUmhkR0ZUWlhSekxtNXZaR1Z6TG5Wd1pHRjBaU2h1YjJSbGMxRjFaWFZsS1R0Y2JseHVJQ0FnSUNBZ0lDQjlYRzRnSUNBZ0lDQWdJR1ZzYzJVZ2UxeHVJQ0FnSUNBZ0lDQWdJR052Ym5OdmJHVXVaWEp5YjNJb1lFNXZJR3hwYm10eklHWnZkVzVrSUdsdUlIUnlZV2xzSUNNa2UzUnlZV2xzU1dSOVlDazdYRzRnSUNBZ0lDQWdJSDFjYmlBZ0lDQjlMRnh1SUNBZ0lDOHFLbHh1SUNBZ0lDQXFJRWRsYm1WeVlYUmxJSFJ5WVdsc0lHTnZiRzl5SUdKaGMyVmtJRzl1SUhSeVlXbHNJR2xrTGx4dUlDQWdJQ0FxWEc0Z0lDQWdJQ29nUUhCaGNtRnRJSFJ5WVdsc1NXUmNiaUFnSUNBZ0tseHVJQ0FnSUNBcUlFQnlaWFIxY201eklIdHpkSEpwYm1kOVhHNGdJQ0FnSUNvdlhHNGdJQ0FnWjJWdVpYSmhkR1ZVY21GcGJFTnZiRzl5S0hSeVlXbHNTV1FnUFNBd0tTQjdYRzRnSUNBZ0lDQmpiMjV6ZENCamIyeHZja2x1WkdWNElEMGdkSEpoYVd4SlpDQWxJSFJvYVhNdVkyOXNiM0p6TG14bGJtZDBhRHRjYmx4dUlDQWdJQ0FnY21WMGRYSnVJSFJvYVhNdVkyOXNiM0p6VzJOdmJHOXlTVzVrWlhoZE8xeHVJQ0FnSUgwc1hHNWNiaUFnSUNBdktpcGNiaUFnSUNBZ0tpQk9iMlJsSUdaMWJtTjBhVzl1Y3k1Y2JpQWdJQ0FnS2k5Y2JpQWdJQ0JwYzFSeVlXbHNTR1ZoWkdWeUtHNXZaR1ZKWkNBOUlHNTFiR3dwSUh0Y2JpQWdJQ0FnSUdsbUlDaHViMlJsU1dRZ1BUMDlJRzUxYkd3cElIdGNiaUFnSUNBZ0lDQWdjbVYwZFhKdUlHWmhiSE5sTzF4dUlDQWdJQ0FnZlZ4dVhHNGdJQ0FnSUNCeVpYUjFjbTRnWkhKMWNHRnNVMlYwZEdsdVozTXVkSEpoYVd4ZlozSmhjR2d1WkdGMFlWTmxkSE11Ym05a1pYTXVaMlYwS0c1dlpHVkpaQ2t1YVhOSVpXRmtaWEk3WEc0Z0lDQWdmU3hjYmlBZ0lDQnNiMk5yVG05a1pYTW9LU0I3WEc0Z0lDQWdJQ0JrY25Wd1lXeFRaWFIwYVc1bmN5NTBjbUZwYkY5bmNtRndhQzV1WlhSM2IzSnJMbk5sZEU5d2RHbHZibk1vZTF4dUlDQWdJQ0FnSUNCcGJuUmxjbUZqZEdsdmJqb2dlMXh1SUNBZ0lDQWdJQ0FnSUdSeVlXZE9iMlJsY3pvZ1ptRnNjMlVzWEc0Z0lDQWdJQ0FnSUgxY2JpQWdJQ0FnSUgwcE8xeHVJQ0FnSUgwc1hHNGdJQ0FnZFc1c2IyTnJUbTlrWlhNb0tTQjdYRzRnSUNBZ0lDQmtjblZ3WVd4VFpYUjBhVzVuY3k1MGNtRnBiRjluY21Gd2FDNXVaWFIzYjNKckxuTmxkRTl3ZEdsdmJuTW9lMXh1SUNBZ0lDQWdJQ0JwYm5SbGNtRmpkR2x2YmpvZ2UxeHVJQ0FnSUNBZ0lDQWdJR1J5WVdkT2IyUmxjem9nZEhKMVpTeGNiaUFnSUNBZ0lDQWdmVnh1SUNBZ0lDQWdmU2s3WEc0Z0lDQWdmU3hjYmx4dUlDQWdJQzhxS2x4dUlDQWdJQ0FxSUZCeVpYQmhjbVVnWkdGMFlTQm1iM0lnVm1sekxtcHpJRVJoZEdFZ1UyVjBjeTVjYmlBZ0lDQWdLaTljYmlBZ0lDQndjbVZ3WVhKbFRtOWtaVVJoZEdFb2JtOWtaU2tnZTF4dUlDQWdJQ0FnYVdZZ0tIUjVjR1Z2WmlCdWIyUmxJRDA5UFNCY0luVnVaR1ZtYVc1bFpGd2lLU0I3WEc0Z0lDQWdJQ0FnSUhKbGRIVnlianRjYmlBZ0lDQWdJSDFjYmx4dUlDQWdJQ0FnYkdWMElHUmhkR0VnUFNCN2ZUdGNibHh1SUNBZ0lDQWdMeThnVkhKaGFXd2dTR1ZoWkdWeUxseHVJQ0FnSUNBZ2FXWWdLRzV2WkdVdWFYTklaV0ZrWlhJcElIdGNiaUFnSUNBZ0lDQWdaR0YwWVNBOUlIdGNiaUFnSUNBZ0lDQWdJQ0JwWkRvZ2JtOWtaUzVwWkN4Y2JpQWdJQ0FnSUNBZ0lDQjBhV1E2SUc1dlpHVXVkR2xrTEZ4dUlDQWdJQ0FnSUNBZ0lHeGhZbVZzT2lCZ1BHSStKSHR1YjJSbExuUnBkR3hsZlR3dllqNWdMRnh1SUNBZ0lDQWdJQ0FnSUhScGRHeGxPaUJnUEdJK0pIdHViMlJsTG5ScGRHeGxmVHd2WWo1Z0xGeHVJQ0FnSUNBZ0lDQWdJR0p2Y21SbGNsZHBaSFJvT2lCMGFHbHpMbk4wZVd4bGN5NW9aV0ZrWlhJdVltOXlaR1Z5VjJsa2RHZ3NYRzRnSUNBZ0lDQWdJQ0FnYjNKcFoybHVZV3hQY0hScGIyNXpPaUI3WEc0Z0lDQWdJQ0FnSUNBZ0lDQmpiMnh2Y2pvZ2UxeHVJQ0FnSUNBZ0lDQWdJQ0FnSUNCaVlXTnJaM0p2ZFc1a09pQjBhR2x6TG5OMGVXeGxjeTVvWldGa1pYSXVZMjlzYjNJdVltRmphMmR5YjNWdVpDeGNiaUFnSUNBZ0lDQWdJQ0FnSUgwc1hHNGdJQ0FnSUNBZ0lDQWdmU3hjYmlBZ0lDQWdJQ0FnSUNCamIyeHZjam9nZTF4dUlDQWdJQ0FnSUNBZ0lDQWdZbUZqYTJkeWIzVnVaRG9nZEdocGN5NXpkSGxzWlhNdWFHVmhaR1Z5TG1OdmJHOXlMbUpoWTJ0bmNtOTFibVFzWEc0Z0lDQWdJQ0FnSUNBZ2ZTeGNiaUFnSUNBZ0lDQWdJQ0J0WVhKbmFXNDZJSFJvYVhNdWMzUjViR1Z6TG1obFlXUmxjaTV0WVhKbmFXNHNYRzRnSUNBZ0lDQWdJQ0FnWTJodmMyVnVPaUJtWVd4elpTeGNiaUFnSUNBZ0lDQWdJQ0JwYzBobFlXUmxjam9nZEhKMVpTeGNiaUFnSUNBZ0lDQWdmVHRjYmlBZ0lDQWdJSDFjYmx4dUlDQWdJQ0FnTHk4Z1RtOWtaUzVjYmlBZ0lDQWdJR1ZzYzJVZ2UxeHVJQ0FnSUNBZ0lDQmtZWFJoSUQwZ2UxeHVJQ0FnSUNBZ0lDQWdJR2xrT2lCdWIyUmxMbWxrTEZ4dUlDQWdJQ0FnSUNBZ0lHeGhZbVZzT2lCZ0pIdHViMlJsTG5ScGRHeGxMbk4xWW5OMGNtbHVaeWd3TENBeE5TbDlMaTR1WUN4Y2JpQWdJQ0FnSUNBZ0lDQjBhWFJzWlRvZ2JtOWtaUzUwYVhSc1pTeGNiaUFnSUNBZ0lDQWdJQ0J5Wld4aGRHVmtWSEpoYVd4ek9pQnViMlJsTG5SeVlXbHNYMlpwWld4a0xGeHVJQ0FnSUNBZ0lDQWdJR052Ym5SbGJuUlFjbVYyYVdWM09pQnViMlJsTG1OdmJuUmxiblJmY0hKbGRtbGxkeXhjYmlBZ0lDQWdJQ0FnSUNCaWIzSmtaWEpYYVdSMGFEb2dkR2hwY3k1emRIbHNaWE11Ym05a1pTNWliM0prWlhKWGFXUjBhQzVwYm1sMGFXRnNMRnh1SUNBZ0lDQWdJQ0FnSUhOb1lYQmxVSEp2Y0dWeWRHbGxjem9nZTF4dUlDQWdJQ0FnSUNBZ0lDQWdZbTl5WkdWeVJHRnphR1Z6T2lBb0lXNXZaR1V1Y0hWaWJHbHphR1ZrS1N4Y2JpQWdJQ0FnSUNBZ0lDQjlYRzRnSUNBZ0lDQWdJSDA3WEc1Y2JpQWdJQ0FnSUNBZ0x5OGdWWEJrWVhSbElHNXZaR1VnY0hKdmNHVnlkR2xsY3lCbWIzSWdVMlZzWldOMFpXUWdibTlrWlM1Y2JpQWdJQ0FnSUNBZ2FXWWdLRzV2WkdVdWMyVnNaV04wWldRcElIdGNiaUFnSUNBZ0lDQWdJQ0JrWVhSaExteGhZbVZzSUQwZ1lEeGlQaVI3Ym05a1pTNTBhWFJzWlM1emRXSnpkSEpwYm1jb01Dd2dNVFVwZlM0dUxqd3ZZajVnTzF4dUlDQWdJQ0FnSUNBZ0lHUmhkR0V1WW05eVpHVnlWMmxrZEdnZ1BTQjBhR2x6TG5OMGVXeGxjeTV1YjJSbExtSnZjbVJsY2xkcFpIUm9Mbk5sYkdWamRHVmtPMXh1SUNBZ0lDQWdJQ0FnSUdSaGRHRXVZMjlzYjNJZ1BTQjdYRzRnSUNBZ0lDQWdJQ0FnSUNCaWIzSmtaWEk2SUhSb2FYTXVjM1I1YkdWekxtNXZaR1V1WTI5c2IzSXVZbTl5WkdWeUxtbHVhWFJwWVd3c1hHNGdJQ0FnSUNBZ0lDQWdJQ0JpWVdOclozSnZkVzVrT2lCMGFHbHpMbk4wZVd4bGN5NXViMlJsTG1OdmJHOXlMbUpoWTJ0bmNtOTFibVF1YzJWc1pXTjBaV1FzWEc0Z0lDQWdJQ0FnSUNBZ2ZUdGNiaUFnSUNBZ0lDQWdJQ0JrWVhSaExtOXlhV2RwYm1Gc1QzQjBhVzl1Y3lBOUlIdGNiaUFnSUNBZ0lDQWdJQ0FnSUdOdmJHOXlPaUI3WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJR0p2Y21SbGNqb2dkR2hwY3k1emRIbHNaWE11Ym05a1pTNWpiMnh2Y2k1aWIzSmtaWEl1YVc1cGRHbGhiQ3hjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdZbUZqYTJkeWIzVnVaRG9nZEdocGN5NXpkSGxzWlhNdWJtOWtaUzVqYjJ4dmNpNWlZV05yWjNKdmRXNWtMbk5sYkdWamRHVmtMRnh1SUNBZ0lDQWdJQ0FnSUNBZ2ZTeGNiaUFnSUNBZ0lDQWdJQ0I5TzF4dUlDQWdJQ0FnSUNCOVhHNGdJQ0FnSUNCOVhHNWNiaUFnSUNBZ0lISmxkSFZ5YmlCa1lYUmhPMXh1SUNBZ0lIMHNYRzRnSUNBZ2NISmxjR0Z5WlV4cGJtdEVZWFJoS0c5d2RHbHZibk1wSUh0Y2JpQWdJQ0FnSUdOdmJuTjBJSHNnZEhKaGFXd3NJR3hwYm1zc0lIUnlZV2xzUTI5c2IzSXNJR05vYjNObGJpQjlJRDBnYjNCMGFXOXVjenRjYmx4dUlDQWdJQ0FnY21WMGRYSnVJSHRjYmlBZ0lDQWdJQ0FnZEdsa09pQjBjbUZwYkM1cFpDeGNiaUFnSUNBZ0lDQWdabkp2YlRvZ2JHbHVheTVtY205dExGeHVJQ0FnSUNBZ0lDQjBiem9nYkdsdWF5NTBieXhjYmlBZ0lDQWdJQ0FnYjNKcFoybHVZV3hQY0hScGIyNXpPaUI3WEc0Z0lDQWdJQ0FnSUNBZ1kyOXNiM0k2SUh0Y2JpQWdJQ0FnSUNBZ0lDQWdJR052Ykc5eU9pQjBjbUZwYkVOdmJHOXlMRnh1SUNBZ0lDQWdJQ0FnSUgwc1hHNGdJQ0FnSUNBZ0lIMHNYRzRnSUNBZ0lDQWdJR052Ykc5eU9pQjdYRzRnSUNBZ0lDQWdJQ0FnWTI5c2IzSTZJSFJ5WVdsc1EyOXNiM0lzWEc0Z0lDQWdJQ0FnSUgwc1hHNGdJQ0FnSUNBZ0lHUmhjMmhsY3pvZ2JHbHVheTVpYjNSb1RtOWtaWE5JWVhabFUyRnRaVmRsYVdkb2RDeGNiaUFnSUNBZ0lDQWdZMmh2YzJWdU9pQmphRzl6Wlc0c1hHNGdJQ0FnSUNCOU8xeHVJQ0FnSUgwc1hHNWNiaUFnSUNBdktpcGNiaUFnSUNBZ0tpQklaV3h3WlhJZ1puVnVZM1JwYjI1ekxseHVJQ0FnSUNBcUwxeHVJQ0FnSUdacGJtUkpibVJsZUU5bVQySnFaV04wS0c5aWFtVmpkQ3dnYTJWNUxDQjJZV3gxWlNrZ2UxeHVJQ0FnSUNBZ2JHVjBJR2x1WkdWNElEMGdNRHRjYmlBZ0lDQWdJR1p2Y2lBb2JHVjBJRnRwTENCdlltcGRJRzltSUc5aWFtVmpkQzVsYm5SeWFXVnpLQ2twSUh0Y2JpQWdJQ0FnSUNBZ2FXWWdLRzlpYWx0clpYbGRJRDA5UFNCMllXeDFaU2tnZTF4dUlDQWdJQ0FnSUNBZ0lHbHVaR1Y0SUQwZ2FUdGNiaUFnSUNBZ0lDQWdJQ0JpY21WaGF6dGNiaUFnSUNBZ0lDQWdmVnh1SUNBZ0lDQWdmVnh1WEc0Z0lDQWdJQ0J5WlhSMWNtNGdhVzVrWlhnN1hHNGdJQ0FnZlN4Y2JpQWdJQ0JvWlhoVWIxSkhRaWhvWlhnc0lHOXdZV05wZEhrcElIdGNiaUFnSUNBZ0lHaGxlQ0E5SUdobGVDNXlaWEJzWVdObEtDY2pKeXdnSnljcE8xeHVJQ0FnSUNBZ1kyOXVjM1FnY2lBOUlIQmhjbk5sU1c1MEtHaGxlQzV6ZFdKemRISnBibWNvTUN3eUtTd2dNVFlwTzF4dUlDQWdJQ0FnWTI5dWMzUWdaeUE5SUhCaGNuTmxTVzUwS0dobGVDNXpkV0p6ZEhKcGJtY29NaXcwS1N3Z01UWXBPMXh1SUNBZ0lDQWdZMjl1YzNRZ1lpQTlJSEJoY25ObFNXNTBLR2hsZUM1emRXSnpkSEpwYm1jb05DdzJLU3dnTVRZcE8xeHVYRzRnSUNBZ0lDQnlaWFIxY200Z1lISm5ZbUVvSkh0eWZTd2dKSHRuZlN3Z0pIdGlmU3dnSkh0dmNHRmphWFI1ZlNsZ08xeHVJQ0FnSUgwc1hHNGdJSDA3WEc1Y2JpQWdMeW9xWEc0Z0lDQXFJRlJ5WVdsc0lFZHlZWEJvSUZOcFpHVmlZWElnVkdGaWN5QkNaV2hoZG1sdmRYSXVYRzRnSUNBcUwxeHVJQ0JFY25Wd1lXd3VZbVZvWVhacGIzSnpMblJ5WVdsc1IzSmhjR2hUYVdSbFltRnlWR0ZpY3lBOUlIdGNiaUFnSUNCaGRIUmhZMmc2SUdaMWJtTjBhVzl1SUNoamIyNTBaWGgwTENCelpYUjBhVzVuY3lrZ2UxeHVJQ0FnSUNBZ0pDZ25JM1J5WVdsc0xXZHlZWEJvTFhOcFpHVmlZWEluTENCamIyNTBaWGgwS1M1dmJtTmxLQ2t1WldGamFDaG1kVzVqZEdsdmJpQW9LU0I3WEc1Y2JpQWdJQ0FnSUNBZ0x5OGdUR2x6ZEdWdWFXNW5JRzl1SUVWMlpXNTBjeTVjYmlBZ0lDQWdJQ0FnSkNoMGFHbHpMQ0JqYjI1MFpYaDBLUzV2YmlnblkyeHBZMnNuTENBbkxuWnBaWGR6TFhSeVlXbHNMV2R5WVhCb1gxOXphV1JsWW1GeVgxOTBZV0p6SUQ0Z2FXNXdkWFFuTENCbElEMCtJSHRjYmlBZ0lDQWdJQ0FnSUNCRWNuVndZV3d1WW1Wb1lYWnBiM0p6TG5SeVlXbHNSM0poY0doVGFXUmxZbUZ5VkdGaWN5NXphRzkzVkdGaVEyOXVkR1Z1ZENoY2JpQWdJQ0FnSUNBZ0lDQWdJQ1FvWlM1amRYSnlaVzUwVkdGeVoyVjBLUzVoZEhSeUtDZHBaQ2NwTEZ4dUlDQWdJQ0FnSUNBZ0lDQWdZMjl1ZEdWNGRGeHVJQ0FnSUNBZ0lDQWdJQ2s3WEc0Z0lDQWdJQ0FnSUgwcE8xeHVJQ0FnSUNBZ2ZTazdYRzRnSUNBZ2ZTeGNiaUFnSUNCemQybDBZMmhVWVdJb2FXUXBJSHRjYmlBZ0lDQWdJQ1FvWUNNa2UybGtmV0FwTG1Oc2FXTnJLQ2s3WEc0Z0lDQWdmU3hjYmlBZ0lDQnphRzkzVkdGaVEyOXVkR1Z1ZENocFpDd2dZMjl1ZEdWNGRDa2dlMXh1SUNBZ0lDQWdKQ2duTG5acFpYZHpMWFJ5WVdsc0xXZHlZWEJvWDE5emFXUmxZbUZ5WDE5elpXTjBhVzl1Snl3Z1kyOXVkR1Y0ZENrdWFHbGtaU2dwTzF4dUlDQWdJQ0FnSkNoZ0xuWnBaWGR6TFhSeVlXbHNMV2R5WVhCb1gxOXphV1JsWW1GeVgxOXpaV04wYVc5dVcyUmhkR0V0ZEdGaUxXbGtQU1I3YVdSOVhXQXNJR052Ym5SbGVIUXBMbk5vYjNjb0tUdGNiaUFnSUNCOVhHNGdJSDFjYm4wcEtHcFJkV1Z5ZVN3Z1JISjFjR0ZzTENCa2NuVndZV3hUWlhSMGFXNW5jeWs3WEc0aVhYMD0ifQ==
