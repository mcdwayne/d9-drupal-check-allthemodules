(function ($) {

  'use strict';

  /**
   * Drupal ContentGuide object.
   */
  Drupal.ContentGuide = Drupal.ContentGuide || {};

  /**
   * Behaviors.
   */
  Drupal.behaviors.contentGuide = {
    attach: function (context, settings) {
      settings.content_guide = settings.content_guide || drupalSettings.content_guide;
      if (typeof settings.content_guide === 'undefined') {
        // Return early if settings for Content Guide do not exist.
        return;
      }

      // Process each field needing a content guide.
      $.each(settings.content_guide.fields, function (index, field_settings) {
        field_settings.field_selector = field_settings.field_selector || ('.field--name-' + field_settings.field_name);
        field_settings.attach_selector = field_settings.attach_selector || 'label';
        var $field = $(field_settings.field_selector, context);
        if ($field.length === 0) {
          return;
        }
        // Find element to attach.
        var $attach = $field.find(field_settings.attach_selector);
        if ($attach.length === 0) {
          // Simply attach element to first child of field.
          $attach = $field.children().first();
        }
        if ($field.hasClass('content-guide--processed')) {
          // Remove content guide elements from field.
          Drupal.ContentGuide.detachTooltip($field);
          Drupal.ContentGuide.detachDescription($field);
          $field.removeClass('content-guide--processed');
        }
        switch (field_settings.display_type) {
          case 'tooltip':
            Drupal.ContentGuide.attachTooltip($attach, field_settings, settings);
            break;

          case 'description':
            Drupal.ContentGuide.attachDescription($attach, field_settings, settings);
            break;
        }
        $field.addClass('content-guide--processed');
      });
    }
  };

  /**
   * Attach a tooltip to the given element.
   *
   * @param $element
   *   Element to attach the tooltip to.
   * @param field_settings
   *   Field settings.
   */
  Drupal.ContentGuide.attachTooltip = function ($element, field_settings, settings) {
    if ($element.find('.content-guide--tooltip-icon').length) {
      return;
    }
    var content_url = settings.path.baseUrl + settings.path.pathPrefix + 'content-guide/';
    content_url = content_url + field_settings.entity_type + '/';
    content_url = content_url + field_settings.bundle + '/';
    content_url = content_url + field_settings.form_display + '/';
    content_url = content_url + field_settings.field_name;
    var $tooltip = $('<a>')
            .attr('title', Drupal.t('Display Content Guide'))
            .addClass('content-guide--tooltip-icon')
            .html('[?]');
    $tooltip.not('.tooltipstered').tooltipster({
      contentAsHTML: true,
      theme: 'tooltipster-light',
      interactive: true,
      functionBefore: function (instance, helper) {
        var $origin = $(helper.origin);
        if ($origin.data('ajax') !== 'cached') {
          instance.content(Drupal.t('Loading ...'));
          $.ajax({
            url: content_url,
            type: 'get',
            success: function (response) {
              instance.content($(response.content));
              $origin.data('ajax', 'cached');
            }
          });
          $origin.data('ajax', 'cached');
        }
      },
      trigger: 'click'
    });
    $tooltip.on({
      'click': function (e) {
        e.stopPropagation();
        e.preventDefault();
      }
    });
    $tooltip.appendTo($element);
  };

  /**
   * Remove a tooltip from the given element.
   *
   * @param $element
   *   Element to attach the tooltip to.
   */
  Drupal.ContentGuide.detachTooltip = function ($element) {
    var $tooltip = $element.find('.content-guide--tooltip-icon');
    if ($tooltip.length) {
      $tooltip.remove();
    }
  }


  /**
   * Attach a description to the given element.
   *
   * @param $element
   *   Element to attach the tooltip to.
   * @param field_settings
   *   Field settings.
   */
  Drupal.ContentGuide.attachDescription = function ($element, field_settings, settings) {
    if ($element.find('.content-guide--description').length) {
      return;
    }
    var content_url = settings.path.baseUrl + settings.path.pathPrefix + 'content-guide/';
    content_url = content_url + field_settings.entity_type + '/';
    content_url = content_url + field_settings.bundle + '/';
    content_url = content_url + field_settings.form_display + '/';
    content_url = content_url + field_settings.field_name;
    var $description = $('<div>')
            .addClass('content-guide--description');
    $.ajax({
      url: content_url,
      type: 'get',
      success: function (response) {
        $description.html($(response.content));
        $description.appendTo($element);
      }
    });
  }

  /**
   * Remove a description from the given element.
   *
   * @param $element
   *   Element to attach the tooltip to.
   */
  Drupal.ContentGuide.detachDescription = function ($element) {
    var $description = $element.find('.content-guide--description');
    if ($description.length) {
      $description.remove();
    }
  }

})(jQuery, drupalSettings);
