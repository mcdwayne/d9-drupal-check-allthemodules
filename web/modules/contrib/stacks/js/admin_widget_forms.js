(function ($, Drupal, drupalSettings) {

  Drupal.stacks = {"validateName": {}, "debouncedSearch": {}};

  Drupal.stacks.validateName.apply = function (el) {
    if (el.val() != '') {
      var current = el.closest('.form-wrapper').find('input[data-drupal-selector="edit-widget-instance-id"]').val();
      var search = el.val();

      if (current) {
        $.ajax({
          url: "/admin/structure/stacks/ajax/validate_name/" + current + "/" + search,
          cache: false
        })
          .done(function (result) {
            var $span = $('<span>', {
                'id': 'widget-name-message',
                'class': 'widget-name-message',
                'role': 'contentinfo'
              }),
              $form_wrapper = el.closest('.form-wrapper'),
              $edit_widget_name = $form_wrapper.find('input[data-drupal-selector="edit-widget-name"]'),
              $edit_next = $form_wrapper.find('input[data-drupal-selector="edit-next"]');

            if (result == 'OK') {
              $span.addClass('ajaxvalidation-ok')
                .attr('aria-label', 'Status message')
                .text('\xa0');

              $edit_widget_name.removeClass('error');
              $edit_next.removeClass('is-disabled').removeAttr('disabled');
            }
            else {
              $span.addClass('ajaxvalidation-fail')
                .attr('aria-label', 'Error message')
                .text(Drupal.t('Widget name already exists'));

              $edit_widget_name.addClass('error');
              $edit_next.addClass('is-disabled').attr('disabled', true);
            }

            $form_wrapper.find(".widget-name-message").remove();
            $form_wrapper.find('.form-item-widget-name').append($span);
          });
        }
      }
  };

  Drupal.stacks.debouncedSearch.apply = function () {
    $('input[data-drupal-selector="edit-filter-title-search"]').trigger('debounce_filter');
  };

  var debounced_search = Drupal.debounce(Drupal.stacks.debouncedSearch, 600);

  function addExistingButtonStatus() {
    var $finish_existing = $('input[data-drupal-selector="edit-finishexisting"]');
    if ($('input.existing-widgets-table.form-radio:checked').length) {
      $finish_existing.removeAttr('disabled');
    }
    else {
      $finish_existing.attr('disabled', 'disabled');
    }
  }

  var previous_value = "";

  Drupal.behaviors.stacks_steps = {
    attach: function (context, settings) {
      var $widget_type_select = $('#widget_type_select');

      $('input[data-drupal-selector="edit-filter-title-search"]').once('stacks').on('keyup blur paste cut', function () {
        var value = $(this).val();
        if (value != previous_value) {
          debounced_search();
          previous_value = value;
        }
      });

      addExistingButtonStatus();
      $('input.existing-widgets-table.form-radio').on('click', function () {
        addExistingButtonStatus();
      });

      $('#tabs-widget-wrapper').on('tabsactivate', function (event, ui) {
        var $finish_existing = $('input[data-drupal-selector="edit-finishexisting"]'),
          $next = $('input[data-drupal-selector="edit-next"]');

        if (ui.newPanel.attr('id') == 'tabs-existing-widget') {
          $finish_existing.show();
          $next.hide();
        }
        else {
          $finish_existing.hide();
          $next.show();
        }
      });

      $('div.form-item-widget-name > label, div.form-item-widget-type > label').addClass('form-required');

      $('input[data-drupal-selector="edit-widget-name"]').once('dkey').each(function () {
        var $this = $(this);
        $this.on('keyup blur paste cut', function () {
          // Call the debounce with parameter.
          Drupal.debounce(Drupal.stacks.validateName.apply, 300)($this);
        });

        Drupal.stacks.validateName.apply($this);
      });

      // Handle updating the template radio, based on widget type.
      $widget_type_select.once('populate_template').on('change', function () {
        var $object = $(this),
          widget_type = $object.val(),
          $widget_template_label = $('.widget_template_label'),
          $widget_template_radio = $('.widget_template_radio'),
          $widget_template_radio_group = $('.widget_template_radio[group="' + widget_type + '"]');

        $widget_template_label.hide();
        $widget_template_radio.hide().find('input.form-radio').removeAttr('checked');
        $('.widget_theme_radio').hide().find('input.form-radio').removeAttr('checked');

        var has_templates = false;
        if ($widget_template_radio_group.length > 0) {
          has_templates = true;

          // Hide the template message div.
          $('#template-message').hide().html('');

          $widget_template_label.show();
          $widget_template_radio.hide();
          $widget_template_radio_group.show();

          // Only click the first option if there isn't a default option.
          if (settings.stacks.form.default_widget_type == '') {
            $widget_template_radio_group.find('input:first').click();
          }
          else {
            // Click the default template that is selected
            $('#template-' + settings.stacks.form.default_widget_template + ' input:first').click();
            settings.stacks.form.default_widget_type = '';
          }

        } else {
          has_templates = false;
          if (widget_type !== '') {
            // This widget type doesn't have any templates! Display a warning.
            var template_file_name = widget_type.replace('_', '-');
            $('#template-message').html(Drupal.t("This widget type doesn't have a default template file. You need to create this file under your theme: stacks/@template_file_name/templates/@template_file_name--default.html.twig", {'@template_file_name': template_file_name})).show();
          } else {
            // Display the default template message.=
            $('#template-message').html(Drupal.t('Select a widget type above.')).show();
          }
        }

        // Enable the next button if they select a template. By default, the 
        // first template will be selected.
        if (widget_type != '' && has_templates) {
          $('input[data-drupal-selector="edit-next"]').removeClass('is-disabled').removeAttr('disabled');
        } else {
          $('input[data-drupal-selector="edit-next"]').addClass('is-disabled').attr('disabled', 'disabled');
        }

      });

      // Make preview images select radio.
      $('.form-type-radio > span').once('radio_click').on('click', function () {
        $(this).parent().children('input').click();
      });

      // Handle updating the theme radios, based on template.
      $('input[name="widget_template"]').once('populate_theme').on('change', function () {
        var $object = $(this),
          template = $object.val(),
          $widget_theme_radio = $('div.widget_theme_radio[bundle-template="' + template + '"]');

        // Clear out theme select options.
        $('.widget_theme_radio').hide().find('input.form-radio').removeAttr('checked');

        $widget_theme_radio.show();

        // If there is a default value, select it.
        if (settings.stacks.form.default_widget_theme != '') {
          $widget_theme_radio.find('input[value="' + settings.stacks.form.default_widget_theme + '"]').click();
          settings.stacks.form.default_widget_theme = '';
        }
        else {
          $widget_theme_radio.find('input:first').click();
        }

      });

      // Make sure to trigger the widget type select for each behavior call.
      // We need to do this after the above events are registered, so that all
      // the code gets hit properly!
      if ($widget_type_select.length > 0) {
        $widget_type_select.once('widget_type_onload').change();
      }

      // Create tabs.
      $('#tabs-widget-wrapper', context).once('tabs_widget_new').tabs();

      $('.existing_stacks_pager li a').on('click', function (e) {
        var selectedPage = $(this).html();
        selectedPage -= 1;
        $('.table_pager_element').val(selectedPage).trigger("change");
        e.preventDefault();
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
