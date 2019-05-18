(function ($, Drupal) {

  function init(editor) {
    const TraitManager = editor.TraitManager;
    TraitManager.addType('hide',
      Object.assign({}, TraitManager.defaultTrait,
        {
          getInputEl: function () {
            if (!this.inputEl) {
              var element = jQuery('<span style="display:none;"></span>');
              this.inputEl = element.get(0);
            }
            return this.inputEl;
          },
          getRenderValue: function (value) {
            return '';
          },
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
