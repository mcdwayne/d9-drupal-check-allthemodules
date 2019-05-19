/**
 * @file
 * jQuery code.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.vexMessage = {
    attach: function () {
      Drupal.vexMessage.init(drupalSettings);
    }
  };

  /**
   * Definition of Drupal.vexMessage object.
   */
  Drupal.vexMessage = Drupal.vexMessage || {};

  /**
   * Initialization of vexMessage popup window.
   *
   * @param {object} settings
   *   Settings for the vexMessage functionality.
   */
  Drupal.vexMessage.init = function (settings) {

    /** @lends drupalSettings.vexMessage **/
    var vexMessage = drupalSettings.vexMessage;

    var state = this.getState();

    if (!state) {
      vex.defaultOptions.className = vexMessage.theme;
      vex.defaultOptions.unsafeMessage = vexMessage.content;
      vex.defaultOptions.showCloseButton = vexMessage.close;

      if (vexMessage.buttons === false) {
        vex.defaultOptions.buttons = [];
      }

      vex.dialog.open(
        vex.defaultOptions
      );

      // Bind an outside click for closing the modalContent
      $(document).on('click', ':not(.vex-content)', function (event) {
        if ($(event.target).is('div.vex.vex-theme-default.vex-closing') || $(event.target).is('.vex-overlay')) {
          Drupal.vexMessage.modalContentClose();
        }
      });

      // Bind a click for closing the modalContent
      $('.vex-close').on('click', this.modalContentClose);

      // Bind a escape for closing the modalContent
      $(document).on('keydown', this.modalEventEscapeCloseHandler);
    }
  };

  Drupal.vexMessage.getState = function () {

    if (drupalSettings.vexMessage.cookie === false) {
      this.deleteCookie();
    }
    return $.cookie('_vexMessage');
  };

  Drupal.vexMessage.setState = function () {
    if (drupalSettings.vexMessage.cookie !== false) {
      var date = new Date();
      var minutes = 1;
      date.setTime(date.getTime() + (minutes * 60 * 1000));

      $.cookie('_vexMessage', 'TRUE', {
        path: drupalSettings.path.currentPath,
        expires: 1
      });
    }
  };

  Drupal.vexMessage.modalEventEscapeCloseHandler = function (event) {
    if (event.keyCode === 27) {
      Drupal.vexMessage.setState();
    }
  };

  Drupal.vexMessage.modalContentClose = function () {
    Drupal.vexMessage.setState();
    vex.closeAll();
  };

  Drupal.vexMessage.modalOutsideClose = function (event) {
    // console.log(event.target);
    // Drupal.vexMessage.setState();
  };

  Drupal.vexMessage.deleteCookie = function () {
    $.cookie('_vexMessage', null, {
      expires: -1
    });
  };

  $(document).on('ready', function () {
    setTimeout(function () {
      $(window).trigger('resize');
    }, 10);
  });


})(jQuery, Drupal, drupalSettings);
