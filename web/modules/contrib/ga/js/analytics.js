/**
 * @file
 * Initialize analytics on the page.
 */
/* global ga*/

(function (drupalSettings) {
  'use strict';

  if (!drupalSettings.ga) {
    return;
  }

  /*eslint-disable */
  window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
  /*eslint-enable */

  for (var i = 0; i < drupalSettings.ga.commands.length; i++) {
    ga.apply(this, drupalSettings.ga.commands[i]);
  }

})(drupalSettings);
