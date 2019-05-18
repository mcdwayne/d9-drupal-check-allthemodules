/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   *
   */
  Drupal.tests.appearance = {
    getInfo: function() {
      return {
        name: 'Theme appearance test',
        description: 'Testing the theme appearance settings',
        group: 'Core'
      };
    },
    tests: {
      themeSettings: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(1);

          // Select 'Header background top' color.
          $('#edit-palette-top').focus();

          // Set the 'Header background top' color with the color picker.
          $.farbtastic('#placeholder').setColor('#ff3344');

          // Check if the 'Header background top' color has changed.
          var rgb = $('#edit-palette-top').css('background-color');
          rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
          var color = "#" + ("0" + parseInt(rgb[1]).toString(16)).slice(-2)
                          + ("0" + parseInt(rgb[2]).toString(16)).slice(-2)
                          + ("0" + parseInt(rgb[3]).toString(16)).slice(-2);

          QUnit.ok(color === '#ff3344', Drupal.t('The "Header background top" color has changed to #ff3344.'));
        };
      }
    }
  };
}(jQuery, Drupal, this, this.document));
