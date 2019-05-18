(function ($, Drupal) {
  Drupal.behaviors.MMNodeRedir = {
    attach: function (context) {
      $('.node-redir-url', context).once('node-redir-url').each(function () {
        $(this).change(function () {
          if (this.value != '' && this.form['field_redirect_mmtid[0][value]'].value != '')
            this.form['field_redirect_mmtid[0][value]'].delAll();
        });
      });
    }
  };
})(jQuery, Drupal);