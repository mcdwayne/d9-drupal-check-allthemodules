/*jslint bitwise: true, eqeqeq: true, immed: true, newcap: true, nomen: false,
 onevar: false, plusplus: false, regexp: true, undef: true, white: true, indent: 2
 browser: true */

/*global jQuery: true Drupal: true window: true ThemeBuilder: true */

(function ($) {

  Drupal.javascriptLibraries = Drupal.javascriptLibraries || {};

  Drupal.behaviors.javascriptLibraries = {
    attach: function (context) {

      if($('input[name="library_type"]').val() == 'external') {
        $('.form-item-js-file-upload').hide();
      }
      if($('#edit-library-type-file').attr('checked') == 'checked'){
        $('.form-item-js-file-upload').show();
      }
      $('input[name="library_type"]').change(function () {
        console.log($(this).val());
        if($(this).val() == 'external') {
          $('.form-item-js-file-upload').hide();
          $('#edit-library-type-file').attr('checked',0);
          $('#edit-library-type-external').attr('checked','checked');
        } else{
          $('.form-item-js-file-upload').show();
          $('#edit-library-type-external').attr('checked',0);
          $('#edit-library-type-file').attr('checked','checked');
        }
      })
    }
  };
}(jQuery));
