(function($) {
  // activate tabs
  function initTabs(tabs) {
    $.each(tabs, function (index, item) {
      setTabSliderPosition(item);
    });
  }

  // set tab slider position
  function setTabSliderPosition(tabsContainer) {
    var activeTab = $(tabsContainer).find('.c-tab.is-active');
    $(tabsContainer).find('.c-tabs__slider').css({
      left: activeTab.position().left,
      width: activeTab.outerWidth()
    });
  }

  // switch active tab
  function switchActiveTab(newActiveTab) {
    var tabsContainer = newActiveTab.closest($('.c-tabs'));
    $(tabsContainer).find('.c-tab.is-active').removeClass('is-active');
    newActiveTab.addClass('is-active');
    setTabSliderPosition(tabsContainer);
  }

  function whitekitTabsInit() {
    var tabs = $('.c-tabs');

    if (tabs) {
      initTabs(tabs);
      $('.c-tab').on('click', function (e) {
        e.preventDefault();
        switchActiveTab($(this));
      });
    }
  }

  document.whitekitTabsInit = whitekitTabsInit;

  // document.ready
  $(document).ready(function () {
    whitekitTabsInit();
  });
})(jQuery);
