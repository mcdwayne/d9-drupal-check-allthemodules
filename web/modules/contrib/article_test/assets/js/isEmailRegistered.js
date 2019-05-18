(function ($, Drupal) {
  Drupal.behaviors.isEmailRegistered = {
    attach: function (context, settings) {
      $('.article-test-register-form', context).once('isEmailRegistered').on('submit', function (event) {
        var article_nid = $(this).find('input[name="article_nid"]').val();
        if ($(this).data('email-validated') != true) {
          event.preventDefault();
          var $form = $(this);
          var input = $(this).find('input[name="email"]')[0];
          input.setCustomValidity('');
          if(input.checkValidity()) {
            var email = $(this).find('input[type="email"]').val();
            $.get('/rest/session/token').done(function (data, textStatus, jqXHR) {
              $.post({
                url: "/role_paywall/isemailregistered?_format=json",
                beforeSend: function (req) {
                  req.setRequestHeader("X-CSRF-Token", data);
                },
                contentType: 'application/json',
                data: JSON.stringify({
                  email: email,
                  article_nid: article_nid
                })
              }).done(function (res) {
                var stringResponse = JSON.stringify(res);
                if (res.status == 200) {
                  input.setCustomValidity('');
                  $form.data('email-validated', true);
                  $form.submit();
                }
                else {
                  input.setCustomValidity(res.message);
                  input.reportValidity();
                  $form.data('email-validated', false);
                }
              });
            });
          }
        }
      });
      $('.article-test-register-form input[name="email"]', context).once('isEmailRegistered').on('keyup', function (event) {
        event.target.setCustomValidity('');
        $(this).closest('form').data('email-validated', false);
      });
    }
  };
})(jQuery, Drupal);
