/**
 * @file
 *  get sharebar code and hoook
 */
(function ($) {
  'use strict';
  Drupal.behaviors.socialsharebar = {
    attach: function (context, settings) {
      var sharebar_button = drupalSettings.sharebardata.buttons;
      var sharebar_button_small = drupalSettings.sharebardata.buttons_small;
      var sharevar_width = drupalSettings.sharebardata.width + 'px';
      var sharevar_position = drupalSettings.sharebardata.position;
      var topoffset = drupalSettings.sharebardata.top_offset + 'px';
      var leftoffset = drupalSettings.sharebardata.left_offset + 'px';
      var rightoffset = drupalSettings.sharebardata.right_offset + 'px';
      if (sharevar_position === 'left') {
        rightoffset = 'auto';
      }
      else {
        leftoffset = 'auto';
      }
      jQuery('#shareBarDiv').css('top', topoffset);
      jQuery('#shareBarDiv').css('left', leftoffset);
      jQuery('#shareBarDiv').css('right', rightoffset);
      jQuery('#shareBarDiv').css('width', sharevar_width);
      jQuery('#shareBarDiv').css('float', sharevar_position);
      jQuery('#shareBarDiv').css('display', 'block');
      jQuery('#shareBarDiv').html(sharebar_button);
      jQuery('#mobileShareBarDiv').html(sharebar_button_small);
      if (jQuery(window).width() >= 992) {
        jQuery('#shareBarDiv').show();
        jQuery('#mobileShareBarDiv').hide();
      }
      else {
        jQuery('#shareBarDiv').hide();
        jQuery('#mobileShareBarDiv').show();
      }

      jQuery(window).resize(function () {
        if (jQuery(window).width() >= 992) {
          jQuery('#shareBarDiv').show();
          jQuery('#mobileShareBarDiv').hide();
        }
        else {
          jQuery('#shareBarDiv').hide();
          jQuery('#mobileShareBarDiv').show();
        }
      });

      // On scroll event decide what to do.
      jQuery(window).scroll(function () {
        if (jQuery(window).width() >= 992) {
          jQuery('#shareBarDiv').css('position', 'fixed').css('right', rightoffset).css('top', topoffset).css('left', leftoffset);
        }
      });
    }
  };
}(jQuery));
