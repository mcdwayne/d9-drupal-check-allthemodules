/**
 * Add new command for reading a message.
 */
"use strict";

(($, Drupal) => {

    let sayHelloDialogflowSettings = {
        initialized: false,
        domain:  '',
        baseurl: '',
        token:   '',
        debug:   '',
        defaultintenttext: ''
    };

    Drupal.behaviors.sayHelloDialogflow = {
        attach: (context, settings) => {
            let init = () => {

                if (false === sayHelloDialogflowSettings.initialized) {
                    loadSetings();
                    sayHelloDialogflowSettings.initialized = true;
                }

            };

            let loadSetings = () => {
                sayHelloDialogflowSettings.token = settings.say_hello_dialogflow.dialogflow.dialogflow_token;
                sayHelloDialogflowSettings.baseurl = settings.say_hello_dialogflow.dialogflow.dialogflow_baseurl;
                sayHelloDialogflowSettings.domain = settings.say_hello_dialogflow.dialogflow.dialogflow_domain;
                sayHelloDialogflowSettings.menu = settings.say_hello_dialogflow.dialogflow.dialogflow_menu;
                sayHelloDialogflowSettings.debug = settings.say_hello_dialogflow.dialogflow.dialogflow_debug;
                sayHelloDialogflowSettings.defaultintenttext = settings.say_hello_dialogflow.dialogflow.dialogflow_defaultintenttext;
            };
 
            init();
        },
        getMenu: () => {
            return sayHelloDialogflowSettings.menu;
        },
        getToken: () => {
            return sayHelloDialogflowSettings.token;
        },
        getDomain: () => {
            return sayHelloDialogflowSettings.domain;
        },
        getBaseurl: () => {
            return sayHelloDialogflowSettings.baseurl;
        },
        getDebug: () => {
            return sayHelloDialogflowSettings.debug;
        },
        getDefaultIntentText: () => {
            return sayHelloDialogflowSettings.defaultintenttext;
        }
    }
})(jQuery, Drupal);