(function ($) {
  Drupal.behaviors.powertagging = {
    attach: function () {

      if ($("form.powertagging-form").length > 0) {
        $("#edit-load-connection").change(function () {
          var connection_value =  $(this).val();
          if (connection_value.length > 0) {
            var connection_details = connection_value.split("|");
            $("#edit-server-title").val(connection_details[0]);
            $("#edit-url").val(connection_details[1]);
            $("#edit-username").val(connection_details[2]);
            $("#edit-password").val(connection_details[3]);
          }
          return false;
        });
      }

    }
  };
})(jQuery);
