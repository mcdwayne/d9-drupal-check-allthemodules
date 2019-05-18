jQuery(document).ready(function () {

  /*- Generic matchHeight class bind
    --------------------------------------------------------------------------  */
  jQuery(".js-matchHeight").matchHeight();


  /*- Smooth scrolling functionality
    --------------------------------------------------------------------------  */
  jQuery('a[href*="#"]')
  // Remove links that don't actually link to anything
  .not('[href="#"]')
  .not('[href="#0"]')
  .click(function(event) {
    // On-page links
    if (
      location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '')
      &&
      location.hostname == this.hostname
    ) {
      // Figure out element to scroll to
      var target = jQuery(this.hash);
      target = target.length ? target : jQuery('[name=' + this.hash.slice(1) + ']');
      // Is the fixed CTA present at the top of the page?
      var top_cta = jQuery('.cb__top-cta');
      var top_cta_height = top_cta.outerHeight();
      // Does a scroll target exist?
      if (target.length) {
        // Only prevent default if animation is actually gonna happen
        event.preventDefault();

        top_cta_height ? (target_pos = target.offset().top - (top_cta_height + 40)) : (target_pos = target.offset().top - 40);

        jQuery('html, body').animate({
          scrollTop: target_pos
        }, 1000, function() {
        });
      }
    }
  });


  /*- Background images
    --------------------------------------------------------------------------  */
  var $targets = jQuery('.js-background-image'); // I've added this custom class through the template
  // note the .js- prefix which maked it clear that the class is being utilised by JavaScript code somewhere


  $targets.each(function() { // Because multiple items are going to be selected, we need to write a function that loops
  // over all the items and does something with them, thus the use of array.each


  var _this = jQuery(this); // Make a selection on the CURRENT element in the loop (this). Because we need to do 2 calls in the
  // following 2 lines, this should be a performance improvement, although I am not 100% on this


  var $background_image_url = _this.attr('data-background-image'); // I also added this attribute through the template, and preprocessed
  // the value of the image URL in my PHP code, so that ALL that I have in my template is JUST the url of the image, no additional HTML


  _this.css("background-image", `url(${$background_image_url})`); // Because the element I've selected is DIV and not an IMG, I apply
  // the background image style to the DIV itself.


  // NOTE:  this code is slightly different to what you will have, as there is additional template and PHP work going
  // on behind the scenes, so it'll have to be adjusted.
  });

});