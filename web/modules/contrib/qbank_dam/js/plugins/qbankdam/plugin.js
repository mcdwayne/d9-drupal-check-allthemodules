/**
 * @file
 * QBank DAM plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

    'use strict';

    CKEDITOR.plugins.add('qbankdam', {
        icons: 'qbankdam',
        hidpi: true,

        init: function (editor) {
            editor.addCommand('qbankdam', {
                allowedContent: {
                    img: {
                        attributes: {
                            '!src': true,
                            '!data-entity-type': true,
                            '!data-entity-uuid': true
                        },
                        classes: {}
                    }
                },
                requiredContent: new CKEDITOR.style({
                    element: 'img',
                    attributes: {
                        'src': '',
                        'data-entity-type': '',
                        'data-entity-uuid': ''
                    }
                }),
                modes: {wysiwyg: 1},
                exec: function (editor) {
                    var qbankdamSaveCallback = function(returnValues) {
                        // Create a new file element if needed.
                        if (returnValues.attributes.src) {
                            var selection = editor.getSelection();
                            var range = selection.getRanges(1)[0];

                            // Use the link title or the file name as text with a collapsed
                            // cursor.
                            if (range.collapsed) {
                                var text;
                                if (returnValues.attributes.title && returnValues.attributes.title.length) {
                                    text = returnValues.attributes.title;
                                }
                                else {
                                    text = returnValues.attributes.src;
                                    text = text.substr(text.lastIndexOf('/') + 1);
                                }
                                text = new CKEDITOR.dom.text(text, editor.document);
                                range.insertNode(text);
                                range.selectNodeContents(text);
                            }

                            // Create the new file by applying a style to the new text.
                            var style = new CKEDITOR.style({element: 'img', attributes: returnValues.attributes});
                            style.type = CKEDITOR.STYLE_INLINE;
                            style.applyToRange(range);
                            range.select();
                        }
                        // Save snapshot for undo support.
                        editor.fire('saveSnapshot');
                    };

                    var existingValues = {};
                    Drupal.ckeditor.openDialog(editor,
                        Drupal.url('qbank_dam/dialog/' + editor.config.drupal.format ),
                        existingValues,
                        qbankdamSaveCallback,
                        {}
                    );
                }
            });

            if (editor.ui.addButton) {
                editor.ui.addButton('Qbankdam', {
                    label: Drupal.t('QBank DAM'),
                    command: 'qbankdam',
                    icon: this.path + 'images/icon.png'
                });
            }
        }
    });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
