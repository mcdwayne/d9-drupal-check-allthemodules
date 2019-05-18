(function ($) {
Drupal.behaviors.cctags_admin = {
   attach: function (context, settings) {

   $('form').once('cctags-processed', function () {
      $('.cctags-voc',context).each( function(value) {
           var vocCheckbox = $(this).find('.form-checkbox');
           var vocLevels = $(this).parent().find('.cctags-level');
           vocLevels.each(function (value) {
              (vocCheckbox.is(':checked'))?$(this).find('.form-checkbox').attr('disabled',false):$(this).find('.form-checkbox').attr('disabled',true);
           }
          );
          vocCheckbox.change(function(value) {
           ($(this).is(':checked'))?$(vocLevels).find('.form-checkbox').attr('disabled',false):$(vocLevels).find('.form-checkbox').attr('disabled',true).attr('checked',false);
           });
       });

         var blockNotify = $('#edit-cctags-block',context);
         blockNotify.not('.cctags-processed').each(function () {
         var blockCheckbox = $(this).find('.form-checkbox:first');
         var blockInfo = $(this).find('.cctags-settings-block');
          (blockCheckbox.is(':checked'))? blockInfo.show(): blockInfo.hide();
          blockCheckbox.change(function(value) {
          ($(this).is(':checked')) ? blockInfo.show() : blockInfo.hide();
         });
    });

    var pageNotify = $('#edit-cctags-page',context);
    pageNotify.not('.cctags-processed').each(function () {
    var pageCheckbox = $(this).find('.form-checkbox:first');
    var pageInfo = $(this).find('.cctags-settings-page');
    (pageCheckbox.is(':checked'))? pageInfo.show(): pageInfo.hide();
          pageCheckbox.change(function(value) {
          ($(this).is(':checked')) ? pageInfo.show() : pageInfo.hide();
         });
    });
     }
    );
   }
  }
})(jQuery);
