jQuery(document).ready(function () {

    jQuery("#lr-loading").click(function () {
        jQuery('#lr-loading').hide();
    });

    if (window.location.href == window.location.origin + domainName + 'admin/people/create') {
        jQuery('.form-item-mail label').attr('class', 'js-form-required form-required');
        jQuery('#edit-mail').attr('required', 'required');
    } else {
        jQuery('#edit-mail').attr('disabled', 'disabled');
        jQuery('#edit-mail').attr('style', 'background:#ededed');
    }

    dropemailvalue = '';
    jQuery('.removeEmail').each(function () {
        jQuery(this).click(function () {
            jQuery('form[name="loginradius-removeemail"]').remove();
            var html = jQuery(this).parents('tr');
            dropemailvalue = jQuery(this).parents('tr').find('.form-email').val();
            showRemoveEmailPopup(html);
        });
    });   
    
    jQuery('#addEmail').attr('onClick', 'showAddEmailPopup()'); 
    
});

if (typeof LoginRadiusV2 === 'undefined') {
    var e = document.createElement('script');
    e.src = 'https://auth.lrcontent2.com/v2/js/LoginRadiusV2.js';
    e.type = 'text/javascript';
    document.getElementsByTagName("head")[0].appendChild(e);
}

var lrloadInterval = setInterval(function () {
    if (typeof LoginRadiusV2 != 'undefined') {
        clearInterval(lrloadInterval);
        LRObject = new LoginRadiusV2(commonOptions);
    }
}, 1);

function showRemoveEmailPopup(html) {
    jQuery('#removeemail-form').show();
    initializeRemoveEmailCiamForms(html);
}

function showAddEmailPopup() {
    jQuery('#addemail-form').show();
    initializeAddEmailCiamForms();
}

function lrCloseRemovePopup() {
    jQuery('form[name="loginradius-removeemail"]').remove();
    jQuery('#removeemail-form').hide();
}

function lrCloseAddEmailPopup() {
    jQuery('#addemail-form').hide();
}

function lrCheckValidJson() {
    jQuery('#add_custom_options').change(function () {
        var profile = jQuery('#add_custom_options').val();
        var response = '';
        try
        {
            response = jQuery.parseJSON(profile);
            if (response != true && response != false) {
                var validjson = JSON.stringify(response, null, '\t').replace(/</g, '&lt;');
                if (validjson != 'null') {
                    jQuery('#add_custom_options').val(validjson);
                    jQuery('#add_custom_options').css("border", "1px solid green");
                } else {
                    jQuery('#add_custom_options').css("border", "1px solid red");
                }
            } else {
                jQuery('#add_custom_options').css("border", "1px solid green");
            }
        } catch (e)
        {
            jQuery('#add_custom_options').css("border", "1px solid green");
        }
    });
}

function show_birthdate_date_block() {
    var maxYear = new Date().getFullYear();
    var minYear = maxYear - 100;
    if (jQuery('body').on) {
        jQuery('body').on('focus', '.loginradius-birthdate', function () {
            jQuery('.loginradius-birthdate').datepicker({
                dateFormat: 'mm-dd-yy',
                maxDate: new Date(),
                minDate: "-100y",
                changeMonth: true,
                changeYear: true,
                yearRange: (minYear + ":" + maxYear)
            });
        });
    } else {
        jQuery(".loginradius-birthdate").live("focus", function () {
            jQuery('.loginradius-birthdate').datepicker({
                dateFormat: 'mm-dd-yy',
                maxDate: new Date(),
                minDate: "-100y",
                changeMonth: true,
                changeYear: true,
                yearRange: (minYear + ":" + maxYear)
            });
        });
    }
}

function handleResponse(isSuccess, message, show, status) {
    status = status ? 'messages--' + status : "messages--status";
    if (isSuccess) {
        jQuery('form').each(function () {
            this.reset();
        });
    }
    if (message != null && message != "") {
        jQuery('#lr-loading').hide();
        jQuery('.messages').text(message);
        jQuery(".messages__wrapper").show();
        jQuery(".messages").show();

        jQuery(".messages").removeClass("messages--error messages--status showmsg");
        jQuery(".messages").addClass(status);
        jQuery(".messages").addClass(show);
        if (autoHideTime != "" && autoHideTime != "0") {
            setTimeout(fade_out, autoHideTime * 1000);
        }
    } else {
        jQuery(".messages__wrapper").hide();
        jQuery('.messages').hide();
        jQuery('.messages').text("");
    }
}
function fade_out() {
    jQuery(".messages").hide();
}

