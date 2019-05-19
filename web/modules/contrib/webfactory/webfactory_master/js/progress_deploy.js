/**
 * @file
 * Progress Deploy bar.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Theme function for the progress site deploy status.
   *
   * @param {string} id
   *   ID of the progress element.
   *
   * @return {string}
   *   The HTML for the progress bar.
   */
  Drupal.theme.progressDeploy = function (id) {
    return '<div id="' + id + '"><div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div></div>';
  };

  /**
   * A progressbar object.
   *
   * Initialized with the given id. Must be inserted into the DOM afterwards
   * through progressBar.element.
   *
   * Method is the function which will perform the HTTP request to get the
   * progress bar state. Either "GET" or "POST".
   *
   * @constructor
   *
   * @param {string} id
   *   ID of the progress element.
   *
   * @example
   * pb = new Drupal.ProgressBar('myProgressBar');
   * some_element.appendChild(pb.element);
   */
  Drupal.ProgressDeploy = function (id) {
    this.id = id;
    this.method = 'GET';

    this.element = $(Drupal.theme('progressDeploy', id));
  };

  $.extend(Drupal.ProgressDeploy.prototype, /** @lends Drupal.ProgressDeploy# */{

    /**
     * Set the percentage and status message for the progressbar.
     *
     * @param {string} host
     *   Hostname of the satellite.
     */
    setAsDeploy: function (host) {
      $(this.element).html('<a href="' + host + '" target="_blank">' + host + '</a>');
    },

    /**
     * Start monitoring progress via Ajax.
     *
     * @param {string} uri
     *   URI of the satellite.
     * @param {number} delay
     *   The delay between ping to the satellite.
     */
    startMonitoring: function (uri, delay) {
      this.delay = delay;
      this.uri = uri;
      this.sendPing();
    },

    /**
     * Stop monitoring progress via Ajax.
     */
    stopMonitoring: function () {
      clearTimeout(this.timer);
      // This allows monitoring to be stopped from within the callback.
      this.uri = null;
    },

    /**
     * Request progress data from server.
     */
    sendPing: function () {
      if (this.timer) {
        clearTimeout(this.timer);
      }
      if (this.uri) {
        var pb = this;
        // When doing a post request, you need non-null data. Otherwise a
        // HTTP 411 or HTTP 406 (with Apache mod_security) error may result.
        var uri = this.uri;
        if (uri.indexOf('?') === -1) {
          uri += '?';
        }
        else {
          uri += '&';
        }
        uri += '_format=json';
        $.ajax({
          type: this.method,
          url: uri,
          data: '',
          dataType: 'json',
          success: function (progress) {
            pb.removeMessages();
            if (progress.status === 1) {
              // Update display.
              pb.setAsDeploy(progress.host);
              pb.stopMonitoring();
            }
            else {
              // There was a client error when checking on the satellite.
              if (progress.error_message !== 'undefined') {
                pb.displayWarning('<pre>' + Drupal.t('Another check will be done in @delay seconds.', {'@delay': pb.delay / 1000}) + '</pre>');
                pb.displayError('<pre>' + progress.error_message + '</pre>');
              }

              // Schedule next timer.
              pb.timer = setTimeout(function () {
                pb.sendPing();
              }, pb.delay);
            }
          },
          error: function (xmlhttp) {
            var e = new Drupal.AjaxError(xmlhttp, pb.uri);
            pb.displayError('<pre>' + e.message + '</pre>');
          }
        });
      }
    },

    /**
     * Display errors on the page.
     *
     * @param {string} string
     *   The error message.
     */
    displayError: function (string) {
      var error = $('<div class="messages messages--error"></div>').html(string);
      $(this.element).after(error);
    },

    /**
     * Display warnings on the page.
     *
     * @param {string} string
     *   The error message.
     */
    displayWarning: function (string) {
      var warning = $('<div class="messages messages--warning"></div>').html(string);
      $(this.element).after(warning);
    },

    /**
     * Remove messages for this satellite on the page.
     */
    removeMessages: function () {
      var pb = this;
      $('.sat-status-deploy').each(function () {
        var satId = $(this).attr('data-satId');
        if (satId === pb.id) {
          $(this).find('.messages').each(function () {
            $(this).remove();
          });
        }
      });
    }
  });

})(jQuery, Drupal);
