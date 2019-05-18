/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  var summary = 'summary';
  if (!Modernizr.details) {
    summary += ' a';
  }
  /**
   * Collapsible fields.
   */
  Drupal.tests.autocomplete = {
    getInfo: function() {
      return {
        name: 'Collapsible fields',
        description: 'Tests for Collapsible fields.',
        group: 'System'
      };
    },
    tests: {
      details1: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(9);
          var collapseDelay = 1000;
          // The first details should be visible initially, but we should be able to
          // toggle it by clicking on the legend.
          QUnit.ok($('#edit-details1').find('div.details-wrapper').is(':visible'), Drupal.t('First details is initially visible.'));
          QUnit.ok($('#edit-details1').hasClass('collapsible'), Drupal.t('First details has the "collapsible" class.'));
          QUnit.ok($('#edit-details1').attr('open') == 'open', Drupal.t('First details is open.'));
          // Trigger the collapse behavior by simulating a click.
          $('#edit-details1').find(summary).click();
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('#edit-details1').find('div.details-wrapper').is(':hidden'), Drupal.t('First details is not visible after being toggled.'));
            QUnit.ok($('#edit-details1').hasClass('collapsible'), Drupal.t('First details has the "collapsible" class after being toggled.'));
            QUnit.ok($('#edit-details1').attr('open') != 'open', Drupal.t('First details is not open class after being toggled.'));

            // Trigger the collapse behavior again by simulating a click.
            $('#edit-details1').find(summary).click();
            setTimeout(function() {
              QUnit.ok($('#edit-details1').find('div.details-wrapper').is(':visible'), Drupal.t('First details is visible after being toggled again.'));
              QUnit.ok($('#edit-details1').hasClass('collapsible'), Drupal.t('First details has the "collapsible" class after being toggled again.'));
              QUnit.ok($('#edit-details1').attr('open') == 'open', Drupal.t('First details is open class after being toggled again.'));
              QUnit.start();
            }, collapseDelay);
          }, collapseDelay);
        };
      },
      details2: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(9);
          var collapseDelay = 1000;

          // The second details should be initially hidden, but we should be able to
          // toggle it by clicking on the legend.
          QUnit.ok($('#edit-details2').find('div.details-wrapper').is(':hidden'), Drupal.t('Second details is not initially visible.'));
          QUnit.ok($('#edit-details2').hasClass('collapsible'), Drupal.t('Second details has the "collapsible" class.'));
          QUnit.ok($('#edit-details2').attr('open') != 'open', Drupal.t('Second details is not open.'));
          // Trigger the collapse behavior by simulating a click.
          $('#edit-details2').find(summary).click();
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('#edit-details2').find('div.details-wrapper').is(':visible'), Drupal.t('Second details is visible after being toggled.'));
            QUnit.ok($('#edit-details2').hasClass('collapsible'), Drupal.t('Second details has the "collapsible" class after being toggled.'));
            QUnit.ok($('#edit-details2').attr('open') == 'open', Drupal.t('Second details is open after being toggled.'));
            $('#edit-details2').find(summary).click();
            setTimeout(function() {
              // Trigger the collapse behavior again by simulating a click.
              QUnit.ok($('#edit-details2').find('div.details-wrapper').is(':hidden'), Drupal.t('Second details is not visible after being toggled again.'));
              QUnit.ok($('#edit-details2').hasClass('collapsible'), Drupal.t('Second details has the "collapsible" class after being toggled again.'));
              QUnit.ok($('#edit-details2').attr('open') != 'open', Drupal.t('Second details is not open after being toggled again.'));
              QUnit.start();
            }, collapseDelay);
          }, collapseDelay);
        };
      },
      //Following 2 tests will fail untill there is decided on http://drupal.org/node/1852104.
      details3: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(6);
          var collapseDelay = 1000;

          QUnit.ok($('#edit-details3').find('div.details-wrapper').is(':visible'), Drupal.t('Third details is initially visible.'));
          QUnit.ok(!$('#edit-details3').hasClass('collapsible'), Drupal.t('Third details does not have the "collapsible" class.'));
          QUnit.ok($('#edit-details3').attr('open') == 'open', Drupal.t('Third details is open.'));
          // After toggling, nothing should happen.
          $('#edit-details3').find(summary).click();
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok($('#edit-details3').find('div.details-wrapper').is(':visible'), Drupal.t('Third details is still visible after toggling.'));
            QUnit.ok(!$('#edit-details3').hasClass('collapsible'), Drupal.t('Third details still does not have the "collapsible" class after toggling.'));
            QUnit.ok($('#edit-details3').attr('open') == 'open', Drupal.t('Third details is still open after toggling.'));
            QUnit.start();
          }, collapseDelay);
        };
      },
      details4: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(6);
          var collapseDelay = 1000;

          QUnit.ok(!$('#edit-details4').find('div.details-wrapper').is(':visible'), Drupal.t('Fourth details is initially invisible.'));
          QUnit.ok(!$('#edit-details4').hasClass('collapsible'), Drupal.t('Fourth details does not have the "collapsible" class.'));
          QUnit.ok($('#edit-details4').attr('open') != 'open', Drupal.t('Fourth details is not open.'));
          // After toggling, nothing should happen.
          $('#edit-details4').find(summary).click();
          QUnit.stop();
          setTimeout(function() {
            QUnit.ok(!$('#edit-details4').find('div.details-wrapper').is(':visible'), Drupal.t('Fourth details is still invisible after toggling.'));
            QUnit.ok(!$('#edit-details4').hasClass('collapsible'), Drupal.t('Fourth details still does not have the "collapsible" class after toggling.'));
            QUnit.ok($('#edit-details4').attr('open') != 'open', Drupal.t('Fourth details is still not open after toggling.'));
            QUnit.start();
          }, collapseDelay);
        };
      }
    }
  };
})(jQuery, Drupal, this, this.document);