var setButtonInterval = setInterval(function () {
    if (typeof LRObject !== 'undefined')
    {
        clearInterval(setButtonInterval);
        LRObject.$hooks.register('startProcess', function () {
            jQuery('#lr-loading').show();
        });

        LRObject.$hooks.register('endProcess', function () {
            if (LRObject.options.twoFactorAuthentication === true || LRObject.options.optionalTwoFactorAuthentication === true)
            {
                jQuery('#authentication-container').show();
            }
            jQuery('#edit-account-phone').hide();
            if (LRObject.options.phoneLogin === true)
            {
                jQuery('#updatephone-container').show();
                jQuery('#edit-account-phone').show();
            }
            jQuery('#lr-loading').hide();
        });

        LRObject.$hooks.call('setButtonsName', {
            removeemail: "Remove"
        });

        LRObject.$hooks.register('socialLoginFormRender', function () {
            //on social login form render
            jQuery('#lr-loading').hide();
            jQuery('#social-registration-form').show();
            show_birthdate_date_block();
        });

        LRObject.$hooks.register('afterFormRender', function (name) {
            if (name == "socialRegistration") {
                jQuery('#login-container').find('form[name=loginradius-socialRegistration]').parent().addClass('socialRegistration');
            }            
            if (name == "updatePhone") {
                if(phoneId == ""){
                     jQuery('#updatephone-container').find('#loginradius-submit-update').attr('value', 'Add');
                }
            }
            if (name == "removeemail") {
                jQuery('#loginradius-removeemail-emailid').val(dropemailvalue);
            }
        });
    }
}, 1);
function getBackupCodes() {
    var lrGetBackupInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrGetBackupInterval);
            LRObject.api.getBackupCode(accessToken,
                    function (response) {
                        jQuery('#backupcode-table-body').empty();
                        for (var i = 0; i < response.BackUpCodes.length; i++) {
                            var html = '';
                            jQuery('#resettable').hide();
                            jQuery('#lr_ciam_reset_table').show();

                            html += '<div class="form-item code-list" id="backup-codes-' + i + '-field">';
                            html += '<span class="backupCode">' + response.BackUpCodes[i] + '</span>';
                            html += '</div>';

                            jQuery('#backupcode-table-body').append(html);
                        }
                        jQuery('.mybackupcopy').click(function () {
                            setClipboard(jQuery(this).parent('.form-item').find('span').text());
                        });
                    }, function (errors) {
                jQuery('#resettable').show();
            });
        }
    }, 1);
}

function resetBackupCodes() {
    var lrResetBackupInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrResetBackupInterval);
            LRObject.api.resetBackupCode(accessToken,
                    function (response) {
                        jQuery('#backupcode-table-body').empty();
                        for (var i = 0; i < response.BackUpCodes.length; i++) {
                            var html = '';
                            jQuery('#resettable').hide();
                            jQuery('#lr_ciam_reset_table').show();

                            html += '<div class="form-item code-list" id="backup-codes-' + i + '-field">';
                            html += '<span class="backupCode">' + response.BackUpCodes[i] + '</span>';
                            html += '</div>';

                            jQuery('#backupcode-table-body').append(html);
                        }
                        jQuery('.mybackupcopy').click(function () {
                            setClipboard(jQuery(this).parent('.form-item').find('span').text());
                        });
                    }, function (errors) {
            });
        }
    }, 1);
}

function callSocialInterface() {
    var custom_interface_option = {};
    custom_interface_option.templateName = 'loginradiuscustom_tmpl';
    var lrSocialInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrSocialInterval);
            LRObject.customInterface(".interfacecontainerdiv", custom_interface_option);
        }
    }, 1);
    jQuery('#lr-loading').hide();
}

function initializeSocialRegisterCiamForm() {
    var sl_options = {};
    sl_options.onSuccess = function (response) {
        if (response.access_token != null && response.access_token != "") {
            handleResponse(true, "");
            ciamRedirect(response.access_token);
            jQuery('#lr-loading').hide();
        } else if (response.IsPosted) {
            handleResponse(true, "An email has been sent to " + jQuery("#loginradius-socialRegistration-emailid").val() + ".Please verify your email address.");
            jQuery('#social-registration-form').hide();
            jQuery('#lr-loading').hide();
        }
    };
    sl_options.onError = function (response) {
        if (response[0].Description != null) {
            handleResponse(false, response[0].Description, "", "error");
            jQuery('#social-registration-form').hide();
            jQuery('#lr-loading').hide();
        }
    };
    sl_options.container = "social-registration-container";
    var lrSocialLoginInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrSocialLoginInterval);
            LRObject.init('socialLogin', sl_options);
        }
    }, 1);

}

