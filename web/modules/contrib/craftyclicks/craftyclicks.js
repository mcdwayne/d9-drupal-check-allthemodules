var formElements = new Object();
//add elements as jquery selectors
formElements.postcode = $("#postcode");
formElements.company = "#companyname";
formElements.address1 = "#address1";
formElements.town = "#town";
formElements.button = $("#postcode_lookup");

formElements.error_msg = "#error_msg";

crafty_lookup(formElements);


/**
 * @file
 * Javascript for Field Example.
 */

/**
 * Provides a farbtastic colorpicker for the fancier widget.
 */
(function ($) {

  'use strict';

  Drupal.behaviors.craftyclicks = {
    attach: function () {
      var formElements = new Object();
      //add elements as jquery selectors
      formElements.postcode = $(".craftyclicks");
      formElements.company = "#companyname";
      formElements.address1 = "#address1";
      formElements.town = "#town";
      formElements.button = $("#postcode_lookup");

      formElements.error_msg = "#error_msg";

      crafty_lookup(formElements);

    }
  };
})(jQuery);




function crafty_lookup(elements){
  $(elements.postcode).autocomplete({
    source: function( request, response ) {
      if(enable_search){
        $.ajax({
          url: "https://pcls1.craftyclicks.co.uk/json/rapidaddress",
          dataType: "jsonp",
          data: {
            postcode: $(elements.postcode).val(),
            response: 'data_formatted',
            lines: '2',
            sort: 'asc',
            key: "xxxxx-xxxxx-xxxxx-xxxxx"
          },
          success: function(data) {
            if(data.error_code != undefined){
              //handle error message
              alert(data.error_msg);
            }
            else{
              var clear_data = new Array();
              $.each( data.delivery_points, function( index, item ) {
                var fullLabel = item.department_name;

                if(fullLabel != '' && item.organisation_name != '')
                  fullLabel = fullLabel + ', ' + item.organisation_name;
                else
                  fullLabel = fullLabel + item.organisation_name;

                if(fullLabel != '' && item.line_1 != '')
                  fullLabel = fullLabel + ', ' + item.line_1;
                else
                  fullLabel = fullLabel + item.line_1;

                if(fullLabel != '' && item.line_2 != '')
                  fullLabel = fullLabel + ', ' + item.line_2;
                else
                  fullLabel = fullLabel + item.line_2;

                clear_data.push({
                  town: data.town,
                  postcode: data.postcode,
                  pcounty: data.postal_county,
                  tcounty: data.traditional_county,
                  dep_name: item.department_name,
                  line_1: item.line_1,
                  line_2: item.line_2,
                  org: item.organisation_name,
                  udprn: item.udprn,
                  label: fullLabel,
                  value: data.postcode
                });
              });
              response( clear_data );
            }
          }
        });
        enable_search = 0;
      }
    },
    minLength: 0,
    select: function( event, ui ) {

      $(elements.town).val(ui.item.town);
      if(ui.item.line_1!='' && ui.item.line_2 != '')
        $(elements.address1).val(leading_caps(ui.item.line_1 +', '+ ui.item.line_2));
      else
        $(elements.address1).val(leading_caps(ui.item.line_1 + ui.item.line_2));

      $(elements.postcode).val(ui.item.postcode);
      $(elements.company).val(ui.item.org);

      // do any action on selecting a result
    },
    open: function() {
      // do any action on showing the results
    },
    close: function() {
      $(elements.postcode).autocomplete({ disabled: true });
      $(elements.postcode).blur();

      // do any action on closing the results
    }
  });
  $(elements.postcode).autocomplete({ disabled: true });

  $(elements.button).on('click', function(){
    enable_search = 1;
    $(elements.postcode).autocomplete({ disabled: false });
    $(elements.postcode).autocomplete("search", "");
  });

  $(elements.postcode).on('keydown',function(){
    $(elements.postcode).autocomplete({ disabled: true });
    $('.ui-autocomplete').css('display','none');
  });
}
function leading_caps(a){if(2>a.length)return a;for(var b="",c=a.split(" "),d=0;d<c.length;d++){var e=this.str_trim(c[d]);""!=e&&(""!=b&&(b+=" "),b+=this.cp_uc(e))}return b}function str_trim(a){for(var b=0,c=a.length-1;b<a.length&&" "==a[b];)b++;for(;c>b&&" "==a[c];)c-=1;return a.substring(b,c+1)}function cp_uc(a){if("PC"==a||"UK"==a||"EU"==a)return a;for(var b="ABCDEFGHIJKLMNOPQRSTUVWXYZ",c="",d=1,e=0,f=0;f<a.length;f++)-1!=b.indexOf(a.charAt(f))?d||e?(c+=a.charAt(f),d=0):c+=a.charAt(f).toLowerCase():(c+=a.charAt(f),f+2>=a.length&&"'"==a.charAt(f)?d=0:"("==a.charAt(f)?(close_idx=a.indexOf(")",f+1),close_idx>f+3?(e=0,d=1):e=1):")"==a.charAt(f)