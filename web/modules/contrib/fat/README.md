# JavaScript test file template

``` javascript

/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";

  Drupal.tests.projectName = {
    getInfo: function() {
      return {
        name: '',
        description: '',
        group: ''
      };
    },
    setup: function () {},
    teardown: function () {},
    tests: {
      individualTestName: function () {
        return function() {
          QUnit.expect(0);
        };
      }
    }
  };
}(jQuery, Drupal, this, this.document));

```
