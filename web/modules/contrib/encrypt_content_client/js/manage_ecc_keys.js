/**
 * @file
 */
(function ($) {
 
 "use strict";
 var publicKeyField = $("#edit-user-public-key");
 var privateKeyField = $("#edit-user-private-key");
 
 var currentPrivateKey = localStorage.getItem('drupal_ecc_private_key');
 $("#edit-private-key").val(currentPrivateKey);

 // Update ECC private key locally when user submit the block's form.
 Drupal.behaviors.encryptionBlockSave = {
   attach: function (context, settings) {
     $(".encrypt-content-client-update-keys-block-form").on("submit", function(e) {
       e.preventDefault();
       
       var $newPrivateKey = $("#edit-private-key").val();
       localStorage.setItem('drupal_ecc_private_key', $newPrivateKey);
       
       alert("[OK] ECC private key has been updated!");
     });
   }
 };
 
 Drupal.behaviors.generateDescription = {
   attach: function (context, settings) {
     // Set form's field to show locally stored private key.
     privateKeyField.val(localStorage.getItem('drupal_ecc_private_key'));
     
     var tasks = "<ul>";
     if (publicKeyField.val() == "") {
       tasks += "<li style='color:#FF4500;'><b> You need to <a id='generate-ecc-keys' href='#'>generate</a> encryption key pair first. </b></li>"; 
       
       if (privateKeyField.val() != "") {
         tasks += "<li style='color:#FF4500;'><b> You seem to have a private key stored locally - <a id='delete-private-key' href='#'>delete</a> it now.</b></li>"; 
       }
     }
     else if (privateKeyField.val() == "") {
       tasks += "<li><b style='color:#FF4500;'> You need to update your local private key. </b></li>"; 
     }
     else if (publicKeyField.val() != "" && privateKeyField.val() != "") {
       tasks += "<li><b style='color:green'> Both keys are present - all looks good!</b></li>";  
       tasks += "<li><b style='color:green'> You can now <a id='test-ecc-keys' href='#'>test</a> if the new keys you provided are valid before submitting.</b></li>";  
     }
     tasks += "</ul>";
     
     // Add description and tasks list if any.
     $("#block-bartik-page-title").append("<p>Here you can generate or edit your client-side encryption key pair.</p><span id='keys-info'>" + tasks + "</span>");
   }
 }
  
 Drupal.behaviors.deletePrivateKey = {
   attach: function (context, settings) {
     $('#delete-private-key').on('click', function(e) {
       if (confirm('Are you sure you want to delete the private key from local storage?')) {
         localStorage.removeItem('drupal_ecc_private_key');
         privateKeyField.val("");
         window.location = "/user/ecc";
       } 
     });
   }
 } 
 
 Drupal.behaviors.updateKeys = {
   attach: function (context, settings) {
     $('#encrypt-content-client-generate-keys-form').on('submit', function(e) {
       e.preventDefault();
       if (confirm('Are you sure you want to update existing encryption keys?')) {
           
         if (isEccKeyPairValid(publicKeyField.val(), privateKeyField.val())) {
           saveKeyThroughRest(publicKeyField.val());
           saveKeysLocalStorage(privateKeyField.val());
           alert("[OK] Provided encryption key pair has been updated!");  
         }
       } 
     });
   }
 } 
 
 Drupal.behaviors.testKeys = {
   attach: function (context, settings) {
     $('#test-ecc-keys').on('click', function(e) {
         
       if (isEccKeyPairValid(publicKeyField.val(), privateKeyField.val())) {
         alert("[OK] Provided encryption key pair is valid!");
       } 
       else {
         alert("[ERROR] Provided encryption key pair is not valid!");
       }
     }); 
     
   }
 }
  
 Drupal.behaviors.generateKeys = {
   attach: function (context, settings) {
     $('#generate-ecc-keys').on('click', function(e) {
       var keyPair = generateEccKeyPair();
       var publicKey = keyPair["public"];
       var privateKey = keyPair["private"];
       
       $("#keys-info").html("");
       if(isEccKeyPairValid(publicKey, privateKey)){
         publicKeyField.val(publicKey);
         privateKeyField.val(privateKey);
       
         saveKeyThroughRest(publicKey);
         saveKeysLocalStorage(privateKey);
         downloadKey(privateKey);
       } else {
         alert("[ERROR] An error has occured when generating encryption keys.")
       }
       // Append a success message if it doesn't exist already.
       if(!$("#ecc-keys-generation-success").length) {
         jQuery("#encrypt-content-client-generate-keys-form").prepend("<div role='contentinfo' id='ecc-keys-generation-success' aria-label='Status message' class='messages messages--status'> \
                                                                       <h2 class='visually-hidden'>Status message</h2>ECC keys pair has been generated.</div><br>");
       }     
     });
   }
 }
 
 // Check if provided ECC key pair is valid.
 function isEccKeyPairValid(publicKeyHex, privateKeyHex) {
   var publicKeyObject = new sjcl.ecc.elGamal.publicKey(
     sjcl.ecc.curves.c256,
     sjcl.codec.hex.toBits(publicKeyHex)
   );
   
   var randomCiphertext = Math.random().toString(36).substring(20);    
   var encrypted = sjcl.encrypt(publicKeyObject, randomCiphertext);
       
   var privateKeyObject = new sjcl.ecc.elGamal.secretKey(
     sjcl.ecc.curves.c256,
     sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.hex.toBits(privateKeyHex))
   );
   
   var decrypted = sjcl.decrypt(privateKeyObject, encrypted);

   if (decrypted == randomCiphertext) {
     return true;
   } 
   else {
     return false;    
   }
 }
 
 // Generate a pair of ECC keys.
 function generateEccKeyPair() {
    var keyPair = sjcl.ecc.elGamal.generateKeys(256); 
    var publicKey = keyPair.pub.get();
    var privateKey = keyPair.sec.get();

    var publicKeyHex = sjcl.codec.hex.fromBits(publicKey.x.concat(publicKey.y));
    var privateKeyHex = sjcl.codec.hex.fromBits(privateKey);
    return { public : publicKeyHex, private : privateKeyHex };
 }
 
 // Generate asynch CSRF token.
 // TODO: change it to synchronous with every REST call.
 function getRestToken() {
   return $.ajax({
     type: "GET",
       url: "/rest/session/token",
       async: false
     }).responseText;
 }
 
 // Save public key in the database through REST.
 function saveKeyThroughRest(publicKey) {
    var payload = { public_key: publicKey };
    $.ajax({
      headers: { 
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': getRestToken()
      },
      type: "POST",
      url: "/client_encryption/keys/update?_format=json",
      data: JSON.stringify(payload),
      success: function(data) {
        console.log(data);
      },
      dataType: "json"
    });
 }
 
 // Save key to the localStorage.
 function saveKeysLocalStorage(privateKey) {
    localStorage.setItem('drupal_ecc_private_key', privateKey);
 }
 
 // Use external JavaScript library to download the key so the server does not know its content.
 function downloadKey(privateKey) {
    var blob = new Blob([privateKey], {type: "text/plain;charset=utf-8"});
    saveAs(blob, "ecc_private_key.pem");
 }

})(jQuery, Drupal)
