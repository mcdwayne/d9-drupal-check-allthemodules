/**
 * @file
 */

(function ($) {
 
 "use strict";

 // Load variables passed from the hook in encrypt_content_client.module file.
 
 // Variables with informations about currently logged in user and the form type.
 var uid = drupalSettings.encrypt_content_client.wrapper_js.uid;
 var formType = drupalSettings.encrypt_content_client.wrapper_js.form_type;
 
 // Information about the node being created/edited/viewed.
 var nodeType = drupalSettings.encrypt_content_client.wrapper_js.node_type;
 var nodeId = drupalSettings.encrypt_content_client.wrapper_js.node_id;
 var nodeFields = drupalSettings.encrypt_content_client.wrapper_js.node_fields;
 var nodeFieldsFiltered = [];
 
 // Get the field policy for current node type.
 var fieldsPolicy = drupalSettings.encrypt_content_client.wrapper_js.fields_policy;
 var encryptedFields = {};
 
 // Filter all of node's field by leaving only those with visible form fields.
 $(nodeFields).each( function(index, fieldName) {
   if ($("#edit-" + fieldName + "-0-value").length) {
     nodeFieldsFiltered.push(fieldName);
   } 
 });
 
 // Load user's private key from the localStorage.
 var privateKey = localStorage.getItem('drupal_ecc_private_key');
 
 // Error checking for few basic scenarios.
 if (formType == "edit" || formType == "view") {
   if (!privateKey) {
     alert("Set your private key first.");
     return false;
   }
 }
 
 // Decrypt fields when editing a node.
 Drupal.behaviors.editNode = {
   attach: function (context, settings) {
     // Decrypt fields when editing a node.
     if (formType == "edit") {
       $.get("/client_encryption/encrypted_fields/" + nodeType + "/" + nodeId + "?_format=json", function(encryptedFields) {
         getEncryptionContainer(encryptedFields);
       });
     }
   }
 }
 
 // Decrypt content when viewing a node.
 Drupal.behaviors.viewNode = {
   attach: function (context, settings) {
     // Decrypt fields when viewing a node.
     if (formType == "view") {
       $.get("/client_encryption/encrypted_fields/" + nodeType + "/" + nodeId + "?_format=json", function(encryptedFields) {
         getEncryptionContainer(encryptedFields);
       });
     }
   }
 }
 
 // Encrypt fields a node create/edit form is submitted.
 Drupal.behaviors.formSent = {
   attach: function (context, settings) {
     $("#edit-submit").on('click', function (e) {
       e.preventDefault();
       
       if (formType == "create") {
         var inputs = getFieldIds(fieldsPolicy);
         var fieldsCombined = "";
         inputs.forEach( function(elem){
           fieldsCombined += $(elem).val();
         });

         var array = new Uint32Array(1);
         var dataKey = generateHash(fieldsCombined + window.crypto.getRandomValues(array)[0]);

         fieldsPolicy.forEach( function(fieldName) {
           var inputElementId = "edit-" + fieldName + "-0-value";
           // Check whether form is an instance of CKEDITOR.
           // TODO: find a more robust way of checking that.
           if($(inputElementId).hasClass("cke_1")) {
             // Special case for CKEDITOR body input.
             var $fieldContent = CKEDITOR.instances[inputElementId].getData();
           }
           else {
             var $fieldContent = $("#" + inputElementId).val();  
           }
           
           if($fieldContent != ""){
             var encryptedContent = sjcl.encrypt(dataKey, $fieldContent);
             if (!encryptedContent) {
               alert("[ERROR] Field " + fieldName + " could not have been encrypted.");
               return false;
             } 
             
             if($(inputElementId).hasClass("cke_1")) {
               // Special case for CKEDITOR body input.
               CKEDITOR.instances[inputElementId].setData("*encrypted*");
             }
             else {
               $("#" + inputElementId).val("*encrypted*");
             }

             encryptedFields[fieldName] = encryptedContent;
           }
           
         });

         var node = buildNodeObject();
         createNodeThroughRest(node, dataKey);
        }
      });
    }
  };

  // Create entries in the encrypted_fields table.
  function createEnryptedFieldsThroughRest(encryptionContainerId, nid) {
    var encryptedFieldsJSON = {
      encryption_container_id: encryptionContainerId,
      encrypted_fields: []
    };
    
    // Encrypt fields and push them into an object.
    Object.keys(encryptedFields).forEach(function(fieldName, index) {
       encryptedFieldsJSON['encrypted_fields'].push({ field_name: fieldName, encrypted_content: encryptedFields[fieldName] });
    });
    
    // Call REST resource for creating encrypted fields.
    $.ajax({
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': getRestToken()
      },
      type: "POST",
      url: "/client_encryption/encrypted_fields?_format=json",
      data: JSON.stringify(encryptedFieldsJSON),
      success: function(data) {
         window.location = "/node/" + nid;
      },
      dataType: "json"
    });
  }
  
  // Build and return a node object based on form's fields.
  function buildNodeObject(submitButton) {
    var nodeObject = {
      type: [{ target_id: nodeType }]
    };
    
    $(nodeFieldsFiltered).each( function(index, fieldName) {
        // Special case for body (summary and actual body + CKeditor fix).
        if(fieldName == "body") {
          nodeObject["body"] = [{ summary : $("#edit-body-0-summary").val(), value : CKEDITOR.instances['edit-body-0-value'].getData() }];
        } 
        else {
          if ($("#edit-" + fieldName + "-0-value").val() != "") {
            nodeObject[fieldName] = [{ value : $("#edit-" + fieldName + "-0-value").val() }];
          }
        }
    });

    return nodeObject;
  }
  
  // Retrieve and parse encryption container's ttstring into JavaScript object.
  function getEncryptionContainer(encryptedFields) {
      $.get("/client_encryption/encryption_container/" + nodeType + "/" + nodeId + "?_format=json", function(data) {
        var encryptionContainer = JSON.parse(data);
        decryptFields(encryptedFields, encryptionContainer);
      });
  }
  
  // Retrieve field from the database and decrypt them.
  function decryptFields(encryptedFields, encryptionContainers) {
    var ownContainer = encryptionContainers[uid];
    var privateKeyObject = new sjcl.ecc.elGamal.secretKey(
      sjcl.ecc.curves.c256,
      sjcl.ecc.curves.c256.field.fromBits(sjcl.codec.hex.toBits(privateKey))
    );

    var decryptionError = false;
    var dataKey = sjcl.decrypt(privateKeyObject, ownContainer);
    var inputs = getFieldIds(fieldsPolicy);
    
    // Go through encrypted fields returned from the REST resource.
    Object.keys(encryptedFields).forEach(function(key, index) {
      var encrypted = encryptedFields[key];
      if (formType == "edit") {
        // When editing a node: replace fields' values.
        $("#edit-" + key + "-0-value").val(sjcl.decrypt(dataKey, encrypted));  
      } 
      else if (formType == "view") {
        // When viewing a node: replace elements' html.
        var decryptedData = sjcl.decrypt(dataKey, encrypted);
        
        if (!decryptedData) {
          decryptionError = key;
        }
        $(".field--name-" + key + ":first").html(decryptedData);    
      } 
    });
    
    if (decryptionError) {
      alert("[" + decryptionError + "] Error with decryption!");
      return false;
    }
    
  }
  
  // Make a POST request with previously generated encryption container.
  function createEncryptionContainerThroughRest(dataKey, publicKeys, nid) {
     var encryptionContainer = {};
     Object.keys(publicKeys).forEach(function(key, index) {
       var publicKeyObject = new sjcl.ecc.elGamal.publicKey(
         sjcl.ecc.curves.c256,
         sjcl.codec.hex.toBits(publicKeys[key])
       );
       encryptionContainer[key] = sjcl.encrypt(publicKeyObject, dataKey);
     });

     var payload = { entity_id: nid, entity_type: nodeType, encrypted_data_keys: JSON.stringify(encryptionContainer) };
     $.ajax({
       headers: {
         'Accept': 'application/json',
         'Content-Type': 'application/json',
         'X-CSRF-Token': getRestToken()
       },
       type: "POST",
       url: "/client_encryption/encryption_container?_format=json",
       data: JSON.stringify(payload),
       success: function(data) {
         createEnryptedFieldsThroughRest(data, nid);
       },
       dataType: "json"
     });
  }

  // Call Drupal's API to create a new node with encrypted fields.
  function createNodeThroughRest(node, dataKey) {
    $.ajax({
      url: '/entity/node?_format=json',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getRestToken()
      },
      data: JSON.stringify(node),
      success: function(data) { getPublicKeys(dataKey, data) },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        console.log("Status: " + textStatus);
        console.log("Error: " + errorThrown);
      }
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

 // Get ECC public keys for all users.
 function getPublicKeys(dataKey, node) {
    var nid = node.nid[0]["value"];
    $.get("/client_encryption/keys/all?_format=json", function (data) {
      var keys = JSON.parse(data);
      var publicKeys = {};
      for (var uid in keys) {
        publicKeys[uid] = keys[uid];
      }
      createEncryptionContainerThroughRest(dataKey, publicKeys, nid);
    });
 }

 // Genereate data-key by hashing all fields which are encrypted and append a random number (1-50000).
 function generateHash(string) {
   var hash = sjcl.hash.sha256.hash(string);
   return sjcl.codec.hex.fromBits(hash);
 }

 // Get all inputs after applying some filters on the node submit form.
 function getFieldIds(fieldsPolicy) {
    var contentFields = [];
    $(fieldsPolicy).each(function(index, fieldName) {
      var fieldId = "#edit-" + fieldName + "-0-value";
      if (!$(fieldId).hasClass("ui-autocomplete-input")) {
        contentFields.push(fieldId);
      }
    });
    
    return contentFields;
  }

})(jQuery, Drupal, CKEDITOR)
