/**
 * @file
 * Behavior for accordion formatter.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.quadrupleFieldAccordion = {
    attach: function () {

      $('.quadruple-field-accordion')
        .accordion({
          collapsible: true,
          active: false
        });

    }
  };

})(jQuery);
