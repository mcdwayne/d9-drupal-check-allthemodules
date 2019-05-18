(function ($, Drupal, drupalSettings) {

$.fn.extend({
  mySerialize: function() {
    var s = [];
    for (var i = 0; i < this.length; i++)
      if (this[i].type == 'select-multiple')
        s.push(this[i].name + '=' + encodeURIComponent(($(this[i]).val() || []).sort().join(',')));
      else if (this[i].type == 'checkbox')
        s.push(this[i].name + '=' + encodeURIComponent(this[i].checked + 0));
      else
        s.push(this[i].name + '=' + encodeURIComponent(this[i].value));
    return s.join('&');
  }
});

var MMSR_CONTENTS=      '0';
var MMSR_PAGES=         '1';
var MMSR_CONT_PAGES=    '2';
var MMSR_GROUPS=        '3';
var MMSR_CONTENTS_PANEL= 0;
var MMSR_PAGES_PANEL=    1;
var MMSR_GROUPS_PANEL=   2;

var MMSR_init_done, MMSR_last_recalc, MMSR_panels;

var MMSR_search_type = function (value) {
  var where = $('#mmsr-where-list');
  // don't use empty() here because it destroys event handlers
  while (where[0].childNodes[0])
    where[0].removeChild(where[0].childNodes[0]);
  switch (value) {
    case MMSR_CONTENTS:
      where.append(MMSR_panels[MMSR_CONTENTS_PANEL]);
      break;
    case MMSR_CONT_PAGES:
      where.append(MMSR_panels[MMSR_CONTENTS_PANEL]);
//    no break
    case MMSR_PAGES:
      where.append(MMSR_panels[MMSR_PAGES_PANEL]);
      break;
    case MMSR_GROUPS:
      where.append(MMSR_panels[MMSR_GROUPS_PANEL]);
  }
};

var MMSR_collapse = function (obj) {
  var tr = $(obj.parentNode.parentNode.nextSibling);
  if ($('div', tr).length) {
    tr.toggle();
    $(obj).toggleClass('collapsed');
  }
  return false;
};

var MMSR_where_changed = function () {
  var widgets = $('form>div#search-' + this.value)
    .clone(true, false)
    .removeClass('hidden')
    .show();
  $('.subpanel-select', widgets)
    .change(MMSR_subpanel_changed)
    .each(MMSR_subpanel_changed);
  var search_changed = function () {
    this.selectedIndex == 1 ? $(this).parent().siblings().show() : $(this).parent().siblings().hide();
  };
  $("[name=search-archive-0]", widgets)
    .change(search_changed)
    .each(search_changed);
  $('[name=mmsr-widgets]', this.parentNode.parentNode.nextSibling)
    .empty()
    .append(widgets)
    .parent()
    .show();
  $(':input:visible,:input[name^=search-archive]', widgets)
    .change(MMSR_recalculate);
  var oldMMLists = $('.mm-list-hidden', $('form div#search-' + this.value));
  $('.mm-list-hidden', widgets)
    .each(function (i) {
      this.mmList = oldMMLists[i].mmList;
    });
  for (var i in drupalSettings.MM.MMSR.fixups)
    if (drupalSettings.MM.MMSR.fixups.hasOwnProperty(i))
      $(i + ':not(.subpanel-inited)', widgets)
        .addClass('subpanel-inited')
        .each(drupalSettings.MM.MMSR.fixups[i]);
  $('[name=mmsr-collapse]', this.parentNode)
    .removeClass('collapsed');
  MMSR_recalculate();
};

var MMSR_plus_clicked = function () {
  var tr = this.parentNode.parentNode;
  var trcopy = $(tr)
    .clone(true, true);
  var from = $('[name=search-logic]', tr);
  $('[name=search-logic]', trcopy)
    .val(from.val())
    .show();
  var pop, trClass;
  switch (tr.className) {
    case 'mmsr-page-row':
      pop = $('[name=search-page-wheres]', trcopy);
      from = $('[name=search-page-wheres]', tr);
      trClass = 'mmsr-page-row-widgets';
      break;
    case 'mmsr-cont-row':
      pop = $('[name=search-node-wheres]', trcopy);
      from = $('[name=search-node-wheres]', tr);
      trClass = 'mmsr-cont-row-widgets';
      break;
    case 'mmsr-group-row':
      pop = $('[name=search-group-wheres]', trcopy);
      from = $('[name=search-group-wheres]', tr);
      trClass = 'mmsr-group-row-widgets';
      break;
  }
  pop
    .val(from.val());
  $(tr)
    .next()
    .after(trcopy)
    .next()
    .after('<tr class="' + trClass + '"><td id="mmsr-widgets" name="mmsr-widgets" colspan="2"></td></tr>');
  $(pop)
    .each(MMSR_where_changed);
  $('[name=mmsr-minus]:hidden', tr.parentNode)
    .show();
};

var MMSR_minus_clicked = function () {
  var tr = this.parentNode.parentNode;
  var parent = tr.parentNode;
  $(tr)
    .next()
    .remove();
  $(tr)
    .remove();
  if ($('[name=mmsr-minus]', parent).length == 1)
    $('[name=mmsr-minus]:first:visible', parent)
      .hide();
  $('[name=search-logic]:first:visible', parent)
    .hide();
  MMSR_recalculate();
};

var MMSR_subpanel_changed = function () {
  $('[name^=' + this.name + '-]', this.parentNode.parentNode)
    .hide()
    .each(function () {
      $('input', this).addClass('mmsr-ignore');
    }
  );
  $('[name=' + this.name + '-' + this.value + ']', this.parentNode.parentNode)
    .show()
    .each(function () {
      $('input', this)
        .removeClass('mmsr-ignore');
      if (!$(this).hasClass('subpanel-inited')) {
        for (var i in drupalSettings.MM.MMSR.fixups)
          if (drupalSettings.MM.MMSR.fixups.hasOwnProperty(i))
            $(i + ':not(.subpanel-inited)', this)
              .addClass('subpanel-inited')
              .each(drupalSettings.MM.MMSR.fixups[i]);
        $(this)
          .addClass('subpanel-inited');
      }
    }
  );
  MMSR_recalculate();
};

var MMSR_initialize = function (context) {
  $('#rightcolumn')
    .hide();    // hide the page's right column (admin menu)
  $('#search-logic,#search-node-type,#search-node-wheres')
    .change(MMSR_recalculate)
    .parent()
    .hide();
  $('#edit-search-group-depth,#edit-search-page-depth')
    .change(MMSR_recalculate);
  $('#edit-search-type')
    .change(
      function () {
        MMSR_search_type(this.value);
        MMSR_recalculate();
      }
    );
  $('.subpanel')
    .hide();
  $('#edit-search-archive-3-wrapper label')
    .css('margin-top', '6px');
  $('#search-archive div:gt(0)')
    .css('display', 'inline');

  // run the fixup for "pages starting at"
  var toFind = $('#search-page-catlist,#search-group-catlist'), n = 0;
  for (var i in drupalSettings.MM.MMSR.fixups) {
    if (drupalSettings.MM.MMSR.fixups.hasOwnProperty(i)) {
      var test = $(i, context);
      if (test.length) {
        var test2 = test[0].parentNode.parentNode;
        if (test2 == toFind[0] || test2 == toFind[1]) {
          test.each(drupalSettings.MM.MMSR.fixups[i]);
          if (++n == 2) break;
        }
      }
    }
  }

  $('#search-page-wheres,#search-group-wheres')
    .after('<div id="mmsr-where-list"></div>');
  MMSR_panels = [
    $('<table class="mmsr-cont-table"><tbody>'+
      '<tr class="mmsr-cont-row-widgets"><td colspan="2"><label>' + Drupal.t('of type') + ':</label></td></tr>' +
      '<tr class="mmsr-cont-row-widgets"><td colspan="2"></td></tr>' +
      '<tr class="mmsr-cont-row-widgets"><td colspan="2"><label>' + Drupal.t('where') + ':</label></td></tr>' +
      '<tr name="mmsr-cont-row" class="mmsr-cont-row"><td id="mmsr-where" name="mmsr-where" nowrap="true"></td><td name="mmsr-plus-minus" id="mmsr-plus-minus" width="0"></td></tr>' +
      '<tr class="mmsr-cont-row-widgets"><td id="mmsr-widgets" name="mmsr-widgets" colspan="2"></td></tr>' +
      '</tbody></table>'),
    $('<table class="mmsr-page-table"><tbody>' +
      '<tr class="mmsr-page-row-widgets"><td colspan="2"></td></tr>' +
      '<tr class="mmsr-page-row-widgets"><td colspan="2"><label>' + Drupal.t('where') + ':</label></td></tr>' +
      '<tr name="mmsr-page-row" class="mmsr-page-row"><td id="mmsr-where" name="mmsr-where" nowrap="true"></td><td name="mmsr-plus-minus" id="mmsr-plus-minus" width="0"></td></tr>' +
      '<tr class="mmsr-page-row-widgets"><td id="mmsr-widgets" name="mmsr-widgets" colspan="2"></td></tr>' +
      '</tbody></table>'),
    $('<table class="mmsr-group-table"><tbody>' +
      '<tr class="mmsr-group-row-widgets"><td colspan="2"></td></tr>' +
      '<tr class="mmsr-group-row-widgets"><td colspan="2"><label>' + Drupal.t('where') + ':</label></td></tr>' +
      '<tr name="mmsr-group-row" class="mmsr-group-row"><td id="mmsr-where" name="mmsr-where" nowrap="true"></td><td name="mmsr-plus-minus" id="mmsr-plus-minus" width="0"></td></tr>' +
      '<tr class="mmsr-group-row-widgets"><td id="mmsr-widgets" name="mmsr-widgets" colspan="2"></td></tr>' +
      '</tbody></table>')
  ];

  // contents panel
  $('tr:first td:first', MMSR_panels[MMSR_CONTENTS_PANEL])
    .append($('#search-node-type'));
  $('[name=mmsr-where]', MMSR_panels[MMSR_CONTENTS_PANEL])
    .append($('<a href="#" name="mmsr-collapse" onclick="return MMSR_collapse(this)"></a>'))
    .append($('#search-logic')
      .clone()
      .change(MMSR_recalculate));
  $('[name=mmsr-where]', MMSR_panels[MMSR_CONTENTS_PANEL])
    .append($('#search-node-wheres')
      .clone()
      .show()
      .change(MMSR_where_changed)
    );
  $('[name=mmsr-plus-minus]', MMSR_panels[MMSR_CONTENTS_PANEL])
    .append('<input type="button" name="mmsr-minus" value="-" title="' + Drupal.t('Delete this row') + '" style="display: none">')
    .append('<input type="button" name="mmsr-plus" title="' + Drupal.t('Add a row') + '" value="+">');
  $('[name=mmsr-plus]', MMSR_panels[MMSR_CONTENTS_PANEL])
    .click(MMSR_plus_clicked);
  $('[name=mmsr-minus]', MMSR_panels[MMSR_CONTENTS_PANEL])
    .click(MMSR_minus_clicked);

  // pages panel
  $('tr:first td:first', MMSR_panels[MMSR_PAGES_PANEL])
    .append($('#search-page-catlist'));
  $('[name=mmsr-where]', MMSR_panels[MMSR_PAGES_PANEL])
    .append($('<a href="#" name="mmsr-collapse" onclick="return MMSR_collapse(this)"></a>'))
    .append($('#search-logic')
      .clone()
      .change(MMSR_recalculate));
  $('[name=mmsr-where]', MMSR_panels[MMSR_PAGES_PANEL])
    .append($('#search-page-wheres')
      .clone()
      .show()
      .change(MMSR_where_changed)
    );
  $('[name=mmsr-plus-minus]', MMSR_panels[MMSR_PAGES_PANEL])
    .append('<input type="button" name="mmsr-minus" value="-" title="' + Drupal.t('Delete this row') + '" style="display: none">')
    .append('<input type="button" name="mmsr-plus" title="' + Drupal.t('Add a row') + '" value="+">');
  $('[name=mmsr-plus]', MMSR_panels[MMSR_PAGES_PANEL])
    .click(MMSR_plus_clicked);
  $('[name=mmsr-minus]', MMSR_panels[MMSR_PAGES_PANEL])
    .click(MMSR_minus_clicked);

  // groups panel
  $('tr:first td:first', MMSR_panels[MMSR_GROUPS_PANEL])
    .append($('#search-group-catlist'));
  $('[name=mmsr-where]', MMSR_panels[MMSR_GROUPS_PANEL])
    .append($('<a href="#" name="mmsr-collapse" onclick="return MMSR_collapse(this)"></a>'))
    .append($('#search-logic'));
  $('[name=mmsr-where]', MMSR_panels[MMSR_GROUPS_PANEL])
    .append($('#search-group-wheres')
      .clone()
      .show()
      .change(MMSR_where_changed)
    );
  $('[name=mmsr-plus-minus]', MMSR_panels[MMSR_GROUPS_PANEL])
    .append('<input type="button" name="mmsr-minus" value="-" title="' + Drupal.t('Delete this row') + '" style="display: none">')
    .append('<input type="button" name="mmsr-plus" title="' + Drupal.t('Add a row') + '" value="+">');
  $('[name=mmsr-plus]', MMSR_panels[MMSR_GROUPS_PANEL])
    .click(MMSR_plus_clicked);
  $('[name=mmsr-minus]', MMSR_panels[MMSR_GROUPS_PANEL])
    .click(MMSR_minus_clicked);

  $('#mm-search-form')
    .append('<div id="mmsr-status"><div id="mmsr-status-text"></div><div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;&nbsp;</div></div></div>');
  $('<details id="mmsr-diagnostic" class="js-form-wrapper form-wrapper"><summary role="button" aria-controls="mmsr-diagnostic" aria-expanded="true" aria-pressed="true">' + Drupal.t('Query') + '</summary><div class="details-wrapper"><div id="mmsr-diagnostic-content"></div></div></details>')
    .appendTo('#mm-search-form').parent();
  $('<input type="button" id="mmsr-recalc" value="' + Drupal.t('Recalc') + '">')
    .click(MMSR_recalculate)
    .prependTo('#mmsr-status');
  $('#mmsr-status #mmsr-status-text')
    .before(
      $('#edit-result')
        .click(function () {
          MMSR_serialize();
          return true;
        }))
    .before(
      $('#edit-reset')
        .click(function () {
          MMSR_init_done = false;
          $('input[type=button][name=mmsr-minus]:gt(0)', MMSR_panels[MMSR_CONTENTS_PANEL])
            .click();
          $('input[type=button][name=mmsr-minus]:gt(0)', MMSR_panels[MMSR_PAGES_PANEL])
            .click();
          $('input[type=button][name=mmsr-minus]:gt(0)', MMSR_panels[MMSR_GROUPS_PANEL])
            .click();
          MMSR_import(drupalSettings.MM.MMSR.reset, document);
          MMSR_init_done = true;
          MMSR_recalculate();
          return false;
        }));

  MMSR_import(drupalSettings.MM.MMSR.startup, document);
  $("[name=search-page-cat]", MMSR_panels[MMSR_PAGES_PANEL])
    .val(drupalSettings.MM.MMSR.startup['search-page-cat'])
    .trigger('change');
  $("[name=search-group-cat]", MMSR_panels[MMSR_GROUPS_PANEL])
    .val(drupalSettings.MM.MMSR.startup['search-group-cat'])
    .trigger('change');
  MMSR_init_done = true;
  MMSR_recalculate();
  Drupal.behaviors.mmListInit = {attach: function() {}};
};

var MMSR_import = function (obj, where) {
  var row, i;
  if (typeof(obj) == 'object')
    if (typeof(obj.length) == 'number') { // array
      for (i in obj) {
        if (obj.hasOwnProperty(i)) {
          row = where;
          if (i > 0) {
            $('[name=mmsr-plus]:last', row)
              .trigger('click');
            row = row.nextSibling.nextSibling;
          }
          $(row)
            .each(
              function() {
                MMSR_import_inner(obj[i], this)
              });
        }
      }
    }
    else {
      for (i in obj) {
        if (obj.hasOwnProperty(i)) {
          row = where;
          if (row.tagName == 'TR' && i != 'search-logic' && i != 'search-node-wheres' && i != 'search-page-wheres' && i != 'search-group-wheres')
            row = row.nextSibling;
          $('[name="' + i + '"]:first', row)
            .each(
              function() {
                MMSR_import_inner(obj[i], this);
              });
        }
      }
    }
};

var MMSR_import_inner = function (obji, where) {
  if (typeof(obji) == 'object')
    MMSR_import(obji, where);
  else {
    where.name.substr(-2) == '[]' ? $(where).val(obji.split(',')) : where.value = obji;
    if (where.tagName == 'SELECT' || where.tagName == 'INPUT')
      $(where).trigger('change');
  }
};

var MMSR_serialize = function () {
  if (MMSR_init_done) {
    var data = $($.merge($.merge(jQuery.makeArray(
      $("#edit-search-type,#mmsr-where-list table :input:not(:button):not(:submit):not([style='display: none']):not(.mmsr-ignore):not(.form-autocomplete[name$='-choose']):not([name=search-page-cat]):not([name=search-group-cat])")),
      $("[name=search-page-cat]", MMSR_panels[MMSR_PAGES_PANEL])),
      $("[name=search-group-cat]", MMSR_panels[MMSR_GROUPS_PANEL])))
      .mySerialize();
    if (data !== MMSR_last_recalc) {
      $('#edit-data')
        .val(data);
      MMSR_last_recalc = data;
      return data;
    }
  }
  return false;
};

var MMSR_recalculate = function () {
  var data = MMSR_serialize();
  if (data) {
    $('#mmsr-status-text')
      .hide()
      .next()
      .show();
    $.ajax({
      type:     'POST',
      dataType: 'json',
      data:     { data: data },
      url:      drupalSettings.MM.MMSR.get_path,
      global:   false,
      success:  function (obj) {
                  $('#mmsr-status-text')
                    .html(obj.result || Drupal.t('An error occurred.'))
                    .show()
                    .next()
                    .hide();
                  $('#mmsr-diagnostic-content')
                    .html(obj.query || '');
                },
      error:    function () {
                  $('#mmsr-status-text')
                    .html(Drupal.t('An error occurred.'))
                    .show()
                    .next()
                    .hide();
                  $('#mmsr-diagnostic-content')
                    .html('');
                }
    });
  }
};

var MMSR_onchange_flags = function (obj, name) {
  $('[name="' + name + '"]', obj.parentNode.parentNode).toggle(obj.value < 10);
  return true;
};

var MMSR_onchange_node_types = function (obj) {
  $('[name="allowed_node_types[]"]', obj.parentNode.parentNode).toggle(obj.value != 2 && obj.value != 5);
  return true;
};

Drupal.behaviors.MMSR_initialize = {
  attach: function() {
    /*********** Fixup code for various custom element types ***********/

    drupalSettings.MM.MMSR.fixups["[name^=\'others_\']"] = function () {
      $(this).change(function () {
        if (this.checked) {
          $('.mm-list-hidden:not([name=owner])', this.parentNode.parentNode.parentNode).each(function() {
            this.delAll();
          });
        }
      });};

    $.each(drupalSettings.MM.mmListInit, function(key, instance) {
      drupalSettings.MM.MMSR.fixups['[name="mm_list_obj' + key + '"]'] = function() {
        if (instance) {
          // Clone parms so that changes at lower levels don't affect other instances.
          instance.parms = jQuery.extend(true, {}, instance.parms);
          var obj = mmListGetObj(this.parentNode.parentNode, instance.listObjDivSelector, instance.outerDivSelector, instance.hiddenName, instance.autoName, instance.parms);
          if (obj && obj.addItem) {
            $.each(instance.add, function(key, list) {
              list.unshift(false);
              obj.addItem.apply(obj, list);
            });
            obj.enableOpts();
            $(obj.p.hiddenElt).change(function() { mmListImport(obj, this.value); });
          }
        }
      };
    });

    Drupal.autocomplete.options.select = function(event, ui) {
      event.target.value = ui.item.label;
      return false;
    };
    drupalSettings.MM.MMSR.fixups['input.form-autocomplete'] = function() {
      Drupal.behaviors.autocomplete.attach(this.parentNode);
    };

    MMSR_initialize();
  }
};

})(jQuery, Drupal, drupalSettings);
