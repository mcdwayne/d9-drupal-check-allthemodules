/**
 * @file
 * Default JavaScript file for Modal Page.
 */

(function ($, Drupal) {
  Drupal.behaviors.modalPage = {
    attach: function (context, settings) {

      var modalPage = $('#js-modal-page-show-modal');

      if (modalPage.length) {

        var modal_id = $('#js-modal-page-show-modal').attr("data-modal-id");
        var cookie_please_do_not_show_again = $.cookie(modal_id);

        if (!cookie_please_do_not_show_again) {
          modalPage.modal();
        }
      }

      var checkbox_please_do_not_show_again = $('.modal-page-please-do-not-show-again');
      var ok_buttom = $(".js-modal-page-ok-buttom");

      ok_buttom.on("click", function () {

        if (checkbox_please_do_not_show_again.is(":checked")) {

          var id_modal = checkbox_please_do_not_show_again.val();

          $.cookie(id_modal, 'please_do_not_show_again', {expires: 365 * 20});

        }
      });
    }
  };
})(jQuery, Drupal);
