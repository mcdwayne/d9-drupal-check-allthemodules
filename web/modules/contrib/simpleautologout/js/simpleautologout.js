/**
 * @file
 * JavaScript for simple autologout.
 */
jQuery(document).ready(function() {
  var time;
  var time_diff;
  var simpleautologout_session_time;
  var logout;

  var uid = drupalSettings.user.uid;
  var timeout = drupalSettings.simpleautologout.timeout;
  var timeout_refresh_rate = drupalSettings.simpleautologout.timeout_refresh_rate;
  var redirect_url = drupalSettings.simpleautologout.redirect_url;
  var simpleautologout_session_time = drupalSettings.simpleautologout.simpleautologout_session_time;

  localStorage.setItem('simpleautologout_session_time', simpleautologout_session_time);

  var checkTimeout = function(simpleautologout_session_time, timeout, redirect_url) {
    var sendData = { 
      'uid' : uid,
    };
    requestUrl = drupalSettings.path.baseUrl + 'get-last-active-time';

    jQuery.ajax({
      url: requestUrl,
      dataType: 'json',
      contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      data: sendData,
      type: 'POST',
      success: function(response) {
        session_active = response.session_active;
        time = Math.floor(Date.now());
        time_diff = time - simpleautologout_session_time;

        if(time_diff > timeout && session_active == 'true') {
          logOut(redirect_url);
        }
        else if(session_active == 'false') {
          window.location.href = redirect_url;
        }
      },
      error: function(response) {
        window.location.href = redirect_url;
      }
    });
  };

  var logOut = function(redirect_url) {
    requestUrl = drupalSettings.path.baseUrl + 'simple-autologout';
    jQuery.ajax({
      url: requestUrl,
      dataType: 'json',
      contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      type: 'GET',
      success: function(response) {
        logout = response.logout;
        if(logout == 'true') {
          window.location.href = redirect_url;
        }
      },
      error: function(response) {
        console.log('Error:- Cannot logout user.');
      }
    });
  };

  // if user is loged check for inacitvity time
  window.setInterval(function() {
    if(uid > 0) {
      simpleautologout_session_time = localStorage.getItem('simpleautologout_session_time');
      checkTimeout(simpleautologout_session_time, timeout, redirect_url);
    } 
  }, timeout_refresh_rate);

  // Bind mousemove events to prevent AutoLogout event.
  jQuery('body').bind('mousemove', function (event) {
    localStorage.setItem('simpleautologout_session_time', Math.floor(Date.now()));
  });

  // Bind keyup events to prevent AutoLogout event. So while typing aything in from fields user cannot be loged out.
  jQuery('body').bind('keyup', function (event) {
    localStorage.setItem('simpleautologout_session_time', Math.floor(Date.now()));
  });

  // Bind scroll events to preventAutoLogout event.
  jQuery(window).bind('scroll', function(event){
    localStorage.setItem('simpleautologout_session_time', Math.floor(Date.now()));
  });

});
