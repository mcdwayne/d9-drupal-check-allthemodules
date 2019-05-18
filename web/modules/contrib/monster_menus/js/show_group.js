(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.MMShowGroup = {
    attach: function () {
      var initialResize = 0;
      $('#mm-user-datatable-members-display').dataTable({
        aoColumns:      drupalSettings.MM.show_group.col_def,
        bProcessing:    true,
        bServerSide:    true,
        iDisplayLength: 20,
        bLengthChange:  false,
        sAjaxSource:    drupalSettings.MM.show_group.link_location,
        oLanguage:      {
          sInfo:         Drupal.t('Showing _START_ to _END_ of _TOTAL_ record(s)'),
          sInfoFiltered: Drupal.t('(filtered from _MAX_ total record(s))')
        },
        fnDrawCallback: function() {
          if (!initialResize++) {
            parent.Drupal.mmDialogResized(document.body.scrollWidth, document.body.scrollHeight);
          }
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);