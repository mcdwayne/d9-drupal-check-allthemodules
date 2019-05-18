/**
 * Toggle the aria-axpanded state of #admin-tabs__list when clicking on #js-admin-tabs-toggle.
 */
(function () {
  'use strict';
  document.getElementById('js-admin-tabs-toggle').onclick = function (e) {
    var adminTabs = document.getElementById('admin-tabs__list');
    adminTabs.setAttribute('aria-expanded', adminTabs.getAttribute('aria-expanded') === 'false' ? 'true' : 'false');
  };

}());
