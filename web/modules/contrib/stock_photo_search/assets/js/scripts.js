/**
 * @file
 */
(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.stock_photo_search = {

    attach: function (context, settings) {

      /* Click on search button */
      $('.btnStockPhotoSearchSelect').click(function(e) {
        e.preventDefault();

        var idInput = $(this).attr('tag');
        var urlImage = $('select[id=' + idInput + ']').val();
        var idImage = $('select[id=' + idInput + ']').attr('tagIdPhoto');

        if (null != urlImage) {
          var txtOrigin = $.cookie('stock_photo_search.txtOrigin');
          $('#' + txtOrigin).val(urlImage + ' [' + idImage + ']');
          $('.ui-icon-closethick').trigger('click');
        }
      });

      /* Click on open modal button */
      $('.btn_open_modal').click(function(e) {
        var txtOrigin = $(this).attr('id').replace("btn-open-modal", "value");
        $.cookie('stock_photo_search.txtOrigin', txtOrigin);
      });
    }
  }

})(jQuery, Drupal);
