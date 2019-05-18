/**
 * @file
 * Javascript for the jvector module.
 */

(function ($, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.jvectorInit = {
    attach: function (context) {
      if (drupalSettings.jvectors) {
        $.each(drupalSettings.jvectors, function (index, value) {
          $.fn.vectorMap('addMap', index, value);
        });
      }
    }
  };
  Drupal.behaviors.jvectorAdmin = {
    attach: function (context) {
      $('.jvector-process').once('jvector-process').each(function() {
        var fieldId = $(this).attr('id');
        var initialSelectedRegions = $("#" + fieldId).val();
        $(this).parent().append('<div id = "jvectordiv-' + fieldId + '" class="jvector-container"></div>');
        var jvectorId = "";
        var jvectorConfigId = "";
        var jvectorSingleSelect = !($(this).attr('multiple') == 'multiple');
        var classes = $(this).attr('class').split(' ');
        for (var i = 0; i < classes.length; i++) {
          if (classes[i].search('jvector-maptype-') !== -1) {
            var val = classes[i];
            jvectorId = val.replace(/jvector-maptype-/, '');
          }
          if (classes[i].search('jvector-settings-') !== -1) {
            var val = classes[i];
            jvectorConfigId = val.replace(/jvector-settings-/, '');
          }
        }
        var configSetting = drupalSettings.jvectorSettings[jvectorId][jvectorConfigId];
        var regionStates = [];
        var regionSettings = configSetting['path_config'];
        $.each(regionSettings, function (index, value) {
          regionStates[index] = value['regionstyle']
        })

        $('#jvectordiv-' + fieldId).vectorMap({
          map: jvectorId,
          jvectorConfigId: jvectorConfigId,
          backgroundColor: configSetting['default_color']['background'],
          zoomAnimate: false,
          regionsSelectable: true,
          regionsSelectableOne: jvectorSingleSelect,
          selectedRegions: initialSelectedRegions,
          series: {
            regions: [{
              attribute: 'fill'
            }]
          },
          // regionStyle: regionStates,
          //backgroundColor: configSetting['default_color']['background'],
          onRegionClick: function (event, code) {
            var $optioncode = $("#" + fieldId + " option[value='" + code + "']");
            var optionDisabled = $optioncode.is(':disabled');
            if (optionDisabled) {
              event.preventDefault();
              return;
            }
            if ($optioncode.length > 0) {
              if ($optioncode.is(':selected')) {
                $optioncode.prop('selected', false);
              } else {
                $optioncode.prop('selected', true);
              }
            }
            if (jvectorSingleSelect != false) {
              $("#" + fieldId).change();
            }

          }
        })
        // Create a listener, check if the select field is somehow changed
        $(this).on('change', function (e) {
          var map = $('#jvectordiv-' + fieldId).vectorMap('get', 'mapObject');
          if (!jvectorSingleSelect) {
            map.clearSelectedRegions();
            $("#" + fieldId + ' option:selected').each(function () {
              var regions = $(this).val();
              map.setSelectedRegions(regions);
            });
          } else {
            map.clearSelectedRegions();
            if ($(this).val() !== "") {
              map.setSelectedRegions($(this).val());
            } else {
              map.clearSelectedRegions();
              map.setSelectedRegions($(this).val());
            }
          }
          //@todo Fix error here when everything is unselected(?).
        });

        $('.jvector-preview-btn').click(function () {
          event.preventDefault();
          alert('@todo');
          //$('.jvector-preview-btn.btn-primary').each(function(){
          //    $(this).removeClass('btn-primary')
          //})
          //$(this).addClass('btn btn-primary')
          //$('#jvectordiv-edit-preview').detachBehaviors(Drupal.behaviors.jvectorAdmin)
          //$('#jvectordiv-edit-preview').remove();
          //$('#edit-preview').detachBehaviors(Drupal.behaviors.jvectorAdmin);
          //Drupal.attachBehaviors('#edit-preview');
          //@todo
        })

        // Set correct map colors after map has been initiated.
        var $map = $('#jvectordiv-' + fieldId).vectorMap('get', 'mapObject');
        var combined = []
        var complete = []
        $.each($map.regions, function (index, value) {
          var initialConfig = value.element.shape.style;
          var additionalConfig = regionStates[index]
          var combined = $.merge(additionalConfig, initialConfig)
          $map.series.regions[0].elements[index].element.config.style = combined
          $map.regions[index].element.shape.style = combined
          complete[index] = combined
        })
        $map.series.regions[0].setValues(combined);
        $('.jvectormap-region').mouseover().mouseout();
      })
    }
  };

  Drupal.behaviors.jvectorCustomize = {
    attach: function () {
      $('.jvector-process').each(function (index) {

      });
      // The map control buttons
      $('#edit-unset-all').click(function (event) {
        event.preventDefault()
        $('#edit-vectorselect option').attr('disabled', false).prop('selected', false).change()
      })
      $('#edit-set-all').click(function (event) {
        event.preventDefault()
        $('#edit-vectorselect option')
          .attr('disabled', false)
          .prop('selected', true)
          .change()
      })
      $('#edit-disable-all').click(function (event) {
        event.preventDefault()
        $('#edit-vectorselect option')
          .prop('selected', false)
          .change()
          .attr('disabled', 'disabled')
      })

      // The State configuring control box()
      $('#edit-state-off').click(function () {
        $('.jv-color-message').html(Drupal.t('Applies to unselected state'))
      })
      $('#edit-state-on').click(function () {
        $('.jv-color-message').html(Drupal.t('Applies to selected state'))
      })
      $('#edit-state-disabled').click(function () {
        $('.jv-color-message').html(Drupal.t('Applies to disabled state'))
      })

      // Background color config
      $('#edit-default-color-background').change(function () {
        var $map = $('#jvectordiv-edit-vectorselect').vectorMap('get', 'mapObject');
        $map.setBackgroundColor($(this).val());
      })
      // The color control interface
      $('#edit-jvector-color-set').click(function (event) {
        event.preventDefault();
        // Get current color
        var color = $('.jvector-color-select.current-color input.form-color').val();
        // Find out what values are selected
        var values = $('#edit-vectorselect').val()
        // Get the state to operate on
        var state = $('#edit-state--2').val();
        var state = $('input[type=radio]:checked').val();
        // Get the state name
        var stateName
        switch (state) {
          case 'off':
            stateName = 'initial'
            break;
          case 'on':
            stateName = 'selected'
            break;
          default:
            stateName = 'disabled'
            break;
        }
        //debugger;
        // Get the map
        // Iterate trough the values and set colors
        $.each(values, function (index, value) {
          var $map = $('#jvectordiv-edit-vectorselect').vectorMap('get', 'mapObject');
          var jvectorConfigId = $map.params['jvectorConfigId']
          var jvectorId = $map.params['map']
          // Jeez..
          $map.series.regions[0].elements[value].element.config.style[stateName].fill = color;
          drupalSettings.jvectorSettings[jvectorId][jvectorConfigId].path_config[value].regionstyle[stateName].fill = color;
          // Update json hidden field.
          var json_string = JSON.stringify(drupalSettings.jvectorSettings[jvectorId][jvectorConfigId]['path_config'])
          $('input[name="jsonreciever"]').val(json_string.toString());
        });
        // Reset map
        $('.jvectormap-region').mouseover().mouseout();
        // Then, set for the config going back to Drupal.

      })
      $('#edit-control .form-type-color').click(function () {
        $('.current-color').removeClass('current-color');
        $(this).parent().addClass('current-color')
      });
    }
  };
})(jQuery, Drupal, drupalSettings);