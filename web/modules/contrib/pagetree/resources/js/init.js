(function ($, Drupal) {
  var lang = drupalSettings.pagetree.defaultLanguage;
  var currentLanguage = drupalSettings.path.currentLanguage;
  var currentNode = drupalSettings.pagetree.currentNode;
  var openElements = {};
  var tree = null;
  var state = 'out';
  var timeout = null;
  openElements = JSON.parse(localStorage.getItem("pt-states"));
  if (openElements == null) {
    openElements = {};
  }
  var loading = false;

  setInterval(
    function () {
      if (state != 'hover' && !loading) {
        loading = true;
        Drupal.restconsumer.get('/pagetree/tree/1').done(function (data) {
          if (data.hash !== sessionStorage.getItem("pt-tree-hash")) {
            sessionStorage.setItem("pt-tree-hash", data.hash);
            createTree(data, currentNode);
          }
          loading = false;
        });
      }
    },
    5000
  );

  $(document).ready(function () {
    $('body').append($('.block-pagetree'));
    var treeContainer = $('.pt-pagetree-wrapper');
    if ($('#toolbar-administration').length > 0 && $('#toolbar-administration').is(':visible')) {
      treeContainer.css('top', '79px');
    }
    loading = true;
    Drupal.restconsumer.get('/pagetree/tree/1').done(function (data) {
      sessionStorage.setItem("pt-tree-hash", data.hash);
      createTree(data, currentNode);
      loading = false;
    });
    $(document).keydown(function (e) {
      // ESCAPE key pressed
      if (e.keyCode == 27) {
        tree.sortable("cancel");
      }
    });
    var treeIcon = $('.pt-pagetree-icon');
    $(treeContainer).append(treeIcon);
    treeIcon.on("click", function () {
      treeContainer.toggleClass("pt-show");
    });
    treeContainer.on('mouseover', function () {
      timeout = setTimeout(
        function () {
          state = 'hover';
        }, 100);
    });
    treeContainer.on('mouseout', function () {
      state = 'out';
      clearTimeout(timeout);
    });
  });

  function createTree(data, currentNode) {
    $('.pt-trees-wrapper').empty();
    var openTree = localStorage.getItem("pt-open-tree");
    openTree = (typeof openTree !== 'undefined') ? openTree : data[0]['id'];
    var trees = data.trees;
    for (var x in trees) {
      if (trees.length > 1) {
        var treeContainer = $('<div class="pt-tree-container"><div class="pt-menu-label"><i class="pt-caret-icon fa fa-caret-down"></i>' + trees[x]['label'] + '</div></div>');
      } else {
        var treeContainer = $('<div class="pt-tree-container pt-open"></div>');
      }
      if (openTree == trees[x]['id']) {
        treeContainer.addClass('pt-open');
      }
      treeContainer.on('click', function (e) {
        $('.pt-tree-container').removeClass('pt-open');
        $(this).addClass('pt-open');
        localStorage.setItem("pt-open-tree", $(this).find('.pt-pagetree').data('menu-id'));
      });
      $('.pt-trees-wrapper').append(treeContainer);
      var tree = $('<ul class="pt-pagetree" data-menu-id="' + trees[x]['id'] + '"></ul>');
      $(treeContainer).append(tree);
      augmentData(trees[x]['tree']);
      buildTree(trees[x]['tree'], tree, currentNode);
      makeSortable(tree);
    }

  }

  function augmentData(list) {
    var hasCurrent = false;
    for (var i in list) {
      if (list[i].children.length > 0) {
        var current = augmentData(list[i].children);
      }
      if (list[i].id == currentNode || current || openElements.hasOwnProperty(list[i].id)) {
        list[i].open = true;
        hasCurrent = true;
      }
    }
    return hasCurrent;
  }

  function openSubtree(id, element) {
    return function (e) {
      $(this).toggleClass("fa-plus").toggleClass('fa-minus');
      if ($(this).hasClass("fa-plus")) {
        delete openElements[id];
      } else {
        openElements[id] = true;
      }
      element.toggleClass("pt-open");
      localStorage.setItem('pt-states', JSON.stringify(openElements));
    }
  }

  function buildElement(entry, language) {
    var element = $('<li class="pt-entry pt-main-entry pt-entry-' + entry.id + '-' + language + '" data-node-id="' + entry.id + '" data-link-id="' + entry.linkId + '" id="list_item_' + entry.id + '"></li>');
    return element;
  }

  function buildTranslation(entry, language) {
    var translationEntry = $('<li class="pt-entry pt-entry-' + entry.id + '-' + language + '"></li>');
    var translationContainer = $("<div></div>");
    if (entry['translations'][language]) {
      var translationLink = $('<a title="' + entry['translations'][language]['name'] + ' [' + language + ']" href="' + entry['translations'][language]['externalLink'] + '">' + entry['translations'][language]['name'] + ' [' + language + ']</a>');
    } else {
      var translationLink = $('<a href="/' + language + '/node/' + entry.id + '">No translation exists [' + language + ']</a>');
    }
    translationContainer.append(translationLink);
    addActions(translationContainer, entry, language);
    addFeedback(translationContainer);
    if (currentLanguage == language) {
      translationEntry.addClass("pt-current");
    }
    if (entry['translations'][language]) {
      setState(translationLink, entry['translations'][language]['status']);
    }
    translationEntry.append(translationContainer);
    return translationEntry;
  }

  function setState(element, status) {
    if (element.find('i').length == 0) {
      element.prepend('<i class="pt-state">');
    }
    if (status == 0) {
      element.find('i').addClass("fas fa-times-circle");
    } else if (status == 2) {
      element.find('i').addClass("fas fa-exclamation-circle");
    } else {
      element.find('i').addClass("fas fa-check-circle");
    }
  }

  function buildTree(list, parent, currentNode) {
    parent.empty();
    for (var x in list) {
      var element = buildElement(list[x], lang);
      var container = $("<div></div>");


      if (list[x]['translations'][lang] && typeof list[x]['translations'][lang] != 'undefined') {
        var link = $('<a title="' + list[x]['translations'][lang]['name'] + ' [' + lang + ']" href="' + list[x]['translations'][lang]['externalLink'] + '">' + list[x]['translations'][lang]['name'] + ' [' + lang + ']</a>');

        setState(link, list[x]['translations'][lang]['status']);
      } else {
        link = $('<a href="/' + lang + '/node/' + list[x]['id'] + '">No translation exists [' + lang + ']</a>');
      }
      container.append(link);

      addActions(container, list[x], lang);
      addFeedback(container);
      if (list[x].children.length > 0) {
        var opener = $('<div class="pt-opener far fa-plus"></div>');
        if (list[x].children.length > 0 && list[x]['open']) {
          opener.removeClass("fa-plus").addClass('fa-minus');
          element.addClass('pt-open');
        }
        opener.on("click", openSubtree(list[x].id, element));
        container.append(opener);
      }

      if (list[x].id == currentNode) {
        if (currentLanguage == lang) {
          element.addClass("pt-current");
        }
        var translations = $('<ul class="pt-translations"></ul>');
        for (var language in list[x]['translations']) {
          if (language != lang) {
            translationEntry = buildTranslation(list[x], language)
            translations.append(translationEntry);
          }
        }
        container.append(translations);
      }
      element.append(container);
      parent.append(element);
      var subtree = $('<ul class="pt-subtree"></ul>');
      element.append(subtree);
      if (list[x].children.length > 0) {
        buildTree(list[x].children, subtree, currentNode);
      }
    }
  }

  function addActions(element, entry, language) {
    var actionList = buildActions(entry, language);
    if (actionList.children().length > 0) {
      var actions = $('<i class="pt-icon fa fa-bars"></i>');
      actions.on("click", function () {
        var hidden = $(this).next().is(":hidden");
        $(".pt-pagetree .pt-page-actions").hide();
        if (hidden) {
          $(this).next().show();
        }
      });
      actionList.hide();
      element.append(actions);
      element.append(actionList);

      actionList.on('click', 'li', function (event) { $(this).parent().addClass('hide'); event.stopPropagation(); });
      actionList.on('mousedown, mouseup, mousemove', 'li', function (event) { event.stopPropagation(); });

      actions.on('mousedown, mouseup, mousemove', function (event) { event.stopPropagation(); });
    }
  }

  function addFeedback(element, entry, language) {
    var feedback = $('<div class="pt-feedback"><i class="fa fa-circle-o-notch fa-spin fa-fw"></i><i class="fa fa-check"></i><i class="fa fa-exclamation"></i></div>');
    element.append(feedback);
  }

  function buildActions(entry, language) {
    var actionList = $('<ul class="pt-page-actions hide"></ul>');
    if (language == lang && entry.permissions.create) {
      actionList.append($('<li><a href="/node/add/?parentEntry=' + entry.linkId + '">New page</a></li>'));
    }
    if (entry['translations'][language] && typeof entry['translations'][language] != 'undefined') {
      if (entry.permissions.update) {
        actionList.append($('<li><a href="' + entry['translations'][language]['externalLink'] + '?pd=1">Edit page</a></li>'));
        actionList.append($('<li><a href="' + entry['translations'][language]['settingsLink'] + '">Settings</a></li>'));
        var publishEntry = $('<li data-node-id="' + entry.id + '" data-lang-id="' + language + '">Publish</li>');
        actionList.append(publishEntry);
        var unpublishEntry = $('<li data-node-id="' + entry.id + '" data-lang-id="' + language + '">Unpublish</li>');
        actionList.append(unpublishEntry);

        publishEntry.on("click", function (e) {
          $('#ptPublishModal').find('#revisionMessage').val("");
          $('#ptPublishModal').dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
              "Publish page": (function (action) {
                return function () {
                  $(this).dialog("close");
                  var message = $('#ptPublishModal').find("textarea").val();
                  publishNode(action.attr('data-node-id'), action.attr('data-lang-id'), message);
                }
              })($(this)),
              Cancel: function () {
                $(this).dialog("close");
              }
            }
          });
        });

        unpublishEntry.on("click", function (e) {
          $('#ptUnpublishModal').find('#revisionMessage').val("");
          $('#ptUnpublishModal').dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
              "Unpublish page": (function (action) {
                return function () {
                  $(this).dialog("close");
                  var message = $('#ptUnpublishModal').find("textarea").val();
                  unpublishNode(action.attr('data-node-id'), action.attr('data-lang-id'), message);
                }
              })($(this)),
              Cancel: function () {
                $(this).dialog("close");
              }
            }
          });
        });
      }
      if (entry.permissions.delete) {
        if (language == lang) {
          actionList.append($('<li><a href="/node/' + entry.id + '/delete">Delete page</a></li>'));
        } else {
          actionList.append($('<li><a href="/' + language + '/node/' + entry['id'] + '/delete">Delete translation</a></li>'));
        }
      }
    } else {
      if (entry.permissions.update) {
        actionList.append($('<li><a href="/' + language + '/node/' + entry['id'] + '/translations">Add translation</a></li>'));
      }
    }
    return actionList;
  }

  function publishNode(nodeId, lang, message) {
    var entry = $('.pt-entry-' + nodeId + '-' + lang);
    entry.addClass("pt-saving");
    Drupal.restconsumer
      .patch('/pagetree/publish', { id: nodeId, language: lang, message: message }, false)
      .done(
        (function (entry) {
          return function (e) {
            entry.find('.pt-state').removeClass('fa-exclamation-circle').removeClass('fa-times-circle').addClass('fa-check-circle');
            entry.removeClass('pt-saving').addClass('pt-saved');
            setTimeout(
              (function (entry) {
                return function () {
                  entry.removeClass('pt-saved');
                }
              })(entry)
              , 1500);
          }
        })(entry)
      )
      .fail(
        (function (entry) {
          return function (e) {
            entry.addClass('pt-error');
          }
        })(entry)
      );
  }

  function unpublishNode(nodeId, lang, message) {
    var entry = $('.pt-entry-' + nodeId + '-' + lang);
    entry.addClass("pt-saving");
    Drupal.restconsumer
      .patch('/pagetree/unpublish', { id: nodeId, language: lang, message: message })
      .done(
        (function (entry) {
          return function (e) {
            entry.find('.pt-state').removeClass('fa-check-circle').removeClass('fa-exclamation-circle').addClass('fa-times-circle');
            entry.removeClass('pt-saving').addClass('pt-saved');
            setTimeout(
              (function (entry) {
                return function () {
                  entry.removeClass('pt-saved');
                }
              })(entry)
              , 1500);
          }
        })(entry)
      )
      .fail(
        (function (entry) {
          return function (e) {
            entry.addClass('pt-error');
          }
        })(entry)
      );
  }

  function copyNodeSingle(nodeId, parentId, position, menu) {
    var entry = $('.pt-main-entry[data-node-id=' + nodeId + ']');
    entry.addClass("pt-saving");
    Drupal.restconsumer
      .post('/pagetree/copy', { id: nodeId, newParent: parentId, weight: position, recursive: true, menu: menu })
      .done(
        (function (entry) {
          return function (data) {
            loading = true;
            Drupal.restconsumer.get('/pagetree/tree/1').done(
              (function (cloneId) {
                return function (data) {
                  var entry = $('.pt-main-entry[data-node-id=' + cloneId + ']');
                  entry.removeClass('pt-saving').addClass('pt-saved');
                  setTimeout(
                    (function (entry) {
                      return function () {
                        entry.removeClass('pt-saved');
                        if (data.hash !== sessionStorage.getItem("pt-tree-hash")) {
                          sessionStorage.setItem("pt-tree-hash", data.hash);
                          createTree(data, currentNode);
                        }
                        loading = false;
                      }
                    })(entry)
                    , 1500);
                }
              })(data['clone'])
            );
          }
        })(entry)
      )
      .fail(function (e) {
        loading = true;
        Drupal.restconsumer.get('/pagetree/tree/1').done(
          function (data) {
            if (data.hash !== sessionStorage.getItem("pt-tree-hash")) {
              sessionStorage.setItem("pt-tree-hash", data.hash);
              createTree(data, currentNode);
            }
            loading = false;
          }
        );
      });
  }

  function copyNodeRecursive(nodeId, parentId, position, menu) {
    var entry = $('.pt-main-entry[data-node-id=' + nodeId + ']');
    entry.addClass("pt-saving");
    Drupal.restconsumer
      .post('/pagetree/copy', { id: nodeId, newParent: parentId, weight: position, recursive: false, menu: menu })
      .done(
        (function (entry) {
          return function (data) {
            loading = true;
            Drupal.restconsumer.get('/pagetree/tree/1').done(
              (function (cloneId) {
                return function (data) {
                  var entry = $('.pt-main-entry[data-node-id=' + cloneId + ']');
                  entry.removeClass('pt-saving').addClass('pt-saved');
                  setTimeout(
                    (function (entry) {
                      return function () {
                        entry.removeClass('pt-saved');
                        if (data.hash !== sessionStorage.getItem("pt-tree-hash")) {
                          sessionStorage.setItem("pt-tree-hash", data.hash);
                          createTree(data, currentNode);
                        }
                        loading = false;
                      }
                    })(entry)
                    , 1500);
                }
              })(data['clone'])
            );
          }
        })(entry)
      )
      .fail(function (e) {
        loading = true;
        Drupal.restconsumer.get('/pagetree/tree/1').done(
          function (data) {
            if (data.hash !== sessionStorage.getItem("pt-tree-hash")) {
              sessionStorage.setItem("pt-tree-hash", data.hash);
              createTree(data, currentNode);
            }
            loading = false;
          }
        );
      });
  }

  function makeSortable(tree) {
    if (tree.hasClass('ui-sortable')) {
      tree.nestedSortable('refresh');
    }
    tree.nestedSortable({
      items: "li.pt-main-entry",
      toleranceElement: '>div',
      listType: 'ul',
      handle: '>div>a',
      protectRoot: false,
      helper: function (e, t) {
        if (e.shiftKey) {
          var copyHelper = t.clone();
          copyHelper.insertAfter(t);
          this.copy = true;
          t.item = copyHelper
        }
        return t;
      },
      relocate: function (e, t) {
        var position = t.item.prevAll('li').length + 1;
        var nid = t.item.attr("data-node-id");
        var lid = t.item.attr("data-link-id");
        var parentId = t.item.parent().parent().attr("data-link-id");
        var menu = t.item.closest('.pt-pagetree').attr("data-menu-id");
        if (typeof parentId == 'undefined') {
          parentId = -1;
        }
        if (this.copy) {
          t.item.attr("data-copy-item", 1);
          $('#ptCopyModal').dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
              "Include children": (
                function () {
                  $(this).dialog("close");
                  copyNodeRecursive(nid, parentId, position, menu);
                }
              ),
              "Exclude children": (
                function () {
                  $(this).dialog("close");
                  copyNodeSingle(nid, parentId, position, menu);
                }
              ),
              Cancel: function () {
                $(this).dialog("close");
              }
            }
          });
        } else {
          t.item.addClass('pt-saving');
          Drupal.restconsumer
            .patch('/pagetree/move', { id: lid, newParent: parentId, weight: position, menu: menu })
            .done(
              (function (entry) {
                return function (e) {
                  loading = false;
                  Drupal.restconsumer.get('/pagetree/tree/1').done(function (data) {
                    entry.removeClass('pt-saving').addClass('pt-saved');
                    setTimeout(
                      (function (entry) {
                        return function () {
                          entry.removeClass('pt-saved');
                          if (data.hash !== sessionStorage.getItem("pt-tree-hash")) {
                            sessionStorage.setItem("pt-tree-hash", data.hash);
                            createTree(data, currentNode);
                          }
                          loading = false;
                        }
                      })(entry)
                      , 1500);
                  });
                }
              })(t.item)
            );
        }
        this.copy = false;
      }
    });
  }

  $(window).on('load', function () {
    var items = $('.pt-main-entry');
    items.addClass('enable-sorting');
    $('.pt-pagetree').each(function (i, tree) {
      $(tree).nestedSortable('refresh');
    });
  });


})(jQuery, Drupal);
