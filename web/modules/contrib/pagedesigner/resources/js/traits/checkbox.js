(function ($, Drupal) {

  function init(editor) {
    const TraitManager = editor.TraitManager;
    TraitManager.addType('checkbox',
      Object.assign({}, TraitManager.defaultTrait,
        {
          events: {
            change: 'onChange', // trigger parent onChange method on keyup
          },
          getInputEl: function () {
            if (!this.inputEl) {
              var checkbox = jQuery('<input type="checkbox" value="1" />');
              var value = this.model.get('value');
              if (value == 1) {
                checkbox.checked = true;
              }
              this.inputEl = checkbox.get(0);
            }

            return this.inputEl;
          },
          getRenderValue: function (value) {
            return this.model.get('value');
          },
          setTargetValue: function (value) {
            this.model.set('value', value);
          },
          setInputValue: function (value) {
            this.model.set('value', value);
            if (value == 1) {
              this.inputEl.checked = true;
            }
          }
        })
    );
  }

  Drupal.behaviors.pagedesigner_trait_checkbox = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
