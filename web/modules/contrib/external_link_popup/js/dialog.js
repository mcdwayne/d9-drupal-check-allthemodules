/**
 * @file
 * Dialog initialization for mail preview.
 */

(function ($, Drupal, drupalSettings) {

  if (!String.prototype.startsWith) {
    // IE doesn't have native method.
    String.prototype.startsWith = function(searchString, position) {
      position = position || 0;
      return this.indexOf(searchString, position) === position;
    };
  }

  Drupal.behaviors.externalLinkPopup = {
    attach: function (context) {
      var self = this,
        settings = drupalSettings.external_link_popup,
        // RegExp /\s*,\s*|\s+/ supports both space and comma delimiters
        // and its combination.
        whitelist = settings.whitelist ? settings.whitelist.split(/\s*,\s*|\s+/) : [],
        current = window.location.host || window.location.hostname,
        popup;

      if (!settings.popups || !settings.popups.length) {
        return;
      }

      whitelist.unshift(current);
      if (current.startsWith('www.')) {
        whitelist.push(current.substr(4));
      } else {
        whitelist.push('www.' + current);
      }

      $('body', context).once('external-link-popup').click(function(e) {
        // Use native JS DOM for performance.
        var element = e.target,
          parent = element.parentNode,
          i = 0;

        // Look for link in up to 5 parents, limit parents for performance.
        while (parent) {
          i++;
          if (parent.tagName == 'A') {
            element = parent;
            break;
          }
          if (i >= 5) {
            break;
          }
          parent = parent.parentNode;
        }

        if (element.tagName != 'A' || element.classList.contains('external-link-popup-disabled')) {
          return;
        }

        var domain = self.getDomain(element.href);
        if (!domain || self.inDomain(domain, whitelist)) {
          return;
        }

        for (var i = 0; i < settings.popups.length;i++) {
          popup = settings.popups[i];
          if (popup.domains !== '*' && !self.inDomain(domain, popup.domains.split(/\s*,\s*|\s+/))) {
            continue;
          }
          e.preventDefault();
          return self.openDialog(
            element,
            popup,
            'external-link-popup-id-' + popup.id.replace('_', '-')
          );
        }
      });
    },
    inDomain: function (domain, domains) {
      for (var i in domains) {
        if (domain == domains[i] || domain.match(new RegExp(
          '\\.' + this.pregEscape(domains[i]) + '$'
        ))) {
          return true;
        }
      }
      return false;
    },
    getDomain: function (url) {
      var matches = url.match(/\/\/([^\/]+)\/?/);
      return matches && matches[1];
    },
    pregEscape: function (str) {
      return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\-]', 'g'), '\\$&');
    },
    openDialog: function (element, settings, className) {
      var dialog,
        body = document.createElement('div'),
        content = document.createElement('div'),
        options = {
          title: settings.title,
          width: '85%',
          buttons: [
            {
              text: settings.labelyes,
              click: function() {
                $(this).dialog('close');
                var target = window.open(element.href, element.target);
                target.focus();
              }
            }
          ],
          create: function() {
            var $widget = $(this).parent();
            $widget.addClass('external-link-popup').addClass(className);
            !settings.close && $widget.find('.ui-dialog-titlebar-close').remove();
            !settings.title && $widget.find('.ui-dialog-titlebar').remove();
          },
          close: function() {
            var $element = $(this);
            dialog && dialog.open && dialog.close();
            $element.dialog('destroy');
          }
        };
      if (settings.labelno) {
        options.buttons.push({
          text: settings.labelno,
          click: function() {
            $(this).dialog('close');
          }
        });
      }

      content.className = 'external-link-popup-content';
      body.innerHTML = settings.body;
      body.className = 'external-link-popup-body';
      content.appendChild(body);

      dialog = Drupal.dialog(content, options);
      dialog.showModal();
    }
  };
})(jQuery, Drupal, drupalSettings);
