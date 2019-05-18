(function ($, Drupal, drupalSettings) {
$.extend(Drupal, {
  mmDialogActive: [],

  mmDialogClick: function(event) {
    var dialog, url = this.href || $(this).attr('rel');
    var frag = url.match(/#(.*)$/);

    if (frag && frag.length) {
      // Inline dialog.
      Drupal.mmDialogActive.unshift(frag[0]);
      dialog = $(frag[0]).dialog($.extend({}, event.data.settings, {
        title: $(this).attr('title'),
        modal: true,
        close: function() {
          dialog = undefined;
          Drupal.mmDialogActive.shift();
        }
      }));
    }
    else {
      // Load using AJAX.
      var instance = event.data.instance;
      var dialogSettings = $.extend({}, {
          title:    $(this).attr('title'),
          modal:    true,
          iframe:   false,
          fullSize: false,
          close:    function() {
            dialog.remove();
            dialog = undefined;
            Drupal.mmDialogActive.shift();
          }
        },
        event.data.settings
      );

      if (dialogSettings.fullSize) {
        dialogSettings.width = window.innerWidth - 20;
        dialogSettings.height = window.innerHeight - 20;
        dialogSettings.draggable = false;
      }

      Drupal.mmDialogActive.unshift('#mm-dialog-dialog-' + instance);
      if (dialogSettings.iframe) {
        dialog = $('<div id="mm-dialog-dialog-' + instance + '" style="display: none" class="loading" />')
          .appendTo('body');
        $('<iframe id="mm-dialog-iframe-' + instance + '"scrolling="no" style="border: 0; margin: 0; width: 100%; height: 100%" src="' + url + '">' + Drupal.t('Loading...') + '</iframe>')
          .on('load', function() {
            dialog.removeClass('loading');
          })
          .appendTo(dialog);
        dialog.dialog(dialogSettings);
        if (dialogSettings.fullSize) {
          dialog.closest('.ui-dialog').css({position: 'fixed', top: 0});
        }
        else {
          dialog.css({width: '100%', height: '100%'});
        }
        dialog.closest('.ui-dialog-resizable').resizable('option', 'resize', function() {
          dialog.css({width: '100%', height: '100%'});
        });

        Drupal.mmDialogResized = function(width, height) {
          dialog.closest('.ui-dialog').css({width: (width + 60) + 'px', height: (height + dialog.siblings('.ui-dialog-titlebar').outerHeight() + 32) + 'px'});
        };
      }
      else {
        dialog = $('<div id="mm-dialog-dialog-' + instance + '" style="display: none" class="loading">' + Drupal.t('Loading...') + '</div>')
          .dialog(dialogSettings);
        // Limit to just body contents, without scripts or CSS
        $.get(url, function (data) {
          dialog.removeClass('loading');
          dialog.html(data);
        });
      }
    }
    // Prevent browser from following the link.
    return false;
  },

  mmDialogOpen: function(obj) {
    var instance = obj.id.match(/-(\d+)$/)[1];
    Drupal.mmDialogClick.call(obj, {
      data: {
        instance: instance,
        settings: drupalSettings.MM.MMDialog[instance] || {}
      }
    });
    return false;
  },

  dialogInstance: 1000,

  mmDialogAdHoc: function(url, label, settings) {
    var tag = $('<a href="' + url + '" title="' + label + '" id="mm-dialog-' + Drupal.dialogInstance + '" />');
    Drupal.mmDialogInitOne(tag, Drupal.dialogInstance++, settings);
    Drupal.mmDialogOpen(tag[0]);
  },

  mmDialogClose: function() {
    $(Drupal.mmDialogActive[0]).dialog('close');
  },

  mmDialogInitOne: function(obj, instance, settings) {
    drupalSettings.MM.MMDialog[instance] = settings;
    $(obj).bind('click.mmDialog', {
      instance: instance,
      settings: settings
    }, Drupal.mmDialogClick);
  },

  mmDialogInit: function(context, instanceSettings) {
    $('a,:button,:submit', context)
      .filter('[rel="#close"]')
        .click(function() {
          Drupal.mmDialogActive.length ? Drupal.mmDialogClose() : parent.Drupal.mmDialogClose();
        })
        .end()
      .filter('[id^="mm-dialog"]')
        .once('mm-dialog')
        .each(function() {
          var instance = this.id.match(/-(\d+)$/)[1];
          Drupal.mmDialogInitOne(this, instance, instanceSettings[instance] || {});
        });
  }
});

Drupal.behaviors.MMDialog = {
  attach: function (context) {
    Drupal.mmDialogInit(context, drupalSettings.MM.MMDialog);
  }
};
})(jQuery, Drupal, drupalSettings);