function initializeLoginCiamForm() {
    //initialize Login form
    var login_options = {};
    login_options.onSuccess = function (response) {
        if (response.access_token) {
            handleResponse(true);
            ciamRedirect(response.access_token);
        } else if(LRObject.options.phoneLogin && typeof (response.Data) != "undefined"){
            handleResponse(true, "An OTP has been sent to your number.");
        } else {
            if (jQuery('#loginradius-login-username').length !== 0) {
                handleResponse(true, "An email has been sent to " + jQuery("#loginradius-login-username").val() + ".Please verify your email address");
            } else if (jQuery('#loginradius-login-emailid').length !== 0) {
                handleResponse(true, "An email has been sent to " + jQuery("#loginradius-login-emailid").val() + ".Please verify your email address");
            }
        }
    };
    login_options.onError = function (response) {
        handleResponse(false, response[0].Description, "", "error");
    };
    login_options.container = "login-container";

    var lrLoginInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrLoginInterval);
            LRObject.init("login", login_options);
        }
    }, 1);
    jQuery('#lr-loading').hide();
}

function initializeRegisterCiamForm() {
    var registration_options = {}
    registration_options.onSuccess = function (response) {
        var optionalemailverification = '';
        var disableemailverification = '';
        if (typeof LRObject.options.optionalEmailVerification != 'undefined') {
            optionalemailverification = LRObject.options.optionalEmailVerification;
        } 
        if (typeof LRObject.options.disabledEmailVerification != 'undefined') {
            disableemailverification = LRObject.options.disabledEmailVerification;
        }
                
        if (response.IsPosted && response.Data == null) {
            if ((typeof (optionalemailverification) == 'undefined' || optionalemailverification !== true) && (typeof (disableemailverification) == 'undefined' || disableemailverification !== true)) {
                handleResponse(true, "An email has been sent to " + jQuery("#loginradius-registration-emailid").val() + ".Please verify your email address");
                jQuery('html, body').animate({scrollTop: 0}, 1000);
            }            
        }else if (response.access_token != null && response.access_token != "") {
            handleResponse(true, "");
            ciamRedirect(response.access_token);
        }else if(LRObject.options.phoneLogin && typeof response.Data !== 'undefined'){
            handleResponse(true, "An OTP has been sent to your number.");
            jQuery('html, body').animate({scrollTop: 0}, 1000);
        }        
    };
    registration_options.onError = function (response) {
        if (response[0].Description != null) {
            handleResponse(false, response[0].Description, "", "error");  
        }else if (response[0] != null) {
            handleResponse(false, response[0], "", "error");      
        }
        jQuery('html, body').animate({scrollTop: 0}, 1000);
    };
    registration_options.container = "registration-container";
    var lrRegisterInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrRegisterInterval);
            LRObject.init("registration", registration_options);
        }
    }, 1);

    jQuery('#lr-loading').hide();
}

