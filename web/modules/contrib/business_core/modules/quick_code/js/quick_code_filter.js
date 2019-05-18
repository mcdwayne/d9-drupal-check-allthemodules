(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.quickCodeFilter = {
    attach: function (context) {
      var $context = $(context);

      $context.find('.quick-code-filter').once('quick-code-filter-processed').each(function () {
        var view = $(this).parents('.view');
        var views_element_container = $(view).parent();
        $(views_element_container).addClass('quick-code-filter-wrapper');
        $(views_element_container).append($(this));
        $(views_element_container).append($(view));

        var action_links = $('ul.action-links');
        $(view).prepend($(action_links));
      });

      $context.find('.folder-icon').once('processed').each(function () {
        var $li = $(this).parent();
        $(this).on('click', function() {
          $li.toggleClass('expanded');
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);

