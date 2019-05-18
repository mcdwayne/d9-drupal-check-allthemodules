(function ($, Drupal) {

  function init(editor) {
    const TraitManager = editor.TraitManager;
    TraitManager.addType('textarea', Object.assign({}, TraitManager.defaultTrait, {
      getInputEl: function () {
        if (!this.inputEl) {
          var input = jQuery('<textarea>');
          input.attr('rows', 20);
          input.html(this.model.get('value'));
          this.inputEl = input.get(0);
        }
        return this.inputEl;
      },
      getRenderValue: function (value) {
        return value.replace(/\n/g, "<br />");
      }
    }));
    TraitManager.addType('longtext', Object.assign({}, TraitManager.getType('textarea')));
  }

  Drupal.behaviors.pagedesigner_trait_textarea = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
