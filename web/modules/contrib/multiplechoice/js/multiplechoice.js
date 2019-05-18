/**
 * @file
 * jQuery functions for multichoice
 */

(function($){
  // Allow only one checkbox to be checked per answer set
  Drupal.behaviors.multichoiceCorrectAnswer = {
    attach: function(context) {
      $('.correct-answer').click(function() {
        var id = $(this).attr('id');
        // What delta is this?
        var parent = $(this).parent().parent().parent().parent().parent();
        var classes = parent.attr('class').split(' ');
        var delta_class = classes[0];
        $('#id-' + delta_class + ' .correct-answer').attr('checked', false);
        $('#' + id).attr('checked', true);
      });

    }
  }
})(jQuery);
