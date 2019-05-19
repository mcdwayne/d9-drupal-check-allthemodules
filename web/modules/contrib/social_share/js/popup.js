(function (Drupal) {
  'use strict';

  function windowPopup(url, width, height) {
    // Calculate the position of the popup so
    // it’s centered on the screen.
    var left = (screen.width / 2) - (width / 2),
      top = (screen.height / 2) - (height / 2);

    width = Math.min(width, screen.width);
    height = Math.min(height, screen.height);

    window.open(
      url,
      "",
      "menubar=no,toolbar=no,resizable=yes,scrollbars=yes,width=" + width + ",height=" + height + ",top=" + top + ",left=" + left
    );
  }

  Drupal.behaviors.socialSharePopup = {
    attach: function (context, settings) {
      var links = context.querySelectorAll(".js-social-share-popup");
      if (links) {
        [].forEach.call(links, function(anchor) {
          anchor.addEventListener("click", function(e) {
            e.preventDefault();

            var width = anchor.getAttribute('data-popup-width');
            var height = anchor.getAttribute('data-popup-height');

            windowPopup(this.href, width ? width: 500, height ? height : 300);
          });
        });
      }
    }
  };

})(Drupal);