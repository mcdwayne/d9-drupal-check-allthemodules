(function ($, Drupal, drupalSettings) {
  Drupal.MMLazyLoadingActiveLoad = false;

  Drupal.behaviors.MMLazyLoadNode = {
    attach: function () {
      $('.mm-lazy-load-max-page').val(1); // Make sure refreshed pages start out at zero
      $(window).scroll(function() {
        if (Drupal.MMLazyLoadScrollCheck($('.node:last')) && !Drupal.MMLazyLoadingActiveLoad) {
          Drupal.MMLazyLoadContent();
        }
      })
      .trigger('scroll');
    }
  };

  Drupal.MMLazyLoadScrollCheck = function (elem) {
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

    var elemTop = elem.offset().top;
    var elemBottom = elemTop + elem.height();

    return elemBottom >= docViewTop && elemTop <= docViewBottom;
  };

  Drupal.MMLazyLoadContent = function () {
    var maxPage = $('.mm-lazy-load-max-page');
    var page = parseInt(maxPage.val());
    if (page < drupalSettings.MM.max_pages) {
      Drupal.MMLazyLoadingActiveLoad = true;
      maxPage.before('<div id="node-loading"><img src="' + drupalSettings.MM.lazy_load_node.loading_img + '" width="24" height="24"> ' + Drupal.t('Loading additional content...') + '</div>');
      $.ajax({
        cache:   false,
        url:     drupalSettings.basePath + 'mm/' + drupalSettings.MM.lazy_load_node.mmtid + '/-2/render?page=' + page,
        success: function (data) {
          $('#node-loading').remove();
          maxPage.before(data);
          maxPage.val(page + 1);
          Drupal.MMLazyLoadingActiveLoad = false;
          if (Drupal.MMLazyLoadScrollCheck(maxPage.siblings('.node:last')) && !Drupal.MMLazyLoadingActiveLoad) {
            Drupal.MMLazyLoadContent();
          }
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);