/**
 * @file
 * Adds JS functionality private message flood limit type elements.
 */

/*global jQuery, Drupal*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal) {

  "use strict";

  function limitTypeWatcher(context) {
    $(context).find(".pm_limit_type_select").once("limit-type-watcher").each(function () {
      var type = $(this).val();
      var types = {};
      types.post = Drupal.t("posts");
      types.thread = Drupal.t("threads");
      $(this).parents(".draggable:first").find(".pm_limit_type_wrapper:first").text(types[type]);

      $(this).change(function () {
        type = $(this).val();
        $(this).parents(".draggable:first").find(".pm_limit_type_wrapper:first").text(types[type]);
      });
    });
  }

  Drupal.behaviors.pmLimitTypeElement = {
    attach: function (context) {
      limitTypeWatcher(context);
    }
  };

}(jQuery, Drupal));
