/**
 * @file
 * JavaScript code for Filefield sources JSON API widget.
 */

(function ($, Drupal) {
    "use strict";

  /**
   * Auto trigger open modal on switching to the remote jsonapi.
   * @type {Object}
   */
    Drupal.behaviors.filefieldSourcesJsonApiWidget = {
        attach: function (context, settings) {
            $(".filefield-sources-list a.filefield-source-remote_jsonapi").on("click", function (e) {
                $(this).closest(".form-managed-file").find(".filefield-source-remote_jsonapi a").click();
            });
        }
    };

}(jQuery, Drupal));