function initializeResetPasswordCiamForm(commonOptions) {
    //initialize reset password form and handel email verifaction
    var resetpasswordInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(resetpasswordInterval);
            var vtype = LRObject.util.getQueryParameterByName("vtype");
            if (vtype != null && vtype != "") {
                if (vtype == "reset") {
                    var resetpassword_options = {};
                    resetpassword_options.container = "resetpassword-container";
                    jQuery('#login-container').hide();
                    jQuery('.interfacecontainerdiv').hide();
                    jQuery('#interfaceLabel').hide();
                    resetpassword_options.onSuccess = function (response) {
                        handleResponse(true, "Password reset successfully. Now you can login with changed password.");
                        window.setTimeout(function () {
                           window.location.replace(commonOptions.verificationUrl);
                        }, 3000);
                    };
                    resetpassword_options.onError = function (errors) {
                        handleResponse(false, errors[0].Description, "", "error");
                    }

                    LRObject.init("resetPassword", resetpassword_options);

                } else if (vtype == "emailverification") {
                    var verifyemail_options = {};
                    verifyemail_options.onSuccess = function (response) {
                        if (typeof response != 'undefined') {
                            if (!loggedIn && typeof response.access_token != "undefined" && response.access_token != null && response.access_token != "") {
                                ciamRedirect(response.access_token);
                            } else if (!loggedIn && response.Data != null && response.Data.access_token != null && response.Data.access_token != "") {
                                ciamRedirect(response.Data.access_token);
                            } else {
                                lrSetCookie('lr_message', 'Your email has been verified successfully.');
                                window.location.href = window.location.href.split('?')[0] + '?lrresponse=true';
                            }
                        }
                    };
                    verifyemail_options.onError = function (errors) {
                        lrSetCookie('lr_message', errors[0].Description);
                        window.location.href = window.location.href.split('?')[0] + '?lrresponse=false';
                    }

                    LRObject.init("verifyEmail", verifyemail_options);


                } else if (vtype == "oneclicksignin") {
                    var options = {};
                    options.onSuccess = function (response) {
                        ciamRedirect(response.access_token);
                    };
                    options.onError = function (errors) {
                        handleResponse(false, errors[0].Description, "", "error");
                    };

                    LRObject.init("instantLinkLogin", options);

                }
            }
        }
    }, 1);
    jQuery('#lr-loading').hide();
}

function initializeForgotPasswordCiamForms() {
    //initialize forgot password form
    var forgotpassword_options = {};
    forgotpassword_options.container = "forgotpassword-container";
    forgotpassword_options.onSuccess = function (response) {
            if (response.IsPosted == true && typeof (response.Data) == "undefined") {
                if(jQuery('form[name="loginradius-resetpassword"]').length > 0) {
                handleResponse(true, "Password reset successfully.");  
                } else {
                handleResponse(true, "An email has been sent to " + jQuery("#loginradius-forgotpassword-emailid").val() + " with reset Password link.");   
                }
            } else {
                handleResponse(true, "OTP has been sent to your phone number.");             
            }
    };
    forgotpassword_options.onError = function (response) {
        if (response[0].Description != null) {
            handleResponse(false, response[0].Description, "", "error");
        }
    }

    var lrForgotInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrForgotInterval);
            LRObject.init("forgotPassword", forgotpassword_options);
        }
    }, 1);

    jQuery('#lr-loading').hide();
}

function initializeAccountLinkingCiamForms() {
    var la_options = {};
    la_options.container = "interfacecontainerdiv";
    la_options.templateName = 'loginradiuscustom_tmpl_link';
    la_options.onSuccess = function (response) {
        if (response.IsPosted != true) {
            handleResponse(true, "");
            ciamRedirect(response);
        } else {
            handleResponse(true, "Account linked successfully", "showmsg");
            window.setTimeout(function () {
                window.location.reload();
            }, 3000);
        }
    };
    la_options.onError = function (errors) {
        handleResponse(false, errors[0].Description, "showmsg", "error");
    }

    var unlink_options = {};
    unlink_options.onSuccess = function (response) {
        if (response.IsDeleted == true) {
            handleResponse(true, "Account unlinked successfully", "showmsg");
            window.setTimeout(function () {
                window.location.reload();
            }, 3000);
        }
    };
    unlink_options.onError = function (errors) {
        handleResponse(false, errors[0].Description, "showmsg", "error");
    }

    var lrLinkingInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrLinkingInterval);
            LRObject.init("linkAccount", la_options);
            LRObject.init("unLinkAccount", unlink_options);
        }
    }, 1);

    jQuery('#lr-loading').hide();
}

function initializeTwoFactorAuthenticator() {
    //initialize two factor authenticator button
    var authentication_options = {};
    authentication_options.container = "authentication-container";
    authentication_options.onSuccess = function (response) {
        if(response.AccountSid){              
            handleResponse(true, "An OTP has been sent.");
        } else if (response.IsDeleted) {
            handleResponse(true, "Disabled successfully.", "showmsg");  
            jQuery('html, body').animate({scrollTop: 0}, 1000);
            window.setTimeout(function () {
                window.location.reload();
            }, 3000);
        } else if (response.Uid) {
            handleResponse(true, "Verified successfully.", "showmsg"); 
            jQuery('html, body').animate({scrollTop: 0}, 1000);
             window.setTimeout(function () {
                window.location.reload();
            }, 3000);
        }
    };
    authentication_options.onError = function (errors) {
        if (errors[0].Description != null) {
              handleResponse(false, errors[0].Description, "showmsg", "error");     
        }
    }
    var lrTwoFAInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrTwoFAInterval);
            LRObject.init("createTwoFactorAuthentication", authentication_options);

        }
    }, 1);
}

