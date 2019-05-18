(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.media_entity_consent = {
    attach: function (context, settings) {

      // Attach libraries for people that are allowed to bypass.
      // We need to do it in js, because at first we want to suppress the libraries.
      if (drupalSettings.mediaEntityConsent.bypass !== undefined) {
        drupalSettings.mediaEntityConsent.bypass.forEach(function (type) {
          Drupal.behaviors.media_entity_consent.attachLibraries(type);
        });
      }

      // Iterate over consents on this page.
      $('.media-entity-consent', context).each(function () {

        // Give consents for those, that already have a cookie set.
        if ($.cookie('Drupal.visitor.' + drupalSettings.mediaEntityConsent.CONSENT_PREFIX + $(this).attr('data-consent-type')) === '1') {
          Drupal.behaviors.media_entity_consent.giveConsent($(this).attr('data-consent-type'));
        }

        // Initialize change event for consents on this page.
        $(this).find('input[type="checkbox"][data-consent-type]').once('media_entity_consent').on('change', function () {
          if ($(this).is(':checked')) {
            let $wrapper = $('#' + $(this).attr('data-consent-id'));
            let type = $wrapper.attr('data-consent-type');
            Drupal.behaviors.media_entity_consent.giveConsent(type);
          }
        });
        $(this).addClass('media-entity-consent--initialized');
      });

      // If user settings form is loaded, initialize it.
      $('.media-entity-consent-user-form input[type="checkbox"]', context).each(function () {

        // Set consent values in all checkboxes, if f.e. caching did prevent it.
        if ($.cookie('Drupal.visitor.' + drupalSettings.mediaEntityConsent.CONSENT_PREFIX + $(this).attr('data-consent-type')) === '1') {
          Drupal.behaviors.media_entity_consent.giveConsent($(this).attr('data-consent-type'));
        }

        // Initialize change event for checkboxes of the user form.
        $(this).once('media_entity_consent').change(function () {
          let type = $(this).attr('data-consent-type');
          if (!$(this).is(':checked')) {
            Drupal.behaviors.media_entity_consent.removeConsent(type);
          } else {
            Drupal.behaviors.media_entity_consent.giveConsent(type);
          }
        });
      });
    },
    giveConsent: function (type) {
      Drupal.behaviors.media_entity_consent.attachLibraries(type);
      Drupal.behaviors.media_entity_consent.setCookie(type);
      Drupal.behaviors.media_entity_consent.setCheckboxes(type, true);

      // Iterate over all consents of this type and activate them.
      $('.media-entity-consent[data-consent-type="'+ type +'"]').each(function () {
        if (!$(this).hasClass('consent-given')) {
          let $wrapper = $(this);
          let content = JSON.parse($wrapper.find('.media-entity-consent--content').attr('data-media-content'));
          let $content_wrapper = $wrapper.find('.media-entity-consent--content');
          $content_wrapper.html(content);
          $wrapper.addClass('consent-given').removeClass('consent-denied');
          Drupal.attachBehaviors($content_wrapper[0]);
        }
      })
    },
    removeConsent: function (type) {
      Drupal.behaviors.media_entity_consent.removeCookie(type);
      Drupal.behaviors.media_entity_consent.setCheckboxes(type, false);

      // Iterate over all consents of this type and deactivate them.
      $('.media-entity-consent[data-consent-type="'+ type +'"]').each(function () {
        let $wrapper = $(this);
        let $content_wrapper = $wrapper.find('.media-entity-consent--content');
        $content_wrapper.html('');
        $wrapper.removeClass('consent-given').addClass('consent-denied');
        Drupal.attachBehaviors($content_wrapper[0]);
      })
    },
    attachLibraries: function (type) {
      let libs = drupalSettings.mediaEntityConsent.libs[type];
      if (libs === undefined || libs === null) {
        return;
      }
      libs.forEach(function (lib) {
        // Fix paths for self-hosted files. They need '/' at the beginning.
        if (lib.indexOf('/') !== 0 && lib.indexOf('http') !== 0) {
          lib = '/' + lib;
        }
        $.getScript(lib)
          .fail(function(jqxhr, settings, exception) {
            console.error('Script could not be loaded:');
            console.error(exception);
          });
      });
    },
    removeCookie: function (type) {
      $.removeCookie('Drupal.visitor.' + drupalSettings.mediaEntityConsent.CONSENT_PREFIX + type, {path: '/'});
    },
    setCookie: function (type) {
      $.cookie('Drupal.visitor.' + drupalSettings.mediaEntityConsent.CONSENT_PREFIX + type, '1', { expires: 365, path: '/' });
    },
    setCheckboxes: function (type, val) {
      $('.media-entity-consent-user-form input[data-consent-type="' + type +'"], .media-entity-consent input[data-consent-type="' + type +'"]').prop('checked', val);
    }

  }
})(jQuery, Drupal, drupalSettings);