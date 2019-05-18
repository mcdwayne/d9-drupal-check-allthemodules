(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.audit_locale_overview = {
    attach: function (context) {

      $(".approval-setting").once().click(function() {
        var $url = '';
        if (drupalSettings.audit_locale.id) {
          $url = Drupal.url('admin/audit_locale/'+ drupalSettings.audit_locale.module + '/' + drupalSettings.audit_locale.id + '/rule/add');
        } else {
          $url = Drupal.url('admin/audit_locale/'+ drupalSettings.audit_locale.module + '/rule/add');
        }
        //location.href = $url;
          var ajaxDialog = Drupal.ajax({
            dialog: {
              title: '添加审批人',
              width: 'auto',
            },
            dialogType: 'modal',//还可以是dialog，但dialog模式用ajax请求返回CloseModalDialogCommand命令会报错
            url: $url,//'/test/dialog/return',
          });
          ajaxDialog.execute();
          return false;
      });

      // 审批列表.
      $("#acalepool").jqGrid({
        url: Drupal.url('ajax/audit_locale/pool'),
        datatype: "json",
			  height : 'auto',
        colNames: ['ID', '类型', '模型编号', '角色', '审批用户', '权重(审批顺序)', '创建人', '创建时间'],
        colModel:[
          {name: 'id', index: 'id', width: 40, editable: false},
          {name: 'module', index: 'module', editable: false},
          {name: 'module_id', index: 'module_id', editable: false},
          {name: 'role', index: 'role', editable: false},
          {name: 'aid', index: 'aid', editable: false},
          {name: 'weight', index: 'weight', editable: false},
          {name: 'uid', index: 'uid', editable: false},
          {name: 'created', index: 'created', editable: false},
        ],
				rowNum : 10,
				rowList : [10, 20, 50, 100, 1000, 5000],
				pager : '#acalepoolnav',
				sortname : 'id',
				autowidth : true,
        toolbarfilter : true,
        viewrecords: true,
        recordpos: 'right',
        caption: "审批列表",
     });
      $("#acalepool").jqGrid('navGrid', "#acalepoolnav", {
        edit : false,
        add : false,
        del : false,
        search: true,
        refresh:true,
      });
			$(window).on('resize.jqGrid', function() {
				$("#acalepool").jqGrid('setGridWidth', $("#content").width());
			})
      // remove classes
      $(".ui-jqgrid").removeClass("ui-widget ui-widget-content");
      $(".ui-jqgrid-view").children().removeClass("ui-widget-header ui-state-default");
      $(".ui-jqgrid-labels, .ui-search-toolbar").children().removeClass("ui-state-default ui-th-column ui-th-ltr");
      $(".ui-jqgrid-pager").removeClass("ui-state-default");
      $(".ui-jqgrid").removeClass("ui-widget-content");

      // add classes
      $(".ui-jqgrid-htable").addClass("table table-bordered table-hover");
      $(".ui-jqgrid-btable").addClass("table table-bordered table-striped");

      $(".ui-pg-div").removeClass().addClass("btn btn-sm btn-primary");
      $(".ui-icon.ui-icon-plus").removeClass().addClass("fa fa-plus");
      $(".ui-icon.ui-icon-pencil").removeClass().addClass("fa fa-pencil");
      $(".ui-icon.ui-icon-trash").removeClass().addClass("fa fa-trash-o");
      $(".ui-icon.ui-icon-search").removeClass().addClass("fa fa-search");
      $(".ui-icon.ui-icon-refresh").removeClass().addClass("fa fa-refresh");
      $(".ui-icon.ui-icon-disk").removeClass().addClass("fa fa-save").parent(".btn-primary").removeClass("btn-primary").addClass("btn-success");
      $(".ui-icon.ui-icon-cancel").removeClass().addClass("fa fa-times").parent(".btn-primary").removeClass("btn-primary").addClass("btn-danger");

      $(".ui-icon.ui-icon-seek-prev").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-prev").removeClass().addClass("fa fa-backward");

      $(".ui-icon.ui-icon-seek-first").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-first").removeClass().addClass("fa fa-fast-backward");

      $(".ui-icon.ui-icon-seek-next").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-next").removeClass().addClass("fa fa-forward");

      $(".ui-icon.ui-icon-seek-end").wrap("<div class='btn btn-sm btn-default'></div>");
      $(".ui-icon.ui-icon-seek-end").removeClass().addClass("fa fa-fast-forward");


    }
  }
})(jQuery, Drupal, drupalSettings);
