/**
 * @file
 * Behaviour for szentirashu_formatter.
 */

(function ($, Drupal) {
  'use strict';

  function referenceLoad(el) {
    if ($(el).hasClass('szentirashu-disabled')) {
      return false;
    }
    $(el).addClass('szentirashu-disabled');
    $(el).append(' <span class="szentirashu-loader"></span>');
    $.ajax({
      url: window.location.protocol + '//szentiras.hu/api/ref/' + $(el).data('reference') + '/' + $(el).data('translation'),
      context: el,
      error: function (jqXHR, textStatus, errorThrown) {
        $(this).children().last().remove();
        $(this).removeClass('szentirashu-disabled');
      },
      statusCode: {
        404: function () {
          $(this).replaceWith('<span class="szentirashu-reference">' + $(this).text() + '<span class="szentirashu-error">' + Drupal.t('The reference was not found (in the specified translation).') + '</span>');
        }
      }
    })
    .done(function (data) {
      $(this).replaceWith('<span class="szentirashu-reference">' + $(this).text() + '(' + data.translationAbbrev + ')</span>: ' + data.text);
    });
  }

  Drupal.behaviors.szentirashuReference = {
    attach: function (context, settings) {
      $('a[data-reference][data-translation][data-behavior="click"]').each(function () {
        $(this).click(function (event) {
          event.preventDefault();
          referenceLoad($(this));
        });
      });
      $('a[data-reference][data-translation][data-behavior="auto"]').each(function () {
        referenceLoad($(this));
      });
    }
  };

})(jQuery, Drupal);
