(function ($) {
  "use strict";
  jQuery(document).ready(function(){
    var swiper = new Swiper('.swiper-container', {
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      loop: true,
    });
  });
})(jQuery);
