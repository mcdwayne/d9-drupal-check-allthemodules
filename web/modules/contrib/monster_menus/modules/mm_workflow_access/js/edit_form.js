(function ($) {
  Drupal.behaviors.MMworkflowAccessEditForm = {
    attach: function(context) {
      $('input.wfe-everyone:not(.wfe-everyone-processed)', context)
        .addClass('wfe-everyone-processed')
        .click(function() {
          if (this.checked) {
            var p = this.parentNode.parentNode;
            $('input.wfe-author', p).removeAttr('checked');
            $('input:hidden', p)[0].delAll();
          }
        });
      $('input.wfe-author:not(.wfe-author-processed)', context)
        .addClass('wfe-author-processed')
        .click(function() {
          if (this.checked) {
            $('input.wfe-everyone', this.parentNode.parentNode)
              .removeAttr('checked');
          }
        });
    }
  };
})(jQuery);
