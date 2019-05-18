(function ($, Drupal) {
  $("#manage-bookmarks-div ul.sortable").sortable({
    axis:                 'y',
    containment:          '#manage-bookmarks-div',
    placeholder:          'ui-state-highlight',
    forcePlaceholderSize: true,
    opacity:              0.5,
    tolerance:            "pointer",
    stop:                 function() {
      var serialStr = '';
      $("#manage-bookmarks-div ul.sortable li").each(function() {
        serialStr += $(this).attr("name") + "|";
      });
      $.post(Drupal.mmBrowserAppendParams(Drupal.url("mm-bookmarks/sort")),
        { neworder: serialStr.substring(0, serialStr.length - 1) },
        function() {
          Drupal.mmBrowserGetBookmarks();
        },
        "json");
    }
  })
  .disableSelection();

  Drupal.mmBrowserDeleteBookmarkConfirm = function(mmtid, title, context) {
    $("#" + mmtid, context)
      .html('<td class="tb-manage-name"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' + Drupal.t('<strong>Are you sure you want to DELETE</strong> %title?', {'%title': title}) + '</td><td><a href="#" onclick="return Drupal.mmBrowserDeleteBookmark(' + mmtid + ', document);">' + Drupal.t('Delete') + '</a></td><td><a href="#" name="bookmark-cancel">' + Drupal.t('Cancel') + '</a></td>')
      .find('[name=bookmark-cancel]')
      .click(function() {
        return resetBookmark(mmtid, title, document);
      });
    return false;
  };

  Drupal.mmBrowserEditBookmarkEdit = function(mmtid, title, context) {
    $("#" + mmtid, context)
      .html('<td class="tb-manage-name"><form action="#" onsubmit="Drupal.mmBrowserSaveBookmark(' + mmtid + '); return false;"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><input type="text" maxlength="35" name="edittitle-' + mmtid + '" value="' + title.replace(/"/g, '&quot;') + '"><input type="hidden" name="editmmtid-' + mmtid + '" value="' + mmtid + '"></form></td><td><a href="#" onclick="return Drupal.mmBrowserSaveBookmark(' + mmtid + ');">' + Drupal.t('Save') + '</a></td><td><a href="#" name="bookmark-cancel">' + Drupal.t('Cancel') + '</a></td>')
      .find('[name=bookmark-cancel]').click(function() {
        return resetBookmark(mmtid, title, document);
      })
      .end()
      .find('[name=edittitle-' + mmtid + ']')
        .focus();
    return false;
  };

  var resetBookmark = function(mmtid, title, context) {
    $("#li_" + mmtid, context)
      .html('<table class="manage-bookmarks-table"><tr id="' + mmtid + '"><td class="tb-manage-name"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' + title + '</td><td><a href="#" name="bookmark-del">' + Drupal.t('Delete') + '</a></td><td><a href="#" name="bookmark-edit">' + Drupal.t('Edit') + '</a></td></tr></table>')
      .find('[name=bookmark-del]').click(function() {
        return Drupal.mmBrowserDeleteBookmarkConfirm(mmtid, title, document);
      })
      .end()
      .find('[name=bookmark-edit]').click(function() {
        return Drupal.mmBrowserEditBookmarkEdit(mmtid, title, document);
      })
      .end()
      .find('.tb-manage-name').dblclick(function() {
        return Drupal.mmBrowserEditBookmarkEdit(mmtid, title, document);
      });
    return false;
  };

  Drupal.mmBrowserDeleteBookmark = function(mmtid, context) {
    $.post(
      Drupal.mmBrowserAppendParams(drupalSettings.path.baseUrl + "mm-bookmarks/delete/" + mmtid),
      {},
      function() {
        $("#li_" + mmtid, context).remove();
        Drupal.mmBrowserGetBookmarks();
      },
      "json"
    );
    return false;
  };

  Drupal.mmBrowserSaveBookmark = function(emmtid) {
    var mmtid = $("input[name=editmmtid-" + emmtid + "]").val();
    $.post(
      Drupal.mmBrowserAppendParams(drupalSettings.path.baseUrl + "mm-bookmarks/edit/" + mmtid),
        {title: $("input[name=edittitle-" + emmtid + "]").val()},
        function(data) {
          resetBookmark(data.mmtid, data.title);
          Drupal.mmBrowserGetBookmarks();
        },
        "json"
    );
    return false;
  };

})(jQuery, Drupal);