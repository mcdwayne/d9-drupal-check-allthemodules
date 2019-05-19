/**
 * @file
 * Polylines support plugin.
 */

(function($) {
  Drupal.behaviors.yamapsLine = {
    attach: function(context, settings) {
      ymaps.ready(function() {
        // Class for one line.
        $.yaMaps.YamapsLine = function(geometry, properties, options) {
          this._init(new ymaps.Polyline(geometry, properties, options));
        };
        $.yaMaps.YamapsLine.prototype = $.yaMaps.BaseYamapsObject;

        // Class for lines collection.
        $.yaMaps.YamapsLineCollection = function(options) {
          this._init(options);
          // Selector "storagePrefix + MAP_ID" will be used
          // for export collection data.
          this.storagePrefix = '.field-yamaps-lines-';

          // Create line and add to collection.
          this.createLine = function(geometry, properties, options) {
            return this.add(new $.yaMaps.YamapsLine(geometry, properties, options));
          };
        };
        $.yaMaps.YamapsLineCollection.prototype = $.yaMaps.BaseYamapsObjectCollection;

        // Edit line balloon template.
        $.yaMaps.addLayout('yamaps#LineBalloonEditLayout',
          ymaps.templateLayoutFactory.createClass(
            [
              '<div class="yamaps-balloon yamaps-line-edit">',
              '<div class="form-element line-colors">',
              '<label>' + Drupal.t('Line color') + '</label>',
              '$[[yamaps#ColorPicker]]',
              '</div>',
              '<div class="form-element line-width">',
              '$[[yamaps#StrokeWidthLayout]]',
              '</div>',
              '<div class="form-element line-opacity">',
              '$[[yamaps#OpacityLayout]]',
              '</div>',
              '<div class="form-element">',
              '<label for="balloonContent">' + Drupal.t('Balloon text') + '</label>',
              '<input type="text" id="balloonContent" value="$[properties.balloonContent]"/>',
              '</div>',
              '$[[yamaps#ActionsButtons]]',
              '</div>'
            ].join(""),
            {
              build: function() {
                this.constructor.superclass.build.call(this);
                this.properties = this.getData().properties.getAll();
                // Balloon HTML element.
                var $element = $(this.getParentElement());
                var _this = this;

                // Line colorpicker.
                this.$lineColors = $element.find('.line-colors .yamaps-color');
                this.$lineColors.each(function() {
                  // Set colorpicker parameters.
                  var $this = $(this);
                  var $div = $this.children('div');
                  if (_this.properties.strokeColor == $div.attr('data-content')) {
                    $this.addClass('yamaps-color-active');
                  }
                });
                this.$lineColors.bind('click', this, this.strokeColorClick);

                // Opacity.
                this.$opacity = $element.find('.line-opacity select');
                this.$opacity.val(_this.properties.opacity);

                // Stroke width.
                this.$width = $element.find('.line-width select');
                this.$width.val(_this.properties.strokeWidth);

                // Balloon content.
                this.$balloonContent = $element.find('#balloonContent');

                // Actions.
                $('#deleteButton').bind('click', this, this.onDeleteClick);
                $('#saveButton').bind('click', this, this.onSaveClick);
              },
              clear: function() {
                this.constructor.superclass.build.call(this);
                this.$lineColors.unbind('click', this, this.strokeColorClick);
                $('#deleteButton').unbind('click', this, this.onDeleteClick);
                $('#saveButton').unbind('click', this, this.onSaveClick);

              },
              strokeColorClick: function(e) {
                // Click to colorpicker.
                e.data.properties.strokeColor = $(this).children('div').attr('data-content');
              },
              onDeleteClick: function(e) {
                // Delete link click.
                e.data.properties.element.remove();
                e.preventDefault();
              },
              onSaveClick: function(e) {
                // Save button click.
                var line = e.data.properties.element;
                // Set opacity.
                e.data.properties.opacity = e.data.$opacity.val();
                line.setOpacity(e.data.properties.opacity);
                // Set width.
                e.data.properties.strokeWidth = e.data.$width.val();
                line.setWidth(e.data.properties.strokeWidth);
                // Set color.
                line.setColor(e.data.properties.strokeColor);
                // Set balloon content.
                line.setContent(e.data.$balloonContent.val());
                // Close balloon.
                line.closeBalloon();
              }
            }
          )
        );

        // Add lines support to map.
        $.yaMaps.addMapTools(function(Map) {
          // Default options.
          var options = {
            balloonMaxWidth: 300,
            balloonCloseButton: true,
            strokeWidth: 3,
            elements: {}
          };
          if (Map.options.edit) {
            // If map in edit mode set edit mode to lines options.
            options.balloonContentLayout = 'yamaps#LineBalloonEditLayout';
            options.draggable = true;
          }

          // Create lines collection.
          var linesCollection = new $.yaMaps.YamapsLineCollection(options);

          // Add empty collection to the map.
          Map.map.geoObjects.add(linesCollection.elements);
          // Add already created lines to map.
          for (var i in Map.options.lines) {
            var Line = linesCollection.createLine(Map.options.lines[i].coords, Map.options.lines[i].params);
            if (Map.options.edit) {
              Line.startEditing();
            }
          }

          // If map in view mode exit.
          if (!Map.options.edit) {
            return;
          }

          // If map in edit mode set map click listener to adding new line.
          var mapClick = function(event) {
            var Line = linesCollection.createLine([event.get('coords')], {
              balloonContent: '',
              strokeColor: 'blue',
              opacity: 0.8,
              strokeWidth: 3
            });
            Line.startEditing(true);
          };

          // Add new button.
          var lineButton = new ymaps.control.Button({
            data: {
              image: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PHN2ZyB3aWR0aD0iMTZweCIgaGVpZ2h0PSIxNnB4IiB2aWV3Qm94PSIwIDAgMTYgMTYiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sbnM6c2tldGNoPSJodHRwOi8vd3d3LmJvaGVtaWFuY29kaW5nLmNvbS9za2V0Y2gvbnMiPiAgICAgICAgPHRpdGxlPmxpbmU8L3RpdGxlPiAgICA8ZGVzYz5DcmVhdGVkIHdpdGggU2tldGNoLjwvZGVzYz4gICAgPGRlZnM+PC9kZWZzPiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIiBza2V0Y2g6dHlwZT0iTVNQYWdlIj4gICAgICAgIDxnIGlkPSJMaW5lLTEyLSstT3ZhbC0yMy0rLU92YWwtMjQiIHNrZXRjaDp0eXBlPSJNU0xheWVyR3JvdXAiPiAgICAgICAgICAgIDxwYXRoIGQ9Ik0xMy41MTA4MzA5LDIuNDg5MTY5NDIgTDEuOTY0MzMxMjEsMTQuMDM1NjY5MyIgaWQ9IkxpbmUtMTIiIHN0cm9rZT0iIzY2NiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0ic3F1YXJlIiBza2V0Y2g6dHlwZT0iTVNTaGFwZUdyb3VwIj48L3BhdGg+ICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbC0yMyIgZmlsbD0iIzY2NiIgc2tldGNoOnR5cGU9Ik1TU2hhcGVHcm91cCIgY3g9IjIiIGN5PSIxNCIgcj0iMiI+PC9jaXJjbGU+ICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbC0yNCIgZmlsbD0iIzY2NiIgc2tldGNoOnR5cGU9Ik1TU2hhcGVHcm91cCIgY3g9IjE0IiBjeT0iMiIgcj0iMiI+PC9jaXJjbGU+ICAgICAgICA8L2c+ICAgIDwvZz48L3N2Zz4=',
              title: Drupal.t('Drawing lines')
            }
          });

          // Button actions.
          lineButton.events
            .add('select', function(event) {
              Map.cursor = Map.map.cursors.push('pointer');
              Map.mapListeners.add('click', mapClick);
            })
            .add('deselect', function(event) {
              Map.cursor.remove();
              Map.mapListeners.remove('click', mapClick);
            });

          return lineButton;
        });
      });
    }
  }
})(jQuery);
