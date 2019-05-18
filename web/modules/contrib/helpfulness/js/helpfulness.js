/**
 * @file
 * JavaScript file for helpfulness module.
 */

(function ($) {

    $(document).ready(function () {
      var selected = $("input[type='radio'][name='helpfulness_rating']:checked");
      if (selected.length > 0) {
        $('.form-item-helpfulness-comments').css('display', 'block');
        $('.helpfulness_submit_button').css('display', 'block');
        if (selected.val() === '1') {
          $('.helpfulness_no_title').css('display', 'none');
          $('.helpfulness_yes_title').css('display', 'block');
          $('.helpfulness_no_description').css('display', 'none');
          $('.helpfulness_yes_description').css('display', 'block');
        }
        else {
          $('.helpfulness_yes_title').css('display', 'none');
          $('.helpfulness_no_title').css('display', 'block');
          $('.helpfulness_yes_description').css('display', 'none');
          $('.helpfulness_no_description').css('display', 'block');
        }
      }
    });

    $('input:radio[name=helpfulness_rating]').change(function () {
      var value = $(this).val();
      $('.form-item-helpfulness-comments').slideDown('slow');
      $('.helpfulness_submit_button').slideDown('slow');
      if (value === '1') {
        $('.helpfulness_no_title').hide('slow');
        $('.helpfulness_yes_title').slideDown('slow');
        $('.helpfulness_no_description').hide('slow');
        $('.helpfulness_yes_description').slideDown('slow');
      }
      else {
        $('.helpfulness_yes_title').hide('slow');
        $('.helpfulness_no_title').slideDown('slow');
        $('.helpfulness_yes_description').hide('slow');
        $('.helpfulness_no_description').slideDown('slow');
      }
    });

})(jQuery);
