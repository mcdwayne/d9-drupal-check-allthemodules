/**
 * @file
 * Ebourgognetf plugin.
 *
 * @ignore
 */

CKEDITOR.plugins.add('ebourgognetf', {
    init: function (editor) {

        // Create an editor command that stores the dialog initialization command.
      // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.command.html
      // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialogCommand.html
      editor.addCommand('tf_linkDialog', new CKEDITOR.dialogCommand('tf_linkDialog'));

        // Fill the forms list, based on the content of the list "liste_tf".
      function setForms()
      {
        CKEDITOR.dialog.getCurrent().getContentElement("general", "forms").clear();

        var listeTeleform = drupalSettings.ebourgogne_tf.liste_tf;
        var baseUrl = drupalSettings.ebourgogne_tf.ebou_tf_fo_base_url;

        for (var i = 0, l = listeTeleform.length; i < l; i++) {
          CKEDITOR.dialog.getCurrent().getContentElement("general", "forms").add(listeTeleform[i]['eForm'].name,baseUrl + listeTeleform[i].url);
        }
      }

      // Add a new dialog window definition containing all UI elements and listeners.
      // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.html#.add
      // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.dialogDefinition.html
      CKEDITOR.dialog.add('tf_linkDialog', function (editor) {

        return {
          // Basic properties of the dialog window: title, minimum size.
          // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.dialogDefinition.html
          title : editor.config.ebourgogneTf_AddForm,
          minWidth : 400,
          minHeight : 200,
          // Dialog window contents.
          // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.definition.content.html
          contents :
          [
          {
            // Definition of the Settings dialog window tab (page) with its id, label and contents.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.contentDefinition.html
            id : 'general',
            label : Drupal.t('Settings'),
            elements :
            [
            {
              type : 'select',
              id : 'forms',
              label : editor.config.ebourgogneTf_Form,
              // Items that will appear inside the selection field, in pairs of displayed text and value.
              // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.dialog.select.html#constructor
              onLoad:function () {
                setForms();
              },
              items :
              [
              [ '-', '-' ]
              ],
              commit : function (data) {

                data.url = this.getValue();
              }
            },

            // Dialog window UI element: a textarea field for the link text.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.dialog.textarea.html
            {
              type : 'textarea',
              id : 'contents',
              // Text that labels the field.
              // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.dialog.labeledElement.html#constructor
              label : editor.config.ebourgogneTf_LinkText,
              // Validation checking whether the field is not empty.
              validate : CKEDITOR.dialog.validate.notEmpty(editor.config.ebourgogneTf_EmptyLink),
              // This field is required.
              required : true,
              // Function to be run when the commitContent method of the parent dialog window is called.
              // Get the value of this field and save it in the data object attribute.
              // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#getValue
              commit : function (data) {

                data.contents = this.getValue();
              }
            },
            // Dialog window UI element: a checkbox for opening in a new page.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.dialog.checkbox.html
            {
              type : 'checkbox',
              id : 'newPage',
              label : editor.config.ebourgogneTf_NewTab,
              // Default value.
              // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.ui.dialog.checkbox.html#constructor
              'default' : true,
              commit : function (data) {

                data.newPage = this.getValue();
              }
            }
            ]
          }
          ],
          onOk : function () {

            // Create a link element and an object that will store the data entered in the dialog window.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.document.html#createElement
            var dialog = this,
            data = {},
            link = editor.document.createElement('a');
            // Populate the data object with data entered in the dialog window.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.html#commitContent
            this.commitContent(data);

            // Set the URL (href attribute) of the link element.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#setAttribute
            link.setAttribute('href', data.url);

            // In case the "newPage" checkbox was checked, set target=_blank for the link element.
            if (data.newPage) {
              link.setAttribute('target', '_blank');
            }

            // Insert the Displayed Text entered in the dialog window into the link element.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dom.element.html#setHtml
            link.setHtml(data.contents);

            // Insert the link element into the current cursor position in the editor.
            // http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.editor.html#insertElement
            editor.insertElement(link);
          }
        };
      });

      // Add buttons.
      if (editor.ui.addButton) {
        editor.ui.addButton('EbourgogneTf', {
          label: editor.config.ebourgogneTf_Form,
          command: 'tf_linkDialog',
          icon: this.path + '/logo.png'
        });
      }
    }
  });
