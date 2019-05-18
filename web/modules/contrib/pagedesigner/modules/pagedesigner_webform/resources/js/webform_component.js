// components
(function ($, Drupal) {

  function component(editor) {

    // add new component type layout
    editor.DomComponents.addType('webform', {
      extend: 'component',
    });
  }

  function commands(editor) {
    if (!initialized) {

      // command create layout
      editor.Commands.add('open-modal-create-layout', {
        run(editor, sender) {
          // open modal
          editor.Modal.setTitle('Create layout form component');
          editor.Modal.setContent('<div><label>Layout name<input/></label><label>Description<textarea></textarea></label><label>Copy contents?<input type="checkbox"/></label></div>').open();
        }
      });

      // command create layout
      editor.Commands.add('save-layout', {
        run(editor, sender) {
          // open modal
        }
      });

    }
  }

  Drupal.behaviors.pagedesigner_webform = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-components', function (e, editor) {
        component(editor);
      });
      // $(document).on('pagedesigner-init-commands', function (e, editor) {
      //   commands(editor);
      // });
    }
  };

})(jQuery, Drupal);
