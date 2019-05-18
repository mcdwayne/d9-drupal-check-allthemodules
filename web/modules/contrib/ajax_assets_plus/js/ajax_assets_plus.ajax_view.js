/**
 * @file
 * Handles AJAX fetching of views, including filter submission and response.
 *
 * This file overrides the code of core/modules/views/js/ajax_view.js to support
 * the Drupal.AjaxAssetsPlusAjax ajax constructor.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Attaches the AJAX behavior to exposed filters forms and key View links.
   *
   * Copied from Drupal.behaviors.ViewsAjaxView.attach. Difference: use the
   * Drupal.ajax_assets_plus.ajaxView class.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches ajaxView functionality to relevant elements.
   */
  Drupal.behaviors.ViewsAjaxAssetsPlusView = {};
  Drupal.behaviors.ViewsAjaxAssetsPlusView.attach = function () {
    if (drupalSettings && drupalSettings.ajaxAssetsPlus.views && drupalSettings.ajaxAssetsPlus.views.ajaxViews) {
      var ajaxViews = drupalSettings.ajaxAssetsPlus.views.ajaxViews;
      for (var i in ajaxViews) {
        if (ajaxViews.hasOwnProperty(i)) {
          Drupal.views.instances[i] = new Drupal.ajax_assets_plus.ajaxView(ajaxViews[i]);
        }
      }
    }
  };

  /**
   * @namespace
   */
  Drupal.ajax_assets_plus = {};

  /**
   * Javascript object for a certain view.
   *
   * @todo Extend Drupal.views.ajaxView instead of copying the code.
   *
   * Copied from Drupal.views.ajaxView. Difference: use different ajax_path, use
   * Drupal.ajaxAssetsPlusAjax instead of Drupal.ajax.
   *
   * @constructor
   *
   * @param {object} settings
   *   Settings object for the ajax view.
   * @param {string} settings.view_dom_id
   *   The DOM id of the view.
   */
  Drupal.ajax_assets_plus.ajaxView = function (settings) {
    var selector = '.js-view-dom-id-' + settings.view_dom_id;
    this.$view = $(selector);

    // Retrieve the path to use for views' ajax.
    var ajax_path = drupalSettings.ajaxAssetsPlus.views.ajax_path;

    // If there are multiple views this might've ended up showing up multiple
    // times.
    if (ajax_path.constructor.toString().indexOf('Array') !== -1) {
      ajax_path = ajax_path[0];
    }

    // Check if there are any GET parameters to send to views.
    var queryString = window.location.search || '';
    if (queryString !== '') {
      // Remove the question mark and Drupal path component if any.
      queryString = queryString.slice(1).replace(/q=[^&]+&?|&?render=[^&]+/, '');
      if (queryString !== '') {
        // If there is a '?' in ajax_path, clean url are on and & should be
        // used to add parameters.
        queryString = ((/\?/.test(ajax_path)) ? '&' : '?') + queryString;
      }
    }

    var progress_type = 'fullscreen';
    if (typeof settings.progress_type !== 'undefined') {
      progress_type = settings.progress_type;
    }

    this.element_settings = {
      url: ajax_path + queryString,
      submit: settings,
      setClick: true,
      event: 'click',
      selector: selector,
      progress: {type: progress_type}
    };

    this.settings = settings;

    // Add the ajax to exposed forms.
    this.$exposed_form = $('form#views-exposed-form-' + settings.view_name.replace(/_/g, '-') + '-' + settings.view_display_id.replace(/_/g, '-'));
    this.$exposed_form.once('exposed-form').each($.proxy(this.attachExposedFormAjax, this));

    // Add the ajax to pagers.
    this.$view
      // Don't attach to nested views. Doing so would attach multiple behaviors
      // to a given element.
      .filter($.proxy(this.filterNestedViews, this))
      .once('ajax-pager').each($.proxy(this.attachPagerAjax, this));

    // Add a trigger to update this view specifically. In order to trigger a
    // refresh use the following code.
    //
    // @code
    // $('.view-name').trigger('RefreshView');
    // @endcode
    var self_settings = $.extend({}, this.element_settings, {
      event: 'RefreshView',
      base: this.selector,
      element: this.$view.get(0)
    });
    this.refreshViewAjax = Drupal.ajaxAssetsPlusAjax(self_settings);
  };

  /**
   * Attaches exposed form.
   *
   * Copied from Drupal.views.ajaxView.prototype. Difference: use
   * Drupal.ajaxAssetsPlusAjax instead of Drupal.ajax.
   */
  Drupal.ajax_assets_plus.ajaxView.prototype.attachExposedFormAjax = function () {
    var that = this;
    this.exposedFormAjax = [];
    // Exclude the reset buttons so no AJAX behaviours are bound. Many things
    // break during the form reset phase if using AJAX.
    $('input[type=submit], input[type=image]', this.$exposed_form).not('[data-drupal-selector=edit-reset]').each(function (index) {
      var self_settings = $.extend({}, that.element_settings, {
        base: $(this).attr('id'),
        element: this
      });
      that.exposedFormAjax[index] = Drupal.ajaxAssetsPlusAjax(self_settings);
    });
  };

  /**
   * Attaches exposed form.
   *
   * Copied from Drupal.views.ajaxView.prototype. Fixed: remove '.size()'
   * replace with '.length'.
   *
   * @return {bool}
   *   If there is at least one parent with a view class return false.
   */
  Drupal.ajax_assets_plus.ajaxView.prototype.filterNestedViews = function () {
    // If there is at least one parent with a view class, this view
    // is nested (e.g., an attachment). Bail.
    return !this.$view.parents('.view').length;
  };

  /**
   * Attach the ajax behavior to each link.
   */
  Drupal.ajax_assets_plus.ajaxView.prototype.attachPagerAjax = Drupal.views.ajaxView.prototype.attachPagerAjax;

  /**
   * Attach the ajax behavior to a singe link.
   *
   * Copied from Drupal.views.ajaxView.prototype. Difference: use
   * Drupal.ajaxAssetsPlusAjax instead of Drupal.ajax.
   *
   * @param {string} [id]
   *   The ID of the link.
   * @param {HTMLElement} link
   *   The link element.
   */
  Drupal.ajax_assets_plus.ajaxView.prototype.attachPagerLinkAjax = function (id, link) {
    var $link = $(link);
    var viewData = {};
    var href = $link.attr('href');
    // Construct an object using the settings defaults and then overriding
    // with data specific to the link.
    $.extend(
      viewData,
      this.settings,
      Drupal.Views.parseQueryString(href),
      // Extract argument data from the URL.
      Drupal.Views.parseViewArgs(href, this.settings.view_base_path)
    );

    var self_settings = $.extend({}, this.element_settings, {
      submit: viewData,
      base: false,
      element: link
    });
    this.pagerAjax = Drupal.ajaxAssetsPlusAjax(self_settings);
  };


})(jQuery, Drupal, drupalSettings);
