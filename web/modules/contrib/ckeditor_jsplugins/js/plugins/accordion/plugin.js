/**
 * @file
 * Functionality to enable accordion functionality in CKEditor.
 */

(function () {
  'use strict';

  // Register plugin.
  CKEDITOR.plugins.add('accordion', {
    hidpi: true,
    icons: 'accordion',
    init: function (editor) {
      // Add single button.
      editor.ui.addButton('Accordion', {
        command: 'addAccordionCmd',
        icon: this.path + 'icons/accordion.png',
        label: Drupal.t('Insert accordion')
      });

      // Add CSS for edition state.
      var cssPath = this.path + 'accordion.css';
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
        var command = editor.getCommand('addAccordionCmd');
        var element = evt.data.path.lastElement && evt.data.path.lastElement.getAscendant('dl', true);
        if (element) {
          command.setState(CKEDITOR.TRISTATE_DISABLED);
        }
        else {
          command.setState(CKEDITOR.TRISTATE_OFF);
        }
      });

      var allowedContent = 'dl dd dt(!ckeditor-accordion)';

      // Command to insert initial structure.
      editor.addCommand('addAccordionCmd', {
        allowedContent: allowedContent,

        exec: function (editor) {
          var dl = new CKEDITOR.dom.element.createFromHtml(
            '<dl class="ckeditor-accordion">' +
              '<dt> Accordion title 1</dt>' +
              '<dd><p>Accordion content 1.</p></dd>' +
              '<dt> Accordion title 2</dt>' +
              '<dd><p>Accordion content 2.</p></dd>' +
              '<dt> Accordion title 3</dt>' +
              '<dd><p>Accordion content 3.</p></dd>' +
            '</dl>');
          editor.insertElement(dl);
        }
      });

      // Other command to manipulate sections.
      editor.addCommand('addAccordionSectionBefore', {
        allowedContent: allowedContent,

        exec: function (editor) {
          var element = editor.getSelection().getStartElement();
          var newHeader = new CKEDITOR.dom.element.createFromHtml('<dt>New accordion title</dt>');
          var newContent = new CKEDITOR.dom.element.createFromHtml('<dd><p>New accordion content</p></dd>');
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
      editor.addCommand('addAccordionSectionAfter', {
        allowedContent: allowedContent,

        exec: function (editor) {
          var element = editor.getSelection().getStartElement();
          var newHeader = new CKEDITOR.dom.element.createFromHtml('<dt>New accordion title</dt>');
          var newContent = new CKEDITOR.dom.element.createFromHtml('<dd><p>New accordion content</p></dd>');
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
      editor.addCommand('removeAccordionSection', {
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
        editor.addMenuGroup('accordionGroup');
        editor.addMenuItem('accordionSectionBeforeItem', {
          label: Drupal.t('Add accordion section before'),
          icon: this.path + 'icons/accordion.png',
          command: 'addAccordionSectionBefore',
          group: 'accordionGroup'
        });
        editor.addMenuItem('accordionSectionAfterItem', {
          label: Drupal.t('Add accordion section after'),
          icon: this.path + 'icons/accordion.png',
          command: 'addAccordionSectionAfter',
          group: 'accordionGroup'
        });
        editor.addMenuItem('removeAccordionSection', {
          label: Drupal.t('Remove accordion section'),
          icon: this.path + 'icons/accordion.png',
          command: 'removeAccordionSection',
          group: 'accordionGroup'
        });

        editor.contextMenu.addListener(function (element) {
          var parentEl = element.getAscendant('dl', true);
          if (parentEl && parentEl.hasClass('ckeditor-accordion')) {
            return {
              accordionSectionBeforeItem: CKEDITOR.TRISTATE_OFF,
              accordionSectionAfterItem: CKEDITOR.TRISTATE_OFF,
              removeAccordionSection: CKEDITOR.TRISTATE_OFF
            };
          }
        });
      }
    }
  });
})();
