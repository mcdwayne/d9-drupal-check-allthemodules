/**
 * @File
 * Javascript for the Admin Feedback module.
 */

(function ($, Drupal, window) {
  $( document ).ready(function() {
    $('.inspected_feedback_chkbox').click(function (event) {
      var checkbox_id = event.target.id;
      // IF IS CHECKED
      if (this.checked) {
        $.ajax({
          type: "POST",
          url: "/feedback_inspected_check",
          data: {"feedback_id": checkbox_id},
          success: function (data) {
          },
        });
      }
      // IF IS NOT CHECKED
      if (!this.checked) {
        $.ajax({
          type: "POST",
          url: "/feedback_inspected_uncheck",
          data: {"feedback_id": checkbox_id},
          success: function (data) {
          },
        });
      }
    })
  });
})(jQuery, Drupal, window);