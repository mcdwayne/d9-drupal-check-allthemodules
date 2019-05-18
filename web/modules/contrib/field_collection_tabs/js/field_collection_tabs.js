/**
 * @file
 * Javascript for the media bundle form.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.field_collection_tabs = {
    attach: function(context) {
     $('.field-collection-tabs', context).once().tabs();
    }
  }

})(jQuery, Drupal);
