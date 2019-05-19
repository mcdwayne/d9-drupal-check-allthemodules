/**
 * @file
 * Javascript for Field Example.
 */

/**
 * Provides a farbtastic colorpicker for the fancier widget.
 */
(function ($) {
    Drupal.behaviors.field_addresstw_zipcodetw = {
      attach:  function (context, settings) {
        
        var $context = $(context);

        $context.find('.addresstw_selection_wrapper').each(function (index, element) {
          var $element = $(element);
          var id = $element.attr('id').replace("div","edit");
          
          var county = $('#' + id + '-county').val();
          var district = $('#' + id + '-district').val();
          var zipcode = $('#' + id + '-zipcode').val();
          var $addressTwZipCode = $element.find('.address_twzipcode');

          $addressTwZipCode.twzipcode({
            'css': ['form-select twcounty', 'form-select twdistrict', 'form-text twzipcode'],
            'readonly': true,
          });
          if(county != "" || district != "" || zipcode != ""){
            $addressTwZipCode.twzipcode('set', {
              'county': county,
              'district': district,
              'zipcode': zipcode
            });

          }

          $element.on('change',function(){
            var county =  $(this).find('.address_twzipcode').twzipcode('get', 'county');
            var district =  $(this).find('.address_twzipcode').twzipcode('get', 'district');
            var zipcode =  $(this).find('.address_twzipcode').twzipcode('get', 'zipcode');
            $('#' + id + '-county').val(county);
            $('#' + id + '-district').val(district);
            $('#' + id + '-zipcode').val(zipcode);
          })


        });
      }
    }

  })(jQuery);
