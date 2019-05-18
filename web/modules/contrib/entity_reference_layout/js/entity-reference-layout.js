(function ($, Drupal, drupalSettings) {

  'use strict';

  var drake;

  Drupal.behaviors.fieldUIEntityReferenceLayout = {

    attach: function attach(context, settings) {

      var updateDisabled = function($container) {
        if ($container.find('.erl-layout .erl-field-item').length > 0) {
          $container.find('.erl-layout-disabled').show();
        }
        else {
          $container.find('.erl-layout-disabled').hide();
        }
        if ($container.find('.erl-layout-disabled .erl-field-item').length > 0) {
          $container.find('.erl-layout-disabled-description').hide();
        }
        else {
          $container.find('.erl-layout-disabled-description').show();
        }
      }

      var updateFields = function($container) {

        // Set deltas:
        $container.find('.erl-field-item').each(function(index, item){
          $(item).find('.ief-entity-delta').val(index + '');
        });

        // Set parents:
        $container.parent().find('.erl-field-item .parent-delta').each(function(index, item){
          var d = getParentDelta($(item));
          if (d >= 0) {
            $(item).val(d);
          }
        });

        // Set regions:
        $container.find('.erl-field-item .erl-region-select').each(function(index, item){
          var $el = $(item);
          if ($el.parents('.erl-region-section')) {
            $el.val(getRegion($el));
          }
        });
      };

      var moveUp = function($item, $container) {
        if ($item.parents('.erl-layout-section').length == 0) {
          $item = $item.parent();
        }
        if ($item.prev().length > 0) {
          $item.after($item.prev());
          updateFields($container);
        }
      }

      var moveDown = function($item, $container) {
        if ($item.parents('.erl-layout-section').length == 0) {
          $item = $item.parent();
        }
        if ($item.next().length > 0) {
          $item.before($item.next());
          updateFields($container);
        }
      }

      var addLayoutControls = function($container) {
        $container.find('.erl-field-item').each(function(index, fieldItem){
          var $fieldItem = $(fieldItem);
          $fieldItem
            .remove('.layout-controls')
            .append($('<div class="layout-controls">')
              .append($('<div class="layout-up"></div>').mousedown(
                function(){
                  moveUp($fieldItem, $container);
                }
              ))
              .append($('<div class="layout-handle"></div>'))
              .append($('<div class="layout-down"></div>').mousedown(
                function(){
                  moveDown($fieldItem, $container);
                }
              )
            )
          );
        });
      }

      var getRegion = function($el) {
        var regEx = /erl-layout-section--([a-z0-9A-Z_]*)/,
          regionName = '',
          $container = $el.is('.erl-layout-section') ? $el : $el.parents('.erl-layout-section');
        if ($container.length) {
          var matches = $container[0].className.match(regEx);
          if (matches && matches.length >= 2) {
            regionName = matches[1];
          }
        }
        return regionName;
      }

      var getParentDelta = function($el) {
        var regEx = /erl-layout-delta--([0-9]+)/,
        delta = -1,
        $container = $el.is('.erl-layout-section') ? $el : $el.parents('.erl-layout-section');
        // Has a section parent
        if ($container.length) {
          var matches = $container[0].className.match(regEx);
          if (matches && matches.length >= 2) {
            delta = matches[1];
          }
        }
        return delta;
      }

      var getSiblingDelta = function($el) {
        var regEx = /erl-layout-delta--([0-9]+)/,
        delta = -1,
        $container = $el.is('.erl-field-item--layout') ? $el : $el.parents('.erl-field-item--layout:first');
        if ($container.length) {
          delta = $container.prev('.erl-field-item--layout').find('> .erl-field-item .ief-entity-delta').val()
        }
        return delta;
      }

      var addNewButton = function($buttonGroup, $optionItem, $section, $erlField, prefix) {
        prefix = prefix ? prefix : '';
        var icon = '';
        if (drupalSettings.erlIcons && drupalSettings.erlIcons['icon_' + $optionItem.val()]) {
          icon = '<img src="' + drupalSettings.erlIcons['icon_' + $optionItem.val()] + '" />';
        }

        $buttonGroup.append($('<button>' + icon + prefix + $optionItem.text() + '</button>')
        .click(function(e){
          return false;
        })
        .mousedown(function(e){
          $erlField.find('.erl-new-item-region').val(getRegion($section));
          $erlField.find('.erl-field-actions select').val($optionItem.val());
          var parent = getParentDelta($section);
          if (parent < 0 ) {
            parent = getSiblingDelta($section);
          }
          $erlField.find('.erl-new-item-delta').val(parent);
          $erlField.find('.erl-field-actions input.js-form-submit').trigger('mousedown');
          return false;
        }));
      }

      var buttonGroup = function($types, $section, $erlField) {
        var $addButtons = $('<div class="erl-add-content--group hidden"></div>');
        $types.each(function(index, elem) {
          addNewButton($addButtons, $(elem), $section, $erlField);
        });
        var $addContent = $('<button class="erl-add-content">+</button>')
          .appendTo($section)
          .on('click', function(e){
            $(e.target).focus();
            return false;
          })
          .on('click', function(e){
            var $b = $(e.target);
            $b.parent().find('.erl-add-content--group').toggleClass('hidden');
            $b.toggleClass('active');
            $b.text($b.text() == '+' ? '-' : '+');
            return false;
          });
        $(window).click(function(){
          $erlField.find('.erl-add-content--group').addClass('hidden');
          $erlField.find('.erl-add-content').removeClass('active');
        });
        $section.append($addButtons);
      }

      var addRegionButtons = function($erlField) {
        var $types = $erlField.find('.erl-field-actions optgroup[label="Content"] option');
        $erlField.find('.erl-layout-section').each(function(index, section) {
          if ($(section).parents('.erl-layout-disabled').length == 0) {
            buttonGroup($types, $(section), $erlField);
          }
        });
      }

      var addSectionButtons = function($erlField) {
        var $types = $erlField.find('.erl-field-actions optgroup[label="Layout"] option');
        $erlField.parent().find('.erl-field-actions:first').each(function(index, section) {
          if ($types.length > 1) {
            buttonGroup($types, $(section), $erlField);
          }
          else {
            // Create the "Add section" button above disabled area.
            var $addSection = $('<div class="erl-add-content--single"></div>').insertBefore($erlField.find('.erl-layout-disabled'));
            addNewButton($addSection, $types, $(section), $erlField, '<span class="icon">+</span> Add ');

            // Add it below all other sections except the first one.
            $erlField.find('.erl-field-item--layout > .erl-field-item:gt(0)').each(function(index, item){
              var $addSection = $('<div class="erl-add-content--single"></div>').appendTo($(item));
              addNewButton($addSection, $types, $(item), $erlField, '<span class="icon">+</span> Add ');
            });
          }
        });
      }

      var enhanceRadioSelect = function() {
        $('.layout-radio-item').click(function(){
            $(this).find('input[type=radio]').prop("checked", true).trigger("change");
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
        });
        $('.layout-radio-item').each(function(){
          if ($(this).find('input[type=radio]').prop("checked")) {
            $(this).addClass('active');
          }
        });
      }

      var editableLayout = function($elem, options) {
        var forceLayouts = options.forceLayouts ? true : false;
        updateFields($elem);
        updateDisabled($elem);

        // Turn on drag and drop if dragula function exists.
        if (typeof dragula !== 'undefined') {
          $elem.addClass('dragula-enabled');
          if (drake) {
            drake.destroy();
          }
          drake = dragula([].slice.apply(document.querySelectorAll('.erl-layout-section, .erl-field-item--layout-container')), {
            moves: function(el, container, handle) {
              return handle.className.toString().indexOf('layout-handle') >= 0;
              },

            accepts: function(el, target, source, sibling) {
              // Layouts can never inside another layout.
              if ($(el).is('.erl-field-item--layout')) {
                if ($(target).parents('.erl-field-item--layout').length) {
                  return false;
                }
              }

              // Layouts can not be dropped into disabled (only individual items).
              if ($(el).is('.erl-field-item--layout')) {
                if ($(target).parents('.erl-layout-disabled').length) {
                  return false;
                }
              }

              // Require non-layout items to be dropped in a layout.
              if ($(el).is('.erl-field-item')) {
                if($(target).parents('.erl-field-item--layout').length == 0 && $(target).parents('.erl-layout-disabled').length == 0) {
                  return false;
                }
              }

              return true;
            }
          });
          drake.on('drop', function(el, target, source, sibling){
            updateFields($elem);
            updateDisabled($elem);
          });

        }
        addRegionButtons($elem);
        addSectionButtons($elem);
        addLayoutControls($elem);

      }

      $('.erl-field', context).once('erlBehaviors').each(function(index, item){
        $('#erl-modal').dialog(
          {
            width: 1000,
            appendTo: $('#erl-modal').parents('.erl-field'),
            close: function (event, ui){
              $('#erl-modal').find('input[value="Cancel"]').trigger('mousedown');
            },
            open: function (event, ui) {
              enhanceRadioSelect();
            },
            modal: true,
            title: $('#erl-modal .ief-title').text()
          }
        );
        editableLayout($(item), {forceLayouts: $(item).hasClass('erl-field-force-layouts')});
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
