/**
 * @file
 * Drupal Advanced Insert View module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches or detaches behaviors, except the ones we do not want.
   *
   * @param {string} action
   *   Either 'attach' or 'detach'.
   * @param {HTMLDocument|HTMLElement} context
   *   The context argument for Drupal.attachBehaviors()/detachBehaviors().
   * @param {object} settings
   *   The settings argument for Drupal.attachBehaviors()/detachBehaviors().
   */
  Drupal.runInsertViewAdvBehaviors = function (action, context, settings) {
    // Do not run the excluded behaviors.
    var stashed = {};
    $.each(Drupal.insertViewAdv.excludedBehaviors, function (i, behavior) {
      stashed[behavior] = Drupal.behaviors[behavior];
      delete Drupal.behaviors[behavior];
    });
    // Run the remaining behaviors.
    (action === 'attach' ? Drupal.attachBehaviors : Drupal.detachBehaviors)(context, settings);
    // Put the stashed behaviors back in.
    $.extend(Drupal.behaviors, stashed);
  };

  /**
   * Ajax 'insert_view_adv_insert' command: insert the rendered view.
   *
   * The regular Drupal.ajax.commands.insert() command cannot target elements
   * within iframes. This is a skimmed down equivalent that works no matter
   * whether the CKEditor is in iframe or div area mode.
   *
   * @param {Drupal.Ajax} ajax
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   * @param {string} response.data
   *    The Ajax response's content.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.insert_view_adv_insert = function (ajax, response, status) {
    var $target = $(ajax.element);
    // No need to detach behaviors here, the widget is created fresh each time.
    $target.html(response.data);
    Drupal.runInsertViewAdvBehaviors('attach', $target.get(0), response.settings || ajax.settings);
  };

  /**
   * Stores settings specific to Advanced Insert view module.
   */
  Drupal.insertViewAdv = {

    /**
     * A list of behaviors which are to be excluded while attaching/detaching.
     *
     * - Drupal.behaviors.editor, to avoid editor inception.
     * - Drupal.behaviors.contextual, to keep contextual links hidden.
     */
    excludedBehaviors: ['editor', 'contextual']

  };


})(jQuery, Drupal);
