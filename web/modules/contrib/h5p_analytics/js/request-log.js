(function ($, Drupal) {
  Drupal.behaviors.H5PAnalyticsRrquestLog = {
    attach: function (context, settings) {
      if (context !== window.document) return;

      $('table.request-log .button.statements').on('click', function(event) {
        event.preventDefault();

        var $content = $('<div>');
        $('<textarea>', {
          rows: 10,
          style: 'width:100%',
          val: JSON.stringify($(this).data('statements'), null, 2)
        }).appendTo($content);

        var statementsDataDialog = Drupal.dialog($content, {
          dialogClass: 'statements-data-dialog',
          resizable: true,
          closeOnEscape: true,
          create: function () {
            $(this).find('textarea').select();
          },
          beforeClose: false,
          close: function (event) {
            $(event.target).remove();
          },
          width: '50%',
          title: Drupal.t('Statements data')
        });
        statementsDataDialog.showModal();
      });
    }
  };
})(jQuery, Drupal);
