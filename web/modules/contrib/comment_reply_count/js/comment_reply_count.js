/**
 * @file
 * Javascript functions.
 */

(function ($) {
  'use strict';
  $(document).ready(() => {
    $('div.indented').css('display', 'none');
    $('article.comment .comment__content li.comment-show span.show-hide').click(function () {
      if ((this).innerHTML === 'Show') {
        (this).innerHTML = 'Hide';
        $(this).parents('article.comment').next().slideToggle();
      }
      else {
        (this).innerHTML = 'Show';
        $(this).parents('article.comment').next().slideToggle();
      }
    });
  });
}(jQuery));
