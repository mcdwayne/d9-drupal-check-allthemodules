jQuery(document).ready(function () {
   
     showAndHideUI();

});
 function showAndHideUI() { 
      var options = jQuery('input[name=raas_email_verification_condition]:checked').val(); 
       var enableLogin =  jQuery('fieldset[data-drupal-selector=edit-raas-enable-login-on-email-verification]');              
        var enableUserName =  jQuery('fieldset[data-drupal-selector=edit-raas-enable-user-name]');              
        var askEmail =  jQuery('fieldset[data-drupal-selector=edit-raas-ask-email-always-for-unverified]');              
        var promptPassword =  jQuery('fieldset[data-drupal-selector=prompt-password]');              
                  
                      
                
          if (options == 2) {  
          enableLogin.hide();              
               //   jQuery('.form-item-raas-enable-login-on-email-verification').hide();                
                  promptPassword.hide();    
                  enableUserName.hide();   
                  askEmail.hide();   
              } else if(options == 1) {
                           
              enableLogin.show();
                   promptPassword.hide(); 
                   enableUserName.hide();
                   askEmail.show();                   
              } else {                  
                   enableLogin.show();                
                   promptPassword.show();                
                   askEmail.show();  
                   enableUserName.show();
              }       
}
 function lrCheckValidJson() {    
      jQuery('#add_custom_options').change(function(){
      var profile = jQuery('#add_custom_options').val();      
      var response = '';
      try
      {
          response = jQuery.parseJSON(profile);
          if(response != true && response != false){
              var validjson = JSON.stringify(response, null, '\t').replace(/</g, '&lt;');
              if(validjson != 'null'){
                  jQuery('#add_custom_options').val(validjson);
                  jQuery('#add_custom_options').css("border","1px solid green");
              }else{                  
                  jQuery('#add_custom_options').css("border","1px solid red");
              }
          }
          else{
              jQuery('#add_custom_options').css("border","1px solid green");
          }
      } catch (e)
      {
          jQuery('#add_custom_options').css("border","1px solid green");
      }
  });
}