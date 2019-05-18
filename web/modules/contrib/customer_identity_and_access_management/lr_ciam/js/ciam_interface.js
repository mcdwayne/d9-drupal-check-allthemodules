//initialize ciam options
var commonOptions = {};
var LocalDomain = drupalSettings.ciam.callback;
var homeDomain = drupalSettings.ciam.home;
var accessToken = drupalSettings.ciam.accessToken;
var phoneId = drupalSettings.ciam.phoneId;
var autoHideTime = drupalSettings.ciam.autoHideTime;
var loggedIn = drupalSettings.ciam.loggedIn;
var domainName = drupalSettings.ciam.appPath;
commonOptions.apiKey = drupalSettings.ciam.apiKey;
commonOptions.appName = drupalSettings.ciam.appName;
commonOptions.appPath = drupalSettings.ciam.appPath;
commonOptions.sott = drupalSettings.ciam.sott;
commonOptions.verificationUrl = drupalSettings.ciam.verificationUrl;
commonOptions.resetPasswordUrl = drupalSettings.ciam.resetPasswordUrl;
commonOptions.callbackUrl = drupalSettings.ciam.callback;
commonOptions.hashTemplate = true;
commonOptions.formValidationMessage = true;

if (drupalSettings.ciam.termsAndConditionHtml) {
    commonOptions.termsAndConditionHtml = drupalSettings.ciam.termsAndConditionHtml;
}
if (drupalSettings.ciam.displayPasswordStrength) {
    commonOptions.displayPasswordStrength = drupalSettings.ciam.displayPasswordStrength;
}
if (drupalSettings.ciam.askRequiredFieldForTraditionalLogin) {
    commonOptions.askRequiredFieldForTraditionalLogin = drupalSettings.ciam.askRequiredFieldForTraditionalLogin;
}else {
    commonOptions.askRequiredFieldForTraditionalLogin = false;
}
if (drupalSettings.ciam.askEmailForUnverifiedProfileAlways) {
    commonOptions.askEmailForUnverifiedProfileAlways = drupalSettings.ciam.askEmailForUnverifiedProfileAlways;
} else {
    commonOptions.askEmailForUnverifiedProfileAlways = false;
}
if (drupalSettings.ciam.usernameLogin) {
    commonOptions.usernameLogin = drupalSettings.ciam.usernameLogin;
} else {
    commonOptions.usernameLogin = false;
}
if (drupalSettings.ciam.promptPasswordOnSocialLogin) {
    commonOptions.promptPasswordOnSocialLogin = drupalSettings.ciam.promptPasswordOnSocialLogin;
}else {
    commonOptions.promptPasswordOnSocialLogin = false;
}
if(drupalSettings.ciam.instantLinkLogin){
    commonOptions.instantLinkLogin = drupalSettings.ciam.instantLinkLogin;
}else {
    commonOptions.instantLinkLogin = false;
}
if(drupalSettings.ciam.instantOTPLogin){
    commonOptions.instantOTPLogin = drupalSettings.ciam.instantOTPLogin;
}else {
    commonOptions.instantOTPLogin = false;
}
if (drupalSettings.ciam.existPhoneNumber) {
    commonOptions.existPhoneNumber = drupalSettings.ciam.existPhoneNumber;
}
if (drupalSettings.ciam.welcomeEmailTemplate) {
    commonOptions.welcomeEmailTemplate = drupalSettings.ciam.welcomeEmailTemplate;
}
if (drupalSettings.ciam.verificationEmailTemplate) {
    commonOptions.verificationEmailTemplate = drupalSettings.ciam.verificationEmailTemplate;
}
if (drupalSettings.ciam.resetPasswordEmailTemplate) {
    commonOptions.resetPasswordEmailTemplate = drupalSettings.ciam.resetPasswordEmailTemplate;
}
if (drupalSettings.ciam.instantLinkLoginEmailTemplate) {
    commonOptions.instantLinkLoginEmailTemplate = drupalSettings.ciam.instantLinkLoginEmailTemplate;
}
if (drupalSettings.ciam.smsTemplateWelcome) {
    commonOptions.smsTemplateWelcome = drupalSettings.ciam.smsTemplateWelcome;
}
if (drupalSettings.ciam.smsTemplatePhoneVerification) {
    commonOptions.smsTemplatePhoneVerification = drupalSettings.ciam.smsTemplatePhoneVerification;
}
if (drupalSettings.ciam.smsTemplateForgot) {
    commonOptions.smsTemplateForgot = drupalSettings.ciam.smsTemplateForgot;
}
if (drupalSettings.ciam.smsTemplateChangePhoneNo) {
    commonOptions.smsTemplateUpdatePhone = drupalSettings.ciam.smsTemplateChangePhoneNo;
}
if (drupalSettings.ciam.smsTemplateInstantOTPLogin) {
    commonOptions.smsTemplateInstantOTPLogin = drupalSettings.ciam.smsTemplateInstantOTPLogin;
}
if (drupalSettings.ciam.smsTemplate2FA) {
    commonOptions.smsTemplate2FA = drupalSettings.ciam.smsTemplate2FA;
}
if (drupalSettings.ciam.debugMode) {
    commonOptions.debugMode = drupalSettings.ciam.debugMode;
}
if (drupalSettings.ciam.customScript) {
    eval(drupalSettings.ciam.customScript);
}
jQuery(document).ready(function () {
    initializeResetPasswordCiamForm(commonOptions);
});