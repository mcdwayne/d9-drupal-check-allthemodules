jQuery(document).ready(function () {

  var $gallery = jQuery('.js-owl-carousel');

  $gallery.owlCarousel({
    items: 1,
    loop: true,
    autoplay: true,
    autoplayTimeout: 3000,
    autoplayHoverPause: true,
    responsiveClass:true,
    dots: true,
  });

  $gallery.addClass('owl-carousel');
});