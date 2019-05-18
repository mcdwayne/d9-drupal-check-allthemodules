(function($) {
  function switchExpandableContent(trigger) {
    var expandableContainer = trigger.closest($('.c-expandable-item'));
    var expandableWrapper = $(expandableContainer).find('.c-expandable-item__wrapper');
    var expandableContent = $(expandableContainer).find('.c-expandable-item__content');

    if (expandableContainer.hasClass('is-expanded')) {
      expandableContainer.removeClass('is-expanded');
      expandableWrapper.height(0);
    } else {
      expandableContainer.addClass('is-expanded');
      expandableWrapper.height(expandableContent.outerHeight(true));
    }
  }

  $(document).ready(function () {
    $(document).off('click', '.js-expand-trigger')
      .on('click', '.js-expand-trigger', function (e) {
        e.preventDefault();
        switchExpandableContent($(this));
      });
  });
})(jQuery);
