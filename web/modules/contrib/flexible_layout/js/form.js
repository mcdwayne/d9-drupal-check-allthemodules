/**
 * File form.js
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to the flexible layout form.
   */
  Drupal.behaviors.FlexibleLayoutConfig = {
    attach: function (context) {
      var $flexible_layout = $('.fl-config', context).once('flexible-layout-init');
      if ($flexible_layout.length) {
        $flexible_layout.flexibleLayout();
      }
    }
  };


  /**
   * Config functions
   */
  $.fn.flexibleLayout = function(presetLayout) {
    var _self = this;
    var $flexibleLayout = $(this);
    var $displayContainer = $flexibleLayout.find('.flexible-layout-container');

    /**
     * Initialize plugin
     */
    this.init = function (presetLayout) {

      // Setup config form.
      _self.configForm();

      $displayContainer.addClass('fl');

      // Parse JSON.
      var $jsonField = $displayContainer.parent().find('.flexible-layout-json-field');
      var observer = new MutationObserver(function () {
        var serializedLayout = _self.serializeLayout($displayContainer.children('.fl-column').first());
        $jsonField.val(JSON.stringify(serializedLayout));
      });
      observer.observe($displayContainer[0], {
        attributes: true,
        childList: true,
        characterData: true,
        subtree: true
      });
      var layout = presetLayout ? JSON.parse(presetLayout) : JSON.parse($jsonField.val());
      if (typeof layout === 'object') {
        _self.unserializeLayout(layout, $displayContainer);
      }
      else {
        _self.addColumn($displayContainer, false, true);
      }

      _self.activate();
    };

    /**
     * Wrapper to setup our configuration forms.
     */
    this.configForm = function() {
      // Row config.
      var $rowConfig = $flexibleLayout.find('.fl-row-container');
      if ($rowConfig.length) {
        _self.getRowConfig($rowConfig);
      }
      // Column Config.
      var $columnConfig = $flexibleLayout.find('.fl-column-container');
      if ($columnConfig.length) {
        _self.getColumnConfig($columnConfig);
      }

    };

    /**
     * Config form actions for columns.
     * @param $config
     */
    this.getRowConfig = function ($config) {
      // Label.
      $config.find('.fl-row-label').keyup(function () {
        $displayContainer.find('.fl-active').children('.name').text($(this).val());
      });

      // Add Column
      $config.find('.fl-add-column').click(function (e) {
        // Check for template.
        var $columnStyle = $config.find('.fl-column-style');
        if ($columnStyle.length) {
          var style = $columnStyle.find('select').val();
          _self.addColumn($displayContainer.find('.fl-active'), false, false, style);
        }
        else {
          _self.addColumn($displayContainer.find('.fl-active'));
        }
        return false;
      });

      // Remove Column
      $config.find('.remove').click(function() {
        $displayContainer.find('.fl-active').remove();
        _self.activate();
        return false;
      });

      // Advanced Toggle
      $config.find('.fl-row-show-advanced').click(function(){
        $config.find('.fl-advanced-container').toggle();
        return false;
      });

      // Classes
      $config.find('.fl-row-classes').keyup(function () {
        $displayContainer.find('.fl-active').attr('class', $(this).val() + ' fl-row fl-active');
      });

      // Advanced Wrapper Toggle
      $config.find('.fl-row-add-wrapper').change(function () {
        var $active = $displayContainer.find('.fl-active');
        var $wrapperContainer = $config.find('.fl-row-wrapper-container');

        if ($(this).is(":checked")) {
          $active.attr('data-wrap-enabled', 1);
          $wrapperContainer.show();
        }
        else {
          $active.attr('data-wrap-enabled', 0);
          $wrapperContainer.hide();
        }
      });

      // Wrapper Classes
      $config.find('.fl-row-wrapper-classes').keyup(function () {
        $displayContainer.find('.fl-active').attr('data-wrap-wrapper', $(this).val());
      });

      // Container Classes.
      $config.find('.fl-row-container-classes').keyup(function () {
        $displayContainer.find('.fl-active').attr('data-wrap-container', $(this).val());
      });
    };

    /**
     * Config form actions for columns.
     * @param $config
     */
    this.getColumnConfig = function($config) {
      // Label.
      $config.find('.fl-column-label').keyup(function () {
        $displayContainer.find('.fl-active').children('.name').text($(this).val());
      });

      // Add Row / Template.
      $config.find('.fl-add-row').click(function(){
        var $newRow = false;
        var parts = [];
        var cssTemplate = $config.find('.row-css-template select').val();
        var bsTemplate = $config.find('.row-bs-template select').val();

        if (cssTemplate) {
          parts = cssTemplate.split('|');
          $newRow = _self.addRow($displayContainer.find('.fl-active'), false, false, 'd-grid ' + parts[0]);
          for (var i = 0; i < parts[1]; i++) {
            _self.addColumn($newRow);
          }
        }
        else if (bsTemplate) {
          parts = bsTemplate.split('|');

          // Requires a container
          if (parts[0] === 'container') {
            $newRow = _self.addRow($displayContainer.find('.fl-active'), false, 'container');
            parts.splice(0, 1);
          }
          else {
            $newRow = _self.addRow($displayContainer.find('.fl-active'), false, true);
          }

          // Add as many columns we need to the row with the custom classes.
          for (var i = 0; i < parts.length; i++) {
            _self.addColumn($newRow, false, false, parts[i]);
          }
        }
        // Just add a row.
        else {
          _self.addRow($displayContainer.find('.fl-active'));
        }

        return false;
      });

      // Styles
      var $styles = $config.find('.fl-column-style select');
      if ($styles.length) {
        $styles.change(function(){
          $displayContainer.find('.fl-active').attr('class', $(this).val() + ' fl-column fl-active');
        });
      }

      // Remove Column
      $config.find('.remove').click(function() {
        $displayContainer.find('.fl-active').remove();
        _self.activate();
        return false;
      });

      // Advanced Toggle
      $config.find('.fl-column-show-advanced').click(function () {
        $config.find('.fl-advanced-container').toggle();
        return false;
      });

      // Classes
      $config.find('.fl-column-classes').keyup(function () {
        $displayContainer.find('.fl-active').attr('class', $(this).val() + ' fl-column fl-active');
      });
    };

    /**
     * Serializes a layout to store in hidden field.
     * @param $element
     * @returns {{}}
     */
    this.serializeLayout = function ($element) {
      var machine_name = $element.attr('data-machine-name');
      var serializedLayout = {};

      serializedLayout[machine_name] = {
        name: $element.children('.name').text(),
        classes: _self.getClasses($element),
        children: {},
        type: $element.hasClass('fl-row') ? 'row' : 'column',
        wrap: {
          enabled: $element.attr('data-wrap-enabled'),
          wrapper: $element.attr('data-wrap-wrapper'),
          container: $element.attr('data-wrap-container')
        }
      };
      $element.children('.fl-column,.fl-row').each(function () {
        $.extend(serializedLayout[machine_name].children, _self.serializeLayout($(this)));
      });
      return serializedLayout;
    };

    /**
     * Unserialize a layout and store the settings into the element.
     * @param settings
     * @param $element
     */
    this.unserializeLayout = function (settings, $element) {
      for (var i in settings) {
        if (settings.hasOwnProperty(i)) {
          settings[i].machine_name = i;
          var $child = settings[i].type === 'row' ? _self.addRow($element, settings[i]) : _self.addColumn($element, settings[i]);

          if (!$.isEmptyObject(settings[i].children)) {
            _self.unserializeLayout(settings[i].children, $child);
          }
        }
      }
    };

    /**
     * Adds a column to the display.
     *
     * @param $row
     * @param settings
     * @param first
     * @param addClass
     * @returns {*|HTMLElement}
     */
    this.addColumn = function ($row, settings, first, addClass) {
      var name = first ? "Container" : "Column";
      var $column = $('<div class="fl-column"><div class="name">' + name + '</div></div>');
      if (settings) {
        $column.addClass(settings.classes);
        $column.children('.name').text(settings.name);
        $column.attr('data-machine-name', settings.machine_name);
      }
      else {
        var num = $row.closest('.fl').find('.fl-column').length + 1;
        $column.attr('data-machine-name', 'column_' + num);
      }

      // Additional Classes
      if (addClass) {
        $column.addClass(addClass);
      }

      _self.bindActions($column);
      $row.append($column);
      return $column;
    };

    /**
     * Adds a row to the active column onto the display.
     * @param $column
     * @param settings
     * @param bootstrap
     * @param addClass
     * @returns {*|HTMLElement}
     */
    this.addRow = function ($column, settings, bootstrap, addClass) {
      var $row = $('<div class="fl-row"><div class="name">Row</div></div>');
      if (settings) {
        $row.addClass(settings.classes);
        $row.children('.name').text(settings.name);
        $row.attr('data-machine-name', settings.machine_name);

        // Wrapper Options
        if (settings.wrap) {
          $row.attr('data-wrap-enabled', settings.wrap.enabled);
          $row.attr('data-wrap-wrapper', settings.wrap.wrapper);
          $row.attr('data-wrap-container', settings.wrap.container);
        }
      }
      else {
        var num = $column.closest('.fl').find('.fl-row').length + 1;
        $row.attr('data-machine-name', 'row_' + num);
      }

      // Bootstrap options
      if (bootstrap) {
        $row.attr('data-wrap-enabled', 1);
        $row.addClass('row');
        if (bootstrap === 'container') {
          $row.attr('data-wrap-container', 'container');
        }
      }

      if (addClass) {
        $row.addClass(addClass);
      }

      $row.sortable({
        connectWith: '.fl-row',
        items: '> .fl-column',
        tolerance: 'pointer'
      });
      _self.bindActions($row);
      $column.append($row);
      return $row;
    };


    /**
     * Binds actions to rows and columns
     * @param $element
     */
    this.bindActions = function ($element) {
      // Row adds column, column adds row.
      var type = $element.hasClass('fl-row') ? 'row' : 'column';

      // Bind clicks to new element.
      $element.click(function (event) {
        event.preventDefault();
        event.stopPropagation();

        // Reset.
        $flexibleLayout.find('.fl-column, .fl-row').removeClass('fl-active');
        $flexibleLayout.find('.fl-type-container').hide();

        // Activate and show.
        $(this).addClass('fl-active');
        var $configPanel = $('.fl-' + type + '-container');
        if ($configPanel.length) {
          _self.showConfig($configPanel, $element);
        }

      });
    };

    /**
     * Show a row or column config panel when active.
     * @param $panel
     * @param $element
     */
    this.showConfig = function ($panel, $element) {
      // Reset.
      $panel.find('.add, .remove, .fl-advanced-container, .fl-row-wrapper-container').hide();
      $panel.find('select').val("");

      // Sets limits on adding/removing to prevent funky layouts.
      var colNum = $displayContainer.find('.fl-column').length;
      var rowNum = $displayContainer.find('.fl-row').length;
      var rowDepth = $element.parents('.fl-row').length;
      var colDepth = $element.parents('.fl-column').length;

      // Limit to 3 nestings for performance.
      if (rowDepth < 3) {
        $panel.find('.add').show();
      }

      // Require at least one row and one column (region).
      if ($element.hasClass('fl-row') && rowNum > 1) {
        $panel.find('.remove').show();
      }
      else if ($element.hasClass('fl-column') && colNum > 2 && colDepth > 0) {
        $panel.find('.remove').show();
      }

      // Populate existing data.
      _self.populateConfig($panel, $element);

      $panel.show();
    };

    /**
     * Grabs the classes associated to an element execpt fl related ones.
     * @param $element
     */
    this.getClasses = function ($element) {
      return $element.attr('class').replace(/(fl\-|ui\-)[^\s]+/g, '').trim();
    };

    /**
     * Populates the config based on the current element.
     * @param $panel
     * @param $element
     */
    this.populateConfig = function ($panel, $element) {
      var type = $element.hasClass('fl-row') ? 'row' : 'column';

      // Label always exists.
      var $label = $panel.find('.fl-label');
      $label.val($element.children('.name').text());

      // Advaned options (classes, id, etc).
      var $advanced = $panel.find('.fl-advanced-container');
      if (type === 'row') {
        $advanced.find('.fl-row-classes').val(_self.getClasses($element));

        var $addWrapper = $advanced.find('.fl-row-add-wrapper');
        var isChecked = $element.attr('data-wrap-enabled') === "1";
        $addWrapper.prop('checked', isChecked);
        isChecked ? $advanced.find('.fl-row-wrapper-container').show() : $advanced.find('.fl-row-wrapper-container').hide();

        $advanced.find('.fl-row-wrapper-classes').val($element.attr('data-wrap-wrapper'));
        $advanced.find('.fl-row-container-classes').val($element.attr('data-wrap-container'));
      }
      else {
        $advanced.find('.fl-column-classes').val(_self.getClasses($element));

        // Bootstrap options
        var $styleSelect = $panel.find('.fl-column-style select');
        if ($styleSelect.length) {
          $styleSelect.find('option').each(function() {
            if ($element.hasClass(this.value)) {
              $styleSelect.val(this.value);
              return false;
            }
          });
        }
      }
    };

    /**
     * Resets to the inital wrapper.
     */
    this.activate = function() {
      $displayContainer.find('[data-machine-name="flexible_layout"]').click();
    };

    // Init
    if ($flexibleLayout.length && $displayContainer.length) {

      // Prevent multiple instantiations.
      if (!$(this).data('has_flexible_layout')) {
        $(this).data('has_flexible_layout', true);
        _self.init(presetLayout);
      }
      // Can change layouts on the fly.
      else if (presetLayout) {
        $displayContainer.empty();
        _self.unserializeLayout(JSON.parse(presetLayout), $displayContainer);
      }
    }

    // Allow chaining.
    return this;
  };

}(jQuery, Drupal));
