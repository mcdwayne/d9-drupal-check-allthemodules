(function ($, Drupal, drupalSettings) {
  if (typeof(Drupal.mmgroupTable) == 'undefined') Drupal.mmgroupTable = [];

  Drupal.behaviors.MMUserDataTable = {
    attach: function (context, settings) {
      var $elem = $('#mm-user-datatable-' + settings.MM.group_table.id_name, context);
      $elem.once('mm-user-datatable').each(function () {
        Drupal.mmgroupTable[settings.MM.group_table.clean_element] = $elem.dataTable({
          aoColumns:      settings.MM.group_table.col_def,
          bProcessing:    true,
          bServerSide:    true,
          iDisplayLength: 20,
          bLengthChange:  false,
          sAjaxSource:    settings.MM.group_table.load,
          oLanguage:      {
            sInfo:         Drupal.t('Showing _START_ to _END_ of _TOTAL_ record(s)'),
            sInfoFiltered: Drupal.t('(filtered from _MAX_ total record(s))')}
        });
      });
    }
  };

  Drupal.mmGroupRemoveUser = function (uid, element) {
    $.ajax({
      url:     drupalSettings.MM.group_table.delete.replace('-USER-', uid),
      async:   false,
      success: function () {
        Drupal.mmgroupTable[element].fnDraw();
      }
    });
  };

  Drupal.mmGroupAddUser = function (mmListObj) {
    var uids;
    if (mmListObj.length && (uids = mmListObj.val())) {
      $.ajax({
        url:     drupalSettings.MM.group_table.add.replace('-UIDS-', uids.replace(/\{.*?\}/g, ',').replace(/,$/, '')),
        async:   false,
        success: function () {
          Drupal.mmDialogClose();
          mmListObj[0].delAll();
          $(mmListObj[0].mmList.p.autoCompleteObj).val('');
          Drupal.mmgroupTable['members'].fnDraw();
        }
      });
    }
    else {
      Drupal.mmDialogClose();
    }
    return false;
  };
})(jQuery, Drupal, drupalSettings);