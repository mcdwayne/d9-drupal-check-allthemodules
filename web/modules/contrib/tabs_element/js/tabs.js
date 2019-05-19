
(function ($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.unearthedTabs = {
        attach: function (context, settings) {
            $('.tabs').tabs();
        }
    }
}(jQuery, Drupal, drupalSettings));
