jQuery(document).ready(function () {   
    if(!(window.location.href.indexOf("admin") > -1)) {          
    initializeChangePasswordCiamForms();
    initializeTwoFactorAuthenticator();
    initializeAddEmailCiamForms();
    initializeRemoveEmailCiamForms();
    getBackupCodes();
    initializePhoneUpdate();
    initializeForgotPasswordCiamForms(); 
   }
});

