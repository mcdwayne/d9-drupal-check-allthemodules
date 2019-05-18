/**
 * @file
 * Responsive image batch admin behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Binds tooltips showing media query values.
   */
  Drupal.behaviors.responsiveImageBatchTooltip = {
    attach: function (context, settings) {
      $('.responsive-image-batch__fieldset', context).once('responsive-image-batch-tooltip').each(function () {
        $(this).tooltip({
          items: '[data-tooltip]',
          content: function () {
            return $(this).data('tooltip');
          },
          tooltipClass: 'responsive-image-batch__tooltip'
        });
      });
    }
  };

  /**
   * Calculates image dimensions based on a given modifier and/or aspect ratio.
   */
  Drupal.behaviors.responsiveImageBatchSizeCalculation = {
    attach: function (context, settings) {
      // Picture interface.
      $('.responsive-image-batch__fieldset--picture')
        .find('.responsive-image-batch__picture-image-styles:first')
        .once('responsive-image-batch-picture')
        .each(function () {

          var $inputTable = $(this);
          var $tables = $('.responsive-image-batch__picture-image-styles');
          $inputTable.find('tbody tr').each(function () {
            new PictureRow(this, $tables);
          });
        });
      // Sizes interface.
      $('.responsive-image-batch__fieldset--sizes')
        .once('responsive-image-batch-sizes')
        .each(function () {

          new Sizes(this);
        });
    }
  };

  /**
   * Function to manipulate an image style table row.
   *
   * @param {Object} row
   *   An image style row with input fields.
   * @param {Object} $tables
   *   jQuery object containing all image style tables each representing a multiplier.
   */
  function PictureRow(row, $tables) {
    this.$row = $(row);
    this.$tables = $tables;
    this.$width = this.$row.find('[data-dimension="width"]');
    this.$height = this.$row.find('[data-dimension="height"]');
    this.$aspectRatio = this.$row.find('select.form-select');
    this.breakpointID = this.$row.data('breakpoint-id');
    this.$targetRows = this.$tables.find('[data-breakpoint-id="' + this.breakpointID + '"]').not('[data-multiplier="1"]');
    this.bindInput();
    this.bindAspectRatio();
  }

  /**
   * Binds input fields of a row.
   */
  PictureRow.prototype.bindInput = function () {
    var self = this;
    this.$row.find('input.form-number').each(function () {
      $(this).on('keyup.responsiveImageBatch', function (e) {
        var dimension = $(this).data('dimension');
        var value = $(this).val();
        self.updateCells(dimension, value);
        var aspectRatio = self.$aspectRatio.val();
        if (aspectRatio) {
          self.updateAspectRatio(aspectRatio, dimension);
        }
      });
    });
  };

  /**
   * Callback function to update the target rows with calculated values.
   *
   * @param {string} dimension
   *   Either width or height.
   * @param {number} value
   *   Original value from the input field.
   */
  PictureRow.prototype.updateCells = function (dimension, value) {
    var selector = '.responsive-image-batch__' + dimension;
    this.$targetRows.each(function () {
      var calculatedDimension = Math.ceil(value * $(this).data('multiplier'));
      if (calculatedDimension === 0) {
        calculatedDimension = '';
      }
      // Update markup.
      $(this).find(selector).text(calculatedDimension);
      // Update hidden field.
      $(this).find(selector + '-hidden').val(calculatedDimension);
    });
  };

  /**
   * Binds aspect radio select element.
   */
  PictureRow.prototype.bindAspectRatio = function () {
    var self = this;
    this.$aspectRatio.on('change.responsiveImageBatchPicture', function (e) {
      var value = $(this).val();
      var text = $(this).find('option').filter(':selected').text();
      self.updateAspectRatioCells(text);
      if (self.$width.val()) {
        self.updateAspectRatio(value, 'width');
      }
      else if (self.$height.val()) {
        self.updateAspectRatio(value, 'height');
      }
    });
  };

  /**
   * Callback function to update input values.
   *
   * Updates the input values after selecting an aspect ratio. Updates both the
   * other input field value and the related multiplier table cells.
   *
   * @param {string} aspectRatio
   *   The value from the aspect ratio select element.
   * @param {string} dimension
   *   Either width or height. The original value, which is used to calculate
   *   and update the other fields value(s).
   */
  PictureRow.prototype.updateAspectRatio = function (aspectRatio, dimension) {
    if (!aspectRatio || !dimension) {
      return;
    }
    var newVal;
    aspectRatio = aspectRatio.split(':');
    var ARWidth = parseInt(aspectRatio[0]);
    var ARHeight = parseInt(aspectRatio[1]);
    if (dimension === 'width') {
      var width = this.$width.val();
      newVal = Math.ceil(width / ARWidth * ARHeight);
      this.$height.val(newVal);
      this.updateCells('height', newVal);
    }
    if (dimension === 'height') {
      var height = this.$height.val();
      newVal = Math.ceil(height / ARHeight * ARWidth);
      this.$width.val(newVal);
      this.updateCells('width', newVal);
    }
  };

  /**
   * Updates aspect ratio text value in target rows.
   *
   * @param {string} aspectRatio
   *   The value from the aspect ratio select element.
   */
  PictureRow.prototype.updateAspectRatioCells = function (aspectRatio) {
    this.$targetRows.find('.responsive-image-batch__aspect-ratio').text(aspectRatio);
  };

  /**
   * Function to calculate and update the Sizes image styles table.
   *
   * @param {Object} sizesFieldset
   *   The fieldset wrapper for the Sizes form.
   */
  function Sizes(sizesFieldset) {
    this.$fieldset = $(sizesFieldset);
    this.$width = this.$fieldset.find('.responsive-image-batch__width');
    this.$height = this.$fieldset.find('.responsive-image-batch__height');
    this.$aspectRatio = this.$fieldset.find('.responsive-image-batch__aspect-ratio');
    this.$incrementValue = this.$fieldset.find('.responsive-image-batch__increment-value');
    this.$incrementType = this.$fieldset.find('.responsive-image-batch__increment-type');
    this.$totalIncrements = this.$fieldset.find('.responsive-image-batch__total-increments');
    this.$roundUp = this.$fieldset.find('.responsive-image-batch__round-up');
    this.componentId = this.$fieldset.data('component-id');
    this.$imageStylesTable = this.$fieldset.find('.responsive-image-batch__sizes-image-styles');
    this.$fallbackImageStyle = this.$fieldset.find('.responsive-image-batch__fallback-image-style');
    this.bindAspectRatio();
    this.bindControls();
  }

  /**
   * Bind callback to aspect ratio select field.
   */
  Sizes.prototype.bindAspectRatio = function () {
    var self = this;
    this.$aspectRatio.on('change.responsiveImageBatchSizes', function (e) {
      var aspectRatio = $(this).val();
      if (self.$width.val()) {
        self.updateAspectRatio(aspectRatio, 'width');
      }
      else if (self.$height.val()) {
        self.updateAspectRatio(aspectRatio, 'height');
      }
      // Update image styles.
      self.updateImageStyles();
    });
  };

  /**
   * Callback function to update dimension input values.
   *
   * @param {string} aspectRatio
   *   The value from the aspect ratio select element.
   * @param {string} dimension
   *   Either width or height. The original value, which is used to calculate
   *   and update the other fields value(s).
   */
  Sizes.prototype.updateAspectRatio = function (aspectRatio, dimension) {
    if (!aspectRatio || !dimension) {
      return;
    }
    var newVal;
    aspectRatio = aspectRatio.split(':');
    var ARWidth = parseInt(aspectRatio[0]);
    var ARHeight = parseInt(aspectRatio[1]);
    if (dimension === 'width') {
      var width = this.$width.val();
      newVal = Math.ceil(width / ARWidth * ARHeight);
      this.$height.val(newVal);
    }
    if (dimension === 'height') {
      var height = this.$height.val();
      newVal = Math.ceil(height / ARHeight * ARWidth);
      this.$width.val(newVal);
    }
  };

  /**
   * Add bind callbacks to form elements.
   */
  Sizes.prototype.bindControls = function () {
    var self = this;

    // All number fields.
    this.$fieldset.find('input.form-number').each(function () {
      $(this).on('keyup.responsiveImageBatchSizes', function (e) {

        // If it's a dimension field and an aspect ratio is set,
        // update those values first.
        var dimension = $(this).data('dimension');
        var aspectRatio = self.$aspectRatio.val();
        if (dimension && aspectRatio) {
          self.updateAspectRatio(aspectRatio, dimension);
        }

        // Update image styles.
        self.updateImageStyles();

      });
    });
    // Increment type select.
    this.$incrementType.on('change.responsiveImageBatchSizes', function (e) {
      // Update image styles.
      self.updateImageStyles();
    });
    // Round up select.
    this.$roundUp.on('change.responsiveImageBatchSizes', function (e) {
      // Update image styles.
      self.updateImageStyles();
    });
  };

  /**
   * Update the DOM with image style calculations.
   */
  Sizes.prototype.updateImageStyles = function () {
    // Calculate image styles.
    var imageStyles = this.calculateImageStyles();
    var $rows = [];
    var $row;

    // Update image styles table.
    if (imageStyles.length > 0) {
      for (var i = 0; i < imageStyles.length; i++) {
        $row = $('<tr>')
          .append($('<td>').text(imageStyles[i].label))
          .append($('<td>').text(imageStyles[i].increase))
          .append($('<td>').text(imageStyles[i].width))
          .append($('<td>').text(imageStyles[i].height));
        $rows.push($row);
      }
    }
    else {
      $row = $('<tr>').append($('<td>').text(Drupal.t('No image styles to generate.')));
      $rows.push($row);
    }
    this.$imageStylesTable.find('tbody').empty().append($rows);

    // Update fallback image style select element.
    if (imageStyles.length === 0) {
      return;
    }
    var selectValue = this.$fallbackImageStyle.val();
    var $options = this.$fallbackImageStyle.find('option');
    // Remove all image styles.
    $options.filter("[value*='__sizes_']").remove();
    // Create new options array.
    var newOptions = [];
    if (imageStyles.length > 0) {
      for (var x = 0; x < imageStyles.length; x++) {
        var $newOption = $('<option></option>')
          .attr('value', imageStyles[x].name)
          .text(imageStyles[x].label);
        newOptions.push($newOption);
      }
    }
    // Drop them in after first option.
    this.$fallbackImageStyle.find('option:eq(0)').after(newOptions);
    // Keep value selected if the name didn't change.
    for (var y = 0; y < imageStyles.length; y++) {
      if (selectValue === imageStyles[y].name) {
        this.$fallbackImageStyle.val(selectValue);
      }
    }
  };

  /**
   * Calculates values for image styles.
   *
   * JS implementation of the PHP function
   * ResponsiveImageBatchForm::generateSizesImageStyles().
   *
   * @returns {Array}
   *   Array containing image style calculations.
   */
  Sizes.prototype.calculateImageStyles = function () {
    var imageStyles = [];
    var width = parseInt(this.$width.val());
    var height = parseInt(this.$height.val());
    var incrementValue = parseInt(this.$incrementValue.val());
    var incrementType = this.$incrementType.val();
    var totalIncrements = parseInt(this.$totalIncrements.val());
    var roundUp = parseInt(this.$roundUp.val());
    var aspectRatio;

    if (!width) {
      return imageStyles;
    }

    if (height) {
      aspectRatio = (height / width) * 100;
    }
    else {
      height = '';
    }

    var imageStyleLabel = this.createImageStyleLabel(width);
    var imageStyleName = this.createImageStyleName(imageStyleLabel);

    // First image style.
    imageStyles.push({
      name: imageStyleName,
      label: imageStyleLabel,
      increase: '',
      width: width,
      height: height
    });

    if (!incrementValue || !incrementType || !totalIncrements) {
      return imageStyles;
    }

    // Increments.
    var preRoundUpWidth = width;
    var prevWidth;
    for (var i = 0; i < totalIncrements; i++) {
      var increase;
      var increment;
      if (incrementType === 'px') {
        prevWidth = width;
        width = width + incrementValue;
        width = preRoundUpWidth + incrementValue;
        preRoundUpWidth = width;
        if (roundUp) {
          width = this.roundUp(width, roundUp);
        }
        increase = width - prevWidth + 'px';
      }
      else if (incrementType === 'percent') {
        prevWidth = width;
        increment = (preRoundUpWidth / 100) * incrementValue;
        width = Math.ceil(preRoundUpWidth + increment);
        preRoundUpWidth = width;
        if (roundUp) {
          width = this.roundUp(width, roundUp);
        }
        increase = Math.round(((width - prevWidth) / prevWidth) * 100) + '%';
        increase += ' (' + (width - prevWidth) + 'px)';
      }
      if (aspectRatio) {
        height = Math.ceil((width / 100) * aspectRatio);
      }
      imageStyleLabel = this.createImageStyleLabel(width);
      imageStyleName = this.createImageStyleName(imageStyleLabel);
      imageStyles.push({
        name: imageStyleName,
        label: imageStyleLabel,
        increase: increase,
        width: width,
        height: height
      });
    }

    return imageStyles;
  };

  /**
   * Helper function to round up to a value.
   *
   * @param {number} num
   *   Value to round up.
   * @param {number} roundValue
   *   Value to round up to.
   *
   * @returns {number}
   *   Rounded up value.
   */
  Sizes.prototype.roundUp = function (num, roundValue) {
    return Math.ceil(num / roundValue) * roundValue;
  };

  /**
   * Helper function to create a Sizes image style label.
   *
   * @param {number} width
   *   Width of the image style.
   *
   * @returns {string}
   *   An image style label.
   */
  Sizes.prototype.createImageStyleLabel = function (width) {
    return this.componentId + '--sizes-' + width;
  };

  /**
   * Helper function to create a Sizes image style name.
   *
   * @param {string} imageStyleLabel
   *   Image style label.
   *
   * @returns {string}
   *   An image style machine name.
   */
  Sizes.prototype.createImageStyleName = function (imageStyleLabel) {
    return imageStyleLabel.replace(/[^a-z0-9]/g, '_');
  };

}(jQuery, Drupal));
