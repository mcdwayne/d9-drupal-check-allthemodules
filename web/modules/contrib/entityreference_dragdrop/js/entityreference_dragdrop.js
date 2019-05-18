(function($){
  
  Drupal.entityreference_dragdrop = Drupal.entityreference_dragdrop ? Drupal.entityreference_dragdrop : {};

  // Used a 'update' event handler for sortable plugin.
  Drupal.entityreference_dragdrop.update = function (event, ui) {
    var items = [];
    var key = $(event.target).attr("data-key");
    $(".entityreference-dragdrop-selected[data-key=" + key + "] li[data-key=" + key + "]").each(function(index) {
      items.push($(this).attr('data-id'));
    });
    $("input.entityreference-dragdrop-values[data-key=" + key +"]").val(items.join(','));
    
    if (drupalSettings.entityreference_dragdrop[key] != -1) {
      if (items.length > drupalSettings.entityreference_dragdrop[key]) {
        $(".entityreference-dragdrop-message[data-key=" + key + "]").show();
        $(".entityreference-dragdrop-selected[data-key=" + key + "]").css("border", "1px solid red");
      }
      else {
        $(".entityreference-dragdrop-message[data-key=" + key + "]").hide();
        $(".entityreference-dragdrop-selected[data-key=" + key + "]").css("border", "");
      }
    }
  };

  // Filters items in a widget.
  Drupal.entityreference_dragdrop.filter = function (input) {
    var $input = $(input);
    var val = $input.val().toLowerCase();
    if (val != '') {
      $input.parents('.entityreference-dragdrop-container').find('li.ui-sortable-handle').each(function(i, elem) {
        var $elem = $(elem);
        if ($elem.data('label').toLowerCase().indexOf(val) >= 0) {
          $elem.show();
        }
        else {
          $elem.hide();
        }
      });
    }
    else {
      $input.parents('.entityreference-dragdrop-container').find('li').show();
    }
  };
  
  Drupal.behaviors.entityreference_dragdrop = {
    attach: function() {
      var $avail = $(".entityreference-dragdrop-available"),
        $select = $(".entityreference-dragdrop-selected");


      $avail.once('entityreference-dragdrop').each(function () {
        var key = $(this).data('key');
        var $sortableAvail = $(this).sortable({
          connectWith: 'ul.entityreference-dragdrop[data-key=' + key + ']',
          items: '.sorting-initialize'
        });
        $sortableAvail.find('li[data-key=' + key + ']').one('mouseenter', function(){
          $(this).addClass('sorting-initialize');
          $sortableAvail.sortable('refresh');
        });
      });

      $select.once('entityreference-dragdrop').each(function() {
        var key = $(this).data('key');
        var $sortableSelect = $(this).sortable({
          connectWith: "ul.entityreference-dragdrop[data-key=" + key + ']',
          items: '.sorting-initialize',
          update: Drupal.entityreference_dragdrop.update
        });
        $sortableSelect.find('li[data-key=' + key + ']').one('mouseenter', function() {
          $(this).addClass('sorting-initialize');
          $sortableSelect.sortable('refresh');
        });
      });

      $('.entityreference-dragdrop-filter').once('entityreference-dragdrop').each(function() {
        Drupal.entityreference_dragdrop.filter(this);
        $(this).bind('keyup paste', function() {
          Drupal.entityreference_dragdrop.filter(this);
        });
      });
    }
  };
})(jQuery);
