/**
 * @file
 * Fractionslider js.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.fractionslider = {
    attach: function (context, settings) {

      if (drupalSettings.fractionslider && $('.block-fractionslider').length > 0) {
        cont = (drupalSettings.fractionslider.controls == 'false') ? false : true;
        pag = (drupalSettings.fractionslider.pager == 'false') ? false : true;
        fullwidth = (drupalSettings.fractionslider.fullwidth == 'false') ? false : true;
        responsive = (drupalSettings.fractionslider.responsive == 'false') ? false : true;
        increase = (drupalSettings.fractionslider.increase == 'false') ? false : true;
        pausehover = (drupalSettings.fractionslider.pausehover == 'false') ? false : true;

        $('.block-fractionslider .slider-wrapper .slider', context).fractionSlider({
          'fullWidth': fullwidth,
          'controls': cont,
          'pager': pag,
          'responsive': responsive,
          'dimensions': drupalSettings.fractionslider.dimensions,
          'increase': increase,
          'pauseOnHover': pausehover,
          'slideEndAnimation': true,
        });

      }

      if (drupalSettings.view_fs_fractionslider && $('.view .slider-wrapper .slider').length > 0) {
        cont = (drupalSettings.view_fs_fractionslider.controls == 'false') ? false : true;
        pag = (drupalSettings.view_fs_fractionslider.pager == 'false') ? false : true;
        fullwidth = (drupalSettings.view_fs_fractionslider.fullwidth == 'false') ? false : true;
        responsive = (drupalSettings.view_fs_fractionslider.responsive == 'false') ? false : true;
        increase = (drupalSettings.view_fs_fractionslider.increase == 'false') ? false : true;

        $('.view .slider-wrapper .slider', context).fractionSlider({
          'fullWidth': fullwidth,
          'controls': cont,
          'pager': pag,
          'responsive': responsive,
          'dimensions': drupalSettings.view_fs_fractionslider.dimensions,
          'increase': increase,
          'pauseOnHover': false,
          'slideEndAnimation': true,
        });

      }

    }
  };
})(jQuery, Drupal, drupalSettings);
