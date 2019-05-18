/**
 * @file
 * Functionality to enable collapsible functionality in CKEditor.
 */

(function () {
  'use strict';

  // Register plugin.
  CKEDITOR.plugins.add('collapsible', {
    hidpi: true,
    icons: 'collapsible',
    init: function (editor) {
      // Add single button.
      editor.ui.addButton('Collapsible', {
        command: 'addCollapsibleCmd',
        icon: this.path + 'icons/collapsible.png',
        label: Drupal.t('Insert collapsible')
      });

      // Add CSS for edition state.
      var cssPath = this.path + 'collapsible.css';
      editor.on('mode', function () {
        if (editor.mode === 'wysiwyg') {
          this.document.appendStyleSheet(cssPath);
        }
      });

      // Prevent nesting DLs by disabling button.
      editor.on('selectionChange', function (evt) {
        if (editor.readOnly) {
          return;
        }
        var command = editor.getCommand('addCollapsibleCmd');
        var element = evt.data.path.lastElement && evt.data.path.lastElement.getAscendant('dl', true);
        if (element) {
          command.setState(CKEDITOR.TRISTATE_DISABLED);
        }
        else {
          command.setState(CKEDITOR.TRISTATE_OFF);
        }
      });

      var allowedContent = 'dl dd dt(!ckeditor-collapsible)';

      // Command to insert initial structure.
      editor.addCommand('addCollapsibleCmd', {
        allowedContent: allowedContent,

        exec: function (editor) {
          var dl = new CKEDITOR.dom.element.createFromHtml(
            '<dl class="ckeditor-collapsible">' +
              '<dt> Collapsible title 1</dt>' +
              '<dd><p>Collapsible content 1.</p></dd>' +
              '<dt> Collapsible title 2</dt>' +
              '<dd><p>Collapsible content 2.</p></dd>' +
              '<dt> Collapsible title 3</dt>' +
              '<dd><p>Collapsible content 3.</p></dd>' +
            '</dl>');
          editor.insertElement(dl);
        }
      });

      // Other command to manipulate sections.
      editor.addCommand('addCollapsibleSectionBefore', {
        allowedContent: allowedContent,

        exec: function (editor) {
          var element = editor.getSelection().getStartElement();
          var newHeader = new CKEDITOR.dom.element.createFromHtml('<dt>New collapsible title</dt>');
          var newContent = new CKEDITOR.dom.element.createFromHtml('<dd><p>New collapsible content</p></dd>');
          if (element.getAscendant('dd', true)) {
            element = element.getAscendant('dd', true).getPrevious();
          }
          else {
            element = element.getAscendant('dt', true);
          }
          newHeader.insertBefore(element);
          newContent.insertBefore(element);
        }
      });
      editor.addCommand('addCollapsibleSectionAfter', {
        allowedContent: allowedContent,

        exec: function (editor) {
          var element = editor.getSelection().getStartElement();
          var newHeader = new CKEDITOR.dom.element.createFromHtml('<dt>New collapsible title</dt>');
          var newContent = new CKEDITOR.dom.element.createFromHtml('<dd><p>New collapsible content</p></dd>');
          if (element.getAscendant('dt', true)) {
            element = element.getAscendant('dt', true).getNext();
          }
          else {
            element = element.getAscendant('dd', true);
          }
          newContent.insertAfter(element);
          newHeader.insertAfter(element);
        }
      });
      editor.addCommand('removeCollapsibleSection', {
        exec: function (editor) {
          var element = editor.getSelection().getStartElement();
          var a;
          if (element.getAscendant('dt', true)) {
            a = element.getAscendant('dt', true);
            a.getNext().remove();
            a.remove();
          }
          else {
            a = element.getAscendant('dd', true);
            if (a) {
              a.getPrevious().remove();
              a.remove();
            }
            else {
              element.remove();
            }
          }
        }
      });

      // Context menu.
      if (editor.contextMenu) {
        editor.addMenuGroup('collapsibleGroup');
        editor.addMenuItem('collapsibleSectionBeforeItem', {
          label: Drupal.t('Add collapsible section before'),
          icon: this.path + 'icons/collapsible.png',
          command: 'addCollapsibleSectionBefore',
          group: 'collapsibleGroup'
        });
        editor.addMenuItem('collapsibleSectionAfterItem', {
          label: Drupal.t('Add collapsible section after'),
          icon: this.path + 'icons/collapsible.png',
          command: 'addCollapsibleSectionAfter',
          group: 'collapsibleGroup'
        });
        editor.addMenuItem('removeCollapsibleSection', {
          label: Drupal.t('Remove collapsible section'),
          icon: this.path + 'icons/collapsible.png',
          command: 'removeCollapsibleSection',
          group: 'collapsibleGroup'
        });

        editor.contextMenu.addListener(function (element) {
          var parentEl = element.getAscendant('dl', true);
          if (parentEl && parentEl.hasClass('ckeditor-collapsible')) {
            return {
              collapsibleSectionBeforeItem: CKEDITOR.TRISTATE_OFF,
              collapsibleSectionAfterItem: CKEDITOR.TRISTATE_OFF,
              removeCollapsibleSection: CKEDITOR.TRISTATE_OFF
            };
          }
        });
      }
    }
  });
})();
