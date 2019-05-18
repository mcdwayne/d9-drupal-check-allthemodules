/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  Drupal.tests.createpagecontent = {
    getInfo: function() {
      return {
        name: 'Contextual links',
        description: 'Tests for contextual links.',
        group: 'System',
        useSimulate: true
      };
    },
    tests: {
      contextualHover: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(4);
          var $block = $('#sidebar-first').find('.block').eq(0);
          var $link = $block.find('button.trigger');
          // Hover the block.
          $block.trigger('mouseenter');
          QUnit.ok($link.hasClass('trigger element-invisible element-focusable'), Drupal.t('Configure link should have .trigger .element-invisible .element-focusable classes when block is hovered.'));

          // Hover the link.
          $link.trigger('mouseenter');
          QUnit.ok($block.hasClass('contextual-region-active'), Drupal.t('Block should have contextual-region-active class when trigger link is hovered.'));

          // Stop hovering the link.
          $link.trigger('mouseleave');
          $link.trigger('mouseleave');
          QUnit.ok(!$block.hasClass('contextual-region-active'), Drupal.t('Block should not have contextual-region-active class when trigger link is hovered.'));

          // Stop hovering the block.
          $block.trigger('mouseleave');
          QUnit.ok(!$link.hasClass('contextual-links-trigger-active'), Drupal.t('Configure link should not have contextual-links-trigger-active class when block is hovered.'));
        };
      },
      contextualMenu: function ($, Drupal, window, document, undefined) {
        return function() {
          var $block = $('#sidebar-first').find('.block').eq(0);
          var $link = $block.trigger('mouseenter').find('button.trigger').trigger('mouseenter');

          // Click on the gear icon.
          $link.trigger('click');

          // Contextual menu opens as closes in 100ms.
          var delay = 105;

          QUnit.stop();
          setTimeout(function() {
            QUnit.equal($link.siblings('ul').css('display'), 'block', Drupal.t('Contextual menu is visible after clicking on the gear icon.'));
            // Click on the link to hide the menu again
            $link.trigger('click');

            setTimeout(function() {
              QUnit.equal($link.siblings('ul').css('display'), 'none', Drupal.t('Contextual menu is hided after clicking on the gear icon.'));
              QUnit.start();
            }, delay);

          }, delay);

        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
