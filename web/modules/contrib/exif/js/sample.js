/**
 * @file
 * File 'sample.js' is used in sample page for add some L&F.
 */

jQuery(document).ready(function () {
  jQuery('tr').css('display', 'table-row');
  jQuery("tr.metadata-row:odd").addClass('odd');
  // Bind a click-handler to the 'tr' elements with the 'header' class-name.
  jQuery('tr.metadata-section').click(function () {
    /* get all the subsequent 'tr' elements until the next 'tr.header',
     set the 'display' property to 'none' (if they're visible), to 'table-row'
     if they're not: */
    jQuery(this).nextUntil('tr.metadata-section').css('display', function (i, v) {
      return this.style.display === 'table-row' ? 'none' : 'table-row';
    });
  });
});
