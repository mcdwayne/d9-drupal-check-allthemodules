(function() {
    function referenceFootnotesDialog( editor, isEdit ) {
        return {
            title : Drupal.t('Reference Footnotes Dialog'),
            minWidth : 500,
            minHeight : 50,
            contents : [
                {
                    id: 'info',
                    label: Drupal.t('Add a reference footnote'),
                    title: Drupal.t('Add a reference footnote'),
                    elements:
                        [
                          {
                            id: 'reference',
                            type: 'select',
                            items: [[" - None - ", 0]].concat(typeof(drupalSettings.bibcite_footnotes) !== 'undefined' ? drupalSettings.bibcite_footnotes.references : []),
                            label: Drupal.t('Reference Footnote item:'),
                            setup: function (element) {
                              if (isEdit)
                                this.setValue(element.getAttribute('reference'));
                            }
                          },
                          {
                            id: 'footnote',
                            type: 'textarea',
                            label: Drupal.t('Or add free-form footnote text :'),
                            setup: function (element) {
                                if (isEdit) {
                                  var markup = element.getHtml();

                                  this.setValue(markup);

                                }
                            }
                          },
                          {
                            id: 'html-help',
                            type: 'html',
                            html: 'HTML tags can be used, e.g., &lt;strong&gt;, &lt;em&gt;, &lt;a href=&quot;...&quot;&gt;',
                          },
                          {
                            id: 'page',
                            type: 'text',
                            labelLayout: 'horizontal',
                            label: Drupal.t('Page(s):'),
                            style: 'float:left:width:50px',
                            setup: function (element) {
                              if (isEdit) {
                                this.setValue(element.getAttribute('page'));
                              }
                            }
                          },
                            {
                                id: 'value',
                                type: 'text',
                                label: Drupal.t('Value :'),
                                labelLayout: 'horizontal',
                                style: 'float:left;width:200px;',
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
                this.fakeObj = CKEDITOR.plugins.reference_footnotes.getSelectedFootnote(editor);
                this.realObj = editor.restoreRealElement(this.fakeObj);
              }
              var select = this.parts.contents.$.getElementsByTagName('select');
              var selectBox = select.item(0);
              // Remove all but the default 'None' item from teh list.
              var i;
              for (i = selectBox.options.length - 1; i >= 1; i--) {
                selectBox.remove(i)
              }

              // Re-add buttons from the current state of Settings.
              if (typeof (drupalSettings.bibcite_footnotes) !== 'undefined') {

                drupalSettings.bibcite_footnotes.references.forEach(function (reference) {
                  var newReference = document.createElement('option');
                  newReference.text = reference[0];
                  newReference.setAttribute("value", reference[1]);
                  selectBox.add(newReference);
                });
              }
              this.setupContent( this.realObj );
            },
            onOk : function() {
                var referenceNote = this.getValueOf('info', 'reference');
                var textNote = this.getValueOf('info', 'footnote');
                var page = this.getValueOf('info', 'page');
                CKEDITOR.plugins.reference_footnotes.createFootnote( editor, this.realObj, textNote, this.getValueOf('info', 'value'), referenceNote, page);
                delete this.fakeObj;
                delete this.realObj;
            }
        }
    }

    CKEDITOR.dialog.add( 'createreferencefootnotes', function( editor ) {
        return referenceFootnotesDialog( editor );
    });
    CKEDITOR.dialog.add( 'editreferencefootnotes', function( editor ) {
        return referenceFootnotesDialog( editor, 1 );
    });
})();