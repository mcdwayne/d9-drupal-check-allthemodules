(function ($, drupalSettings) {

  /**
   * Generator constructor.
   * @param taggd
   * @param data_field
   * @constructor
   */
  function Generator(taggd, data_field) {
    this.taggd = taggd;
    this.dataField = data_field;
  };

  /**
   * Generates json and adds it to hidden field.
   */
  Generator.prototype.generateTagsJson = function () {
    var tags = Generator.cleanTags(this.taggd.getTags());

    this.dataField.val(JSON.stringify(tags));
    this.disableButtons();
  };

  /**
   * Disables buttons in tags.
   */
  Generator.prototype.disableButtons = function () {
    // Disable click event, because Drupal will submit form.
    $('button.taggd__button, button.taggd__editor-button').on('click', function () {
      return false;
    });
  };

  /**
   * Helper function to clean tags.
   * @param tags
   * @returns {*|Array|Taggd}
   */
  Generator.cleanTags = function (tags) {
    var precision = 5;
    var precisionMultiplier = Math.pow(10, precision);
    return tags.map(function (tag) {
      tag = tag.toJSON();

      return {
        "position": {
          x: Math.round(tag.position.x * precisionMultiplier) / precisionMultiplier,
          y: Math.round(tag.position.y * precisionMultiplier) / precisionMultiplier
        },
        "text": tag.text
      };
    });
  };

  Drupal.behaviors.taggdWidget = {
    attach: function (context) {
      // Get all taggd widgets.
      var taggd_widgets = $('.taggd-image');
      var taggd_settings = drupalSettings.taggd_widget || [];

      // Loop over widgets and apply Taggd.
      taggd_widgets.each(function (index, widget) {
        $widget = $(widget);
        // This is jQuery object.
        var $image = $widget.find('img');
        // This is pure JS object.
        var image = $image.get(0);

        var taggd_id = $widget.attr('data-taggd');
        var data_field = $widget.find('input.taggd-image-data');

        // Init options.
        var settings = {};
        var options = {
          show: 'click',
          hide: 'click'
        };
        var data = [];
        // Check if settings are set for current widget.
        if (taggd_id in taggd_settings) {
          // Check if parent container is bigger than image width.
          if ($image.parent().width() > $image.attr('width')) {
            // If so make set parent dimensions equal to image dimension.
            // Because taggd library sets width of image to 100%.
            $image.parent().width($image.attr('width')).height($image.attr('height'));
          }

          settings = taggd_settings[taggd_id];

          $.each(settings, function (key, setting) {
            data.push(Taggd.Tag.createFromObject(setting));
          });
        }

        // Init taggd.
        var taggd = new Taggd(image, options, data);
        taggd.enableEditorMode();
        // Init generator to manage tags.
        var generator = new Generator(taggd, data_field);
        taggd.on('taggd.tag.added', generator.generateTagsJson.bind(generator));
        taggd.on('taggd.tag.deleted', generator.generateTagsJson.bind(generator));
        taggd.on('taggd.tag.changed', generator.generateTagsJson.bind(generator));
        taggd.on('taggd.tag.changed', function (taggd, tag) {
          tag.hide();
        });
        // Disable buttons so that Drupal won't submit form.
        generator.disableButtons();
      });

    }
  };

})(jQuery, drupalSettings);
