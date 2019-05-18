(function() {
    function footnotesDialog( editor, isEdit ) {
        return {
            title : Drupal.t('Footnotes Dialog'),
            minWidth : 500,
            minHeight : 50,
            contents : [
                {
                    id: 'info',
                    label: Drupal.t('Add a footnote'),
                    title: Drupal.t('Add a footnote'),
                    elements:
                        [
                            {
                                id: 'footnote',
                                type: 'text',
                                label: Drupal.t('Footnote text :'),
                                setup: function (element) {
                                    if (isEdit)
                                        this.setValue(element.getHtml());
                                }
                            },
                            {
                                id: 'value',
                                type: 'text',
                                label: Drupal.t('Value :'),
                                labelLayout: 'horizontal',
                                style: 'float:left;width:100px;',
                                setup: function (element) {
                                    if (isEdit)
                                        this.setValue(element.getAttribute('value'));
                                }
                            }
                        ],
                }
            ],
            onShow : function() {
                if (isEdit) {
                    this.fakeObj = CKEDITOR.plugins.footnotes.getSelectedFootnote( editor );
                    this.realObj = editor.restoreRealElement( this.fakeObj );
                }
                this.setupContent( this.realObj );
            },
            onOk : function() {
                CKEDITOR.plugins.footnotes.createFootnote( editor, this.realObj, this.getValueOf('info', 'footnote'), this.getValueOf('info', 'value'));
                delete this.fakeObj;
                delete this.realObj;
            }
        }
    }

    CKEDITOR.dialog.add( 'createfootnotes', function( editor ) {
        return footnotesDialog( editor );
    });
    CKEDITOR.dialog.add( 'editfootnotes', function( editor ) {
        return footnotesDialog( editor, 1 );
    });
})();