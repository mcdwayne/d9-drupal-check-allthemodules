/**
 * @file
 * Defines Javascript behaviors for the site module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Behaviors for tabs in the site edit form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the site edit form.
   */
  Drupal.behaviors.siteDetailsSummaries = {
    attach: function (context) {
      var $context = $(context);

      $('.entity-meta').find('details').each(function (index, element) {
        var required = $(element).find('.js-form-required');
        // console.log(required);
        if (required.length) {
          $(element).find('summary').addClass('form-required');
        }
      });

      $('.entity-meta.is-new').find('summary.form-required').each(function (index, element) {
        $(element).click();
      });


      $context.find('.site-form-owner').drupalSetSummary(function (context) {
        var $authorContext = $(context);
        var name = $authorContext.find('.field--name-user-id input').val();
        var date = $authorContext.find('.field--name-created input').val();

        if (name && date) {
          return Drupal.t('By @name on @date', {'@name': name, '@date': date});
        }
        else if (name) {
          return Drupal.t('By @name', {'@name': name});
        }
        else if (date) {
          return Drupal.t('Authored on @date', {'@date': date});
        }
      });

      $context.find('.site-form-url-info').drupalSetSummary(function (context) {
        var $siteContext = $(context);
        var scheme = $siteContext.find('.field--name-site_scheme input').val();
        var url = $siteContext.find('.field--name-site-url input').val();
        // console.log(scheme);
        if (scheme && url) {
          return Drupal.t(scheme + '://' + url);
        }
      });

    }
  };

})(jQuery, Drupal, drupalSettings);