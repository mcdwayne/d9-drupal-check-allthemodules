/**
 * @file
 * The admonition dialog definition.
 *
 */
(function () {

  "use strict";
  /**
   * @todo: Localize titles and labels.
   */
  CKEDITOR.dialog.add('admonition', function (editor) {
    // var lang = editor.lang.admonition;

    return {

      // Basic properties of the dialog window: title, minimum size.
      title: "Admonition", //lang.dialogTitle,
      minWidth: 200,
      maxWidth: 480,
      minHeight: 200,
      maxHeight: 400,

      // Dialog window contents definition.
      contents: [
        {
          // Definition of the dialog tab.
          //@todo Need to define when have one tab?
          id: 'tab-basic',
          label: 'Basic Settings',
          // The controls on the tab.
          elements: [
            {
              //Explain what an admonition is.
              type: 'html',
              html: 'Advice to the reader, like a hint, or warning. '
            },
            {
              //Radio buttons for the type of the admonition.
              type: 'select',
              id: 'admonitionType',
              label: 'Admonition type',
              inputStyle: 'width: 120px',
              items: [
                ['Extra', 'extra'],
                ['Hint', 'hint'],
                ['Note', 'note'],
                ['Troubleshoot', 'troubleshoot'],
                ['Warning', 'warning']
              ],
              default: Drupal.settings.admonition.DEFAULT_TYPE,
              setup: function (widget) {
                //Called during dialog init.
                //Set the radio's value to the data on the widget.
                this.setValue(
                  widget.data.type ? widget.data.type : this.default
                )
              },
              commit: function (widget) {
                //Called when saving changes.
                //Set the widget's type value, depending on the radio button's value.
                widget.setData('type', this.getValue());
              }
            },
            {
              //Control for the width of the admonition.
              type: 'select',
              id: 'admonitionWidth',
              label: 'Display width',
              inputStyle: 'width: 120px',
              items: [
                ['Quarter', 'quarter'],
                ['Half', 'half'],
                ['Full', 'full']
              ],
              default: Drupal.settings.admonition.DEFAULT_WIDTH,
              setup: function( widget ) {
                this.setValue(
                  widget.data.width ? widget.data.width : this.default
                );
              },
              commit: function( widget ) {
                widget.setData( 'width', this.getValue() );
              }
            }, //End width field
            {
              //Control for alignment.
              type: 'select',
              id: 'admonitionAlignment',
              label: 'Alignment',
              inputStyle: 'width: 120px',
              items: [
                ['Left', 'left'],
                ['Center', 'center'],
                ['Right', 'right']
              ],
              default: Drupal.settings.admonition.DEFAULT_ALIGNMENT,
              setup: function( widget ) {
                this.setValue(
                  widget.data.alignment ? widget.data.alignment : this.default
                );
              },
              commit: function( widget ) {
                widget.setData( 'alignment', this.getValue() );
              }
            } //End alignment field
          ], //End elements
        }
      ] //End contents (dialog fields defs).
    };
  }); //End dialog add.

})();