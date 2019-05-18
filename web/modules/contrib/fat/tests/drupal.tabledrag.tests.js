/*jshint stict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {
  "use strict";
  /**
   * Drag function.
   */
  function drag (handle, dx, dy) {
    $(handle).simulate("drag", {
      dx: dx || 0,
      dy: dy || 0
    });
  }
  function checkRowWeights($table) {
    var rightorder = true;
    var previousweight;
    // Check all the row weights
    $table.find('tr').each(function() {
      var currentweight = $(this).find('.menu-weight').val();
      if (typeof previousweight !== 'undefined') {
        rightorder = rightorder && (parseInt(previousweight, 10) <= parseInt(currentweight, 10));
      }
      previousweight = currentweight;
    });
    // Check the row weights
    QUnit.ok(rightorder, Drupal.t('The row weights are in compliance with the order of the rows.'));
  }

  /**
   * Tests tabledrag.
   */
  Drupal.tests.dragdrop = {
    getInfo: function() {
      return {
        name: 'Tabledrag',
        description: 'Tests for tabledrag.',
        group: 'System',
        useSimulate: true
      };
    },
    tests: {
      reorderVertical: function ($, Drupal, window, document, undefined) {
        return function() {
          QUnit.expect(2);

          var $table = $('#menu-overview');
          var $dragrow = $table.find('.draggable').eq(1);
          var handle = $dragrow.find('.handle:first')[0];
          var dy = -$dragrow.height();
          var rowid = $dragrow.attr('id');
          if (typeof rowid === 'undefined') {
            // add a row id so we can identify the row.
            rowid = 'testswarm-tabledrag-row';
            $dragrow.attr('id', rowid);
          }

          // drag the row one up.
          drag(handle, 0, dy);
          // Check the position of the row.
          QUnit.equal(rowid, $table.find('tr.draggable').eq(0).attr('id'), Drupal.t('Row is in the correct position after draggin in vertically'));

          if (rowid === 'testswarm-tabledrag-row') {
            //remove our row id for consistency.
            $dragrow.removeAttr('id');
          }

          // Check the row weights.
          checkRowWeights($table);
        };
      },
      reorderHorizontal: function ($, Drupal, window, document, undefined) {
        return function () {
          QUnit.expect(3);
          var tabledrag = Drupal.tableDrag['menu-overview'];
          var $table = $('#menu-overview');
          var $dragrow = $table.find('.draggable').eq(1);
          var handle = $dragrow.find('.handle:first')[0];
          var origpos = $(handle).position();
          var expected = {top: origpos.top, left: origpos.left + tabledrag.indentAmount};
          var rowid = $dragrow.attr('id');
          if (typeof rowid === 'undefined') {
            // add a row id so we can identify the row.
            rowid = 'testswarm-tabledrag-row';
            $dragrow.attr('id', rowid);
          }
          // Make the dx larger than the indentAmount to make sure the element
          // is not dragged further than it is supposed to.
          var dx = tabledrag.indentAmount + (tabledrag.indentAmount / 2) - 2;
          // drag the row to the right.
          drag(handle, dx);
          var newpos = $(handle).position();

          // Allow a margin of a tenth of the indentamount for the horizontal dragging.
          var margin = tabledrag.indentAmount / 10;
          QUnit.ok((newpos.left - expected.left) < margin, Drupal.t('The row is at the expected position after dragging it to the right.'));
          // Vertical position should not have changed at all.
          QUnit.equal(rowid, $table.find('tr.draggable').eq(1).attr('id'), Drupal.t('Row is in the correct position after draggin in horizontally'));

          if (rowid === 'testswarm-tabledrag-row') {
            //remove our row id for consistency.
            $dragrow.removeAttr('id');
          }

          // Check the row weights.
          checkRowWeights($table);

        };
      }
    }
  };

})(jQuery, Drupal, this, this.document);
