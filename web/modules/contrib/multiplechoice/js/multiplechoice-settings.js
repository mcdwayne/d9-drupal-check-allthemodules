/**
 * @file
 * jQuery functions for multichoice
 */

(function($){
  // Save settings
  Drupal.behaviors.multiplechoiceSettings = {
    attach: function(context) {

      $('#multiplechoice-settings-submit').click(function(event) {
        event.preventDefault();

        var takes = $('#edit-takes').val();
        var quiz_open = $('#edit-quiz-open').val();
        var quiz_close = $('#edit-quiz-close').val();
        var pass_rate = $('#edit-pass-rate').val();
        var backwards_navigation = $('#edit-backwards-navigation').val();
        $.ajax({
          url: drupalSettings.path.baseUrl + 'ajax/multiplechoice',
          data: {
            pass_rate: pass_rate,
            backwards_navigation: backwards_navigation,
            nid: $('#edit-entity-id').val(),
            vid: $('#edit-revision-id').val(),
            takes: takes,
            quiz_open: quiz_open,
            quiz_close: quiz_close
          },
          error: function (XMLHttpRequest, textStatus) {
            // Disable error reporting to the screen.
          },
          success: function(data) {
            $('#edit-settings--content').find('save-msg').remove();
            $('#edit-settings--content').append('<div class="save-msg">Settings saved</div>');
          }
        });
      });

    }
  }
})(jQuery);
