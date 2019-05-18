/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal) {
  "use strict";

  /**
   * Tests field_ui.
   */
  Drupal.tests.dragdrop = {
    getInfo: function () {
      return {
        name: 'Field UI',
        description: 'Tests for field_ui.',
        group: 'System',
        useSimulate: true
      };
    },
    tests: {
      hideWithKeyboard: function ($, Drupal) {
        return function () {
          QUnit.expect(1);

          var $dragrow = $('#body');
          var $select = $('#edit-fields-body-type');
          var $handle = $dragrow.find('a.tabledrag-handle');

          QUnit.stop();
          setTimeout(function () {
            QUnit.start();
            $handle.focus();
            $handle.simulate('keydown', {keyCode: 40});
            $handle.simulate('keydown', {keyCode: 40});
            $handle.trigger('blur');
            QUnit.equal('hidden', $select.val(), 'Row is hidden correctly using keyboard');
          }, 800);
        };
      },
      hideWithMouse: function ($, Drupal) {
        return function () {
          QUnit.expect(1);

          QUnit.stop();
          setTimeout(function () {
            var $dragrow = $('#field-tags');
            var $select = $(".form-item-fields-field-tags-type").find('select');
            var $handle = $dragrow.find('td a.tabledrag-handle');
            QUnit.start();
            $handle.simulate('drag', {dy: $handle.height() * 4});
            $handle.simulate('blur');
            QUnit.equal('hidden', $select.val(), 'Row is hidden correctly using mouse');
          }, 800);
        };
      }

    }
  };
})(jQuery, Drupal);
