(function ($, Drupal) {
  Drupal.visualnData.drawers.visualnSlickGalleryBasicDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];
    var html_selector = drawing.html_selector;
    //$('.' + html_selector).append('<div width="960" height="500">');
    var data = drawing.resource.data;
    var slick_gallery_selector = '.' + html_selector;

    var controls_color = drawing.drawer.config.controls_color;
    var slide_content = drawing.drawer.config.slide_content;
    var show_dots = drawing.drawer.config.show_dots;


    // prepare slick setup
    var slick_setup = {
      dots : show_dots,
    };

    var slick_content = '';
    switch (slide_content) {
      case 'image_url':
        data.forEach(function(d){
          slick_content += '<div><img src="' + d.url + '" /></div>';
        });
        break;
      case 'html':
        data.forEach(function(d){
          slick_content += '<div>' + d.html + '</div>';
        });
        break;
    }


    var slick_id = html_selector + '--slick-id';
    $(slick_gallery_selector).append('<div id="' + slick_id + '">' + slick_content + '</div>');

    // @todo: Here a small trick is used to set arraows color. It set to the wrapping element,
    //   not the array themselves since those are implemented as ':before' pseudo-elements
    //   and can't be manipulated directly by js.
    //   For slick, prevArrow and nextArrow properties with custom css styles can be used
    //   to change markup used for arrows and make them directly accessible for js.
    $('#' + slick_id).on('init', function(){
      $('#' + slick_id + ' .slick-prev').css('color', controls_color);
      $('#' + slick_id + ' .slick-next').css('color', controls_color);
      $('#' + slick_id + ' .slick-dots li button').css('color', controls_color);
    });

    // init slick gallery
    $('#' + slick_id).slick(
      slick_setup
    );


  };

})(jQuery, Drupal);
