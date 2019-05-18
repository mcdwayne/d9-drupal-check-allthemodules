// components
(function ($, Drupal) {
  var initialized;

  function init(editor) {
    if (!initialized) {

      // add new component type layout
      editor.DomComponents.addType('layout', {
        extend: 'layout',
        model: {
          defaults: {
            editable: true,
            badgable: true,
            selectable: true,
            highlightable: true,
            hoverable: true,
            removable: true,
            copyable: true,
            draggable: '[data-grapes-block="content"] [data-gjs-type="cell"]',
            draggable: true
          },

          create(connector = defaultConnector) {
            console.log('create layout')
          },

          init() {
            console.log('init layout');
            this.components('<p>Layout Test</p>');
          },

          getRenderData(values) {
            console.log('getRenderData');
          },

          updateView() {
            console.log('updateView');
            if (this.changed.attributes) {
              this.components('<p>Layout Test<</p>');
            }
          },

          toHTML() {
            console.log('toHTML');
            return '<p>Layout Test<</p>'
          },

        }
      });
    }
  }

  Drupal.behaviors.pagedesigner_layouts_components = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-components', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);




// commands
(function ($, Drupal) {
  var initialized;

  function init(editor) {
    if (!initialized) {

      // command create layout
      editor.Commands.add('open-modal-create-layout', {
        run(editor, sender) {
          // open modal
          editor.Modal.setTitle('Create layout form component');
          var modal = $('#pdCreateLayout');
          if (modal.length == 0) {
            modal = $(`
            <div id="pdCreateLayout" title="Create layout form component" style="display: none;">
              <p>
              <i class="fas fa-copy" style="float:left; margin:12px 12px 20px 0;"></i>
              The component will be saved as a layout. Please give a name and a description.
              </p>
              <h4>Name*</h4>
              <input type="text" name="name" value="" />
              <h4>Description</h4>
              <textarea id="revisionMessage" name="description" style="height:150px;width: 100%;"></textarea>

            </div >
            `);
            // and indicate whether to include the current content.
            //<h4>Include content</h4>
            //<input type="checkbox" name="includecontent" value="1" />
            $('body').append(modal);
          }
          modal.dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
              "Save layout": (function (action) {
                return function () {
                  var name = modal.find("input[name=name]");
                  if (name.val() == '') {
                    name.css('border', '2px solid red');
                    return;
                  }
                  var description = modal.find("textarea[name=description]");
                  // var includeContent = modal.find("input[name=includecontent]");
                  Drupal.restconsumer.post('/pagedesigner/layout', {
                    original: editor.getSelected().get('entityId'),
                    name: name.val(),
                    description: description.val(),
                    // include_content: includeContent.get(0).checked,
                  });
                  $(this).dialog("close");
                }
              })($(this)),
              Cancel: function () {
                $(this).dialog("close");
              }
            }
          });
        }
      });

      // command create layout
      editor.Commands.add('save-layout', {
        run(editor, sender) {

        }
      });

    }
  }

  Drupal.behaviors.pagedesigner_layouts_commands = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-commands', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);

// before init
(function ($, Drupal) {

  function init(editor) {
    // var baseType = editor.DomComponents.getType('default');

    // lock components for editing etc.
    editor.DomComponents.getTypes().forEach(function (componentType) {
      if (componentType.id == 'row' || componentType.id == 'component') {
        editor.DomComponents.addType(componentType.id, {
          model: componentType.model.extend(
            {
              initToolbar(...args) {
                componentType.model.__super__.initToolbar.apply(this, args);
                var tb = this.get('toolbar');
                if (tb) {
                  tb.push({
                    attributes: {
                      class: `fas fa-cube`,
                      title: Drupal.t('Create Layout'),
                    },
                    command: 'open-modal-create-layout'
                  });
                  this.set('toolbar', tb);
                }
              },
            }
          ),
          view: componentType.view
        });
      }
    });
  }

  Drupal.behaviors.pagedesigner_layouts_before_init = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-components', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
