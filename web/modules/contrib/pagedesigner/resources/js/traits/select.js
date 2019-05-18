(function ($, Drupal) {

  function init(editor) {
    initialized = true;
    const TraitManager = editor.TraitManager;
    TraitManager.addType('select',
      Object.assign({}, TraitManager.defaultTrait,
        {
          events: {
            change: 'onChange', // trigger parent onChange method on keyup
          },
          getInputEl: function () {
            if (!this.inputEl) {
              var select = jQuery('<select>');
              var options = this.model.attributes.additional.options;
              var value = this.model.get('value');
              for (var key in options) {
                var option = $('<option value="' + key + '">' + options[key] + '</option>');
                if ((!value || value == '') && key == this.model.attributes.additional.preview) {
                  this.model.set(key);
                  option.prop('selected', true);
                } else if (key == value) {
                  option.prop('selected', true);
                }
                select.append(option);
              }
              this.inputEl = select.get(0);
            }

            return this.inputEl;
          },
          getRenderValue: function (value) {
            if (typeof this.model.get('value') == 'undefined') {
              return value;
            }
            return this.model.get('value');
          },
          setTargetValue: function (value) {
            this.model.set('value', value);
          },
          setInputValue: function (value) {
            if (value) {
              this.model.set('value', value);
              $(this.inputEl).val(value);
            }
          }
        })
    );
  }

  Drupal.behaviors.pagedesigner_trait_select = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
