(function ($, Drupal) {
  var initialized;

  function init(editor) {
    if (!initialized) {
      initialized = true;
      const TraitManager = editor.TraitManager;
      TraitManager.addType('target',
        Object.assign({}, TraitManager.defaultTrait,
          {
            events: {
              change: 'onChange', // trigger parent onChange method on keyup
            },
            getInputEl: function () {
              if (!this.inputEl) {
                var select = jQuery('<select>');
                select.append('<option value="_top">Same window</option>');
                select.append('<option value="_blank">New window</option>');
                this.inputEl = select.get(0);
                var value = this.model.get('value');
                if (value) {
                  select.val(value);
                }
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
                $(this.inputEl).val(value);
              }
              this.model.set('value', value);
            }
          })
      );

    }
  }

  Drupal.behaviors.pagedesigner_link_target = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
