(function applyExpandableFormatter($) {
  'use strict';

  /**
   * Logic for expandable/collapsing fields that configured with a toggle.
   */
  Drupal.behaviors.expandableFormatter = {
    attach: function attachExpansionHandling(context) {
      var $formatters = $(context).find('.expandable-formatter');
      $formatters.each(function initialize() {
        var $formatter = $(this);
        var $ellipsis = $formatter.find('.expandable-formatter--ellipsis');
        var $content = $formatter.find('.expandable-formatter--content');
        var $trigger = $formatter.find('.expandable-formatter--trigger');
        var data = $formatter.data();

        // Only make expandable if it's actually larger than the collapsed
        // height.
        var expandedHeight = $content.outerHeight(false);
        if (expandedHeight > data.collapsedHeight) {
          $ellipsis.show();
          $trigger.show();
          $content.addClass('js-collapsed');
          $content.height(data.collapsedHeight);
          $trigger.bind('click', function toggleExpansion() {
            var collapsed = $content.hasClass('js-collapsed');
            if (collapsed) {
              if (data.effect === 'slide') {
                $content.animate({
                  height: expandedHeight
                }, data.jsDuration, function () {
                  $content.removeClass('js-collapsed');
                });
              }
              else {
                $content.show();
              }
              $ellipsis.hide();
              $trigger.find('a').text(data.collapsedLabel);
            }
            else {
              if (data.effect === 'slide') {
                $content.animate({
                  height: data.collapsedHeight
                }, data.jsDuration, function () {
                  $content.addClass('js-collapsed');
                });
              }
              else {
                $content.hide();
              }
              $ellipsis.show();
              $trigger.find('a').text(data.expandedLabel);
            }
          });
        }
      });
    }
  };
})(jQuery);
