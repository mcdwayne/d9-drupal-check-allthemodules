(function ($, Drupal) {
  Drupal.selectMenuAddSelect = function (ul, div, preselect) {
    var select = $('<select />')
      .change(function (event, preselect) {
        $(this).nextAll().remove();
        var href = this.value;  // $(this).val() doesn't work in IE
        if (href && href[0] != '~')
          if (window.parent) window.parent.location = href;
          else document.location = href;
        else {
          $('.select-menu-no-val', this).remove();
          $($(':selected', this)[0].li).next('ul')
            .each(function () {
              Drupal.selectMenuAddSelect(this, div, preselect);
            });
        }
      })
      .attr('id', $(ul).attr('id'))
      .appendTo(div);

    var title = $(ul).attr('title');
    $('<label />')
      .attr('for', $(ul).attr('id'))
      .text(title ? title + ':' : '')
      .insertBefore(select)
      .after('&nbsp;');

    if (!$('.select-menu-no-val', select).length)
      $('<option class="select-menu-no-val" value="">' + Drupal.t('(choose)') + '<' + '/option>')
        .appendTo(select);

    var kids = 0, kid_val;
    $(ul)
      .children('li')
      .each(function () {
        kids++;
        var a = $('a', this);
        kid_val = a.length ? a[0].href : '~' + $(this).text();
        $('<option>' + $(this).text() + '<' + '/option>')
          .appendTo(select)
          .val(kid_val)
          [0].li = this;
      });

    if (kids == 1 && preselect && !kid_val) {
      $('.select-menu-no-val', select).remove();
      if (select.val() == '') select.trigger('change', [true]);
    }
  };

  Drupal.behaviors.selectMenuInit = {
    attach: function (context) {
      $('ul.select-menu', context).once('selectMenuInit').each(function () {
        $(this).hide();
        var div = $('<div class="select-menu"/>').insertAfter(this);
        // pre-select active path
        var list = $('a.active', this)
          .parents('ul');
        Drupal.selectMenuAddSelect(this, div, list.length == 0);
        // traverse the list backwards
        if (list.length) {
          var select = $('select', div);
          for (var i = list.length - 1; --i >= 0 && select.length;) {
            select.val('~' + $(list[i]).prev().text());
            select.trigger('change');
            select = select.next();
          }

          if (select.length) {
            select.val($('a.active', this)[0].href);
            $('.select-menu-no-val', select).remove();
          }
        }
      });
    }
  };
})(jQuery, Drupal);