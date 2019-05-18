(function ($) {

  Drupal.behaviors.docbinder = {
    attach: function (context, settings) {
      $('a.docbinder').click(function(e) {
        if (e.target.tagName.toLowerCase() == 'a') {
          a = e.target;
        }
        else {
          if (a = $(e.target).parents('a[href]')) {
            // found parent a
          }
          else {
            return;
          }
        }
        e.preventDefault();
        filePath = $(a).attr('href');
        if (filePath.substring(0, 1) != '/') {
          filePath = '/' + filePath;
        }
        if (filePath.indexOf(Drupal.settings.docbinder.path_add) != -1) {
          // add AJAX parameter - add
          filePath = filePath.replace(Drupal.settings.docbinder.path_add, Drupal.settings.docbinder.path_add_ajax);
        }
        else if (filePath.indexOf(Drupal.settings.docbinder.path_remove) != -1) {
          // add AJAX parameter - remove
          filePath = filePath.replace(Drupal.settings.docbinder.path_remove, Drupal.settings.docbinder.path_remove_ajax);
        }
        else {
          // add AJAX parameter - toggle
          filePath = filePath.replace(Drupal.settings.docbinder.path_toggle, Drupal.settings.docbinder.path_toggle_ajax);
        }
        // remove multiple slashes at start
        filePath = filePath.replace(/^\/+/, '/');

        $.ajax({
          'url': filePath,
          'dataType': 'json',

          success: function(data, textStatus, req) {
            if (!data.result) {
              if (data.action == 'added') {
                alert(Drupal.t('Unable to add the file to your collection.'));
              }
              else {
                alert(Drupal.t('Unable to remove the file from your collection.'));
              }
            }
            else {
              var linkText = $(a).html();
              if (data.action == 'added') {
                  $(a).attr('href', $(a).attr('href').replace(Drupal.settings.docbinder.path_add,Drupal.settings.docbinder.path_remove));
                  $(a).html(linkText.replace(Drupal.settings.docbinder.indicator_plus,Drupal.settings.docbinder.indicator_minus));
              }
              else {
                  $(a).attr('href', $(a).attr('href').replace(Drupal.settings.docbinder.path_remove,Drupal.settings.docbinder.path_add));
                  $(a).html(linkText.replace(Drupal.settings.docbinder.indicator_minus,Drupal.settings.docbinder.indicator_plus));
              }
              Drupal.settings.docbinder.docbinder = data.docbinder;
              docbinder = $('.docbinder-block-downloads');
              if (Drupal.settings.docbinder.animate) {
                // copy the element for animation - with thanks to jQuery 'fake' plugin by Carl Fürstenberg
                orig_offset = $(a).offset();
                orig_height = $(a).height();
                orig_width = $(a).width();
                clone = $(a).clone();
                // prevent this element catching any events on the way across the screen
                possEvents = "blur focus focusin focusout load resize scroll unload click dblclick " +
                    "mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave " +
                    "change select submit keydown keypress keyup error";
                clone.bind(possEvents, function (event) {
                  event.preventDefault();
                });
                clone.addClass('clone');
                clone.css({
                  position: 'absolute',
                  visibility: 'visible',
                  left: orig_offset.left,
                  top: orig_offset.top,
                  width: orig_width,
                  height: orig_height
                });
                clone.appendTo($('body'));
                animProps = {
                  top: parseInt(docbinder.offset().top + (($(docbinder).height() - clone.height() / 2) / 2)),
                  left: parseInt(docbinder.offset().left + (($(docbinder).width() - clone.width() / 2) / 2)),
                  width: orig_width / 2,
                  height: orig_height / 2
                };
                animCallback = function (data) {
                  clone.fadeOut().remove();
                  $('.docbinder-download-count').html(Object.keys(Drupal.settings.docbinder.docbinder).length);
                }
                clone.animate(animProps, 'slow', 'swing', animCallback);
              }
            }
          },

          error: function(req, textStatus, errorThrown) {
            alert(Drupal.t('Unable to add the file to your collection.'));

            if (console) {
              console.log(req);
              console.log(textStatus);
              console.log(errorThrown);
            }

            switch (textStatus) {
              case 'timeout' :
              case 'null' :
              case 'error' :
              case 'parsererror' :
              case 'notmodified' :
                break;
              default :
                break;
            }
            // probably not permitted - handle file access restriction here
          }
        });
      });
      $('a.docbinder-docbinder').click(function(e) {
        if (e.target.tagName.toLowerCase() == 'a') {
          a = e.target;
        }
        else {
          if (a = $(e.target).parents('a[href]')) {
            // found parent a
          }
          else {
            return;
          }
        }
        e.preventDefault();
        // handle subdir Drupal installation
        filePath = $(a).attr('href').replace(Drupal.settings.basePath, '');
        if (filePath.substring(0, 1) != '/') {
          filePath = '/' + filePath;
        }
        filePath = filePath.replace(Drupal.settings.docbinder.path_remove, Drupal.settings.docbinder.path_remove_ajax);
        // add Drupal basePath
        filePath = Drupal.settings.basePath + filePath;
        // remove multiple slashes at start
        filePath = filePath.replace(/^\/+/, '/');

        $.ajax({
          'url': filePath,
          'dataType': 'json',

          success: function(data, textStatus, req) {
            if (!data.result) {
              if (data.action == 'added') {
                alert(Drupal.t('Unable to add the file to your collection.'));
              }
              else {
                alert(Drupal.t('Unable to remove the file from your collection.'));
              }
            }
            else {
              var linkText = $(a).text();
              Drupal.settings.docbinder.docbinder = data.docbinder;
              docbinder = $('.docbinder-block-downloads');
              $(a).closest('tr').animate({ height: 'toggle', opacity: 'toggle' }, 'slow', function() {$(this).remove(); });
              $('.docbinder-download-count').html(Object.keys(Drupal.settings.docbinder.docbinder).length);
            }
          },

          error: function(req, textStatus, errorThrown) {
            alert(Drupal.t('Unable to add the file to your collection.'));

            if (console) {
              console.log(req);
              console.log(textStatus);
              console.log(errorThrown);
            }

            switch (textStatus) {
              case 'timeout' :
              case 'null' :
              case 'error' :
              case 'parsererror' :
              case 'notmodified' :
                break;
              default :
                break;
            }
            // probably not permitted - handle file access restriction here
          }
        });
      });
    }
  };
})(jQuery);
