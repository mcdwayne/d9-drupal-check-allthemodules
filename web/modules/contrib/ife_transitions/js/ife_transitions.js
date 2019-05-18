/**
 * @file
 * Attaches the behaviors for ife_transitions module.
 */

(function ($) {

  // Behaviors for the transitions.
  Drupal.behaviors.ife_transitions = {
    attach: function (context, settings) {
      // Error Links - Reference.
      var $error_link = '.messages__wrapper .messages--error a';

      // OnClick of error Hyper-Link.
      $($error_link).on('click', function(){
        // Prevent the default transition.
        //event.preventDefault();

        // Get the Target Href.
        var target = $($(this).attr('href'));

        // Make the transition.
        $('html, body').animate({scrollTop: target.offset().top}, drupalSettings.ife_transitions_time, function() {});

      });
    }
  }

  // Behaviors for Back to top feature.
  Drupal.behaviors.ife_transitions_back_to_top = {
    attach: function (context, settings) {
      // If Back to top addition is needed.
      if (drupalSettings.ife_transitions_back_to_top_text !== undefined) {
        $('body', context).append('<button id="ife_transitions_back_to_top" class="ife_transitions_back_to_top" title="' + drupalSettings.ife_transitions_back_to_top_text + '">' + drupalSettings.ife_transitions_back_to_top_text + '</button>');
      }

      // On scroll down, Show back to top button. Else, Hide.
      $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
          $('.ife_transitions_back_to_top').show();
        }
        else {
          $('.ife_transitions_back_to_top').hide();
        }
      });

      // scroll body to top on click.
      $('.ife_transitions_back_to_top').click(function () {
        $('body,html').animate({
          scrollTop: 0
        }, drupalSettings.ife_transitions_back_to_top_time);
        return false;
      });

    }
  }

})(jQuery, Drupal, drupalSettings);
