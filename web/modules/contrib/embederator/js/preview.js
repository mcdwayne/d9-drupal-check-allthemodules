/**
 * Label highlighting
 */
(function($) {
  $(document).on('focus', '.embederator-token-form input', function (e) {
    var $target = $(e.target);
    var $wrapper = $target.closest('.form-wrapper');
    var token = $wrapper.data('embederator-token');
    var $form = $target.closest('.embederator-token-form');
    var $preview = $form.find('.embederator__preview--unhighlighted');
    var markup = $preview.html();
    markup = markup.replace(token, '<span class="highlighted">' + token + '</span>');
    var $newmarkup = $('<div class="embederator__preview--highlighted">');
    $newmarkup.html(markup);
    $preview.hide();
    $form.find('.embederator__preview--highlighted').remove();
    $preview.parent().append($newmarkup);
  });
})(jQuery);
  