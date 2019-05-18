(function ($, Drupal, settings) {
  "use strict";
  
  Drupal.behaviors.dexp_image_browser = {
    attach: function (context, settings) {
      $('.image-browser', context).find('input.image-selector').once('click').each(function(){
        $(this).click(function(e){
          e.preventDefault();
          $('.image-browser').removeClass('active');
          $(this).closest('.image-browser').addClass('active');
          Drupal.ajax({
            url: Drupal.url('image_browser/browser'),
            dialog: {
              width: '80%',
              height: 'auto',
              title: Drupal.t('Select Image')
            },
            dialogType: 'modal'
          }).execute();
        });
      });
      $('.image-browser', context).find('input.image-remove').once('click').each(function(){
        $(this).click(function(e){
          e.preventDefault();
          $(this).closest('.image-browser').find('input.form-image-browser').val('file:0').trigger('update');
          $(this).closest('.image-browser').removeClass('has-image');
        });
      });
      $('ul.dexp-image-browser-tabs').find('a:first').once('click').each(function(){
        $(this).trigger('click');
      });
      $('.view-dexp-image-browser .views-col').once('click').each(function(){
        $(this).on('click', function(){
          $('.view-dexp-image-browser .views-col').find('input[type=checkbox]').prop('checked', false);
          $(this).find('input[type=checkbox]').prop('checked', true);
        });
      });
      $('.image-browser', context).find('input[type=hidden]').on('reload', function(){
        $('.image-browser').removeClass('active');
        $(this).closest('.image-browser').addClass('active');
        Drupal.ajax({
          url: Drupal.url('image_browser/update'),
          submit: {
            selector: '#' + $(this).parent().attr('id'),
            file: $(this).val()
          }
        }).execute();
      });
    }
  };
})(jQuery, Drupal, drupalSettings);