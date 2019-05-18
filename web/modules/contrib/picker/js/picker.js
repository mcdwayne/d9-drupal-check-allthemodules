(function ($) {
  'use strict';
  Drupal.behaviors.picker = {
    attach: function (context, settings) {
       $('.select-picker-enable').picker({search : true});
    }
  };
})(jQuery);
