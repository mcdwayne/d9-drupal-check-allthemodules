(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.closeblock = {
    attach: function (context, settings) {
      var $blockSettings = settings.closeBlockSettings,
          $close_buttton = $('<span />').addClass('closeblock-button').html($blockSettings.close_block_button_text),
          $close_contaier = $('<div />').addClass('closeblock').append($close_buttton);

      $('.close-block').each(function () {
        if ($.cookie('closeblock-' + this.id)) {
          $('#' + this.id).hide();
        } else {
          $('.close-block').once().prepend($close_contaier);
        }
      });

      $('.closeblock-button').once().click(function () {
        switch ($blockSettings.close_block_type){
          case 'fadeOut':
            $(this).closest('.close-block').fadeOut($blockSettings.close_block_speed);
            break;
          case 'slideUp':
            $(this).closest('.close-block').slideUp($blockSettings.close_block_speed);
            break;
          default:
            $(this).closest('.close-block').hide();
            break;
        }
        $.cookie('closeblock-' + $(this).closest('.close-block').attr('id'), '1', { path: '/', expires: parseInt($blockSettings.reset_cookie_time) });
      });

      $('#closeblock-clear-cookie-button').once().click(function () {
        for (var $item in $.cookie()) {
          if ($item.indexOf('closeblock-') >= 0) {
            $.removeCookie($item, { path: '/' });
          }
        }
      });
    }
  }
}) (jQuery, Drupal);
