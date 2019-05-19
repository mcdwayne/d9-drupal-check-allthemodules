
(function ($) {

Drupal.behaviors.mailCheck = {
  attach: function (context) {
    $('#mail-check-informer:not(.mail-check-processed)', context)
    .each(function() {
      var input = $(this).parents('.form-item').find('input');

      Drupal.mailCheck.mail = '';
      input
      .keyup(function () {
        if(input.val() != Drupal.mailCheck.mail) {
          clearTimeout(Drupal.mailCheck.timer);
          Drupal.mailCheck.timer = setTimeout(function () {Drupal.mailCheck.check(input)}, parseFloat(drupalSettings.usermailCheck.delay)*1000);

          if(!$("#mail-check-informer").hasClass('mail-check-informer-progress')) {
            $("#mail-check-informer")
              .removeClass('mail-check-informer-accepted')
              .removeClass('mail-check-informer-rejected');
          }

          $("#mail-check-message")
            .hide();
        }
      })
      .blur(function () {
        if(input.val() != Drupal.mailCheck.mail) {
          Drupal.mailCheck.check(input);
        }
      });
    })
    .addClass('mail-check-processed');
  }
};

Drupal.mailCheck = {};
Drupal.mailCheck.check = function(input) {
  clearTimeout(Drupal.mailCheck.timer);
  Drupal.mailCheck.mail = input.val();

  $.ajax({
    url: drupalSettings.usermailCheck.ajaxUrl,
    data: {mail: Drupal.mailCheck.mail},
    dataType: 'json',
    beforeSend: function() {
      $("#mail-check-informer")
        .removeClass('mail-check-informer-accepted')
        .removeClass('mail-check-informer-rejected')
        .addClass('mail-check-informer-progress');
    },
    success: function(ret){
      if(ret['allowed']){
        $("#mail-check-informer")
          .removeClass('mail-check-informer-progress')
          .addClass('mail-check-informer-accepted');

        input
          .removeClass('error');
      }
      else {
        $("#mail-check-informer")
          .removeClass('mail-check-informer-progress')
          .addClass('mail-check-informer-rejected');

        $("#mail-check-message")
          .addClass('mail-check-message-rejected')
          .html(ret['msg'])
          .show();
      }
    }
   });
}

})(jQuery);
