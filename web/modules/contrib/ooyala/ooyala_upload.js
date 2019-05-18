// $Id$

/**
 * @file
 * Ooyala upload widget JavaScript code.
 */

function onProgress(event) {
  $('#ooyala-upload-progress').empty().append( (parseInt(event.ratio * 100) ) + '%');
} 

function onUploadComplete() {
  $('#ooyala-upload-progress').empty().append(Drupal.t('Finished'));
  $('#ooyala-upload-progress').parent().find('input').value(Drupal.settings.ooyala_final_embedCode);
  $('.ooyala_button_container').css('background-color', 'lightgreen');
} 

function onUploadError(text) { 
  $('#ooyala-upload-progress').empty().append('Upload Error: ' + text);
}

function onEmbedCodeReady(embedCode) { 
  $('.ooyala_embed_code_input').val(embedCode);
} 

var ooyala_process_upload = function() {
  $(this).parent('.ooyala_button_container').find('input[type=text]').addClass('ooyala_embed_code_input');
  try { 
    var formvalues = $(this).parents('form').serializeArray();
    $.post(
      '/ooyala/js/' + Drupal.settings.ooyala_upload_input_id,
      formvalues,
      function(data, textStatus) {
        ooyalaUploader.setTitle(data.title);
        ooyalaUploader.setParameters(data.parameters.signature);
          var errorText = ooyalaUploader.validate();

          if (errorText) {
            alert(errorText);
            return false;
          }
          ooyalaUploader.upload();
      },
      'json'
    );
  }
  catch(e) {
    alert(e);
  }
}


function onOoyalaUploaderReady()  { 

  ooyalaUploader.addEventListener('progress', 'onProgress');  
  ooyalaUploader.addEventListener('complete', 'onUploadComplete');  
  ooyalaUploader.addEventListener('error', 'onUploadError');  
    ooyalaUploader.addEventListener('embedCodeReady', 'onEmbedCodeReady');  
  
  $('#ooyala_upload_button').attr('disabled', false).click(ooyala_process_upload);
}