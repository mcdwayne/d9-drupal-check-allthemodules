(function ($) {
  "use strict";
  $(function () {
      var excluded = window.drupalSettings.pinterest_hover.excluded;
      // Disable pinning items specified by the selectors
      for (var i = 0; i < excluded.length; i++) {
        // pinit_main.js won't add hoverbuttons to anything but img
        // https://github.com/pinterest/widgets/blob/ae255301c66864a55bd8ba2c750d60e7ffefc842/pinit_main.js#L786
        $(excluded[i]).filter('img').attr('data-pin-nopin', '1');
      }
    }
  );
})(jQuery);
