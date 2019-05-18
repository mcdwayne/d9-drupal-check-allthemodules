/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  Drupal.tests.createpagecontent = {
    getInfo: function() {
      return {
        name: 'Menu settings',
        description: 'Tests for vertical tabs summary.',
        group: 'System'
        // @TODO: fix permissions first waitForPageLoad: true
      };
    },
    tests: {
      menutitlevisible: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(2);

          // Find the menu vertical tab and click
          var menutab = '.vertical-tabs li:contains("Menu")';
          $(menutab).find('a').first().trigger('click');

          // Click on the checkbox.
          $('#edit-menu-enabled')
            .trigger('click')
            .trigger('change');

          QUnit.equal($('#edit-menu-link-title:visible').length, 1, Drupal.t('Menu title visible'));

          // Fill in a title
          $('#edit-menu-link-title')
            .val('xyzzy')
            .trigger('change');

          // Check if summary is set
          QUnit.equal($(menutab).find('span.summary:contains("xyzzy")').length, 1, Drupal.t('Menu summary found'));

          $('#edit-title').val('test');
          $('#edit-field-test-und-0-value').val('test');

          // @TODO: fix permissions first $('#page-node-form').submit();
        };
      }
      /*
       * @TODO: fix permissions first
      ,
      checktitle: function ($, Drupal, window, document, undefined) {
        return function() {
          expect(1);
          QUnit.equal($('#page-title').text().replace(/[\s+|\n]/g, ''), 'test', 'Menu title set correctly');
          TestSwarm.gotoURL (TestSwarm.getURL() + '/edit');
        }
      }
      */
    }
  };
})(jQuery, Drupal, this, this.document);
