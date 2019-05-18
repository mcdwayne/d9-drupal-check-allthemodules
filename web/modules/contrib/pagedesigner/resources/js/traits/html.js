(function ($, Drupal) {

  function init(editor) {
    const TraitManager = editor.TraitManager;
    TraitManager.addType('html', Object.assign({}, TraitManager.defaultTrait, {

      afterInit: function () {
        el = this.getInputEl();
        Drupal.editorAttach(el, drupalSettings.editor.formats['pagedesigner']);
        model = this.model;
        CKEDITOR.instances[el.id].on('change', function (evt) {
          model.setTargetValue(evt.editor.getData());
        }, CKEDITOR.instances[el.id].element.$);

      },


      getInputEl: function () {
        if (!this.inputEl) {
          var input = document.createElement('textarea');
          // input.innerHTML = this.model.get('value');
          var value = this.target.attributes.attributes[this.model.get('name')];
          if( value ){
            input.innerHTML = value;
          }
          input.id = 'component_' + this.target.get('entityId') + '_' + this.model.get('name');
          this.inputEl = input;
        }
        return this.inputEl;
      },
      getRenderValue: function (value) {
        if (typeof this.model.get('value') == 'undefined') {
          return value;
        }
        return this.model.get('value');
      }
    }));
  }

  Drupal.behaviors.pagedesigner_trait_html = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
