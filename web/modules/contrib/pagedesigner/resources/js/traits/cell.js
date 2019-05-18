(function ($, Drupal) {

  function init(editor) {
    const TraitManager = editor.TraitManager;
    TraitManager.addType('cell', Object.assign({}, TraitManager.defaultTrait, {
      getInputEl: function () {
        return document.createElement('div');
      }
    }));
    TraitManager.getType('cell').hidden = true;
  }

  Drupal.behaviors.pagedesigner_trait_cell = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
