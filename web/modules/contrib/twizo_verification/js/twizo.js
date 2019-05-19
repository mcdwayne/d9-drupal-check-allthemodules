(function ($) {
    $.fn.validateLogin = function(sessionToken, name, pass, logoUrl){
        const handler = TwizoWidget.configure({
            sessionToken: sessionToken,
            askTrusted: true,
            logoUrl: logoUrl,
            trustedDays: 30
        });

        handler.open(function(sessionToken, isError, errorCode, returnData){
            if(isError){
                if(!alert(isError)){location.reload();}
            } else {
                $.ajax({
                    type: 'POST',
                    url: 'twizo/validatelogin',
                    dataType: 'json',
                    data: {
                        sessionToken: sessionToken,
                        name: name,
                        pass: pass,
                        isTrusted: returnData.isTrusted
                    },
                    success: function($response){
                        if($response != null) {
                            if (!alert($response)) {
                                window.location.reload();
                            }
                        } else{
                            window.location.reload();
                        }
                    }
                })
            }
        })
    };

    $.fn.openWidget = function(sessionToken, number, logoUrl){
        const handler = TwizoWidget.configure({
            sessionToken: sessionToken,
            logoUrl: logoUrl
        });

        handler.open(function(sessionToken, isError, errorCode){
            if(isError){
                if(!alert(isError)){location.reload();}
            } else {
                $.ajax({
                    type: 'POST',
                    url: 'twizo/accountsave',
                    dataType: 'json',
                    data: {
                        sessionToken: sessionToken,
                        number: number
                    },
                    success: function($response){
                        if($response != null) {
                            if (!alert($response)) {
                                window.location.reload();
                            }
                        } else{
                            window.location.reload();
                        }
                    }
                })
            }
        });
    };

    $.fn.testAlert = function (data) {
        alert(data);
    };

    $.fn.showCodes = function($codes){
        if(!alert('Please save these backupcodes carefully: ' + $codes)){ window.location.reload(); }
    };

    $.fn.reload = function () {
        location.reload();
    };

})(jQuery, Drupal, window);
