/**
 * @file
 * Drupal AJAX adapter for HTML 5 History API methods.
 */

(function (window, Drupal) {
  'use strict';

  /**
   * Command to go back one frame in the history stack.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   The response from the Ajax request.
   */
  Drupal.AjaxCommands.prototype.history_back = function(ajax, response) {
    Drupal.html5history.back();
  }

  /**
   * Command to go forward one frame in the history stack.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   The response from the Ajax request.
   */
  Drupal.AjaxCommands.prototype.history_forward = function(ajax, response) {
    Drupal.html5history.forward();
  }
  /**
   * Command to go n frames forward or backward in the history stack.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   The response from the Ajax request.
   */
  Drupal.AjaxCommands.prototype.history_go = function(ajax, response) {
    Drupal.html5history.go(response.cursor);
  }

  /**
   * Command to push a frame onto the browser history stack.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   The response from the Ajax request.
   */
  Drupal.AjaxCommands.prototype.history_push_state = function(ajax, response) {
    if (response.url != (window.location.pathname + window.location.search)) {
      Drupal.html5history.pushState(response.state, response.title, response.url);
    }
  }

  /**
   * Command to replace the current frame on the browser history stack.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   The response from the Ajax request.
   */
  Drupal.AjaxCommands.prototype.history_replace_state = function(ajax, response) {
    if (response.url != (window.location.pathname + window.location.search)) {
      Drupal.html5history.replaceState(response.state, response.title, response.url);
    }
  }

})(window, Drupal);
