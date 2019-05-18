/**
 * @file
 */
(function ($) {
    
 "use strict";
 
 // Get variables passed from PHP function.
 var uid = drupalSettings.encrypt_content_client.wrapper_js.uid;
 
 // Only used for development!
 // TODO: remove this after development of below features is finished.
 var nodeType = 'article';
 var nodeId = 0;
 
 if (nodeId > 0) {
   reEncryptNodeInit();
 }

 // Start re-encryption process for a single node.
 Drupal.behaviors.reEncryptSingle = {
   attach: function (context, settings) {     
     // .client_re-encrypt | #client-rencrypt-nodeType-nodeId 
     $(".client-reencrypt").on("click", function() {
         var $elemId = $(this).attr("id");
         
     });
   }
 }
 
 // Re-encrypt multiple nodes when button with given class is clicked.
 Drupal.behaviors.reEncryptAll = {
   attach: function (context, settings) {       
     // .client_re-encrypt | #client-rencrypt-nodeType-nodeId 
     $(".client-reencrypt").on("click", function() {
         
     });
   }  
 }
 
 // Re-encrypt given node's fields.
 function reEncryptNodeInit() {
      var array = new Uint32Array(1);
      var hash = sjcl.hash.sha256.hash("" + window.crypto.getRandomValues(array)[0]);
      var dataKey = sjcl.codec.hex.fromBits(hash);
      
      $.get("/client_encryption/all?_format=json", function (data) {
        var keys = JSON.parse(data);
        var publicKeys = {};
        for (var uid in keys) {
          publicKeys[uid] = keys[uid];
        }

        var encryptionContainer = {};
        Object.keys(publicKeys).forEach(function(key, index) {
          var publicKeyObject = new sjcl.ecc.elGamal.publicKey(
            sjcl.ecc.curves.c256,
            sjcl.codec.hex.toBits(publicKeys[key])
          );
          encryptionContainer[key] = sjcl.encrypt(publicKeyObject, dataKey);
        });
    
        var payload = { entity_id: nodeId, entity_type: nodeType, encrypted_data_keys: JSON.stringify(encryptionContainer) };
        $.ajax({
          headers: {
           'Accept': 'application/json',
           'Content-Type': 'application/json',
           'X-CSRF-Token': getRestToken()
          },
          type: "POST",
          url: "/client_encryption/encryption_container?_format=json",
          data: JSON.stringify(payload),
          success: function(encryptionContainerId, dataKey) {
            reEncryptFields(encryptionContainerId, dataKey);
          },
          dataType: "json"
         });
        });
        
 }

 // Re-encrypt encrypted fields in the database. 
 function reEncryptFields(encryptionContainerId, dataKey) { 
   $.get("/client_encryption/encrypted_fields/" + nodeType + "/" + nodeId + "?_format=json", function (data) {
     var encryptedFields = data;
     
     // Check whether there are any fields encrypted for this entity.
     if (!encryptedFields) {
       alert("ERROR: this node is not encrypted.");     
       return false;
     }
     
     var newEncryptedFields = { 
       encryption_container_id: encryptionContainerId,
       fields: []
     }
     
     $.get("/client_encryption/encryption_container/" + nodeType + "/" + nodeId + "?_format=json", function (encryptionContainer) {
       var encryptedDataKey = JSON.parse(encryptionContainer)[uid];
       console.log(encryptedDataKey);
       
       // Load private ECC key from localStorage.
       var privateKey = localStorage.getItem("drupal_ecc_private_key");
       
       if (privateKey == "") {
         alert("ERROR: ECC private key not found locally.");
         return false;
       }
       
       var privateKeyObject = new sjcl.ecc.elGamal.secretKey(
         sjcl.ecc.curves.c256,
         sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.hex.toBits(privateKey))
       );
       
       var oldDataKey = sjcl.decrypt(privateKeyObject, encryptedDataKey);

       Object.keys(encryptedFields).forEach(function(fieldName, index) {
         // Decrypt a field with previous dataKey in order to get its content.
         var plaintext = sjcl.decrypt(oldDataKey, encryptedFields[fieldName]);
         
         // Encrypt it again with the new dataKey. 
         var encryptedField = sjcl.encrypt(dataKey, plaintext);
         
         // Push encrypted field into new encrypted fields object.
         newEncryptedFields['fields'].push({ field_name: fieldName, encrypted_content: encryptedField });       
       });
     
       $.ajax({
         headers: {
           'Accept': 'application/json',
           'Content-Type': 'application/json',
           'X-CSRF-Token': getRestToken()
         },
         type: "POST",
         url: "/client_encryption/encrypted_fields?_format=json",
         data: JSON.stringify(newEncryptedFields),
         success: function(data) {
            alert("SUCCESS: Re-encryption processes finished.");
         },
         dataType: "json"
       });
     });
   });
 }
 
 // Get CSRF token needed for making POST calls to the REST resources.
 function getRestToken() {
   return $.ajax({
     type: "GET",
     url: "/rest/session/token",
     async: false
   }).responseText;
 }
 
})(jQuery, Drupal)
