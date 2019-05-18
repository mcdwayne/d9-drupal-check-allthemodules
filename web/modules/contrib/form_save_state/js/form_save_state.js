(function ($, Drupal, CKEDITOR) {
  Drupal.behaviors.imFormSaveState = {
    attach: function (context, settings) {
      if (typeof drupalSettings.form_save_state.form_id ===  'undefined') {
        return;
      }

      // Autocomplete support.
      $('.ui-autocomplete-input').on('autocompleteselect', function(elem, ui) {
        $(this).val(ui.item.value);
        $(this).trigger('oninput');
      });

      // CKEDITOR support.
      // 1. Bind events to CKEDITOR instances.
      for (var i in CKEDITOR.instances) {
        //1.1 Bind KeyUp event to textsave.sisyphus jQuery event
        CKEDITOR.instances[i].on('contentDom', function () {
          var ckElement = this;
          this.document.on('keyup', function (event) {
            $(ckElement.element.$).trigger("textsave.sisyphus");
          });
        });
        //1.2 Bind Blur event to textsave.sisyphus jQuery event (useful after paste etc., otherwise can be omitted)
        CKEDITOR.instances[i].on('blur', function () {
          $(this.element.$).trigger("textsave.sisyphus");
        });
      }
      // 2. Initialize Sisyphus with handlers.
      var form_id = '#' + drupalSettings.form_save_state.form_id;
      $(form_id).sisyphus({
        //2.1 Set onBeforeSave to update all textareas from CKEDITOR instances
        onBeforeSave: function () {
          for (var edid in CKEDITOR.instances) {
            CKEDITOR.instances[edid].updateElement();
          }
        },
        //2.2 Set onBeforeTextSave, which is triggered when text is changed in textareas or input[type=text] elements
        onBeforeTextSave: function () {
          //Update
          var ed = CKEDITOR.instances[$(this).attr("id")];
          if (ed) {
            ed.updateElement();
          }
        },
        //2.3 Set onRestore to update CKEDITOR instances from textareas
        // onRestore: function () {
        //   for (var edid in CKEDITOR.instances) {
        //     var instance = CKEDITOR.instances[edid];
        //     var value = instance.element.getValue();
        //     instance.setData(value);
        //   }
        // }
      });

    }
  };

})(jQuery, Drupal, CKEDITOR);
