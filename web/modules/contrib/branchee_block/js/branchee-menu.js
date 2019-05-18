(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.branchMenuInit = {
    attach: function (context) {
      $('#menu--mobile', context).branchee({
        onAfterInit: function () {
          var url = window.location.pathname;
          this.setActivePaneByHref(url);
        }
      });
    }
  };
})(jQuery, Drupal);
