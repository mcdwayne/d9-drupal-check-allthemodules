/**
 * @file
 * JavaScript library for the Statistics API (sapi) module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @namespace
   */
  Drupal.sapi = {

    /**
     * Sends an action to Drupal for tracking.
     *
     * @param {string} type action type plugin create
     * @param {Object} action keyed map of values to send to the plugin instance $configuration
     * @param {Object} options used for the AJAX call
     *
     * @note To implement callback handlers, add them to the options before running this function
     */
    send: function(type, action, options) {
      options = $.extend({
        'completeCallback': function(){},
        'successCallback': function(){},
        'errorCallback': function(){}
      }, typeof options === 'object' ? options : {});

      $.ajax({
        url: Drupal.url('sapi/js/action/'+type),
        type: 'POST',
        data: {
            action: action
        },
        dataType: 'json',
        timeout: 2000,
        complete: options.completeCallback,
        success: options.successCallback,
        error: options.errorCallback
      });
    }

  };

})(jQuery, Drupal);
