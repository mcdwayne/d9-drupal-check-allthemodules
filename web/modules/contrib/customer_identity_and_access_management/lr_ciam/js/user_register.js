jQuery(document).ready(function () {    
    if(!(window.location.href.indexOf("admin") > -1)) {  
    initializeRegisterCiamForm();
        initializeSocialRegisterCiamForm();
          var isClear = 1;
          var formIntval;
        setTimeout(show_birthdate_date_block, 1000);
          formIntval = setInterval(function(){ jQuery('#lr-loading').hide();
             if (isClear > 0) {
                 clearInterval(formIntval);
             }
         }, 1000); 
    }
});

