/* Javascript file for the Monster Menus tree browser */

(function ($, Drupal, drupalSettings) {

Drupal.behaviors.mm_browser_init = {
  attach: function (context) {
    $("#mmtree-browse-browser", context).once('mm-browser').each(function() {
      Drupal.mm_back_in_history(true);

      // Splitter
      var outer = $("#mmtree-browse-browser", context), leftPane = outer.children(':first');
      var ckpos = parseInt($.cookie('vsplitter'));
      if (!isNaN(ckpos)) {
        var w = outer.width();
        if (ckpos > w) ckpos = w - 50;
        leftPane
          .width(ckpos)
          .next()
          .width(w - ckpos);
      }
      leftPane.resizable({
        handles: 'e',
        resize:  function(e, ui) {
          var remainingSpace = outer.width() - ui.element.outerWidth(),
            divTwo = ui.element.next(),
            divTwoWidth = (remainingSpace - (divTwo.outerWidth() - divTwo.width())) / outer.width() * 100 + "%";
          divTwo.width(divTwoWidth);
          ui.element.resizable('option', {
            minWidth: 50,
            maxWidth: outer.width() - 50
          });
        },
        stop:    function(e, ui) {
          ui.element.css('width', ui.element.width() / outer.width() * 100 + "%");
          $.cookie('vsplitter', ui.element.width(), {
            expires: 365,
            path:    drupalSettings.path.baseUrl
          });
        }
      });

      $('select.mm-browser-button')
        .selectmenu({
          change: function() {
                    var path = this.value;
                    $(this).val('').selectmenu('refresh');
                    if (path[0] === '#') {
                      Drupal.mmDialogAdHoc(path.substr(1), Drupal.t('Organize Bookmarks'), []);
                    }
                    else if (path) {
                      Drupal.mm_browser_reload_data(path);
                    }
                    return false;
                  }
        });

      $('html').css({overflow: 'hidden'});

      var startPath = drupalSettings.MM.mmBrowser.startBrowserPath;
      if (document.cookie.indexOf("goto_last=1") >= 0) {
        // User pressed Back button on media properties page
        var date = new Date(0);
        document.cookie = "goto_last=1;expires=" + date.toUTCString() + ";path=/";
        startPath = drupalSettings.MM.mmBrowser.lastBrowserPath;
      }
      Drupal.mm_browser_init_jstree(startPath);
    });
  }
};

Drupal.mm_browser_init_jstree = function (path) {
  var setHeight = function() {
    if ($('#mmtree-browse:visible').length) {
      var ht = $('#mmtree-browse').height() - $('#mmtree-browse-nav').height();
      if (ht > 50) $("#mmtree-browse-browser,#mmtree-browse-tree,#mmtree-browse-iframe,#mmtree-browse-items").height(ht);
    }
  };

  var initially_open = path.split('/');
  if (!drupalSettings.MM.mmBrowser.browserShowRoot) {
    initially_open.shift();
  }
  var jstree = $("#mmtree-browse-tree")
    .jstree({
      core: {
        strings: {
          'Loading ...': Drupal.t("Loading...")
        },
        data: {
          url:   function(n) {
            var id = n.id === '#' ? (drupalSettings.MM.mmBrowser.browserShowRoot ? 0 : drupalSettings.MM.mmBrowser.browserTop) : n.id.substr(5);
            var params = Drupal.mm_browser_params();
            if (id <= 0) {
              return drupalSettings.path.baseUrl + "mm-browser/" + drupalSettings.MM.mmBrowser.browserTop + '?_vusr=' + id + '&' + params;
            }
            return drupalSettings.path.baseUrl + "mm-browser/" + id + '?' + params;
          },
          error: function(x) {
            alert(x.status === 403 ?
              Drupal.t('You do not have permission to perform this operation.') :
              Drupal.t('An error occurred: @err', { '@err' : x.statusText })
            );
            $("#mmtree-browse-tree").jstree('destroy');
          }
        },
        themes: {
          url: true,
          dots: false
        }
      }
    })
    .on("ready.jstree after_open.jstree", function(e, data) {
// Not sure why this is here, but it can sometimes cause the initial selection to become unset
//       if (e.type === 'after_open') {
//        jstree.jstree('deselect_all').jstree('select_node', data.node.id);
//       }
      if (i = parseInt(initially_open.shift())) {
        var id = 'mmbr-' + i;
        if (initially_open.length) {
          jstree.jstree('open_node', id, null, false);
        }
        else {
          jstree.jstree('deselect_all').jstree('select_node', id)
        }
      }
    })
    .on("select_node.jstree", function(e, data) {
      if (data.selected.length > 1) {
        jstree.deselect_all().select_node(data.node.id);
        return;
      }
      var obj = $('#' + data.node.id);
      if (obj.length) Drupal.mm_browser_refresh_right(obj[0].nodeName === 'A' ? obj.parent()[0] : obj[0]);
    });

  if (window.opener) {
    $(window).resize(setHeight);
  }
  else {
    $("#mmtree-browse-browser,#mmtree-browse-tree,#mmtree-browse-iframe").resize(setHeight);
  }
  setHeight();
};

Drupal.mmBrowserAddBookmarkSubmit = function(context) {
  $("#add-bookmark-div", context).hide();
  var mmtid = $("input[name=linkmmtid]", context).val();
  $.post(
    Drupal.mmBrowserAppendParams(drupalSettings.path.baseUrl + "mm-bookmarks/add/" + mmtid), {
      linktitle: $("input[name=linktitle]", context).val(),
      linkmmtid: mmtid
    },
    function() {
      Drupal.mmBrowserGetBookmarks();
      Drupal.mmDialogClose();
    }
  );
  return false;
};

Drupal.mmBrowserGetBookmarks = function() {
  $.getJSON(
    Drupal.mmBrowserAppendParams(drupalSettings.path.baseUrl + 'mm-browser-get-bookmarks'),
    function(data) {
      var menu = $('select.mm-browser-button');
      menu.find('option:gt(1)').remove();
      for (var i in data) {
        if (data.hasOwnProperty(i))
          menu.append('<option value="' + data[i][2] + '">' + data[i][1] + '</option>');
      }
      menu.selectmenu('refresh');
    }
  );
};

Drupal.mmBrowserAppendParams = function(uri) {
  return uri + (uri.indexOf('?') > 0 ? '&' : '?') + Drupal.mm_browser_params();
};

Drupal.mm_browser_reload_data = function(path) {
  path = path || '1';
  if (!path.match('(^|/)' + drupalSettings.MM.mmBrowser.browserTop + '(/|$)')) {
    drupalSettings.MM.mmBrowser.browserTop = path.split('/')[0];
  }
  $("#mmtree-browse-tree").jstree('destroy');
  Drupal.mm_browser_init_jstree(path);
};

Drupal.mm_browser_goto_top = function(path) {
  drupalSettings.MM.mmBrowser.browserTop = path.split('/')[0];
  Drupal.mm_browser_reload_data(path);
};

Drupal.mm_browser_params = function() {
  var out = [];
  for (i in drupalSettings.MM.mmBrowser)
    if (drupalSettings.MM.mmBrowser.hasOwnProperty(i))
      if (i.substring(0, 7) === 'browser' && i.length > 7)
        out.push(i + '=' + encodeURI(drupalSettings.MM.mmBrowser[i]));
  return out.join('&');
};

Drupal.mm_browser_params_json = function() {
  var obj = {};
  for (i in drupalSettings.MM.mmBrowser) {
    if (drupalSettings.MM.mmBrowser.hasOwnProperty(i))
      if (i.substring(0, 7) === 'browser' && i.length > 7)
        obj[i] = encodeURI(drupalSettings.MM.mmBrowser[i]);
  }
  return obj;
};

Drupal.mm_browser_close_menus = function() {
  for (var i in allUIMenus)
    if (allUIMenus.hasOwnProperty(i))
      if (allUIMenus[i].menuOpen)
        allUIMenus[i].kill();
};

Drupal.mm_browser_last_viewed = function() {
  if (drupalSettings.MM.mmBrowser.lastBrowserPath) {
    Drupal.mm_browser_reload_data(drupalSettings.MM.mmBrowser.lastBrowserPath);
  }
};

Drupal.mm_browser_refresh_right = function(node) {
  var params = Drupal.mm_browser_params_json();
  params.id = node.id;
  $.getJSON(drupalSettings.path.baseUrl + 'mm-browser-getright',
    params,
    function(data) {
      $('#mmtree-assist-title').html(data.title);
      $('#mmtree-assist-links').html(data.links);
      $('#mmtree-assist-content')
        .html(data.body)
        .find('a:not([onclick]):not([id^="mm-dialog"])')
          .click(Drupal.mm_browser_right_link_click);
      // Initialize any modal dialog links.
      if (typeof Drupal.mmDialogInit !== 'undefined') {
        Drupal.mmDialogInit($('#mmtree-assist-content,#mmtree-assist-links'), data.dialogs);
      }
      if (data.lastviewed) drupalSettings.MM.mmBrowser.lastBrowserPath = data.lastviewed;
    }
  );
};

Drupal.mm_browser_change_parent_url = function(url) {
  document.location = url;
};

Drupal.mm_browser_gallery_add = function(mmtid, filename, fid) {
  parent && parent.mmListInstance && parent.mmListInstance.addFromChild($("#mmbr-" + mmtid)[0], 0, fid ? '0/' + fid : '', filename);
  return false;
};

Drupal.mm_browser_nodepicker_add = function(mmtid, nodename, nid) {
  parent && parent.mmListInstance && parent.mmListInstance.addFromChild($("#mmbr-" + mmtid)[0], 0, nid, nodename);
  return false;
};

Drupal.mm_browser_page_add = function(mmtid, info) {
  parent && parent.mmListInstance && parent.mmListInstance.addFromChild($("#mmbr-" + mmtid)[0], info);
};

Drupal.mm_browser_right_link_click = function() {
  $.getJSON(this.href,
    null,
    function(data) {
      $('#mmtree-assist-content')
        .html(data.body)
        .find('a:not([onclick])')
          .click(Drupal.mm_browser_right_link_click);
    }
  );
  return false;
};

})(jQuery, Drupal, drupalSettings);
