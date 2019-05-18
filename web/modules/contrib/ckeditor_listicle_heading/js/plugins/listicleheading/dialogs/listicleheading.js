/**
 * @file
 * The listicleheading dialog definition.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

// Our dialog definition.
CKEDITOR.dialog.add('listicleheadingDialog', function (editor) {
  var lang = editor.lang.listicleheading;

  return {

    // Basic properties of the dialog window: title, minimum size.
    title: lang.dialogTitle,
    minWidth: 400,
    minHeight: 200,

    // Dialog window contents definition.
    contents: [
    {
      // Definition of the Basic Settings dialog tab (page).
      id: 'tab-basic',
      label: 'Basic Settings',

      // The tab contents.
      elements: [
      {
        // Select input field for the heading tag.
        type: 'select',
        id: 'format',
        label: lang.dialogListicleHeadingFormat,
        items: [
          [ lang.dialogHeading1, 'h1' ],
          [ lang.dialogHeading2, 'h2' ],
          [ lang.dialogHeading3, 'h3' ],
          [ lang.dialogHeading4, 'h4' ],
          [ lang.dialogHeading5, 'h5' ],
          [ lang.dialogHeading6, 'h6' ]
        ],
        'default': 'h2',

        // Called by the main setupContent call on dialog initialization.
        setup: function (element) {
          if (element.getChild(0) !== null) {
            var h = element.getChild(0).$.localName;
            this.setValue(h);
          }
        },

        // Called by the main commitContent call on dialog confirmation.
        commit: function (element) {
          var tag = this.getValue();
          element.setHtml("");
          var h = editor.document.createElement(tag);
          element.append(h);
        }

      },
      {
        // Text input field for the listicle heading number.
        type: 'text',
        id: 'number',
        label: lang.dialogListicleHeadingNumber,

        // Validation checking whether the field is not empty.
        validate: CKEDITOR.dialog.validate.notEmpty("Listicle heading number field cannot be empty"),

        // Called by the main setupContent call on dialog initialization.
        setup: function (element) {
          if (element.getChild(0) !== null) {
            var h = element.getChild(0).$.localName;
            var list = element.find(h + ' span');
            if (list.count() === 3) {
              var span = list.getItem(0);
              this.setValue(span.getText());
            }
          }
        },

        // Called by the main commitContent call on dialog confirmation.
        commit: function (element) {
          if (element.getChild(0) !== null) {
            var number = this.getValue();

            // Append a new span for the number.
            var span = editor.document.createElement("span");
            span.setText(number);
            span.setAttribute("class", "number");
            element.append(span);
            element.getChild(0).append(span);

            // Append a new span separator node.
            var separator = editor.document.createElement("span");
            separator.setText(". ");
            separator.setAttribute("class", "separator");
            element.append(separator);
            element.getChild(0).append(separator);
          }
        }
      },
      {
        // Text input field for the listicle heading title.
        type: 'text',
        id: 'title',
        label: lang.dialogListicleHeadingTitle,

        // Validation checking whether the field is not empty.
        validate: CKEDITOR.dialog.validate.notEmpty("Listicle heading title field cannot be empty"),

        // Called by the main setupContent call on dialog initialization.
        setup: function (element) {
          if (element.getChild(0) !== null) {
            var h = element.getChild(0).$.localName;
            var list = element.find(h + ' span');
            if (list.count() === 3) {
              var span = list.getItem(2);
              this.setValue(span.getText());
            }
          }
        },

        // Called by the main commitContent call on dialog confirmation.
        commit: function (element) {
          if (element.getChild(0) !== null) {
            var title = this.getValue();

            // Append a new span for the title.
            var span = editor.document.createElement("span");
            span.setText(title);
            span.setAttribute("class", "title");
            element.append(span);
            element.getChild(0).append(span);
          }
        }
      }
      ]
    }
    ],

    // Invoked when the dialog is loaded.
    onShow: function () {

      // Get the selection in the editor.
      var selection = editor.getSelection();

      // Get the element at the start of the selection.
      var element = selection.getStartElement();

      // Get the div element closest to the selection, if any.
      if (element) {
        element = element.getAscendant('div', true);
      }

      // Create a new <div> element if it does not exist.
      if (!element || element.getName() !== 'div') {
        element = editor.document.createElement('div');
        element.setAttribute("class", "listicle-heading");

        // Flag the insertion mode for later use.
        this.insertMode = true;
      }
      else {
        this.insertMode = false;
      }

      // Store the reference to the <div> element in an internal property, for later use.
      this.element = element;

      // Invoke the setup methods of all dialog elements, so they can load the element attributes.
      if (!this.insertMode) {
        this.setupContent(this.element);
      }
    },

    // This method is invoked once a user clicks the OK button, confirming the dialog.
    onOk: function () {

      // The context of this function is the dialog object itself.
      // http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
      var dialog = this;

      var div = this.element;

      // Invoke the commit methods of all dialog elements, so the <div> element gets modified.
      this.commitContent(div);

      // Finally, in if insert mode, inserts the element at the editor caret position.
      if (this.insertMode) {
        editor.insertElement(div);
      }
    }
  };
});
