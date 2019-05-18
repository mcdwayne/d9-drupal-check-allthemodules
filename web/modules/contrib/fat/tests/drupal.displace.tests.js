/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Tests Drupal.displace().
   */
  Drupal.tests.displace = {
    getInfo: function() {
      return {
        name: 'Drupal displace utility.',
        description: 'Tests for the viewport component displacement utility.',
        group: 'System'
      };
    },
    setup: function () {
      // Append a set of elements to the body that will be used as displacing
      // elements. Make sure that they are bigger than the toolbar.
      $('<div id="testswarm-displace-test-displacing-container">')
        // Top.
        .append(
          $('<div id="testswarm-displace-test-displacing-top">')
            .css({
              'background-color': 'red',
              'height': '110px',
              'left': 0,
              'position': 'fixed',
              'right': 0,
              'top': '90px',
              'width': '100%',
              'z-index': 10000
            })
            .attr('data-offset-top', '')
        )
        // Right.
        .append(
          $('<div id="testswarm-displace-test-displacing-right">')
            .css({
              'background-color': 'blue',
              'bottom': 0,
              'height': '100%',
              'position': 'fixed',
              'right': '10px',
              'top': 0,
              'width': '100px',
              'z-index': 10000
            })
            .attr('data-offset-right', '')
        )
        // Bottom.
        .append(
          $('<div id="testswarm-displace-test-displacing-bottom">')
            .css({
              'background-color': 'yellow',
              'bottom': '45px',
              'height': '100px',
              'left': 0,
              'position': 'fixed',
              'right': 0,
              'width': '100%',
              'z-index': 10000
            })
            .attr('data-offset-bottom', '')
        )
        // Left.
        .append(
          $('<div id="testswarm-displace-test-displacing-left">')
            .css({
              'background-color': 'orange',
              'bottom': 0,
              'height': '100%',
              'left': '10px',
              'position': 'fixed',
              'top': 0,
              'width': '300px',
              'z-index': 10000
            })
            .attr('data-offset-left', '')
        )
        .appendTo('body');
        // Create a DOM element that will respond to displaced screen elements.
        $('<div id="testswarm-displace-test-displaced">')
          .css({
            'background-color': 'black',
            'top': '0',
            'max-height': '100%',
            'left': '0',
            'opacity': '0.4',
            'position': 'fixed',
            'max-width': '100%',
            'z-index': 10001
          })
          .appendTo('body');

      // Register a drupalViewportOffsetChange handler on the document.
      $(document).on('drupalViewportOffsetChange.testswarm', drupalViewportOffsetChangeHandler);
    },
    teardown: function () {
      // $('#testswarm-displace-test-displacing-container').remove();
      $('#testswarm-displace-test-displaced').remove();
      $(document).off('drupalViewportOffsetChange.testswarm');
    },
    tests: {
      displace: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(4);

          // Trigger a calculation of the displacing elements and a
          // drupalViewportOffsetChange event.
          Drupal.displace();

          // Confirm that the displaced element is shifted by the dimensions of
          // displacing elements on each edge.
          //
          // Get the dimensions and placement for the displaced element.
          var $displacedElement = $('#testswarm-displace-test-displaced');
          var displacedElementWidth = $displacedElement.outerWidth();
          var displacedElementHeight = $displacedElement.outerHeight();
          var displacedElementTop = $displacedElement.offset().top - window.scrollY;
          var displacedElementLeft = $displacedElement.offset().left - window.scrollX;
          var displacedElementRight = document.documentElement.clientWidth - (displacedElementLeft + displacedElementWidth);
          var displacedElementBottom = document.documentElement.clientHeight - (displacedElementTop + displacedElementHeight);

          // Verify each edge.
          QUnit.equal(displacedElementTop, 200, Drupal.t('The top of the displaced element has the expected offset.'));
          QUnit.equal(displacedElementRight, 110, Drupal.t('The right of the displaced element has the expected offset.'));
          QUnit.equal(displacedElementBottom, 145, Drupal.t('The bottom of the displaced element has the expected offset.'));
          QUnit.equal(displacedElementLeft, 310, Drupal.t('The left of the displaced element has the expected offset.'));
        };
      }
    }
  };

  /**
   * Responds to the drupalViewportOffsetChange event.
   */
  function drupalViewportOffsetChangeHandler (event, offsets) {
    $('#testswarm-displace-test-displaced')
      .css({
        'top': offsets.top,
        'right': offsets.right,
        'bottom': offsets.bottom,
        'left': offsets.left
      });
  }

}(jQuery, Drupal, this, this.document));
