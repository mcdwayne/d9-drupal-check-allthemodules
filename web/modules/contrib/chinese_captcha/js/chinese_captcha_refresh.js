(function ($) {
  Drupal.behaviors.chineseCaptchaRefresh = {
    attach: function (context) {
      $('.reload-captcha', context).not('.processed').bind('click', function () {
        var $this = $(this);
        $this.addClass('processed element-hidden');

        var $form = $(this).parents('form');
        // Send ajax query for getting a new captcha data.
        var date = new Date();
        var url = this.href + '?' + date.getTime();
        $.get(
          url,
          {},
          function (response) {
            $this.parent().find('.ajax-progress-throbber').remove();
            $this.removeClass('element-hidden');
            if (response.status == 1) {
              $('.captcha', $form).find('img').attr('src', response.data.url);
              $('input[name=captcha_sid]', $form).val(response.data.sid);
              $('input[name=captcha_token]', $form).val(response.data.token);
            }
            else {
              alert(response.message);
            }
          },
          'json'
        );
        return false;
      });
    }
  };
})(jQuery);
