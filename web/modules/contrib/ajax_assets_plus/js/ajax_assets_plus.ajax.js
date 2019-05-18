/**
 * @file
 * Provides improved ajax logic, which supports cacheable requests.
 *
 * This file extends and partially overrides the code of core/misc/ajax.js.
 */

(function ($, window, Drupal, drupalSettings) {

  /**
   * Provides Ajax page updating via jQuery $.ajax.
   *
   * Copied from Drupal.ajax. Difference: used Drupal.AjaxAssetsPlusAjax instead
   * of Drupal.Ajax.
   *
   * @param {object} settings
   *   The settings object passed to {@link Drupal.Ajax} constructor.
   * @param {string} [settings.base]
   *   Base is passed to {@link Drupal.Ajax} constructor as the 'base'
   *   parameter.
   * @param {HTMLElement} [settings.element]
   *   Element parameter of {@link Drupal.Ajax} constructor, element on which
   *   event listeners will be bound.
   *
   * @return {Drupal.Ajax}
   *   The created Ajax object.
   *
   * @see Drupal.ajax
   * @see Drupal.AjaxCommands
   */
  Drupal.ajaxAssetsPlusAjax = function (settings) {
    if (arguments.length !== 1) {
      throw new Error('Drupal.ajaxAssetsPlusAjax() function must be called with one configuration object only');
    }
    // Map those config keys to variables for the old Drupal.ajax function.
    var base = settings.base || false;
    var element = settings.element || false;
    delete settings.base;
    delete settings.element;

    // By default do not display progress for ajax calls without an element.
    if (!settings.progress && !element) {
      settings.progress = false;
    }

    var ajax = new Drupal.AjaxAssetsPlusAjax(base, element, settings);
    ajax.instanceIndex = Drupal.ajax.instances.length;
    Drupal.ajax.instances.push(ajax);

    return ajax;
  };

  /**
   * {@inheritDoc}
   *
   * Extends Drupal.Ajax to support cacheable ajax requests.
   *
   * @see Drupal.Ajax
   */
  Drupal.AjaxAssetsPlusAjax = function (base, element, element_settings) {
    Drupal.Ajax.call(this, base, element, element_settings);

    this.AJAX_REQUEST_PARAMETER = '_ajax_assets_plus';
    // Allow AJAX requests to use GET to support render caching.
    this.options.type = 'GET';
    // Variable for tracking if any ajax command is altering the focus.
    this.focusChanged = false;

    /**
     * {@inheritDoc}
     *
     * Extends Drupal.Ajax.prototype.success to handle libraries load and
     * ajax commands execution.
     *
     * @see Drupal.Ajax.prototype.success
     */
    this.success = function (response, status) {
      // Remove the progress element.
      if (this.progress.element) {
        $(this.progress.element).remove();
      }
      if (this.progress.object) {
        this.progress.object.stopMonitoring();
      }
      $(this.element).prop('disabled', false);

      // Save element's ancestors tree so if the element is removed from the dom
      // we can try to refocus one of its parents. Using addBack reverse the
      // result array, meaning that index 0 is the highest parent in the hierarchy
      // in this situation it is usually a <form> element.
      var elementParents = $(this.element).parents('[data-drupal-selector]').addBack().toArray();

      // Track if any command is altering the focus so we can avoid changing the
      // focus set by the Ajax command.
      this.focusChanged = false;

      // Handle assets load.
      this.loadLibraries(response.libraries);
      this.executeCommands(response.commands);

      // If the focus hasn't be changed by the ajax commands, try to refocus the
      // triggering element or one of its parents if that element does not exist
      // anymore.
      if (!this.focusChanged && this.element && !$(this.element).data('disable-refocus')) {
        var target = false;

        for (var n = elementParents.length - 1; !target && n > 0; n--) {
          target = document.querySelector('[data-drupal-selector="' + elementParents[n].getAttribute('data-drupal-selector') + '"]');
        }

        if (target) {
          $(target).trigger('focus');
        }
      }

      // Reattach behaviors, if they were detached in beforeSerialize(). The
      // attachBehaviors() called on the new content from processing the response
      // commands is not sufficient, because behaviors from the entire form need
      // to be reattached.
      if (this.$form) {
        var settings = this.settings || drupalSettings;
        Drupal.attachBehaviors(this.$form.get(0), settings);
      }

      // Remove any response-specific settings so they don't get used on the next
      // call by mistake.
      this.settings = null;

      // Allows to react on ajax success.
      $.event.trigger('ajax_assets_plus_success', [this.element, response]);
    };

    /**
     * {@inheritDoc}
     *
     * Extends Drupal.Ajax.prototype.beforeSerialize to remove the unwanted ajax
     * page state.
     *
     * @see Drupal.Ajax.prototype.beforeSerialize
     */
    this.beforeSerialize = function (element, options) {
      // Allow detaching behaviors to update field values before collecting them.
      // This is only needed when field values are added to the request data, so
      // only when there is a form such that this.$form.ajaxSubmit() is used
      // instead of $.ajax(). When there is no form and $.ajax() is used,
      // beforeSerialize() isn't called, but don't rely on that: explicitly
      // check this.$form.
      if (this.$form) {
        var settings = this.settings || drupalSettings;
        Drupal.detachBehaviors(this.$form.get(0), settings, 'serialize');
      }

      // Inform Drupal that this is an AJAX request.
      options.data[Drupal.Ajax.AJAX_REQUEST_PARAMETER] = 1;
    };

    /**
     * Handles the libraries load.
     *
     * Every library is loaded by executing a corresponding ajax command.
     *
     * @param {Array} [libraries]
     *   Array of libraries, where keys is library name, value is an ajax command
     *   to load the library.
     */
    this.loadLibraries = function(libraries) {
      var ajaxLibraries = drupalSettings.ajaxAssetsPlus.libraries;

      if (libraries) {
        for (var libraryName in libraries) {
          // Check if library exists in ajaxPageState.
          if (ajaxLibraries.indexOf(libraryName) === -1) {
            var library = libraries[libraryName];
            for (var assetName in library) {
              var asset = library[assetName];
              this.commands[asset.command](this, asset, 200)
            }
            ajaxLibraries.push(libraryName)
          }
        }
        drupalSettings.ajaxAssetsPlus.libraries = ajaxLibraries;
      }
    };

    /**
     * Handles the ajax commands execution.
     *
     * @param {Array} [commands]
     *   Array of ajax commands.
     */
    this.executeCommands = function(commands) {
      if (!commands) {
        return;
      }
      for (var i = 0; i < commands.length; i++) {
        if (commands[i].command && this.commands[commands[i].command]) {
          this.commands[commands[i].command](this, commands[i], 200);

          if (commands[i].command === 'invoke' && commands[i].method === 'focus') {
            this.focusChanged = true;
          }
        }
      }
    };

  };

  // Extend Drupal.Ajax.
  Drupal.AjaxAssetsPlusAjax.prototype = Object.create(Drupal.Ajax.prototype);
  Drupal.AjaxAssetsPlusAjax.prototype.constructor = Drupal.AjaxAssetsPlusAjax;

})(jQuery, window, Drupal, drupalSettings);
