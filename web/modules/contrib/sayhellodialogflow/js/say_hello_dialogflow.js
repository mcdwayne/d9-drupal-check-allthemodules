
"use strict";

(function ($, Drupal) {

    var sayHelloDialogflowSettings = {
        initialized: false,
        domain: '',
        baseurl: '',
        token: '',
        debug: '',
        defaultintenttext: ''
    };

    Drupal.behaviors.sayHelloDialogflow = {
        attach: function attach(context, settings) {
            var init = function init() {

                if (false === sayHelloDialogflowSettings.initialized) {
                    loadSetings();
                    sayHelloDialogflowSettings.initialized = true;
                }
            };

            var loadSetings = function loadSetings() {
                sayHelloDialogflowSettings.token = settings.say_hello_dialogflow.dialogflow.dialogflow_token;
                sayHelloDialogflowSettings.baseurl = settings.say_hello_dialogflow.dialogflow.dialogflow_baseurl;
                sayHelloDialogflowSettings.domain = settings.say_hello_dialogflow.dialogflow.dialogflow_domain;
                sayHelloDialogflowSettings.menu = settings.say_hello_dialogflow.dialogflow.dialogflow_menu;
                sayHelloDialogflowSettings.debug = settings.say_hello_dialogflow.dialogflow.dialogflow_debug;
                sayHelloDialogflowSettings.defaultintenttext = settings.say_hello_dialogflow.dialogflow.dialogflow_defaultintenttext;
            };

            init();
        },
        getMenu: function getMenu() {
            return sayHelloDialogflowSettings.menu;
        },
        getToken: function getToken() {
            return sayHelloDialogflowSettings.token;
        },
        getDomain: function getDomain() {
            return sayHelloDialogflowSettings.domain;
        },
        getBaseurl: function getBaseurl() {
            return sayHelloDialogflowSettings.baseurl;
        },
        getDebug: function getDebug() {
            return sayHelloDialogflowSettings.debug;
        },
        getDefaultIntentText: function getDefaultIntentText() {
            return sayHelloDialogflowSettings.defaultintenttext;
        }
    };
})(jQuery, Drupal);

//# sourceMappingURL=say_hello_dialogflow.js.map