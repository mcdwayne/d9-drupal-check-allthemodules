(function ($, Drupal) {

  function init(editor) {
    editor.TraitManager.addType('text', Object.assign({}, editor.TraitManager.defaultTrait));
  }

  Drupal.behaviors.pagedesigner_trait_text = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
