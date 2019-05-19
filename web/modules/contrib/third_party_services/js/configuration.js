/**
 * @file
 * Third-party services configuration controller.
 */

// Use own settings object because Drupal can easily override
// the "drupalSettings" in any moment. For instance, after
// AJAX request, all settings will be re-initialized.
window.thirdPartyServices = window.thirdPartyServices || Object.create(null);

(function ($, settings, storage, config, id) {
  'use strict';

  config.allowed = [];
  config.markup = {};

  // Actual configuration came from backend. LocalStorage needs to be updated.
  if (settings.hasOwnProperty(id) && settings[id].length > 0) {
    // An array lays to localStorage separating by comma automatically.
    storage.setItem(id, settings[id]);
    config.allowed = settings[id];
  }
  else {
    var uuids = storage.getItem(id);

    config.allowed = Boolean(uuids) ? uuids.split(',') : [];
  }

  $('[data-placeholder-for][data-placeholder-type][data-delta]').each(function () {
    var $placeholder = $(this);
    var content = $placeholder.find('textarea.third-party-services--original-content').text();
    var data = $placeholder.data();

    if (config.allowed.length > 0 && config.allowed.indexOf(data.placeholderFor) >= 0) {
      $placeholder.html(content);
    }

    config.markup[data.placeholderType] = config.markup[data.placeholderType] || {};
    config.markup[data.placeholderType][data.placeholderFor] = config.markup[data.placeholderType][data.placeholderFor] || {};
    config.markup[data.placeholderType][data.placeholderFor][data.delta] = content;
  });
})(window.jQuery, window.drupalSettings, window.localStorage, window.thirdPartyServices, 'third_party_services_allowed');
