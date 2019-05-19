/**
 * @file
 * Put all your page JS here.
 */

(function ($, Drupal) {
  Drupal.behaviors.SlickQuiz = {
    attach: function (context, settings) {
      var quizConf = settings.DrupalSlickQuiz.slick_conf;
      var quizConfig = JSON.parse(quizConf);
      $('#slickQuiz').slickQuiz({json: quizConfig});
    }
  };
})(jQuery, Drupal);
