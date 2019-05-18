/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Drupal toolbar tests to check horizontal and vertical expandable elements and children.
   */
  Drupal.tests.toolbar = {
    getInfo: function() {
      return {
        name: 'Toolbar',
        description: 'Tests for toolbar.',
        group: 'Core',
        useSimulate: true
      };
    },
    setup: function () {},
    teardown: function () {},
    tests: {
      toolbarHorizontal: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(7);

          // Test if toolbar bar orientation is horizontal, if not then toggle it.
          if (!$('#toolbar-tray').hasClass('horizontal')) {
            $('button.icon-toggle-horizontal').trigger('click');
          }

          QUnit.ok($('#toolbar-tray').hasClass('horizontal'), Drupal.t('Toolbar orientation is horizontal.'));

          // Test if Menu toolbar tray is shown when "Menu" item is clicked.
          $('#toolbar-tab-toolbar-tray'). trigger('click');
          QUnit.ok($('#toolbar-tray').hasClass('active'), Drupal.t('Menu toolbar tray is shown when "Menu" item is clicked.'));

          // Test if Shortcuts toolbar tray is shown when "Shortcuts" item is clicked.
          $('#toolbar-tab-toolbar-tray--2').trigger('click');
          QUnit.ok($('#toolbar-tray--2').hasClass('active'), Drupal.t('Shortcuts toolbar tray is shown when "Shortcuts" item is clicked.'));

          // Test if User toolbar tray is shown when "My account" item is clicked.
          $('#toolbar-tab-toolbar-tray--3').trigger('click');
          QUnit.ok($('#toolbar-tray--3').hasClass('active'), Drupal.t('User toolbar tray is shown when "My account" item is clicked.'));

          // Test if toolbar toggles between horizontal and vertical.
          QUnit.ok($('#toolbar-tray').hasClass('horizontal'), Drupal.t('Toolbar is horizontal before click the toggle orientation button.'));

          $('button.icon-toggle-vertical').trigger('click');
          QUnit.ok($('#toolbar-tray').hasClass('vertical'), Drupal.t('Toolbar is vertical after click the toggle orientation button.'));

          $('button.icon-toggle-horizontal').trigger('click');
          QUnit.ok($('#toolbar-tray').hasClass('horizontal'), Drupal.t('Toolbar is horizontal after click the toggle orientation button.'));
        };
      },
      toolbarVertical: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(7);

          // Test if toolbar bar orientation is vertical, if not then toggle it.
          if (!$('#toolbar-tray').hasClass('vertical')) {
            $('button.icon-toggle-vertical').trigger('click');
          }

          QUnit.ok($('#toolbar-tray').hasClass('vertical'), Drupal.t('Toolbar orientation is vertical.'));

          // Test if Menu toolbar tray is shown when "Menu" item is clicked.
          $('#toolbar-tab-toolbar-tray'). trigger('click');
          QUnit.ok($('#toolbar-tray').hasClass('active'), Drupal.t('Menu toolbar tray is shown when "Menu" item is clicked.'));

          // Test if Shortcuts toolbar tray is shown when "Shortcuts" item is clicked.
          $('#toolbar-tab-toolbar-tray--2').trigger('click');
          QUnit.ok($('#toolbar-tray--2').hasClass('active'), Drupal.t('Shortcuts toolbar tray is shown when "Shortcuts" item is clicked.'));

          // Test if User toolbar tray is shown when "My account" item is clicked.
          $('#toolbar-tab-toolbar-tray--3').trigger('click');
          QUnit.ok($('#toolbar-tray--3').hasClass('active'), Drupal.t('User toolbar tray is shown when "My account" item is clicked.'));

          // Test if toolbar toggles between vertical and horizontal.
          QUnit.ok($('#toolbar-tray').hasClass('vertical'), Drupal.t('Toolbar is vertical before click the toggle orientation button.'));

          $('button.icon-toggle-horizontal').trigger('click');
          QUnit.ok($('#toolbar-tray').hasClass('horizontal'), Drupal.t('Toolbar is horizontal after click the toggle orientation button.'));

          $('button.icon-toggle-vertical').trigger('click');
          QUnit.ok($('#toolbar-tray').hasClass('vertical'), Drupal.t('Toolbar is vertical after click the toggle orientation button.'));
        };
      },
      contentChildren: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(6);

          // Check if Menu toolbar tray is open. If not, then open it.
          if (!$('#toolbar-tray').hasClass('active')) {
            $('#toolbar-tab-toolbar-tray'). trigger('click');
          }

          // Test if Content expandable children are shown when expand icon is clicked.
          $('#toolbar-link-admin-content').next().trigger('click');
          QUnit.ok($('#toolbar-link-admin-content').next().hasClass('open'), Drupal.t('The Content expand icon changes to collapse.'));
          QUnit.ok($('#toolbar-link-admin-content').next().find('span.action').text() === 'Collapse', Drupal.t('The Content icon action changes to Collapse.'));
          QUnit.ok($('#toolbar-link-admin-content').parent().next().css('display') === 'block', Drupal.t('The Content expandable children are shown.'));

          // Test if Content expandable children are collapsed when collapse icon is clicked.
          $('#toolbar-link-admin-content').next().trigger('click');
          QUnit.ok(!$('#toolbar-link-admin-content').next().hasClass('open'), Drupal.t('The Content expand icon changes to extend.'));
          QUnit.ok($('#toolbar-link-admin-content').next().find('span.action').text() === 'Extend', Drupal.t('The Content icon action changes to Extend.'));
          QUnit.ok($('#toolbar-link-admin-content').parent().next().css('display') === 'none', Drupal.t('The Content expandable children are collapsed.'));
        };
      },
      structureChildren: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(8);

          // Check if Menu toolbar tray is open. If not, then open it.
          if (!$('#toolbar-tray').hasClass('active')) {
            $('#toolbar-tab-toolbar-tray'). trigger('click');
          }

          // Test if Structure expandable children are shown when expand icon is clicked.
          $('#toolbar-link-admin-structure').next().trigger('click');

          QUnit.ok($('#toolbar-link-admin-structure').next().hasClass('open'), Drupal.t('The Structure expand icon changes to collapse.'));
          QUnit.ok($('#toolbar-link-admin-structure').next().find('span.action').text() === 'Collapse', Drupal.t('The Structure icon action changes to Collapse.'));
          QUnit.ok($('#toolbar-link-admin-structure').parent().next().css('display') === 'block', Drupal.t('The Structure expandable children are shown.'));

          // Structure children expandable items testing.
          // Test if Menus children are expanded when expand icon is clicked.
          $('#toolbar-link-admin-structure-menu').next().trigger('click');
          QUnit.ok($('#toolbar-link-admin-structure-menu').next().hasClass('open'), Drupal.t('The Menu expand icon changes to collapse.'));
          QUnit.ok($('#toolbar-link-admin-structure-menu').next().find('span.action').text() === 'Collapse', Drupal.t('The Menu icon action changes to Collapse.'));

          // Test if Menus children are collapsed when collapse icon is clicked.
          $('#toolbar-link-admin-structure-menu').next().trigger('click');
          QUnit.ok(!$('#toolbar-link-admin-structure-menu').next().hasClass('open'), Drupal.t('The Menu expand icon changes to extend.'));
          QUnit.ok($('#toolbar-link-admin-structure-menu').next().find('span.action').text() === 'Extend', Drupal.t('The Menu icon action changes to Extend.'));

          // Test if Structure expandable children are collapsed when the collapse icon is clicked.
          $('#toolbar-link-admin-structure').next().trigger('click');
          QUnit.ok($('#toolbar-link-admin-structure').parent().next().css('display') === 'none', Drupal.t('The Structure expandable children are collapsed.'));

        };
      },
      configurationChildren: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(22);

          // Check if Menu toolbar tray is open. If not, then open it.
          if (!$('#toolbar-tray').hasClass('active')) {
            $('#toolbar-tab-toolbar-tray'). trigger('click');
          }

          // Test if Configuration expandable children are shown when expand icon is clicked.
          $('#toolbar-link-admin-config').next().trigger('click');

          QUnit.ok($('#toolbar-link-admin-config').next().hasClass('open'), Drupal.t('The Configuration expand icon changes to collapse.'));
          QUnit.ok($('#toolbar-link-admin-config').next().find('span.action').text() === 'Collapse', Drupal.t('The Configuration icon action changes to Collapse.'));
          QUnit.ok($('#toolbar-link-admin-config').parent().next().css('display') === 'block', Drupal.t('The Configuration expandable children are shown.'));

          // Test if Configuration expandable children expands and collapse.
          var buttons = $('#toolbar-link-admin-config').parent().next().children().find('span.action');

          buttons.each(function() {
            QUnit.ok($(this).text() === 'Extend', Drupal.t('Children action is Extend before click'));
            $(this).trigger('click');
            QUnit.ok($(this).text() === 'Collapse', Drupal.t('Children action is Collapse after click'));
          });

          // Test if Configuration expandable children are collapsed when collapse icon is clicked.
          $('#toolbar-link-admin-config').next().trigger('click');
          QUnit.ok($('#toolbar-link-admin-config').parent().next().css('display') === 'none', Drupal.t('The Configuration expandable children are collapsed.'));

        };
      },
      reportsChildren: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(4);

          // Check if Menu toolbar tray is open. If not, then open it.
          if (!$('#toolbar-tray').hasClass('active')) {
            $('#toolbar-tab-toolbar-tray'). trigger('click');
          }

          // Test if Reports expandable children are shown when expand icon is clicked.
          $('#toolbar-link-admin-reports').next().trigger('click');

          QUnit.ok($('#toolbar-link-admin-reports').next().hasClass('open'), Drupal.t('The Reports expand icon changes to collapse.'));
          QUnit.ok($('#toolbar-link-admin-reports').next().find('span.action').text() === 'Collapse', Drupal.t('The Reports icon action changes to Collapse.'));
          QUnit.ok($('#toolbar-link-admin-reports').parent().next().css('display') === 'block', Drupal.t('The Reports expandable children are shown.'));

          // Test if Reports expandable children are collapsed when collapse icon is clicked.
          $('#toolbar-link-admin-reports').next().trigger('click');
          QUnit.ok($('#toolbar-link-admin-reports').parent().next().css('display') === 'none', Drupal.t('The Reports expandable children are collapsed.'));

        };
      }
    }
  };
}(jQuery, Drupal, this, this.document));
