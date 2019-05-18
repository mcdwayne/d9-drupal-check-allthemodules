(function ($, Drupal) {
  $( document ).ready(function() {
    // Countdown
    var countDownDate = new Date($('#countdown').data('refresh-date')).getTime();
    var countx = setInterval(function() {
      var now = new Date().getTime();
      var distance = countDownDate - now;
      var days = Math.floor(distance / 86400000);
      var hours = Math.floor((distance % 86400000) / 3600000);
      var minutes = Math.floor((distance % 3600000) / 60000);
      var seconds = Math.floor((distance % 60000) / 1000);
      $('#countdown').html((days ? days + 'd ' : '') + (hours ? hours + 'h ' : '') + (minutes ? minutes + 'm ' : '') + seconds + 's ');
      if (distance < 0) {
        clearInterval(countx);
        $('#countdown').html(Drupal.t('expired'));
      }
    }, 1000);

		// Hide Error messages on Close button
		$('.error__message.close').click(function () {
		   $(this).parent().hide();
		});

		// Check window height then center content if not mobile and height lower than 650px
		function browserResize(){
			if (!(/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent)) && (($(window).height() > 800) || ($(window).width() > 768))) {
				$(".locker .login").css({
					"position" : "fixed",
					"top" : "50%",
					"left" : "50%",
					"transform" : "translate(-50%, -50%)"
				});
			}
			if (!(/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent)) && (($(window).height() < 801) || ($(window).width() < 769))) {
				$(".locker .login").css({
					"position" : "relative",
					"top" : "0",
					"left" : "0",
					"transform" : "translate(0, 0)"
				});
			}
		}

		$( window ).resize(function() {
			browserResize();
		});

		browserResize();

  });
})(jQuery);
