//initialize raas options
var raasoption = {};
var LocalDomain = drupalSettings.raas.callback;
var homeDomain = drupalSettings.raas.home;
raasoption.apikey = drupalSettings.raas.apikey;
raasoption.appName = drupalSettings.raas.appName;
raasoption.V2Recaptcha = true;
raasoption.emailVerificationUrl = drupalSettings.raas.emailVerificationUrl;
raasoption.forgotPasswordUrl = drupalSettings.raas.forgotVerificationUrl;
raasoption.templatename = "loginradiuscustom_tmpl";
raasoption.hashTemplate = true; 
if(drupalSettings.raas.inFormvalidationMessage){
	raasoption.inFormvalidationMessage = drupalSettings.raas.inFormvalidationMessage;
}
if(drupalSettings.raas.termsAndConditionHtml){
	raasoption.termsAndConditionHtml = drupalSettings.raas.termsAndConditionHtml;
}
if(drupalSettings.raas.formRenderDelay){
	raasoption.formRenderDelay = drupalSettings.raas.formRenderDelay;
}if(drupalSettings.raas.passwordminlength && drupalSettings.raas.passwordmaxlength){
	raasoption.passwordlength  = {min : drupalSettings.raas.passwordminlength, max :drupalSettings.raas.passwordmaxlength}
}

if(drupalSettings.raas.V2RecaptchaSiteKey){
	raasoption.V2RecaptchaSiteKey = drupalSettings.raas.V2RecaptchaSiteKey;
}
if(drupalSettings.raas.forgotPasswordTemplate){
	raasoption.forgotPasswordTemplate = drupalSettings.raas.forgotPasswordTemplate;
}
if(drupalSettings.raas.enableLoginOnEmailVerification){
	raasoption.enableLoginOnEmailVerification = drupalSettings.raas.enableLoginOnEmailVerification;
}
if(drupalSettings.raas.enableUserName){
	raasoption.enableUserName = drupalSettings.raas.enableUserName;
}
if(drupalSettings.raas.askEmailAlwaysForUnverified){
	raasoption.askEmailAlwaysForUnverified = drupalSettings.raas.askEmailAlwaysForUnverified;
}
if(drupalSettings.raas.promptPasswordOnSocialLogin){
	raasoption.promptPasswordOnSocialLogin = drupalSettings.raas.promptPasswordOnSocialLogin;
}
if(drupalSettings.raas.OptionalEmailVerification){
	raasoption.OptionalEmailVerification = drupalSettings.raas.OptionalEmailVerification;
}
if(drupalSettings.raas.emailVerificationTemplate){
	raasoption.emailVerificationTemplate = drupalSettings.raas.emailVerificationTemplate;
}
if(drupalSettings.raas.DisabledEmailVerification){
	raasoption.DisabledEmailVerification = drupalSettings.raas.DisabledEmailVerification;
}
if(drupalSettings.raas.CustomScript){  
       eval(drupalSettings.raas.CustomScript);  
}
jQuery(document).ready(function () {
    initializeResetPasswordRaasForm(raasoption);
});