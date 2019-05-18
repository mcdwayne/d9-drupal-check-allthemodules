(function ($) {
  'use strict';

  function getFloatLabelElements(context, settings) {
    var $forms = $('.float-labels-include-children', context);

    var $elements = $('.float-labels-include', context)
      .add($forms.find(':input:text, textarea'))
      .not('.float-labels-processed, :button, select');

    if (settings.includes && settings.includes.length > 0) {
      $elements = $elements.is(settings.includes.join(', '));
    }

    if (settings.excludes && settings.excludes.length > 0) {
      $elements = $elements.not(settings.excludes.join(', '));
    }

    return $elements;
  }

  function getLabel(element) {
    var id = element.attr('id');
    var $label = $(element).closest('form').find('label[for=' + id + ']');
    var text = '';

    if ($label.length > 0) {
      text = $label.text();

      $label.remove();
    }
    else if (element.attr('title')) {
      text = element.attr('title');
    }
    else if (element.attr('placeholder')) {
      text = element.attr('placeholder');
    }

    return text;
  }

  function processFloatLabels($elements, settings) {
    $elements.each(function () {
      var $element = $(this).addClass('float-labels-item');
      var id = $(this).attr('id');
      var labelText = getLabel($element);
      var required = $element.prop('required');

      $element
        .wrap($('<div class="float-labels-wrapper">').toggleClass('float-labels-required', required).toggleClass('float-labels-star', settings.mark_required))
        .before($('<label class="float-labels-label">').attr('for', id).text(labelText))
        .removeAttr('placeholder')
        .addClass('float-labels-processed');
    });

    var $processed = $('.float-labels-processed');

    $processed.on('focus blur', function (e) {
      var length = (typeof this.value === 'undefined') ? 0 : this.value.length;

      $(this).parents('.float-labels-wrapper').toggleClass('float-labels-focused', (e.type === 'focus' || length > 0));
    }).trigger('blur');

    $processed.on('change', function (e) {
      $(this).not(':focus').trigger('blur');
    });
  }

  function getFloatLabelSettings(settings) {
    if (settings.hasOwnProperty('float_labels')) {
      settings = settings.float_labels;
    } else {
      settings = Drupal.settings.float_labels;
    }

    return settings;
  }

  Drupal.behaviors.floatLabels = {
    attach: function (context, settings) {
      settings = getFloatLabelSettings(settings);
      var elements = getFloatLabelElements(context, settings);
      processFloatLabels(elements, settings);
    }
  };
}(jQuery));
