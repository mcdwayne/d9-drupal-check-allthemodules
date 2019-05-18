/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Tests the jQuery Once plugin.
   */
  Drupal.tests.once = {
    getInfo: function() {
      return {
        name: 'jQuery Once',
        description: 'Tests for the jQuery Once plugin.',
        group: 'System'
      };
    },
    tests: {
      createonce: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(4);

          var html = '<span>Hello</span>';
          var jqueryhtml = $(html);
          $('#page-title').append(jqueryhtml);

          // Test One
          jqueryhtml.once('testone', function() {
            QUnit.ok(true, Drupal.t('Once function is executed fine.'));
          });
          jqueryhtml.once('testone', function() {
            QUnit.ok(false, Drupal.t('Once function is executed twice.'));
          });

          // Test Two
          jqueryhtml.once('testtwo', function() {
            QUnit.ok(true, Drupal.t('Once function is executed fine one different tests.'));
          });

          // Test Three
          jqueryhtml.once('newclassfortestthree').addClass('testthreecomplete');
          QUnit.ok(jqueryhtml.hasClass('testthreecomplete'), Drupal.t('Once each function is called.'));

          // Test Four
          jqueryhtml.once('newclassfortestthree').addClass('failure');
          QUnit.equal(jqueryhtml.hasClass('failure'), false, Drupal.t('Once each function is called multiple times rather then once.'));
        };
      }
    }
  };

})(jQuery, Drupal, this, this.document);
