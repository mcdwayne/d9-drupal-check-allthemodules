/**
 * @File
 * Javascript for the Admin Feedback module.
 */

(function ($, Drupal, window) {
  $( document ).ready(function() {
    $(".feedback-webform-button").click(function (event) {
      $('#edit-feedback-msg').val('');
      $('#edit-feedback-send').prop('disabled', true);
      $('#edit-feedback-msg').keyup(function () {
        if ($(this).val() != '') {
          $('#edit-feedback-send').prop('disabled', false);
          $('#edit-feedback-send').css('cursor', 'pointer');
        } else if ($(this).val() == '') {
          $('#edit-feedback-send').prop('disabled', true);
          $('#edit-feedback-send').css('cursor', 'not-allowed');
        }
      });

      $('#admin-feedback-form').css('display', 'inline-block');
      var thankYouMsg = '';
      var requestMsg = '';
      var feedback = event.target.id;
      var feedback = feedback.split("/");
      var langCode = drupalSettings.path.currentLanguage;
      if (feedback[0] == 'yes') {
        thankYouMsg = Drupal.t('Great! Thank you for the feedback.');
        requestMsg = Drupal.t('If you\'d like, give us more feedback.');
        $('#upper_feedback_content').replaceWith('<h2 class="feedback_webform_upper_text">' + thankYouMsg + '</h2><h3 class="feedback_webform_lower_text">' + requestMsg + '</h3>');
      } else if (feedback[0] == 'no') {
        thankYouMsg = Drupal.t('Thank you for the feedback.');
        requestMsg = Drupal.t('If you\'d like, give us more feedback.');
        $('#upper_feedback_content').replaceWith('<h2 class="feedback_webform_upper_text">' + thankYouMsg + '</h2>' +
          '<h3 class="feedback_webform_lower_text">' + requestMsg + '</h3>');
      }
      $.ajax({
        type: "POST",
        url: "/feedback_vote",
        data: {"vote": feedback[0], "node_id": feedback[1]},
        success: function (data) {
          $('#feedback_id').val(data[0]);
        },
      });
    });
  });
})(jQuery, Drupal, window);