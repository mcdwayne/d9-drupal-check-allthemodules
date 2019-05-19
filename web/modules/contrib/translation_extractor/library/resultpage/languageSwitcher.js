/**
 * @file
 * Contains a small script that toggles the language path attribute on the results page.
 *
 * @package Drupal\translation_extractor
 */

(function ($, Drupal, window) {

  'use strict';

  Drupal.behaviors.showHidden = {
    attach: function (context) {
      $('select#languageSwitcher', context).bind('change', function (event) {
        var $select = $(event.target);
        var url = window.location.href.split('/');
        if (url[url.length - 1] !== 'results') {
          url.pop();
        }
        url.push($select.val());
        url = url.join('/');
        window.location.href = url;
      });
    }
  };

}(jQuery, Drupal, window));
