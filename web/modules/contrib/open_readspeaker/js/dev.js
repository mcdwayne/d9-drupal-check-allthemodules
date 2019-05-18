(function ($) {
  'use strict';
  Drupal.behaviors.open_readspeaker_dev_mode = {
    attach: function (context, settings) {
      window.rsConf = {general: {usePost: true}};
    }
  };
})(jQuery);
