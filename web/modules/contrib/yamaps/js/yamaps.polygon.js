/**
 * @file
 * Polygons support plugin.
 */

(function($) {
  Drupal.behaviors.yamapsPolygon = {
    attach: function(context, settings) {
      ymaps.ready(function() {
        // Class for one polygon.
        $.yaMaps.YamapsPolygon = function(geometry, properties, options) {

          this._init(new ymaps.Polygon(geometry, properties, options));
        };
        $.yaMaps.YamapsPolygon.prototype = $.yaMaps.BaseYamapsObject;

        // Class for polygons collection.
        $.yaMaps.YamapsPolygonCollection = function(options) {
          this._init(options);
          // Selector "storagePrefix + MAP_ID" will be used
          // for export collection data.
          this.storagePrefix = '.field-yamaps-polygons-';

          // Create polygon and add to collection.
          this.createPolygon = function(geometry, properties, opt) {
            return this.add(new $.yaMaps.YamapsPolygon(geometry, properties, opt));
          };
        };
        $.yaMaps.YamapsPolygonCollection.prototype = $.yaMaps.BaseYamapsObjectCollection;

        // Edit polygon balloon template.
        $.yaMaps.addLayout('yamaps#PolygonBalloonEditLayout',
          ymaps.templateLayoutFactory.createClass(
            [
              '<div class="yamaps-balloon yamaps-polygon-edit">',
              '<div class="form-element line-colors">',
              '<label>' + Drupal.t('Line color') + '</label>',
              '$[[yamaps#ColorPicker]]',
              '</div>',
              '<div class="form-element poly-colors">',
              '<label>' + Drupal.t('Polygon color') + '</label>',
              '$[[yamaps#ColorPicker]]',
              '</div>',
              '<div class="form-element line-width">',
              '$[[yamaps#StrokeWidthLayout]]',
              '</div>',
              '<div class="form-element poly-opacity">',
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

                // Polygon background colorpicker.
                this.$polyColors = $element.find('.poly-colors .yamaps-color');
                this.$polyColors.each(function() {
                  var $this = $(this);
                  var $div = $this.children('div');
                  if (_this.properties.fillColor == $div.attr('data-content')) {
                    $this.addClass('yamaps-color-active');
                  }
                });
                this.$polyColors.bind('click', this, this.fillColorClick);

                // Polygon line colorpicker.
                this.$lineColors = $element.find('.line-colors .yamaps-color');
                this.$lineColors.each(function() {
                  var $this = $(this);
                  var $div = $this.children('div');
                  if (_this.properties.strokeColor == $div.attr('data-content')) {
                    $this.addClass('yamaps-color-active');
                  }
                });
                this.$lineColors.bind('click', this, this.strokeColorClick);

                // Opacity.
                this.$opacity = $element.find('.poly-opacity select');
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
                this.$polyColors.unbind('click', this, this.fillColorClick);
                this.$lineColors.unbind('click', this, this.strokeColorClick);
                $('#deleteButton').unbind('click', this, this.onDeleteClick);
                $('#saveButton').unbind('click', this, this.onSaveClick);
              },
              fillColorClick: function(e) {
                // Fill colorpicker click.
                e.data.properties.fillColor = $(this).children('div').attr('data-content');
              },
              strokeColorClick: function(e) {
                // Stroke colorpicker click.
                e.data.properties.strokeColor = $(this).children('div').attr('data-content');
              },
              onDeleteClick: function(e) {
                // Delete click.
                e.data.properties.element.remove();
                e.preventDefault();
              },
              onSaveClick: function(e) {
                // Save click.
                var polygon = e.data.properties.element;
                // Set opacity.
                e.data.properties.opacity = e.data.$opacity.val();
                polygon.setOpacity(e.data.properties.opacity);
                // Set stroke width.
                e.data.properties.strokeWidth = e.data.$width.val();
                polygon.setWidth(e.data.properties.strokeWidth);
                // Set colors.
                polygon.setColor(e.data.properties.strokeColor, e.data.properties.fillColor);
                // Set balloon content.
                polygon.setContent(e.data.$balloonContent.val());
                polygon.closeBalloon();
              }
            }
          )
        );

        // Add polygons support to map.
        $.yaMaps.addMapTools(function(Map) {
          // Default options.
          var options = {
            balloonMaxWidth: 300,
            balloonCloseButton: true,
            strokeWidth: 3,
            elements: {}
          };
          if (Map.options.edit) {
            // If map in edit mode set edit mode to polygons options.
            options.balloonContentLayout = 'yamaps#PolygonBalloonEditLayout';
            options.draggable = true;
          }

          // Create polygons collection.
          var polygonsCollection = new $.yaMaps.YamapsPolygonCollection(options);
          // Add empty collection to the map.
          Map.map.geoObjects.add(polygonsCollection.elements);

          // Add already created polygons to map.
          for (var i in Map.options.polygons) {
            var Polygon = polygonsCollection.createPolygon(Map.options.polygons[i].coords, Map.options.polygons[i].params);
            if (Map.options.edit) {
              Polygon.startEditing();
            }
          }

          // If map in view mode exit.
          if (!Map.options.edit) {
            return;
          }

          // If map in edit mode set map click listener to adding new polygon.
          var mapClick = function(event) {
            var sss = {
              balloonMaxWidth: 300,
              balloonCloseButton: true,
              strokeWidth: 3,
              elements: {}
            };
            if (Map.options.edit) {
              // If map in edit mode set edit mode to polygons options.
              options.balloonContentBodyLayout = 'yamaps#PolygonBalloonEditLayout';
              options.draggable = true;
            }
            var Polygon = polygonsCollection.createPolygon([[event.get('coords')]], {
              balloonContent: '',
              fillColor: 'lightblue',
              strokeColor: 'blue',
              opacity: 0.6,
              strokeWidth: 3
            }, sss);
            Polygon.startEditing(true);
          };

          // Add new button.
          var polygonButton = new ymaps.control.Button({
            data: {
              image: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PHN2ZyAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIgICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiICAgd2lkdGg9IjE2IiAgIGhlaWdodD0iMTYiICAgdmlld0JveD0iMCAwIDE2IDE2IiAgIGlkPSJzdmcyIiAgIHZlcnNpb249IjEuMSIgICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjkxIHIxMzcyNSIgICBzb2RpcG9kaTpkb2NuYW1lPSJwb2x5Z29ucyAoMSkuc3ZnIj4gIDxtZXRhZGF0YSAgICAgaWQ9Im1ldGFkYXRhMjQiPiAgICA8cmRmOlJERj4gICAgICA8Y2M6V29yayAgICAgICAgIHJkZjphYm91dD0iIj4gICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2Uvc3ZnK3htbDwvZGM6Zm9ybWF0PiAgICAgICAgPGRjOnR5cGUgICAgICAgICAgIHJkZjpyZXNvdXJjZT0iaHR0cDovL3B1cmwub3JnL2RjL2RjbWl0eXBlL1N0aWxsSW1hZ2UiIC8+ICAgICAgPC9jYzpXb3JrPiAgICA8L3JkZjpSREY+ICA8L21ldGFkYXRhPiAgPGRlZnMgICAgIGlkPSJkZWZzMjIiIC8+ICA8c29kaXBvZGk6bmFtZWR2aWV3ICAgICBwYWdlY29sb3I9IiNmZmZmZmYiICAgICBib3JkZXJjb2xvcj0iIzY2NjY2NiIgICAgIGJvcmRlcm9wYWNpdHk9IjEiICAgICBvYmplY3R0b2xlcmFuY2U9IjEwIiAgICAgZ3JpZHRvbGVyYW5jZT0iMTAiICAgICBndWlkZXRvbGVyYW5jZT0iMTAiICAgICBpbmtzY2FwZTpwYWdlb3BhY2l0eT0iMCIgICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiICAgICBpbmtzY2FwZTp3aW5kb3ctd2lkdGg9IjE5MjAiICAgICBpbmtzY2FwZTp3aW5kb3ctaGVpZ2h0PSIxMDE3IiAgICAgaWQ9Im5hbWVkdmlldzIwIiAgICAgc2hvd2dyaWQ9ImZhbHNlIiAgICAgaW5rc2NhcGU6em9vbT0iMTQuNzUiICAgICBpbmtzY2FwZTpjeD0iOCIgICAgIGlua3NjYXBlOmN5PSI4IiAgICAgaW5rc2NhcGU6d2luZG93LXg9IjE5MTIiICAgICBpbmtzY2FwZTp3aW5kb3cteT0iLTgiICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIxIiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0iZzYiIC8+ICA8dGl0bGUgICAgIGlkPSJ0aXRsZTQiPnBvbHlnb25zPC90aXRsZT4gIDxnICAgICBpZD0iZzYiICAgICBmaWxsLXJ1bGU9ImV2ZW5vZGQiICAgICBmaWxsPSIjNjY2Ij4gICAgPHBhdGggICAgICAgZD0iTTE0IDEzSDJ2MmgxMnYtMnpNMTMuNzY2IDEuMDI4TDEuMzkgNC4wMTJsLjQ2OCAxLjk0NCAxMi4zNzYtMi45ODQtLjQ2OC0xLjk0NHoiICAgICAgIGlkPSJwYXRoOCIgLz4gICAgPHBhdGggICAgICAgZD0ibSAxMi43Njc1NTQsMi4xMzU1OTMyIDAsMTEuODQwNDA2OCAxLjk2MTI2LDAgMCwtMTEuODQwNDA2OCAtMS45NjEyNiwwIHogTSAxLDUuOTE0MzIwMiAxLDEzLjk3NiBsIDEuOTYxMjU5MSwwIDAsLTguMDYxNjc5OCAtMS45NjEyNTkxLDAgeiIgICAgICAgaWQ9InBhdGgxMCIgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgLz4gICAgPGNpcmNsZSAgICAgICBjeD0iMTQiICAgICAgIGN5PSIxNCIgICAgICAgcj0iMiIgICAgICAgaWQ9ImNpcmNsZTEyIiAvPiAgICA8Y2lyY2xlICAgICAgIGN4PSIxNCIgICAgICAgY3k9IjIiICAgICAgIHI9IjIiICAgICAgIGlkPSJjaXJjbGUxNCIgLz4gICAgPGNpcmNsZSAgICAgICBjeD0iMiIgICAgICAgY3k9IjE0IiAgICAgICByPSIyIiAgICAgICBpZD0iY2lyY2xlMTYiIC8+ICAgIDxjaXJjbGUgICAgICAgY3g9IjIiICAgICAgIGN5PSI1IiAgICAgICByPSIyIiAgICAgICBpZD0iY2lyY2xlMTgiIC8+ICA8L2c+PC9zdmc+',
              title: Drupal.t('Drawing polygons')
            }
          });

          // Button actions.
          polygonButton.events
            .add('select', function(event) {
              Map.cursor = Map.map.cursors.push('pointer');
              Map.mapListeners.add('click', mapClick);
            })
            .add('deselect', function(event) {
              Map.cursor.remove();
              Map.mapListeners.remove('click', mapClick);
            });

          return polygonButton;
        });
      });
    }
  }
})(jQuery);
