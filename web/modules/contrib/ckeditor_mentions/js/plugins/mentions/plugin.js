/**
 * @file
 * Contains plugin.js.
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('mentions', {
    hidpi: true,

    afterInit: function (editor) {

      editor.observe = 0;
      editor.replacementChars = [];
      editor.timeout_delay = 500;
      editor.observe_count = 3;
      editor.timeout_id = null;
      editor.image = false;

      if (drupalSettings.editor && drupalSettings.editor.formats !== 'undefined') {
        for (var key in drupalSettings.editor.formats) {
          // Allow for custom settings - settings identical regardless of format.
          if (typeof drupalSettings.editor.formats[key].editorSettings.mentions !== 'undefined') {
            if (drupalSettings.editor.formats[key].editorSettings.mentions.charcount >= 3) {
              editor.observe_count = drupalSettings.editor.formats[key].editorSettings.mentions.charcount;
            }

            if (drupalSettings.editor.formats[key].editorSettings.mentions.image !== 'undefined' && drupalSettings.editor.formats[key].editorSettings.mentions.image === 1) {
              editor.image = true;
            }

            if (drupalSettings.editor.formats[key].editorSettings.mentions.timeout >= 500) {
              editor.timeout_delay = drupalSettings.editor.formats[key].editorSettings.mentions.timeout;
            }
            break;
          }
        }
      }

      editor.on("key", function (event) {
        var key = event.data.domEvent.$.key;
        checkMentions(event.editor, key);
      }, editor, null, 50);

      function checkMentions(editorInstance, key) {
        // Stop/ignore input when certain character codes occur.
        if (breakCheck(key)) {
          editorInstance.replacementChars = [];
          editorInstance.observe = 0;
          clearTimeout(editorInstance.timeout_id);
          clearSelections();
        }
        else {
          if (editorInstance.observe) {
            if (key === 'Backspace') {
              // If backspacing during a selection pop the last item in the array out.
              editorInstance.replacementChars.pop();
            }
            else if (key.match(/^[0-9a-zA-Z]$/)) {
              // Keep weird characters out of list (spaces, line breaks, shift key, etc)
              // pushes characters into array only after check for '@' so @ is not part of query.
              editorInstance.replacementChars.push(key);
            }

            // Only ping JSON callback when there are a certain number of characters in array.
            if (editorInstance.replacementChars.length >= editorInstance.observe_count) {
              editorInstance.timeout_id = setTimeout(timeoutCallback, editorInstance.timeout_delay, [editorInstance]);
            }
          }
          if (key === '@' && editorInstance.observe === 0) {
            editorInstance.observe = 1;
          }
        }
      }

      // Check for realname and image information for input.
      function timeoutCallback(args) {
        var editorInstance = args[0];
        var substr = editorInstance.replacementChars.join('').toLowerCase();
        var date = new Date();

        var element = editorInstance.element.getId();
        var parentElement = $('#' + element).parent();

        if (substr) {
            $.ajax({
              url: "/ckeditor-mentions/ajax/" + substr + "?t=" + date.getUTCSeconds(),
              beforeSend: function (xhr) {
                xhr.overrideMimeType("text/plain; charset=x-user-defined");
              }
            })
            .done(function (data) {
              var content = $.parseJSON(data);
              var href;

              if (content.data && content.result === 'success') {
                clearSelections();
                var links = '<ul class="mentions">';
                for (var key in content.data) {
                  links += '<li class="mention">';

                  if (editorInstance.image) {
                    if (content.data[key].image) {
                      links += '<img class= "mention-icon" src="' + content.data[key].image + '" />';
                    }
                    else {
                      links += '<div class="mention-icon default-image"></div>';
                    }
                  }

                  if (/^\d+$/.test(content.data[key].uid)) {
                    href = '/user/' + content.data[key].uid;
                  }
                  else {
                    href = '#' + content.data[key].uid;
                  }
                  links += '<a href="' + href + '" data-mention="' + content.data[key].uid + '">' + content.data[key].name + '</a>';
                  links += '</li>';
                }
                links += '</ul>';
                $('<div class="mention-wrapper"><div class="mention-suggestions">' + links + '</div></div>').insertAfter(parentElement);
                $('.mention-wrapper a').bind('click', function () {
                  // Had to reconstruct link this way to avoid pulling over image markup.
                  var link = '<a href="' + $(this).attr('href') + '" data-mention="' + $(this).attr('data-mention') + '">' + $(this).html() + '</a>&nbsp;';

                  var pattern = new RegExp('@' + substr, 'i');
                  var content = editorInstance.getData();
                  var newContent = content.replace(pattern, link + '&nbsp;');
                  editorInstance.setData(newContent);
                  editorInstance.observe = 0;
                  editorInstance.replacementChars = [];

                  clearSelections();

                  return false;
                });
              }
            });
        }

        clearTimeout(editorInstance.timeout_id);
      }

      function clearSelections() {
          $('.mention-wrapper a').unbind();
          $('.mention-wrapper').remove();
      }

      function breakCheck(key) {
        var special = ['Enter', 'ArrowLeft', 'ArrowUp', 'ArrowRight', 'ArrowDown', 'Delete', 'Home'];
        for (var i = 0; i < special.length; i++) {
          if (special[i] === key) {
            return true;
          }
        }
        return false;
      }
    },

  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
