/**
 * @file
 * Index tag cloud.
 */

(function ($, Drupal) {
  Drupal.behaviors.index_tag_cloud = {
    attach: function (context, settings) {
      $('.index-tag-tabs').tabs({
        active: 0
      });
    }
  }
})(jQuery, Drupal);
