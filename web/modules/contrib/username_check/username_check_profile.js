
(function ($) {

Drupal.behaviors.profileCheck = {
  attach: function (context) {
    $('#profile-check-informer:not(.profile-check-processed)', context)
    .each(function() {
      var input = $(this).parents('.form-item').find('input');

      Drupal.profileCheck.profile = '';
      input
      .keyup(function () {
        if(input.val() != Drupal.profileCheck.profile) {
          clearTimeout(Drupal.profileCheck.timer);
          Drupal.profileCheck.timer = setTimeout(function () {Drupal.profileCheck.check(input)}, parseFloat(drupalSettings.profileCheck.delay)*1000);

          if(!$("#profile-check-informer").hasClass('profile-check-informer-progress')) {
            $("#profile-check-informer")
              .removeClass('profile-check-informer-accepted')
              .removeClass('profile-check-informer-rejected');
          }

          $("#profile-check-message")
            .hide();
        }
      })
      .blur(function () {
        if(input.val() != Drupal.profileCheck.profile) {
          Drupal.profileCheck.check(input);
        }
      });
    })
    .addClass('profile-check-processed');
  }
};

Drupal.profileCheck = {};
Drupal.profileCheck.check = function(input) {
  clearTimeout(Drupal.profileCheck.timer);
  Drupal.profileCheck.profile = input.val();

  $.ajax({
    url: drupalSettings.profileCheck.ajaxUrl,
    data: {profile: Drupal.profileCheck.profile},
    dataType: 'json',
    beforeSend: function() {
      $("#profile-check-informer")
        .removeClass('profile-check-informer-accepted')
        .removeClass('profile-check-informer-rejected')
        .addClass('profile-check-informer-progress');
    },
    success: function(ret){
      if(ret['allowed']){
        $("#profile-check-informer")
          .removeClass('profile-check-informer-progress')
          .addClass('profile-check-informer-accepted');

        input
          .removeClass('error');
      }
      else {
        $("#profile-check-informer")
          .removeClass('profile-check-informer-progress')
          .addClass('profile-check-informer-rejected');

        $("#profile-check-message")
          .addClass('profile-check-message-rejected')
          .html(ret['msg'])
          .show();
      }
    }
   });
}

})(jQuery);
