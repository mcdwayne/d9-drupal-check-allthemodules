(function ($, Drupal, drupalSettings) {

  // Set default ajax settings.
  var gridAjaxSettings_base = {
    url: '/ajax/grid',
    // This is the wrapper div for all ajax functionality.
    ajax_wrapper: '.ajax_wrapper',
    // This is the div where results will be replaced.
    ajax_results: '.ajax_results',
    ajax_filters: '.ajax_filter, .ajax_filter_checkbox:checked',
    effect: 'fade',
    progress: {
      // Normally either progress or bar, but we created a custom one.
      type: 'gridajax',
      message: Drupal.t('Loading...')
    }
  };

  // Contains the settings for each grid widget.
  var widget_settings = {};

  Drupal.behaviors.gridAjax = {
    smoothscroll: function(hash, speed) {
      // Set default offset for main frontend (for like a fixed nav bar)
      var offset = 0;

      if (drupalSettings.user.uid > 0) {
        // This user is logged in. Account for the nav bar.
        offset += 85;
      }

      if (!speed) {
        speed = 1000;
      }

      if ($(window).width() < 640) offset = 0;
      $('html,body').animate({scrollTop: $(hash).offset().top - offset}, speed);
    },

    attach: function (context, settings) {

      /**
       * Go through all ajax containers to put together the post data object.
       * This is done on page load and prevents us from having to do this for
       * every ajax call. We only want this to run once on initial page load.
       */
      $(gridAjaxSettings_base.ajax_results, context).once('grid-ajax').each(function () {
        var $ajax_results = $(this);
        var wrapper_id = $ajax_results.attr('id');
        var widget_id = $ajax_results.attr('widgetid');

        // Set all attributes on the div with the AJAX post.
        widget_settings[wrapper_id] = {};
        $.each(this.attributes, function () {
          if (this.specified) {
            widget_settings[wrapper_id][this.name] = this.value;
          }
        });

        // Do we need to bind to scroll for this grid? Triggers on the scroll
        // event, then clicks on the hidden pagination link in the html.
        if (widget_id in settings.stacksgrid.scroll) {
          $(window).on('scroll', function () {
            if (isScrolledIntoView($ajax_results)) {
              $('.ajax_results_pagination a:first()', $ajax_results).trigger('click');
            }
          });
        }
      });

      // Bind pagination links. This needs to be triggered when AJAX happens as well.
      $('.ajax_results_pagination a', context).once('grid-ajax-pagination').each(function () {
        gridAjax(this, 'click');
      });

      // Smooth scroll to top of results for pagination links.
      $('.ajax_results_pagination:not(.load_more):not(.load_more):not(.load_more_scroll) a').once('grid-ajax-smoothscroll').on('click', function () {
        var wrapper_id = $(this).closest(gridAjaxSettings_base.ajax_results)[0].getAttribute('id');
        Drupal.behaviors.gridAjax.smoothscroll('#' + wrapper_id, 500);
      });

      // Bind filter drop downs. This only needs to happen on page load.
      var hasDefaultValues = 0;
      $(gridAjaxSettings_base.ajax_wrapper + ' .ajax_filter', context).once('grid-ajax').each(function () {
        // Set URL attached values (if exist) before attaching events.
        hasDefaultValues += setDefaultValue($(this));
        gridAjax(this, 'change');
      });

      // Handle filters that are checkboxes/radio buttons.
      $(gridAjaxSettings_base.ajax_wrapper + ' .ajax_filter_checkbox_wrapper', context).once('grid-ajax').each(function () {
        // Get the correct object for the checkboxes.
        var object = $('input[name="' + $(this).attr('fieldname') + '"]');
        for (i = 0; i < object.length; i++) {
          gridAjax(object[i], 'change');
        }
      });

      // Trigger ajax call for search field.
      $('.filter_search_form').once('grid-ajax-submit').submit(function (e) {
        e.preventDefault();
        $('.filter_search', this).change();
      });

      // Triggers ajax if the form is filled with default URL parameters.
      if (hasDefaultValues > 0) {
        $('select.ajax_filter').last().trigger('change');
      }

      // Add an active class when the radio filter is checked.
      $('.radio-active input').once('radio-change').change(function () {

        var $radio = $(this);
        var $wrapper = $radio.parent().parent();

        // Remove is-active class from all other radios.
        $('label', $wrapper).removeClass('is-active');

        // Add class to the selected option.
        if (this.checked) {
          $radio.parent().addClass('is-active');
        }
      });

      /**
       * Do AJAX on page load.
       *
       * If the attribute ajaxpageload=1 on the ajax_results div, we need to
       * load the results via ajax on page load. We do this by setting a "load"
       * event on ajax_results and triggering it.
       */
      //$(gridAjaxSettings_base.ajax_results + '[ajaxpageload="1"]', context).once('grid-ajax-page-load').each(function () {
      //ajaxPageLoad(this);
      //});
    }
  };

  /**
   * Function for parsing query strings
   */
  function parseQueryString(query) {
    var args = {};
    var pos = query.indexOf('?');
    if (pos !== -1) {
      query = query.substring(pos + 1);
    }
    var pair;
    var pairs = query.split('&');
    for (var i = 0; i < pairs.length; i++) {
      pair = pairs[i].split('=');
      // Ignore the 'q' path argument, if present.
      if (pair[0] !== 'q' && pair[1]) {
        args[decodeURIComponent(pair[0].replace(/\+/g, ' '))] = decodeURIComponent(pair[1].replace(/\+/g, ' '));
      }
    }
    return args;
  }

  /**
   * Main function for creating the Drupal.ajax events.
   */
  function gridAjax(object, event_target) {
    var gridAjaxSettings = jQuery.extend({}, gridAjaxSettings_base);

    var $object = $(object);
    var $ajax_wrapper = $object.closest(gridAjaxSettings.ajax_wrapper);
    var $ajax_results = $(gridAjaxSettings.ajax_results, $ajax_wrapper);

    var wrapper_id = $ajax_results.attr('id');
    var widget_id = $ajax_results.attr('widgetid');

    if ($object.parent().hasClass('pager__item') ||
      $object.parent().parent().hasClass('js-pager__items') ||
      $object.closest('div.ajax_results_pagination').hasClass('load_more') ||
      $object.closest('div.ajax_results_pagination').hasClass('load_more_scroll') ||
      $object.attr('rel') == 'prev' ||
      $object.attr('rel') == 'next'
    ) {
      // Fix the URL when multiple pagers
      var pagerHref = parseQueryString($object.attr('href'));
      // Explode query to get the right page number
      var pageAttrs = pagerHref.page.split(",");
      var validQueryComponents = pageAttrs.slice(0, parseInt(widget_id) + 1);
      widget_settings[wrapper_id].page = pageAttrs[widget_id];
      widget_settings[wrapper_id].pager_element = widget_id;
      var fixedPageLink = '?_wrapper_format=drupal_ajax&page=' + pageAttrs[widget_id];
      $object.attr('href', fixedPageLink);
    }

    // Add url query to url. Mainly to get the page parameter.
    if (object.hasAttribute('href')) {
      gridAjaxSettings.url += $object.attr('href');
    }

    gridAjaxSettings.wrapper = wrapper_id;
    gridAjaxSettings.event = event_target;
    gridAjaxSettings.element = object;

    // Send the widget data with the ajax post.
    gridAjaxSettings.submit = widget_settings[wrapper_id];

    var ajax = Drupal.ajax(gridAjaxSettings);

    // Add filters values before sending data. We overrite default Drupal.ajax
    // behavior.
    //TODO: fix this function call or find a different way to alter default AJAX handlers
    Drupal.Ajax.prototype.beforeSerialize = function (element, options) {

      var requestURL = options.url;

      if (requestURL.includes('ajax/grid')) {
        // Include default Drupal.Ajax.prototype.beforeSerialize code.
        options.data[Drupal.Ajax.AJAX_REQUEST_PARAMETER] = 1;
        var pageState = drupalSettings.ajaxPageState;
        options.data['ajax_page_state[theme]'] = pageState.theme;
        options.data['ajax_page_state[theme_token]'] = pageState.theme_token;
        options.data['ajax_page_state[libraries]'] = pageState.libraries;

        // Add the data for the filters.
        options.data['filters'] = getFilters(options);

      } else {
        // Allow detaching behaviors to update field values before collecting them.
        // This is only needed when field values are added to the POST data, so only
        // when there is a form such that this.$form.ajaxSubmit() is used instead of
        // $.ajax(). When there is no form and $.ajax() is used, beforeSerialize()
        // isn't called, but don't rely on that: explicitly check this.$form.
        if (this.$form) {
          var settings = this.settings || drupalSettings;
          Drupal.detachBehaviors(this.$form.get(0), settings, 'serialize');
        }

        // Inform Drupal that this is an AJAX request.
        options.data[Drupal.Ajax.AJAX_REQUEST_PARAMETER] = 1;

        // Allow Drupal to return new JavaScript and CSS files to load without
        // returning the ones already loaded.
        // @see \Drupal\Core\Theme\AjaxBasePageNegotiator
        // @see \Drupal\Core\Asset\LibraryDependencyResolverInterface::getMinimalRepresentativeSubset()
        // @see system_js_settings_alter()
        var pageState = drupalSettings.ajaxPageState;
        options.data['ajax_page_state[theme]'] = pageState.theme;
        options.data['ajax_page_state[theme_token]'] = pageState.theme_token;
        options.data['ajax_page_state[libraries]'] = pageState.libraries;
      }
    };

    return ajax;
  }

  /**
   * Put together the filters data object to send with the AJAX. We need to
   * trigger this after every ajax request to make sure we have the correct
   * filter options.
   */
  function getFilters(options) {
    var $get_filters = $('#' + options.data['id']).closest(gridAjaxSettings_base.ajax_wrapper).find(gridAjaxSettings_base.ajax_filters);

    var filters = {};
    $get_filters.each(function () {
      var $filter_object = $(this);
      if ($filter_object.val() != '' && !$filter_object.hasClass('ajax_filter_checkbox')) {
        var filter_name = $filter_object.attr('name');
        if ($filter_object.attr('field')) {
          var field_name = $filter_object.attr('field');

          // For certain filters like taxonomy, add one more level to the filter.
          if ($filter_object.attr('filtertype')) {

            var filter_type = $filter_object.attr('filtertype');
            if (typeof filters[filter_type] === 'undefined') {
              filters[filter_type] = {};
            }

            filters[filter_type][filter_name] = {};
            filters[filter_type][filter_name] = [$filter_object.val()];
          }
          else {
            filters[filter_name] = {};
            filters[filter_name][field_name] = [$filter_object.val()];
          }

        }
        else {
          filters[filter_name] = [$filter_object.val()];
        }
      }
      else if ($filter_object.hasClass('ajax_filter_checkbox')) {
        // Checkboxes/radios.
        var field_name = $filter_object.attr('name');
        filters[field_name] = [];

        // Since this could be a checkbox, there might be multiple selected values.
        $($filter_object).each(function () {
          filters[field_name].push($(this).val());
        });

      }
    });

    return filters;
  }

  /**
   * Function to set prefixed values that come from the URL (if exist).
   */
  function setDefaultValue(object) {
    var found = 0;
    if (object.is('select') && (parameter = getParameterByName(object.attr('name')))) {
      object.find('option').each(function () {
        if ($(this).val() == parameter) {
          object.val(parameter).change().trigger("chosen:updated");
          found = true;
        }
      });
    }

    if (found) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Function to get URL parameters.
   */
  function getParameterByName(name) {
    if (typeof name !== "undefined") {
      url = window.location.href;
      name = name.replace(/[\[\]]/g, "\\$&");
      var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, " "));
    }
  }

  /**
   * Call AJAX for a grid on page load.
   *
   * Default Drupal.ajax works, but it doesn't seems to get rid of the progress
   * loaders, except for the last one. So we use setTimeout() to manually
   * delete the progress loaders.
   */
  var ajax_load_delay_counter = 200;

  function ajaxPageLoad(cur_this) {
    var $object = $(cur_this);
    var $ajax_wrapper = $object.closest(gridAjaxSettings_base.ajax_wrapper);
    gridAjax(cur_this, 'load');
    $object.trigger('load');

    setTimeout(function () {
      $('.ajax-progress', $ajax_wrapper).remove();
    }, ajax_load_delay_counter);

    ajax_load_delay_counter += 200;
  }

  /**
   * See if something something is viewable and if so, returns true.
   */
  var $window = $(window);

  function isScrolledIntoView($elem) {
    var docViewTop = $window.scrollTop();
    var docViewBottom = docViewTop + $window.height();
    var elemTop = $elem.offset().top;
    var elemBottom = elemTop + $elem.height();
    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
  }

  /**
   * Create a new progress bar "type" option (gridajax).
   *
   * We make sure to attach the loader to the location we are expecting.
   */
  Drupal.Ajax.prototype.setProgressIndicatorGridajax = function () {
    this.progress.element = $('<div>', {
      class: 'ajax-progress ajax-progress-gridajax',
      html: $('<div>', {
        class: 'gridajax',
        html: '\u00a0'
      })
    });
    if (this.progress.message) {
      this.progress.element.find('.gridajax').after('<div class="message">' + this.progress.message + '</div>');
    }
    $(this.wrapper).after(this.progress.element);
  };

  /**
   * Create a jquery method to equalize the row of columns.
   */
  $.fn.ajaxequalizer = function () {
    setTimeout(function () {
      $('.js-equal').each(function () {
        $(this).equalize({
          children: '.js-equal__item',
          reset: true,
          equalize: 'innerHeight'
        });
      });
    }, 1500);
  };

})(jQuery, Drupal, drupalSettings);
