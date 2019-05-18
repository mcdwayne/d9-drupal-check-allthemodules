(function ($, Drupal) {
  Drupal.behaviors.H5PAnalytics = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      if ( window.H5P && window.H5P.externalDispatcher )
      {
        var moduleSettings = settings.H5PAnalytics;
        H5P.externalDispatcher.on('xAPI', function (event) {
          $.post(moduleSettings.endpointUrl, {
            statement: JSON.stringify(event.data.statement)
          });
        });
      }
    }
  };
})(jQuery, Drupal);
