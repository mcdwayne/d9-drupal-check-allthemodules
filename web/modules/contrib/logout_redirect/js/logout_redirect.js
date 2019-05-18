/**
 * @file
 * Implements Custom JS for Logout Redirect Functionality.
 */

/**
 * @file
 * JavaScript to allow back button submit wizard page.
 */

(function ($) {

  'use strict';
  var status = false;
  var logout_redirect_url = drupalSettings.logout_redirect_url;
  if(logout_redirect_url === '' || logout_redirect_url === undefined){
    logout_redirect_url = "/user/login";
  }
  var login_status = localStorage.getItem('logout_userStatus');
  if ($('body').hasClass('user-logged-in')) {
    localStorage.setItem('logout_userStatus', true);
  } else {
    localStorage.setItem('logout_userStatus', false);
  }
  if (login_status && (!$('body').hasClass('user-logged-in'))) {
    var status = true;
  }

  if (window.history && window.history.pushState) {
    window.history.pushState('', null, '');
    window.onpopstate = function (event) {
      if (status) {
        window.location.href =  logout_redirect_url;
      } else {
        if ((window.location.href.indexOf(logout_redirect_url) > -1)) {
          window.history.pushState('', null, '');
          // do nothing. if condition will use in future to change logic
        } else {
          history.back(1);
        }
      }
    };
  }

})(jQuery);
