(function ($) {

  'use strict';

  Drupal.ccc_permissions_link = Drupal.ccc_permissions_link || {};

  Drupal.behaviors.cccPermissionsLink = {
    attach: function (context, settings) {
      $('body', context).delegate('.ccc-permissions-link', 'click', function (e) {
        e.preventDefault();
        Drupal.ccc_permissions_link.popUp(this);
      });
    }
  }

  /**
   * Open CCC Permissions Link poup window.
   *
   * var link
   *   The link to open in the popup window.
   */
  Drupal.ccc_permissions_link.popUp = function(link) {
    var url = link.getAttribute('href');
    if (url) {window.open(url,'RightsLink','location=no,toolbar=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=770,height=550')}
  }

}(jQuery));
