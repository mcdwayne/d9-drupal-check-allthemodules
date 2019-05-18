/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Tests Drupal.checkPlain().
   */
  Drupal.tests.tabbingmanager = {
    getInfo: function() {
      return {
        name: 'Drupal.tabbingmanager()',
        description: 'Tests for Drupal.tabbingmanager().',
        group: 'System'
      };
    },
    setup: function () {
      $('a[href="#main-content"]').attr('id', 'drupal-content-skip-link');
      $('<div id="testswarm-tabbingmanager-container" />')
        .append($('<div id="testswarm-tabbingmanager-context-1" />')
          .append(getFakeForm())
        )
        .append($('<div id="testswarm-tabbingmanager-context-2" />')
          .append(getFakeForm(1))
        )
        .append($('<div id="testswarm-tabbingmanager-context-3" />')
          .append(getFakeForm(2))
        )
        .append($('<div id="testswarm-tabbingmanager-context-4" />')
          .append(getFakeForm(3))
        )
        .append($('<div id="testswarm-tabbingmanager-context-5" />')
          .append(getFakeForm(4))
        )
        .appendTo('body');
    },
    teardown: function () {
      $('#testswarm-tabbingmanager-container').remove();
      $('a[href="#main-content"]').focus();
    },
    tests: {
      testtabbingmanagerapi: function () {
        return function () {
          QUnit.expect(13);

          // Check for the testing container.
          QUnit.equal($('#testswarm-tabbingmanager-container').length, 1, Drupal.t('The testing container is present.'));

          // Check that the tabbingManager is available on the Drupal object.
          QUnit.ok('tabbingManager' in Drupal, Drupal.t('tabbingManager is available on the Drupal object.'));
          // Verify that the tabbingManager methods are available.
          QUnit.ok('constrain' in Drupal.tabbingManager, Drupal.t('constrain() is available on the Drupal.tabbingManager object.'));
          QUnit.ok('release' in Drupal.tabbingManager, Drupal.t('release() is available on the Drupal.tabbingManager object.'));
          QUnit.ok('activate' in Drupal.tabbingManager, Drupal.t('activate() is available on the Drupal.tabbingManager object.'));
          QUnit.ok('deactivate' in Drupal.tabbingManager, Drupal.t('deactivate() is available on the Drupal.tabbingManager object.'));
          QUnit.ok('recordTabindex' in Drupal.tabbingManager, Drupal.t('recordTabindex() is available on the Drupal.tabbingManager object.'));
          QUnit.ok('restoreTabindex' in Drupal.tabbingManager, Drupal.t('restoreTabindex() is available on the Drupal.tabbingManager object.'));

          // Check for a context container.
          var $context = $('#testswarm-tabbingmanager-context-1');
          QUnit.equal($context.length, 1, Drupal.t('A testing context container is present.'));
          // Create a TabbingContext instance.
          var tabbingContext = Drupal.tabbingManager.constrain($context);
          // Verify that the tabbingManager methods are available.
          QUnit.ok('release' in tabbingContext, Drupal.t('release() is available on the TabbingContext object.'));
          QUnit.ok('activate' in tabbingContext, Drupal.t('activate() is available on the TabbingContext object.'));
          QUnit.ok('deactivate' in tabbingContext, Drupal.t('deactivate() is available on the TabbingContext object.'));

          // Verify that the TabbingContext can be released.
          tabbingContext.release();
          var tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));
        };
      },
      testconstrain: function () {
        return function() {
          QUnit.expect(5);

          // Check for the testing container.
          QUnit.equal($('#testswarm-tabbingmanager-container').length, 1, Drupal.t('The testing container is present.'));

          // Check for a context container.
          var $context = $('#testswarm-tabbingmanager-context-1');
          QUnit.equal($context.length, 1, Drupal.t('A testing context container is present.'));
          // Create a TabbingContext instance.
          var tabbingContext = Drupal.tabbingManager.constrain($context.find('input'));
          // Check that five tabbable elements are in the context.
          QUnit.equal(tabbingContext.$tabbableElements.length, 5, Drupal.t('The context contains 5 tabbable elements.'));

          // Check that the first tabbable has focus.
          var $tabbable = $('#testswarm-tabbingmanager-tabbable-1');
          var tabbableID = $tabbable.attr('id');
          var activeElementID = document.activeElement.id;

          // Verify that the input is the active element on the page.
          QUnit.equal(tabbableID, activeElementID, Drupal.t('The first tabbable is the active element in the document.'));

          // Verify that the TabbingContext can be released.
          tabbingContext.release();
          var tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));

        };
      },
      // Enable in series 4 tabbingContexts and check the state of the page
      // at each constraint application.
      testconstrainsets: function () {
        return function() {
          QUnit.expect(24);

          // Verify that a input element from each of the three contexts is present.
          QUnit.ok($('#testswarm-tabbingmanager-tabbable-1').length === 1, Drupal.t('An input from the first context is present.'));
          QUnit.ok($('#testswarm-tabbingmanager-tabbable-6').length === 1, Drupal.t('An input from the second context is present.'));
          QUnit.ok($('#testswarm-tabbingmanager-tabbable-11').length === 1, Drupal.t('An input from the third context is present.'));

          // Verify that the default tabbing context is the entire page.
          var tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));

          // Check for a context container.
          var $context = $('#testswarm-tabbingmanager-context-1');
          // Create a TabbingContext instance.
          var tabbingContext = Drupal.tabbingManager.constrain($context.find('input'));
          // Check that five tabbable elements are in the context.
          QUnit.equal(tabbingContext.$tabbableElements.length, 5, Drupal.t('The first context contains 5 tabbable elements.'));
          // Verify that the input is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-1').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the first tabbingContext is the active element in the document.'));
          // Verify that another tabbable in the set isn't the focused element.
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-2').attr('id'), document.activeElement.id, Drupal.t('The second tabbable is not the active element in the document.'));

          // Verify that the default tabbing context is no longer tabbable page.
          tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok(tabindex === -1, Drupal.t('The skip link is no longer tabbable.'));

          // Select the second tabbing context.
          var $context_2 = $('#testswarm-tabbingmanager-context-2');
          // Create a TabbingContext instance.
          var tabbingContext_2 = Drupal.tabbingManager.constrain($context_2.find('input'));
          // Check that five tabbable elements are in the context.
          QUnit.equal(tabbingContext_2.$tabbableElements.length, 5, Drupal.t('The second context contains 5 tabbable elements.'));

          // Verify that the first input of the first tabbingContext is no longer tabbable.
          tabindex = parseInt($('#testswarm-tabbingmanager-tabbable-1').attr('tabindex'), 10);
          QUnit.ok(tabindex === -1, Drupal.t('The first tabbable in the first tabbingContext is no longer tabbable.'));

          // Verify that the first input in the second tabbingContext is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-6').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the second tabbingContext is the active element in the document.'));

          // Select the third tabbing context.
          var $context_3 = $('#testswarm-tabbingmanager-context-3');
          // Create a TabbingContext instance.
          var tabbingContext_3 = Drupal.tabbingManager.constrain($context_3.find('input'));
          // Check that five tabbable elements are in the context.
          QUnit.equal(tabbingContext_3.$tabbableElements.length, 5, Drupal.t('The third context contains 5 tabbable elements.'));

          // Verify that the first input of the second tabbingContext is no longer tabbable.
          tabindex = parseInt($('#testswarm-tabbingmanager-tabbable-6').attr('tabindex'), 10);
          QUnit.ok(tabindex === -1, Drupal.t('The first tabbable in the second tabbingContext is no longer tabbable.'));

          // Verify that the second input (it has autofocus) in the third tabbingContext is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-12').attr('id'), document.activeElement.id, Drupal.t('The second tabbable (it has autofocus) in the third tabbingContext is the active element in the document.'));

          // Select the third tabbing context.
          var $context_4 = $('#testswarm-tabbingmanager-context-4');
          // Create a TabbingContext instance.
          var tabbingContext_4 = Drupal.tabbingManager.constrain($context_4.find('input'));
          // Check that five tabbable elements are in the context.
          QUnit.equal(tabbingContext_4.$tabbableElements.length, 5, Drupal.t('The fourth context contains 5 tabbable elements.'));

          // Verify that the second input (it has autofocus) of the third tabbingContext is no longer tabbable.
          tabindex = parseInt($('#testswarm-tabbingmanager-tabbable-12').attr('tabindex'), 10);
          QUnit.ok(tabindex === -1, Drupal.t('The second tabbable (it has autofocus) in the third tabbingContext is no longer tabbable.'));

          // Verify that the first input in the fourth tabbingContext is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-16').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the fourth tabbingContext is the active element in the document.'));

          // Release the third tabbingContext.
          tabbingContext_4.release();
          // Verify that the first tabbable in the second tabbing context is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-12').attr('id'), document.activeElement.id, Drupal.t('The second tabbable (it has autofocus) in the third tabbingContext is the active element in the document.'));

          // Release the third tabbingContext.
          tabbingContext_3.release();
          // Verify that the first tabbable in the second tabbing context is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-6').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the second tabbingContext is the active element in the document.'));

          // Release the second tabbingContext.
          tabbingContext_2.release();
          // Verify that the first tabbable in the first tabbing context is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-1').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the first tabbingContext is the active element in the document.'));

          // Release the first tabbingContext.
          tabbingContext.release();
          tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));


          // Verify that the stack unwinds if a intermediary tabbingContexts are
          // released before the topmost context is released.
          tabbingContext_2 = Drupal.tabbingManager.constrain($context_2.find('input'));
          // Create a TabbingContext instance.
          tabbingContext_3 = Drupal.tabbingManager.constrain($context_3.find('input'));
          // Verify that the second input (it has autofocus) in the third tabbingContext is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-12').attr('id'), document.activeElement.id, Drupal.t('The second tabbable (it has autofocus) in the third tabbingContext is the active element in the document.'));
          // Release the second tabbingContext.
          tabbingContext_2.release();

          // Verify that the second input (it has autofocus) in the third tabbingContext is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-12').attr('id'), document.activeElement.id, Drupal.t('The second tabbable (it has autofocus) in the third tabbingContext is the active element in the document.'));

          // Release the second tabbingContext.
          tabbingContext_3.release();

          tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));
        };
      },
      // Release the intermediary constraints in the order they were declared.
      testconstrainsetsunwindlinear: function () {
        return function() {
          QUnit.expect(10);
          var skipLinktabindex;

          // Verify that the default tabbing context is the entire page.
          var tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));

          // Create a TabbingContext instance.
          var tabbingContext_1 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-1'));
          var tabbingContext_2 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-2'));
          var tabbingContext_3 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-3'));
          var tabbingContext_4 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-4'));
          var tabbingContext_5 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-5'));

          // Verify that the default tabbing context is no longer tabbable page.
          skipLinktabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok(skipLinktabindex === -1, Drupal.t('The skip link is no longer tabbable.'));

          // Verify that tabbables in the first 4 contexts are not tabbable.
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-1').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the first tabbingContext is not the active element in the document.'));
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-6').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the second tabbingContext is not the active element in the document.'));
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-12').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the third tabbingContext is not the active element in the document.'));
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-16').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the fourth tabbingContext is not the active element in the document.'));

          // Verify that the first tabbable in the fifth tabbingContext is the active element in the document.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-21').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the fifth tabbingContext is the active element in the document.'));

          // Now release all but the fifth context.
          tabbingContext_1.release();
          tabbingContext_2.release();
          tabbingContext_3.release();
          tabbingContext_4.release();
          // Verify that the default tabbing context is no longer tabbable page.
          skipLinktabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok(skipLinktabindex === -1, Drupal.t('The skip link is no longer tabbable.'));

          // Verify that the first tabbable in the fifth tabbingContext is the active element in the document.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-21').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the fifth tabbingContext is the active element in the document.'));

          // Release the fifth context. The default context should obtain.
          tabbingContext_5.release();

          // Verify that the default tabbing context is the entire page.
          tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));
        };
      },
      // Release the intermediary constraints out of the order they were declared.
      testconstrainsetsunwindnonlinear: function () {
        return function() {
          QUnit.expect(11);
          var skipLinktabindex;

          // Verify that the default tabbing context is the entire page.
          var tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));

          // Create a TabbingContext instance.
          var tabbingContext_1 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-1'));
          var tabbingContext_2 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-2'));
          var tabbingContext_3 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-3'));
          var tabbingContext_4 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-4'));
          var tabbingContext_5 = Drupal.tabbingManager.constrain($('#testswarm-tabbingmanager-context-5'));

          // Verify that the default tabbing context is no longer tabbable page.
          skipLinktabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok(skipLinktabindex === -1, Drupal.t('The skip link is no longer tabbable.'));

          // Verify that tabbables in the first 4 contexts are not tabbable.
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-1').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the first tabbingContext is not the active element in the document.'));
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-6').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the second tabbingContext is not the active element in the document.'));
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-12').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the third tabbingContext is not the active element in the document.'));
          QUnit.notEqual($('#testswarm-tabbingmanager-tabbable-16').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the fourth tabbingContext is not the active element in the document.'));

          // Verify that the first tabbable in the fifth tabbingContext is the active element in the document.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-21').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the fifth tabbingContext is the active element in the document.'));

          // Now release all but the second context. Mix them up.
          tabbingContext_3.release();
          tabbingContext_1.release();
          tabbingContext_4.release();

          // Verify that the first tabbable in the fifth tabbingContext is the active element in the document.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-21').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the fifth tabbingContext is the active element in the document.'));

          // Release the fifth context. The default context should obtain.
          tabbingContext_5.release();

          // Verify that the default tabbing context is not the page.
          skipLinktabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok(skipLinktabindex === -1, Drupal.t('The skip link is no longer tabbable.'));

          // Verify that the first tabbable in the second tabbing context is the active element on the page.
          QUnit.equal($('#testswarm-tabbingmanager-tabbable-6').attr('id'), document.activeElement.id, Drupal.t('The first tabbable in the second tabbingContext is the active element in the document.'));

          // Release the second context.
          tabbingContext_2.release();

          // Verify that the default tabbing context is the entire page.
          tabindex = parseInt($('a[href="#main-content"]').attr('tabindex'), 10);
          QUnit.ok((tabindex > 0 || isNaN(tabindex)), Drupal.t('The skip link is tabbable.'));
        };
      },
      // Test the restoration of the tabindex attribute on an element.
      testtabindexrestoration: function () {
        return function () {
          QUnit.expect(15);

          var $tabbables, tabindex;

          // Verify that the tabindex values in the second tabbingContext are present.
          var $context_2 = $('#testswarm-tabbingmanager-context-2');
          $tabbables = $context_2.find('input');
          for (var i = 0, il = $tabbables.length; i < il; i++) {
            tabindex = $tabbables.eq(i).attr('tabindex');
            QUnit.equal(tabindex, (i + 1), Drupal.t('The tabindex of the tabbable in the second tabbingContext is @index.', {'@index': (i + 1)}));
          }

          // Check for a context container.
          var $context = $('#testswarm-tabbingmanager-context-1');
          // Create a TabbingContext instance.
          var tabbingContext = Drupal.tabbingManager.constrain($context);

          // Verify that the tabindex values in the second tabbingContext are -1.
          for (i = 0, il = $tabbables.length; i < il; i++) {
            tabindex = $tabbables.eq(i).attr('tabindex');
            QUnit.equal(tabindex, -1, Drupal.t('The tabindex of the tabbable in the second tabbingContext is -1'));
          }

          // Release the first tabbingContext.
          tabbingContext.release();

          // Verify the tabindex attributes have been restored on the five tabbables.
          for (i = 0, il = $tabbables.length; i < il; i++) {
            tabindex = $tabbables.eq(i).attr('tabindex');
            QUnit.equal(tabindex, (i + 1), Drupal.t('The tabindex of the tabbable in the second tabbingContext is @index.', {'@index': (i + 1)}));
          }
        };
      },
      // Test the restoration of the autofocus attribute on an element.
      testautofocusrestoration: function () {
        return function () {
          QUnit.expect(3);

          var hasAutoFocus;

          // Verify that the 12th tabbable has autofocus.
          var $autofocusable = $('#testswarm-tabbingmanager-tabbable-12');
          hasAutoFocus = !!$autofocusable.attr('autofocus');
          QUnit.ok(hasAutoFocus, Drupal.t('The 12th tabbable has autofocus.'));

          // Check for a context container.
          var $context = $('#testswarm-tabbingmanager-context-1');
          // Create a TabbingContext instance.
          var tabbingContext = Drupal.tabbingManager.constrain($context);

          // Verify that the 12th tabbable does not have  autofocus.
          hasAutoFocus = !!$autofocusable.attr('autofocus');
          QUnit.ok(!hasAutoFocus, Drupal.t('The 12th tabbable does not have autofocus.'));

          // Release the first tabbingContext.
          tabbingContext.release();

          // Verify that the 12th tabbable has autofocus.
          hasAutoFocus = !!$autofocusable.attr('autofocus');
          QUnit.ok(hasAutoFocus, Drupal.t('The 12th tabbable has autofocus.'));
        };
      }
    }
  };

  function getFakeForm (formVariant) {
    var $form = $('<div />');
    switch (formVariant) {
      case 1:
        $form
          .append('<input id="testswarm-tabbingmanager-tabbable-6" type="text" tabindex="1" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-7" type="text" tabindex="2" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-8" type="text" tabindex="3" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-9" type="text" tabindex="4" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-10" type="text" tabindex="5" />');
        break;
      case 2:
        $form
          .append('<input id="testswarm-tabbingmanager-tabbable-11" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-12" type="text" autofocus />')
          .append('<input id="testswarm-tabbingmanager-tabbable-13" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-14" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-15" type="text" />');
        break;
      case 3:
        $form
          .append('<input id="testswarm-tabbingmanager-tabbable-16" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-17" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-18" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-19" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-20" type="text" />');
        break;
      case 4:
        $form
          .append('<input id="testswarm-tabbingmanager-tabbable-21" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-22" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-23" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-24" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-25" type="text" />');
        break;
      default:
        $form
          .append('<input id="testswarm-tabbingmanager-tabbable-1" type="text" autofocus />')
          .append('<input id="testswarm-tabbingmanager-tabbable-2" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-3" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-4" type="text" />')
          .append('<input id="testswarm-tabbingmanager-tabbable-5" type="text" />');
        break;

    }
    return $form.html();
  }

}(jQuery, Drupal, this, this.document));
