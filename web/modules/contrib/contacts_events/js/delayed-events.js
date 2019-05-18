/**
 * @file
 * Provides delayed events for AJAX handlers.
 */

(function($, Drupal) {

  /**
   * Attaches the delayed events to each Ajax form element.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Register the delayed events for all relevant elements in
   *   `drupalSettings.ajax`.
   */
  Drupal.behaviors.DelayedEvents = {
    attach: function attach(context, settings) {
      Object.keys(settings.ajax || {}).forEach(function (base) {
        var elementSettings = settings.ajax[base];
        if (elementSettings.event.substr(0, 8) === 'delayed.') {
          $(elementSettings.selector).once('delayed-events').each(function () {
            var timeout;
            var $this = $(this);
            $this.on(elementSettings.event.substr(8), function () {
              clearTimeout(timeout);
              timeout = setTimeout(function () {
                $this.trigger(elementSettings.event);
              }, 500);
            });
          });
        }
      });
    }
  };

  /**
   * Prepare the Ajax request before it is sent.
   *
   * Overrides core to add the no_disable option.
   *
   * @param {XMLHttpRequest} xmlhttprequest
   *   Native Ajax object.
   * @param {object} options
   *   jQuery.ajax options.
   */
  Drupal.Ajax.prototype.beforeSend = function (xmlhttprequest, options) {
    if (this.$form) {
      options.extraData = options.extraData || {};

      options.extraData.ajax_iframe_upload = '1';

      var v = $.fieldValue(this.element);
      if (v !== null) {
        options.extraData[this.element.name] = v;
      }
    }

    if (!this.elementSettings.no_disable) {
      $(this.element).prop('disabled', true);
    }

    if (!this.progress || !this.progress.type) {
      return;
    }

    var progressIndicatorMethod = 'setProgressIndicator' + this.progress.type.slice(0, 1).toUpperCase() + this.progress.type.slice(1).toLowerCase();
    if (progressIndicatorMethod in this && typeof this[progressIndicatorMethod] === 'function') {
      this[progressIndicatorMethod].call(this);
    }
  };

})(jQuery, Drupal);
