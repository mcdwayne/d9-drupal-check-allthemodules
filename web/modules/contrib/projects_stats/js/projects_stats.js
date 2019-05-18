(function ($) {
  'use strict';
  $(document).ready(function () {
    if (drupalSettings.collapsibleList === false) {
      return;
    }

    $('.block-projects-stats_projects-group').hide();

    $('.block-projects-stats__type > a').on('click', function (e) {
      e.preventDefault();

      var isChildVisible = $(this).parent().children('.block-projects-stats_projects-group').is(':visible');
      if (isChildVisible) {
        $(this).parent().children('.block-projects-stats_projects-group').slideUp();
        $(this).parent().removeClass('active');
      }
      else {
        $(this).parent().children('.block-projects-stats_projects-group').slideDown();
        $(this).parent().addClass('active');
      }
    });
  });
})(jQuery);
