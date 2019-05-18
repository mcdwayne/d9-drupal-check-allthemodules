/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights
 *     reserved. For licensing, see LICENSE.md or
 *     https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @fileOverview The "drupalplaceholder" plugin.
 *
 */

'use strict';

(function ($, Drupal, CKEDITOR) {
  CKEDITOR.plugins.add('drupalplaceholder', {
    requires: 'widget,dialog',
    icons: 'drupalplaceholder',
    hidpi: true,

    onLoad: function () {
      // Register styles for placeholder widget frame.
      CKEDITOR.addCss('.cke_placeholder{background-color:#ff0}');
    },

    init: function (editor) {

      // Register dialog.
      CKEDITOR.dialog.add('drupalplaceholder', this.path + 'dialogs/placeholder.js');

      // Put ur init code here.
      editor.widgets.add('drupalplaceholder', {
        // Widget code.
        dialog: 'drupalplaceholder',
        // We need to have wrapping element, otherwise there are issues in
        // add dialog.
        template: '<span class="cke_placeholder">[[]]</span>',

        downcast: function () {
          return new CKEDITOR.htmlParser.text('[[' + this.data.name + ']]');
        },

        init: function () {
          // Note that placeholder markup characters are stripped for the name.
          this.setData('name', this.element.getText().slice(2, -2));
        },

        data: function () {
          this.element.setText('[[' + this.data.name + ']]');
        },

        getLabel: function () {
          return this.editor.lang.widget.label.replace(/%1/, this.data.name + ' ' + this.pathName);
        }
      });

      editor.ui.addButton && editor.ui.addButton('DrupalPlaceholder', {
        label: Drupal.t('Placeholder'),
        command: 'drupalplaceholder'
      });
    },

    afterInit: function (editor) {
      var placeholderReplaceRegex = /\[\[([^\[\]])+\]\]/g;

      editor.dataProcessor.dataFilter.addRules({
        text: function (text, node) {
          var dtd = node.parent && CKEDITOR.dtd[node.parent.name];

          // Skip the case when placeholder is in elements like <title> or
          // <textarea> but upcast placeholder in custom elements (no DTD).
          if (dtd && !dtd.span) {
            return;
          }

          return text.replace(placeholderReplaceRegex, function (match) {
            // Creating widget code.
            var widgetWrapper = null,
                innerElement = new CKEDITOR.htmlParser.element('span', {
                  'class': 'cke_placeholder'
                });

            // Adds placeholder identifier as innertext.
            innerElement.add(new CKEDITOR.htmlParser.text(match));
            widgetWrapper = editor.widgets.wrapElement(innerElement, 'drupalplaceholder');

            // Return outerhtml of widget wrapper so it will be placed
            // as replacement.
            return widgetWrapper.getOuterHtml();
          });
        }
      });
    }
  });

})(jQuery, Drupal, CKEDITOR);
