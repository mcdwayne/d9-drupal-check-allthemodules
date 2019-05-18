(function() {
    CKEDITOR.plugins.add( 'reference_footnotes',
        {
            requires : [ 'fakeobjects','dialog' ],
            icons: 'reference_footnotes',
            onLoad: function() {
                var icon_path = window.location.origin + this.path + 'images/fn_icon2.png';
                var ref_icon_path = window.location.origin + this.path + 'images/fn_icon3.png';
                CKEDITOR.addCss(
                    '.cke_reference_footnote' +
                    '{' +
                    'background-image: url(' + CKEDITOR.getUrl( ref_icon_path ) + ');' +
                    'background-position: center center;' +
                    'background-repeat: no-repeat;' +
                    'width: 16px;' +
                    'height: 16px;' +
                    '}' +
                    '.cke_footnote' +
                    '{' +
                    'background-image: url(' + CKEDITOR.getUrl( icon_path ) + ');' +
                    'background-position: center center;' +
                    'background-repeat: no-repeat;' +
                    'width: 16px;' +
                    'height: 16px;' +
                    '}'

                );
            },
            beforeInit: function( editor )
            {
                // Adapt some critical editor configuration for better support
                // of BBCode environment.
                var config = editor.config;
                CKEDITOR.tools.extend( config,
                    {
                        enterMode : CKEDITOR.ENTER_BR,
                        basicEntities: false,
                        entities : false,
                        fillEmptyBlocks : false
                    }, true );
            },
            init: function( editor )
            {

                editor.addCommand('createreferencefootnotes', new CKEDITOR.dialogCommand('createreferencefootnotes', {
                    allowedContent: 'fn[value]'
                }));
                editor.addCommand('editreferencefootnotes', new CKEDITOR.dialogCommand('editreferencefootnotes', {
                    allowedContent: 'fn[value]'
                }));

                // Drupal Wysiwyg requirement: The first argument to editor.ui.addButton()
                // must be equal to the key used in $plugins[<pluginName>]['buttons'][<key>]
                // in hook_wysiwyg_plugin().
                editor.ui.addButton && editor.ui.addButton( 'reference_footnotes', {
                    label: Drupal.t('Add a reference footnote'),
                    command: 'createreferencefootnotes',
                    icon: 'reference_footnotes'
                });

                if (editor.addMenuItems) {
                    editor.addMenuGroup('reference_footnotes', 100);
                    editor.addMenuItems({
                        footnotes: {
                            label: Drupal.t('Edit footnote'),
                            command: 'editreferencefootnotes',
                            icon: 'reference_footnotes',
                            group: 'reference_footnotes'
                        }
                    });
                }
                if (editor.contextMenu) {
                    editor.contextMenu.addListener( function( element, selection ) {
                        if ( !element || element.data('cke-real-element-type') != 'fn' )
                            return null;

                        return { footnotes: CKEDITOR.TRISTATE_ON };
                    });
                }

                editor.on( 'doubleclick', function( evt ) {
                    if ( CKEDITOR.plugins.reference_footnotes.getSelectedFootnote( editor ) )
                    {
                      evt.data.dialog = 'editreferencefootnotes';
                    }
                }, null, null, 1); // Ensure this event fires after the 'footnotes' event so we can decide which dialog to show.

                CKEDITOR.dialog.add( 'createreferencefootnotes', this.path + 'dialogs/footnotes.js' );
                CKEDITOR.dialog.add( 'editreferencefootnotes', this.path + 'dialogs/footnotes.js' );
            },
            afterInit : function( editor ) {
                var dataProcessor = editor.dataProcessor,
                    dataFilter = dataProcessor && dataProcessor.dataFilter;

                if (dataFilter) {
                    dataFilter.addRules({
                        elements: {
                            fn: function(element ) {

                              return editor.createFakeParserElement(element, 'cke_reference_footnote', 'hiddenfield', false);

                            }
                        }
                    });
                }
            }
        });
})();

CKEDITOR.plugins.reference_footnotes = {
    createFootnote: function( editor, origElement, text, value, reference, page) {
        if (!origElement) {
            var realElement = CKEDITOR.dom.element.createFromHtml('<fn></fn>');
        }
        else {
            realElement = origElement;
        }

        if (text && text.length > 0 ) {
            realElement.setHtml(text);
        }
        realElement.setAttribute('value',value);
        if (page && page.length > 0) {
            realElement.setAttribute('page', page);
        }
        if (reference && reference.length > 0) {
            realElement.setAttribute('reference', reference);
        }

        var fakeElement = editor.createFakeElement( realElement , 'cke_reference_footnote', 'hiddenfield', false );
        editor.insertElement(fakeElement);
    },

    getSelectedFootnote: function( editor ) {
        var selection = editor.getSelection();
        var element = selection.getSelectedElement();
        var seltype = selection.getType();

        if ( seltype == CKEDITOR.SELECTION_ELEMENT && element.data('cke-real-element-type') == 'hiddenfield') {
            return element;
        }
    }
};
