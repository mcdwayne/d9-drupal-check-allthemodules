(function($){
  var button = document.getElementById("ot-show-hide");
  var allClass = document.getElementsByClassName("ot-show-hide-tab");

  function ShowHideTab(event) {
    event.preventDefault();
    button.innerHTML = (button.innerHTML == "#Show items") ? "#Hide items" : "#Show items";
    for (var i = 0; i < allClass.length; i++) {
      allClass[i].classList.toggle('hide');
    }
  }
  if(button){
    button.addEventListener("click", ShowHideTab);
  }
  Drupal.behaviors.ot_show_hide = {
    attach: function(context, settings){
      var count_td = $('table#edit-ot-modify-multiple thead th').length;
      if(count_td == 12){
        $('#edit-ot-modify-multiple thead th:nth-child(9), #edit-ot-modify-multiple thead th:nth-child(10)').addClass('ot-show-hide-tab hide');
        $('#edit-ot-modify-multiple tbody tr').each(function(){
          $(this).find('td:nth-child(9), td:nth-child(10)').addClass('ot-show-hide-tab hide');
        });
      }
      if(count_td == 11){
        $('#edit-ot-modify-multiple thead th:nth-child(8), #edit-ot-modify-multiple thead th:nth-child(9)').addClass('ot-show-hide-tab hide');
        $('#edit-ot-modify-multiple tbody tr').each(function(){
          $(this).find('td:nth-child(8), td:nth-child(9)').addClass('ot-show-hide-tab hide');
        });
      }
    }
  };

  Drupal.behaviors.ot_label_change = {
    attach: function(context, settings){
      $('input[name="ot_type"]').change(function(){
        var ot_type_id = $('input[name="ot_type_id"]');
        ot_type_id.val('');
        $('#ot-type-id-exists').empty();
        if($(this).val() == 'node_path'){
          ot_type_id.siblings('label').text('Node and Path/URL');
        }else if($(this).val() == 'view'){
          ot_type_id.siblings('label').text('Views');
        }
      });
    }
  };

  Drupal.behaviors.ot_span_html = {
    attach: function(context, settings){
      $('#override-title tbody td').each(function(){
        var string1 = $(this).html();
        if(string1.indexOf('&lt;span class="error-ot"&gt;&amp;#10006;&lt;/span&gt;') != -1){
          var string2 = string1.replace('&lt;span class="error-ot"&gt;&amp;#10006;&lt;/span&gt;', '');
          $(this).html('<span class="error-ot">&#10006;</span>'+string2);
        }
      });
    }
  };

  Drupal.behaviors.ot_del_confirm = {
    attach: function(context, settings){
      otDelete = function () {
        if($('select[name="ot_action"] option:selected').val() == 'ot_delete'){
          return confirm("Are you sure want to delete all selected OT items?") ? true: false;
        }
      };
    }
  };
})(jQuery);
