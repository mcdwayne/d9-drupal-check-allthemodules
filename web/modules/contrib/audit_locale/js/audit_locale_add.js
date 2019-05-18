(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.audit_rule_edit = {
    attach: function (context) {
      // 解决右边栏折叠和收缩的问题
      $(".orginfo").click(function() {
        var t = $(this).find("i").hasClass('expanded');
        if (t) {
          $(this).find("i").removeClass('expanded');
          $(this).siblings("div.emps").css({"display":"none"});
          $(this).siblings("div.orgs").css({"display":"none"});
        } else {
          $(this).find("i").addClass('expanded');
          $(this).siblings("div.emps").css({'display':'block'});
          $(this).siblings("div.orgs").css({'display':'block'});
        }
      });

      // 解决点击右边的用户列表选项，自动追加选项到左边框的问题
      $(".approval-useritem.approval-empitem").click(function(){
        var $this = $(this);
        var $img = $this.find("p").siblings('img')[0];

        if ($img == "" || $img == undefined || $img == null) {
          var $avatar = '//gw.alicdn.com/tps/TB1lY.0OpXXXXaWaXXXXXXXXXXX-80-80.png';
        } else {
          var $avatar = $img.src;
        }

        var $user = "<div class='approval-item approval-canvas-item' data-id='"+ $this.data('id') +"'><div class='approval-item-person draghandler'><img class='approval-item-avatar' src='"+ $avatar +"'><div class='approval-item-name-director'>"+ $this.find("p").text() +"</div></div><i class='kuma-icon kuma-icon-close approval-item-remove'></i><div class='approval-item-line'></div></div>";
        $("div.column-left .approval-canvas .approval-inner").append($user);
      });


      // 点击移除时
      // 解决jquery 对后来生成的js代码的click事件无效
      $('.approval-inner').on("click", ".approval-item .approval-item-remove", function(){
        var $this = $(this);
        $this.parent().remove();
      });
      // 选项移动
      $('#edit-submit').click(function(){
        var $data = new Array();
        $(".approval-inner .approval-item").each(function(){
          $data.push($(this).data("id"));
        });
        var $url;
        if ( drupalSettings.audit_locale.id) {
          $url = Drupal.url('ajax/audit_locale/'+ drupalSettings.audit_locale.module + '/' + drupalSettings.audit_locale.id +'/rule/add');
        } else {
          $url = Drupal.url('ajax/audit_locale/'+ drupalSettings.audit_locale.module +'/rule/add');
        }
        $.post({
          url: $url,
          data: {'id': $data},
          success: function(msg) {
            alert(msg);
          }
        });
      });

      // 选项移动
      $('#edit_control_save').click(function(){
        var $data = new Array();
        $(".approval-inner .approval-item").each(function(){
          $data.push($(this).data("id"));
        });
        var $url;
        var $url_overview;
        if ( drupalSettings.audit_locale.id) {
          $url = Drupal.url('ajax/audit_locale/'+ drupalSettings.audit_locale.module + '/' + drupalSettings.audit_locale.id +'/rule/add');
          $url_overview = Drupal.url('admin/audit_locale/'+ drupalSettings.audit_locale.module + '/' + drupalSettings.audit_locale.id +'/rule/overview');
        } else {
          $url = Drupal.url('ajax/audit_locale/'+ drupalSettings.audit_locale.module +'/rule/add');
          $url_overview = Drupal.url('admin/audit_locale/'+ drupalSettings.audit_locale.module +'/rule/overview');
        }



        $.post({
          url: $url,
          data: {'id': $data},
          success: function(msg) {
            alert(msg);
            location.href = $url_overview;
          }
        });
      });

      $('#edit_control_cancel').click(function() {
        $('#drupal-modal').remove();
        $('.modal-backdrop').remove();
      });

      $('.approval-panel-close').click(function() {
        $('.modal-dialog').hide();
      });
    }
  }
})(jQuery, Drupal, drupalSettings);

