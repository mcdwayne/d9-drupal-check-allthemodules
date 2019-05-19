(function ($, Drupal) {
  var ajaxHelperClasses = {
    element: 'ajax-loading',
    form: 'form--ajax-loading'
  };

  /**
   * Add ajax helper classes.
   */
  function addAjaxHelperClasses(element) {
    var $element = $(element);
    $element.addClass(ajaxHelperClasses.element);
    $element.closest('form').addClass(ajaxHelperClasses.form);
  }

  /**
   * Remove ajax helper classes.
   */
  function removeHelperClasses(element) {
    var $element = $(element);
    $element.removeClass(ajaxHelperClasses.element);
    $element.closest('form').removeClass(ajaxHelperClasses.form);
  }

  /**
   * Override AJAX before serialize callback.
   */
  var originalAjaxBeforeSerialize = Drupal.Ajax.prototype.beforeSerialize;
  Drupal.Ajax.prototype.beforeSerialize = function (element, options) {
    // Change request url to url from element data-ajax-url attribute
    var elementAjaxUrl = $(element).data('ajax-url');
    if (elementAjaxUrl) {
      var wrapperFormatIndex = options.url.indexOf(Drupal.ajax.WRAPPER_FORMAT);
      options.url = (wrapperFormatIndex > 0)
        ? elementAjaxUrl + options.url.substring(wrapperFormatIndex - 1)
        : elementAjaxUrl;
    }

    originalAjaxBeforeSerialize.apply(this, arguments);
  };

  /**
   * Override AJAX before send callback.
   */
  var originalAjaxBeforeSend = Drupal.Ajax.prototype.beforeSend;
  Drupal.Ajax.prototype.beforeSend = function (xmlhttprequest, options) {
    addAjaxHelperClasses(this.element);
    originalAjaxBeforeSend.apply(this, arguments);
  };

  /**
   * Override AJAX success callback.
   */
  var originalAjaxSuccess = Drupal.Ajax.prototype.success;
  Drupal.Ajax.prototype.success = function (response, status) {
    removeHelperClasses(this.element);
    originalAjaxSuccess.apply(this, arguments);
  };

  /**
   * Override AJAX error callback.
   */
  var originalAjaxError = Drupal.Ajax.prototype.error;
  Drupal.Ajax.prototype.error = function (response, status) {
    removeHelperClasses(this.element);
    originalAjaxError.apply(this, arguments);
  };

  /**
   * Override "Insert" command.
   */
  var originalInsertCommand = Drupal.AjaxCommands.prototype.insert;
  Drupal.AjaxCommands.prototype.insert = function (ajax, response, status) {
    // Trim data
    if (response.data) {
      response.data = $.trim(response.data);
    }
    originalInsertCommand.apply(this, arguments);
  };

  /**
   * Send Ajax request and execute response commands using Drupal Ajax API.
   */
  Drupal.ajaxExecute = function (url, options) {
    options = options || {};
    if (url) {
      options.url = url;
    }
    var ajax = new Drupal.Ajax(false, false, options);
    return ajax.execute();
  }
})(jQuery, Drupal);
