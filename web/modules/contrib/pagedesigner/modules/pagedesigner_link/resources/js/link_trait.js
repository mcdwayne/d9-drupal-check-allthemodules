(function ($, Drupal) {

  function init(editor) {
    const TraitManager = editor.TraitManager;
    TraitManager.addType('link',
      Object.assign({}, TraitManager.defaultTrait,
        {
          events: {
            //            change: 'onChange', // trigger parent onChange method on keyup
          },

          afterInit: function () {
            var linktrait = this;

            var action = jQuery(this.inputEl);
            var input = action.find('input');



            input.on('autocompleteselect', function (event, entry) {
              // if the the already chosen link is selected,
              // entry.item.path = "Title (node/id)" = entry.item.path
              if (linktrait.model.get('value').title + ' (' + linktrait.model.get('value').uri + ')' != entry.item.path) {
                var value = {
                  uri: entry.item.path,
                  title: entry.item.value
                };
                input.val(value.title + ' (' + value.uri + ')');
                linktrait.setTargetValue(value);
              }

              event.preventDefault();
            });
            input.on("autocompleteclose", function (event, ui) {
              var value = linktrait.model.get('value');
              if (value && value.uri != null) {
                if (!value.uri.startsWith('/')) {
                  input.val(value.uri);
                } else {
                  input.val(value.title + ' (' + value.uri + ')');
                }
              }
              event.preventDefault();
            });

            Drupal.behaviors.linkit_autocomplete.attach(action);

          },

          getInputEl: function () {
            if (!this.inputEl) {
              var linktrait = this;
              var action = jQuery('<div />');
              var input = jQuery('<input type="text" name="link" class="form-linkit-autocomplete ui-autocomplete-input" data-autocomplete-path="/linkit/autocomplete/default_linkit" style="width:100%" maxlength="2048" autocomplete="off" />');
              action.append(input);
              var value = this.model.get('value');
              if (value && value.uri != null) {
                if (!value.uri.startsWith('/')) {
                  input.val(value.uri);
                } else {
                  var text = value.uri;
                  if (value.title) {
                    text = value.title + ' (' + value.uri + ')';
                  }
                  input.val(text);
                }
              }
              this.inputEl = action.get(0);
            }
            return this.inputEl;
          },
          getRenderValue: function (value) {
            if (typeof this.model.get('value') == 'undefined') {
              return value;
            }
            return this.model.get('value').uri;
          },
          setTargetValue: function (value) {
            this.model.set('value', value);
          },
          setInputValue: function (value) {
            if (value && value.uri != null) {
              if (!value.uri.startsWith('/')) {
                $(this.inputEl).find('input').val(value.uri);
              } else {
                var text = value.uri;
                if (value.title) {
                  text = value.title + ' (' + value.uri + ')';
                }
                $(this.inputEl).find('input').val(text);
              }
            }
            this.model.set('value', value);
          }
        })
    );
  }

  Drupal.behaviors.pagedesigner_link_link = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
