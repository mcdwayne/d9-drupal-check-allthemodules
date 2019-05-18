function toggleppssopreview(){
    var customSsoButtonSize;
    var customSsoColour;
    
    var previewSSOButton = document.getElementById("previewSSObutton");
    previewSSOButton.className = '';

    var customiseDropdown = document.getElementById("edit-ppsso-customise-enable");
    var customiseValue = customiseDropdown.value;

    var buttonColourDropdown = document.getElementById("edit-ppsso-customise-colour");
    var buttonColourValue = buttonColourDropdown.value;

    var buttonSizeDropdown = document.getElementById("edit-ppsso-customise-size");
    var buttonSizeValue = buttonSizeDropdown.value;

    var showButtonTextDropdown = document.getElementById("edit-ppsso-customise-show-text");
    var showButtonTextValue = showButtonTextDropdown.value;

    var buttonText = document.getElementById("edit-ppsso-customise-login-text");
    var buttonTextValue = buttonText.value;

    if(showButtonTextValue == 1){
        if(buttonSizeValue == 0 ){
            customSsoButtonSize = 'ppsso-logo-lg';
        } else if (buttonSizeValue == 1){
            customSsoButtonSize = 'ppsso-md ppsso-logo-md';
        } else if (buttonSizeValue == 2){
            customSsoButtonSize = 'ppsso-sm ppsso-logo-sm';
        } else {
            customSsoButtonSize = '';
        }
    } else {
        if(buttonSizeValue == 0 ){
            customSsoButtonSize = '';
        } else if (buttonSizeValue == 1){
            customSsoButtonSize = 'ppsso-md';
        } else if (buttonSizeValue == 2){
            customSsoButtonSize = 'ppsso-sm';
        } else {
            customSsoButtonSize = '';
        }
    }

    if(buttonColourValue == 0 ){
        customSsoColour = '';
    } else if (buttonColourValue == 1){
        customSsoColour = 'ppsso-cyan';
    } else if (buttonColourValue == 2){
        customSsoColour = 'ppsso-pink';
    } else if (buttonColourValue == 3){
        customSsoColour = 'ppsso-white';
    } else {
        customSsoColour = '';
    }

    if(customiseValue === '0'){
        jQuery('#previewSSObutton').addClass('ppsso-btn');
        previewSSOButton.innerHTML = 'Log In With <span class="ppsso-logotype">PixelPin</span>';
    } else {
        if(showButtonTextValue === '1'){
            jQuery('#previewSSObutton').addClass('ppsso-btn ' + customSsoColour + ' ' + customSsoButtonSize);
            previewSSOButton.innerHTML = '';
        } else {
            jQuery('#previewSSObutton').addClass('ppsso-btn ' + customSsoColour + ' ' + customSsoButtonSize);
            previewSSOButton.innerHTML = buttonTextValue + ' <span class="ppsso-logotype">PixelPin</span>';
        }
    }
}