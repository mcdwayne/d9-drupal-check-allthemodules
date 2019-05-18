(function($, Drupal, settings){
  var druminateCore = Drupal.druminateCore = function(options) {
    this.options = options;
  };

  if (!settings.druminate.settings.api_key ||
      !settings.druminate.settings.secure ||
      !settings.druminate.settings.nonsecure) {
    console.log('Please add Druminate Settings.');
  }

  // Initialize luminateExtend.
  luminateExtend({
    apiKey: settings.druminate.settings.api_key,
    path: {
      secure: settings.druminate.settings.secure + '/',
      nonsecure: settings.druminate.settings.nonsecure + '/'
    }
  });

  druminateCore.prototype.request = function(options) {
    // Merge default options with request options with callback options.
    var self = this;
    var params = $.extend({
      callback: {
        success: self.success,
        error: self.error
      }
    }, options, this.options);
    luminateExtend.api.request(params);
  };

  druminateCore.prototype.success = function(data) {};
  druminateCore.prototype.error = function(data) {};

})(jQuery, Drupal, drupalSettings);