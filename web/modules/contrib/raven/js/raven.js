/**
 * @file
 * Configures @sentry/browser with the public DSN and extra options.
 */
(function (drupalSettings, Sentry) {

  'use strict';

  Sentry.init(drupalSettings.raven.options);

  Sentry.configureScope(function (scope) {
    scope.setUser({'id': drupalSettings.user.uid});
  });

})(window.drupalSettings, window.Sentry);
