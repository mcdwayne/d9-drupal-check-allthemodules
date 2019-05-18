(function (jQuery) {
  Drupal.behaviors.commerce_culqi = {
    attach: function (context, settings) {
    	console.log("Hola mundo");
      // You can access the variable by using Drupal.settings.SOMEVARIABLE

console.log(Drupal.settings.commerce_culqi.data);
//alert(Drupal.settings.commerce_culqi.SOMEVARIABLE); 

    jQuery("#response-panel").hide();
    jQuery("#response-panel").removeClass();

    jQuery(".cancel-pay-culqi").click(function(){
      run_waitMe('Cancelando...');
    });
    
     // Culqi.publicKey = 'pk_test_vzMuTHoueOMlgUPj';
      Culqi.publicKey = Drupal.settings.commerce_culqi.data.public_keySecret;
     
      Culqi.settings({
        title: Drupal.settings.commerce_culqi.data.title,
        currency: Drupal.settings.commerce_culqi.data.currency,
        description: Drupal.settings.commerce_culqi.data.description,
        amount: Drupal.settings.commerce_culqi.data.amount
       });
       jQuery('#payCulqi').on('click', function (e) {
            // Open the form with the settings of Culqi.configure
            Culqi.open();
            e.preventDefault();
        });
    }
  };
})(jQuery);