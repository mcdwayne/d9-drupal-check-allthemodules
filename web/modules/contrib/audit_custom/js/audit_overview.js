(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.audit_overview = {
    attach: function (context) {
      //console.log(Drupal);
      /*
      $("#check_audit").once().click(function() {
        var $url = '';
        $url = Drupal.url('admin/audit/'+ drupalSettings.audit_locale.module + '/overview');
        var ajaxDialog = Drupal.ajax({
          dialog: {
            title: '审批流程进度详情',
            width: 'auto',
          },
          dialogType: 'modal',
          url: $url,
        });
        ajaxDialog.execute();
        return false;
      });
      */
    }
  }
})(jQuery, Drupal, drupalSettings);

