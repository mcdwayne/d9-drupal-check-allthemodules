<a id="payCulqi" class="btn btn-primary" href="#" >Pagar Ahora</a>


<div  id="response-panel">
    <i class="fa fa-info-circle"></i>
      <span id="response"></span>
    <i onclick="this.parentElement.style.display='none';" class="closebtn fa fa-close"></i>
</div>


<script>
	  // We received Token from Culqi.js
        function culqi() {
          //console.log("antes del token");
          if (Culqi.token) {
           // console.log("Token ok");
              jQuery(document).ajaxStart(function(){
                run_waitMe();
              });
              jQuery('#response-panel').removeClass();
              // Print Token
              jQuery.ajax({
                 type: 'POST',
                 url: '<?php print $data['confirmationUrl']; ?>',
                 data: { token: Culqi.token.id, installments: Culqi.token.metadata.installments },
                 datatype: 'json',
                 success: function(data) {
                   var result = "";
                   if(data.constructor == String){
                       result = JSON.parse(data);
                   }
                   if(data.constructor == Object){
                       result = JSON.parse(JSON.stringify(data));
                   }
                   if(result.object === 'charge'){
                     jQuery('#response-panel').addClass('isa_success ');
                     resultdiv(result.outcome.user_message);
                     document.location = "<?php print $data['responseUrl']; ?>";
                   }
                   if(result.object === 'error'){
                     jQuery('#response-panel').addClass('isa_error');
                     resultdiv(result.user_message);
                   }

                   //console.log(result);
                 },
                 error: function(error) {
                 //console.log('errorqqs');
                   resultdiv(error)
                 }
              });
          } else {
            // Hubo un problema...
            // Mostramos JSON de objeto error en consola
            jQuery('#response-panel').show();
            jQuery('#response').html(Culqi.error.merchant_message);
            jQuery('body').waitMe('hide');
          }
        };
        function run_waitMe(message){
          jQuery('body').waitMe({
            effect: 'orbit',
            text: message ? message : 'Procesando pago...',
            bg: 'rgba(255,255,255,0.7)',
            color:'#28d2c8'
          });
        }
        function resultdiv(message){
          jQuery('#response-panel').show();
          jQuery('#response').html(message);
          jQuery('body').waitMe('hide');
        }

</script>