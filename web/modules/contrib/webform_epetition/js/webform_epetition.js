(function ($, Drupal) {
  Drupal.behaviors.webform_epetition = {
    attach: function (context, settings) {
      $('#lookup_rep', context).once('search').bind('click', function() {
        var postCode = $("input[name*='[ep_postcode]']").val();
        var dataType = $('#data_type').val();
        $('#results_rep').html('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
        $.ajax({url: "/webform_epetition/searchForRepresentatives/"+ postCode +"/"+ dataType +"/details", success: function(result){
            $('#results_rep').html(result);
          }});
        $.ajax({url: "/webform_epetition/searchForRepresentatives/"+ postCode +"/"+ dataType +"/emails", success: function(emails){
            $("input[name*='[ep_email_to]']").val(emails);
          }});
        $.ajax({url: "/webform_epetition/searchForRepresentatives/"+ postCode +"/"+ dataType +"/names", success: function(names){
            $("input[name*='[ep_names_list]']").val(names);
            // When exists add first name in array to campaign name field.
            if ( $( "#edit-campaign-ep-message-name" ).length ) {
              var namesList = names.split(':');
              var firstNameInList = namesList[0];
              if (firstNameInList.indexOf('Invalid') === -1) {
                var curr = $("#edit-campaign-ep-message-name").val();
                $("#edit-campaign-ep-message-name").val(curr + firstNameInList + ',');
              }
            }
          }});
      });
    }
  };
})(jQuery, Drupal);