/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Tests drag & drop.
   */
  Drupal.tests.dragdrop = {
    getInfo: function() {
      return {
        name: 'Drag and drop',
        description: 'Tests for drag and drop.',
        group: 'System',
        useSimulate: true
      };
    },
    tests: {
      dragdrop: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(3);
          var $dragme = $('tr.region-title-help').next().next().find('a.tabledrag-handle').first();
          var $dragto = $('tr.region-title-header');

          var dragmeOffset = $dragme.offset().top;
          var dragtoOffset = $dragto.offset().top;

          // Check that 'System help' is in region 'Help'.
          $('select[name="blocks[bartik.help][region]"]').val('help');

          QUnit.equal($('select[name="blocks[bartik.help][region]"]').val(), 'help', Drupal.t('"System help" in region "Help"'));

          // Drag to 'Featured'
          $dragme.simulate("drag", {
            dx: 0,
            dy: dragmeOffset - dragtoOffset + $dragme.closest('tr').height() + 10
          });

          // Check that 'System help' is in region 'Featured'.
          QUnit.equal($('select[name="blocks[bartik.help][region]"]').val(), 'featured', Drupal.t('"System help" in region "Featured"'));

          // Check for the presence of the warning.
          QUnit.equal($('div.messages.warning').length, 1, Drupal.t('Warning message found.'));
        };
      }
    }
  };

})(jQuery, Drupal, this, this.document);
