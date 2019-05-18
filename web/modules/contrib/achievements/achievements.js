/**
 * @file
 * Attaches behaviors for the Achievements module.
 */

(function ($) {

  Drupal.behaviors.achievements = {
    attach: function (context, settings) {

      var height = 104;
      var margin = 10;
      var timeout;
      var notifications = $('.achievement-notification', context).once('processed').dialog({
          dialogClass:    'achievement-notification-dialog',
          autoOpen:       false,
          show:           'fade',
          hide:           'fade',
          closeOnEscape:  false,
          draggable:      false,
          resizable:      false,
          height:         height,
          width:          500,
          position:       {
            my:           "right bottom",
            at:           "right bottom",
            of:            window,
            collision:    "none"
          },
          closeText:      '',
          close:           onClose
        });

      if (notifications.length) {
        setTimeout(showDialogs, 500);
      }

      function showDialogs() {
        var length = notifications.length;

        notifications.on('dialogopen', function( event, ui ) {
          var i, notification, top;

          for (i = 0; i < length; i += 1) {
            notification = notifications.eq(i).dialog('widget');
            if (i === 0) {
              top = parseFloat(notification.css('top'));
            }
            else {
              top -= height + margin;
              notification.css('top', top + 'px');
              notification.css('display', 'block');
              notification.position.at = "right top+" + top;
            }
          }

          // the longer the list, longer the onscreen time.
          timeout = setTimeout(closeDialog, 5000 + (length * 500));
        });

        notifications.dialog('open').hover(
          function () {
            // Pretty sure this doesn't work, though it does get called.
            clearTimeout(timeout);
          },
          function () {
            // the longer the list, longer the onscreen time.
            timeout = setTimeout(closeDialog, 1500 + (length * 500));
          }
        );
      }

      function onClose() {
        var i, length, properties, widget;
        notifications = notifications.not(notifications[0]);
        length = notifications.length;

        function close() {
          timeout = setTimeout(closeDialog, 1500);
        }

        if (length) {
          properties = {
            top: '+=' + (height + margin)
          };
          for (i = 0; i < length; i += 1) {
            widget = notifications.eq(i).dialog('widget');
            if (i === 0) {
              widget.animate(properties, close);
            }
            else {
              widget.animate(properties)
            }
          }
        }
      }

      function closeDialog() {
        notifications.eq(0).dialog('close');
      }

    }
  };

})(jQuery);
