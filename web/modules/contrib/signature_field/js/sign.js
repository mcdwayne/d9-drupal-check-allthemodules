(function ($, window, Drupal, drupalSettings) {

    Drupal.behaviors.signature = {
        attach: function (context) {
            var canvas = document.querySelector("canvas");
            var signaturePad = new SignaturePad(canvas, {
                minWidth: 0.5,
                maxWidth: 2.5,
                backgroundColor: "rgb(250, 250, 250)",
                penColor: "rgb(66, 133, 244)",
                onEnd:function(){
                    $('#signature_data').val(signaturePad.toDataURL());
                    $('#signature_thumb').attr({ 'src':signaturePad.toDataURL()});
                    $('#edit-signed-value').attr({'checked':true});
                    $('#edit-submit').removeAttr('disabled');
                }
            });
                
            $('#signature_data').hide();
            $('#clear-sign').click(function(){
                var signaturePad;
                var canvas = document.querySelector("canvas");
                signaturePad = new SignaturePad(canvas,{
                 penColor: "rgb(66, 133, 244)",
                });
                signaturePad.clear();
                $('#signature_thumb').attr({ 'src':''});
				$('#signature_data').val('');
            });
            if ($('.sign-img').attr('src')) {
                $('.sign-canvas').hide();
                $('#signature_thumb').attr({ 'src':$('.sign-img').attr('src')});
                $('#clear-sign').addClass('chnge-canvas');
            }
            if ($('.chnge-canvas').length) {
                $('.chnge-canvas').click(function(){
                    $('#clear-sign').removeClass('chnge-canvas');
                    $('.sign-img').hide().attr({ 'src':''});
                    $('#signature_thumb').attr({ 'src':''});
                    $('.sign-canvas').show();
                });
            }
        
        },
        detach: function (context) {

        }
    };
    
})(jQuery, this, Drupal, drupalSettings);
