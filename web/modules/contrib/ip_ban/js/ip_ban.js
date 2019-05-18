(function ($) {
  'use strict';
  Drupal.behaviors.ipBan = {
    attach: function (context, settings) {
      $('#edit-ip-ban-setdefault', context).change(function () {
        var selected = this.selectedIndex;
        $('.ip-ban-table-cell', context).attr('selectedIndex', selected);
        $('.ip-ban-table-cell', context).val(selected).change();
      });
    }
  };
}(jQuery));
