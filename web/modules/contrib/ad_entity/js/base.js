/**
 * @file
 * Initially builds the base for the adEntity object, including settings.
 */

(function (window, document) {

  var settingsElement = document.getElementById('ad-entity-settings');

  if (!(typeof window.adEntity === 'object')) {
    window.adEntity = {settings: {}, helpers: {}, queue: []};
  }
  else {
    window.adEntity.settings = {};
    window.adEntity.helpers = window.adEntity.helpers || {};
    window.adEntity.queue = window.adEntity.queue || [];
  }

  if (settingsElement !== null) {
    window.adEntity.settings = JSON.parse(settingsElement.textContent);
  }

  window.adEntity.usePersonalization = function () {
    return false;
  };

}(window, window.document));
