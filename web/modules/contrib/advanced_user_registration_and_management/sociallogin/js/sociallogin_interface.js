var LoginRadius_Social_JS;

if (window.LoginRadius_Social_JS != true) {
    LoginRadius_Social_JS = true;
    var options = {};
    options.login = true;
    LoginRadius_SocialLogin.util.ready(function () {
        var loc = drupalSettings.lrsociallogin.callback;
        $ui = LoginRadius_SocialLogin.lr_login_settings;
        $ui.interfacesize = drupalSettings.lrsociallogin.interfacesize;
        $ui.lrinterfacebackground = drupalSettings.lrsociallogin.lrinterfacebackground;
        $ui.noofcolumns = drupalSettings.lrsociallogin.noofcolumns;
        $ui.apikey = drupalSettings.lrsociallogin.apikey;
        $ui.is_access_token = true;
        if (detectmob()) {
            $ui.isParentWindowLogin = true;
            loc = drupalSettings.lrsociallogin.location;
        }
        $ui.callback = loc;
        $ui.lrinterfacecontainer = "interfacecontainerdiv";
        LoginRadius_SocialLogin.init(options);
    });
}
if (window.LoginRadiusSDK) {
    LoginRadiusSDK.setLoginCallback(function () {
        var token = LoginRadiusSDK.getToken();
        var form = document.createElement('form');
        form.action = drupalSettings.lrsociallogin.location;
        form.method = 'POST';

        var hiddenToken = document.createElement('input');
        hiddenToken.type = 'hidden';
        hiddenToken.value = token;
        hiddenToken.name = 'token';
        form.appendChild(hiddenToken);

        document.body.appendChild(form);
        form.submit();
    });
}
function detectmob() {
    if (navigator.userAgent.match(/Android/i) || navigator.userAgent
        .match(/webOS/i) || navigator.userAgent.match(/iPhone/i) ||
        navigator.userAgent.match(/iPad/i) || navigator.userAgent
        .match(/iPod/i) || navigator.userAgent.match(
        /BlackBerry/i) || navigator.userAgent.match(
        /Windows Phone/i)) {
        return true;
    } else {
        return false;
    }
}