function initializePhoneUpdate() {
    var updatephone_options = {};
    updatephone_options.container = "updatephone-container";
    updatephone_options.onSuccess = function (response) {
        if(response.access_token){
            handleResponse(true, "Updated successfully.", 'showmsg');  
            window.setTimeout(function () {
                window.location.reload();
            }, 3000);
        } else {        
            handleResponse(true, "Resend OTP.", 'showmsg');
        }
    };
    updatephone_options.onError = function (errors) {
        if (errors[0].Description != null) {
            handleResponse(false, errors[0].Description, "showmsg", "error");       
        }
    };
    var lrUpdateInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrUpdateInterval);
            LRObject.init("updatePhone", updatephone_options);            
        }
    }, 1);
    
}

function initializeAddEmailCiamForms() {
    var addemail_options = {};
    addemail_options.container = "addemail-container";
    addemail_options.onSuccess = function (response) {
        jQuery('#addemail-form').hide();
        handleResponse(true, "Email added successfully. Please verify your email address.", 'showmsg');
        jQuery('html, body').animate({scrollTop: 0}, 1000);

    };
    addemail_options.onError = function (errors) {
        jQuery('#addemail-form').hide();
        handleResponse(false, errors[0].Description, "showmsg", "error");
        jQuery('html, body').animate({scrollTop: 0}, 1000);

    };

    var lrAddInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrAddInterval);
            LRObject.init("addEmail", addemail_options);
            jQuery('#lr-loading').hide();
        }
    }, 1);
}

function initializeRemoveEmailCiamForms(divhtml) {
    var removeemail_options = {};
    removeemail_options.container = "removeemail-container";
    removeemail_options.onSuccess = function (response) {
        jQuery('#removeemail-form').hide();
        handleResponse(true, "Email has been removed successfully.", 'showmsg');
        divhtml.remove();
        jQuery('html, body').animate({scrollTop: 0}, 1000);
    };
    removeemail_options.onError = function (errors) {
        jQuery('#removeemail-form').hide();
        handleResponse(false, errors[0].Description, "showmsg", "error");
        jQuery('html, body').animate({scrollTop: 0}, 1000);

    };
    var lrRemoveInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrRemoveInterval);
            LRObject.init("removeEmail", removeemail_options);
            jQuery('#lr-loading').hide();
        }
    }, 1);
}

function initializeChangePasswordCiamForms() {
    var changepassword_options = {};
    changepassword_options.container = "changepassword-container";
    changepassword_options.onSuccess = function (response) {
        handleResponse(true, "Password has been updated successfully.");
    };
    changepassword_options.onError = function (errors) {
        handleResponse(false, errors[0].Description, "", "error");
    };

    var lrChangeInterval = setInterval(function () {
        if (typeof LRObject !== 'undefined')
        {
            clearInterval(lrChangeInterval);
            LRObject.init("changePassword", changepassword_options);
            jQuery('#lr-loading').hide();
        }
    }, 1);
}

function ciamRedirect(token, name) {
    if (window.redirect) {
        redirect(token, name);
    } else {
        var token_name = name ? name : 'token';
        var source = typeof lr_source != 'undefined' && lr_source ? lr_source : '';

        var form = document.createElement('form');
        form.action = LocalDomain;
        form.method = 'POST';

        var hiddenToken = document.createElement('input');
        hiddenToken.type = 'hidden';
        hiddenToken.value = token;
        hiddenToken.name = token_name;
        form.appendChild(hiddenToken);

        document.body.appendChild(form);
        form.submit();
    }
}

function setClipboard() {
    var value = '';
    jQuery('.code-list').find('span').each(function () {
        value += jQuery(this).html() + "\n";
    });
    var tempInput = document.createElement("textarea");
    tempInput.style = "position: absolute; left: -1000px; top: -1000px";
    tempInput.value = value;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    jQuery('.copyMessage').show();
    setTimeout(removeCodeCss, 5000);
}

function removeCodeCss() {
    jQuery('.code-list').find('span').removeAttr('style');
    jQuery('.copyMessage').hide();
}

function changeIconColor() {
    jQuery('.code-list').find('span').css({'background-color': '#29d', 'color': '#fff'});
}

function lrSetCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}