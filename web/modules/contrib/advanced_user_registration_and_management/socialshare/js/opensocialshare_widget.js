var script = document.createElement('script');
script.type = 'text/javascript';
script.text = 'var islrsharing = true; var islrsocialcounter = true;';
document.body.appendChild(script);

    if (drupalSettings.advanceopensocialshare.horizontal != undefined && drupalSettings.advanceopensocialshare.horizontal) {
        var string = drupalSettings.advanceopensocialshare.providers;
        var providers = string.split(",");      

        var str = drupalSettings.advanceopensocialshare.widgets;
        var widgets = str.split(',');       
    
        var script = '{';       
        if (drupalSettings.advanceopensocialshare.emailMessage != '') {
            script = script + 'emailMessage:' + "'" + drupalSettings.advanceopensocialshare.emailMessage + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.emailSubject != '') {
            script = script + 'emailSubject:' + "'" + drupalSettings.advanceopensocialshare.emailSubject + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.isEmailContentReadOnly != '') {
            script = script + 'isEmailContentReadOnly:' + drupalSettings.advanceopensocialshare.isEmailContentReadOnly + ',';
        }       
        script = script + "isCounterWidgetTheme:" + drupalSettings.advanceopensocialshare.isCounterWidgetTheme + ',';
        script = script + "isHorizontalCounter:" + drupalSettings.advanceopensocialshare.isHorizontalCounter + ',';
        script = script + "isHorizontalLayout:" + drupalSettings.advanceopensocialshare.isHorizontalLayout + ',';

        if (drupalSettings.advanceopensocialshare.widgetIconSize != '') {
            script = script + 'widgetIconSize:' + "'" + drupalSettings.advanceopensocialshare.widgetIconSize + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.widgetStyle != '') {
            script = script + 'widgetStyle:' + "'" + drupalSettings.advanceopensocialshare.widgetStyle + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.theme != '') {
            script = script + 'theme:' + "'" + drupalSettings.advanceopensocialshare.theme + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.isShortenUrl != '') {
            script = script + 'isShortenUrl:' + drupalSettings.advanceopensocialshare.isShortenUrl + ',';
        }
        if (drupalSettings.advanceopensocialshare.facebookAppId != '') {
            script = script + 'facebookAppId:' + drupalSettings.advanceopensocialshare.facebookAppId + ',';
        }
        script = script + 'providers: {top:' + JSON.stringify(providers) + '},';
        script = script + 'widgets: {top:' + JSON.stringify(widgets) + '},';

        if (drupalSettings.advanceopensocialshare.isTotalShare != '') {
            script = script + 'isTotalShare:' + drupalSettings.advanceopensocialshare.isTotalShare + ',';
        }
        if (drupalSettings.advanceopensocialshare.isOpenSingleWindow != '') {
            script = script + 'isOpenSingleWindow:' + drupalSettings.advanceopensocialshare.isOpenSingleWindow + ',';
        }
        if (drupalSettings.advanceopensocialshare.twittermention != '') {
            script = script + 'twittermention:' + "'" + drupalSettings.advanceopensocialshare.twittermention + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.twitterhashtag != '') {
            script = script + 'twitterhashtag:' + "'" + drupalSettings.advanceopensocialshare.twitterhashtag + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.isMobileFriendly != '') {
            script = script + 'isMobileFriendly:' + drupalSettings.advanceopensocialshare.isMobileFriendly + ',';
        }
        if (drupalSettings.advanceopensocialshare.popupWindowSize != '') {
            script = script + 'popupWindowSize: ' + drupalSettings.advanceopensocialshare.popupWindowSize + ',';
        }      
        if (drupalSettings.advanceopensocialshare.customOption != '') {           
            script = script + drupalSettings.advanceopensocialshare.customOption;
        }          
        script = script + '}';    
        
        var shareWidget = new OpenSocialShare();
        shareWidget.init((eval("(" + script + ")")));
        shareWidget.injectInterface("." + drupalSettings.advanceopensocialshare.divwidget);
        shareWidget.setWidgetTheme("." + drupalSettings.advanceopensocialshare.divwidget);
    }

    if (drupalSettings.advanceopensocialshare.vertical != undefined && drupalSettings.advanceopensocialshare.vertical) {
        var string = drupalSettings.advanceopensocialshare.vericalProviders;
        var providers = string.split(",");

        var str = drupalSettings.advanceopensocialshare.verticalWidgets;
        var widgets = str.split(',');   

        var vscript = '{';
        if (drupalSettings.advanceopensocialshare.verticalEmailMessage != '') {
            vscript = vscript + 'emailMessage:' + "'" + drupalSettings.advanceopensocialshare.verticalEmailMessage + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalEmailSubject != '') {
            vscript = vscript + 'emailSubject:' + "'" + drupalSettings.advanceopensocialshare.verticalEmailSubject + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalIsEmailContentReadOnly != '') {
            vscript = vscript + 'isEmailContentReadOnly:' + drupalSettings.advanceopensocialshare.verticalIsEmailContentReadOnly + ',';
        }             
        vscript = vscript + "isCounterWidgetTheme:" + drupalSettings.advanceopensocialshare.verticalIsCounterWidgetTheme + ',';
        vscript = vscript + "isHorizontalCounter:" + drupalSettings.advanceopensocialshare.verticalIsHorizontalCounter + ',';
        vscript = vscript + "isHorizontalLayout:" + drupalSettings.advanceopensocialshare.verticalIsHorizontalLayout + ',';

        if (drupalSettings.advanceopensocialshare.verticalWidgetIconSize != '') {
            vscript = vscript + 'widgetIconSize:' + "'" + drupalSettings.advanceopensocialshare.verticalWidgetIconSize + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalWidgetStyle != '') {
            vscript = vscript + 'widgetStyle:' + "'" + drupalSettings.advanceopensocialshare.verticalWidgetStyle + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalTheme != '') {
            vscript = vscript + 'theme:' + "'" + drupalSettings.advanceopensocialshare.verticalTheme + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalIsShortenUrl != '') {
            vscript = vscript + 'isShortenUrl:' + drupalSettings.advanceopensocialshare.verticalIsShortenUrl + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalFacebookAppId != '') {
            vscript = vscript + 'facebookAppId:' + drupalSettings.advanceopensocialshare.verticalFacebookAppId + ',';
        }
        vscript = vscript + 'providers: {top:' + JSON.stringify(providers) + '},';
        vscript = vscript + 'widgets: {top:' + JSON.stringify(widgets) + '},';
        if (drupalSettings.advanceopensocialshare.verticalIsTotalShare != '') {
            vscript = vscript + 'isTotalShare:' + drupalSettings.advanceopensocialshare.verticalIsTotalShare + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalIsOpenSingleWindow != '') {
            vscript = vscript + 'isOpenSingleWindow:' + drupalSettings.advanceopensocialshare.verticalIsOpenSingleWindow + ',';
        }     
        if (drupalSettings.advanceopensocialshare.verticalTwitterMention != '') {
            vscript = vscript + 'twittermention:' + "'" + drupalSettings.advanceopensocialshare.verticalTwitterMention + "'" + ',';
        }
        if (drupalSettings.advanceopensocialshare.verticalTwitterHashTag != '') {
            vscript = vscript + 'twitterhashtag:' + "'" + drupalSettings.advanceopensocialshare.verticalTwitterHashTag + "'" + ',';
        }      
        if (drupalSettings.advanceopensocialshare.verticalPopupWindowSize != '') {
            vscript = vscript + 'popupWindowSize: ' + drupalSettings.advanceopensocialshare.verticalPopupWindowSize + ',';
        }    
         if (drupalSettings.advanceopensocialshare.verticalCustomOption != '') {           
            vscript = vscript + drupalSettings.advanceopensocialshare.verticalCustomOption;
        } 
        vscript = vscript + '}';

        var shareWidget = new OpenSocialShare();
        shareWidget.init((eval("(" + vscript + ")")));
        shareWidget.injectInterface("." + drupalSettings.advanceopensocialshare.verticalDivwidget);
        shareWidget.setWidgetTheme("." + drupalSettings.advanceopensocialshare.verticalDivwidget);
    }
