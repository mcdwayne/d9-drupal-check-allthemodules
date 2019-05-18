     if (window.LoginRadiusSSO) {
         var tokencookie = "lr-user--token";
         LoginRadiusSSO.setToken = function (token) {
        LoginRadiusSSO.Cookie.setItem(tokencookie, token, drupalSettings.sso.sso_path);
      }
      }   

jQuery(document).ready(function () {
    if (window.LoginRadiusSSO) {
        jQuery('a[href*="user/logout"]').click(function () {

            LoginRadiusSSO.init(drupalSettings.sso.site_name, drupalSettings.sso.sso_path, drupalSettings.sso.secure_cookie);
            LoginRadiusSSO.logout(drupalSettings.sso.logout)
        });

    }
});
if(drupalSettings.sso.redirect){
       jQuery(document).ready(function () {
      if (window.LoginRadiusSSO) {
      var apidomain = "https://api.loginradius.com";
            LoginRadiusSSO.init(drupalSettings.sso.site_name, drupalSettings.sso.sso_path, drupalSettings.sso.secure_cookie);
             var str = window.location.href;
            if(str.indexOf("user/login") == -1) {
              if (!LoginRadiusRaaS.loginradiushtml5passToken) {
                   LoginRadiusRaaS.loginradiushtml5passToken = function (token) {
                       if (token) {
                        window.location = drupalSettings.sso.login_url;
                       }
                     }
                   }
             }
            LoginRadiusSSO.login(drupalSettings.sso.callback);
        }    
        });
        jQuery("#lr-loading").hide();
    }
    if(drupalSettings.sso.isNotLoginThenLogout){
        jQuery(document).ready(function () {
      if (window.LoginRadiusSSO ) {
            LoginRadiusSSO.init(drupalSettings.sso.site_name, drupalSettings.sso.sso_path, drupalSettings.sso.secure_cookie);
            LoginRadiusSSO.isNotLoginThenLogout(drupalSettings.sso.logout_url);}
        });
    }