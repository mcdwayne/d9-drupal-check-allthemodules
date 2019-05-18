/**
 * @file
 * Role Expire js
 *
 * Set of jQuery related routines.
 */


(function ($) {

  Drupal.behaviors.role_expire = {
    attach: function (context, settings) {

      $('input.role-expire-role-expiry', context).parent().hide();

      $('#edit-roles input.form-checkbox', context).each(function() {
        var textfieldId = this.id.replace("roles", "role-expire");

        // Move all expiry date fields under corresponding checkboxes
        $(this).parent().after($('#'+textfieldId).parent());

        // Show all expiry date fields that have checkboxes checked
        if ($(this).attr("checked")) {
          $('#'+textfieldId).parent().show();
        }
      });

      $('#edit-roles input.form-checkbox', context).click(function() {

        var textfieldId = this.id.replace("roles", "role-expire");

        $('#'+textfieldId).parent().toggle();
      });
    }
  }

})(jQuery);
