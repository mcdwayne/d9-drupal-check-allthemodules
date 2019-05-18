(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.entityToolbar = {
    attach: function attach(context) {
      $(context).find('.entity-toolbar-menu').once('toolbar').each(function () {
        var toolbarId = $(this).data('toolbar-id');

        var $placeholder = $('#entity-toolbar-placeholder-' + toolbarId + ' .toolbar-menu .menu-item a');

        $placeholder.append(Drupal.theme.ajaxProgressThrobber('Loading'));

        if (typeof toolbarId == 'undefined') {
          return;
        }

        var tab = $(this).parent().parent().parent();

        $(tab).attr('id', 'toolbar-tab-' + toolbarId);

        var endpoint = Drupal.url('admin/entity_toolbar/' + toolbarId);
        Drupal.ajax({
          url: endpoint,
          error: function error() {
            $(tab).remove();
          }
        }).execute();
      });
    }
  };

  Drupal.AjaxCommands.prototype.EntityToolbarLoadedCommand = function (ajax, response) {
    Drupal.attachBehaviors($(response.tab)[0]);
  };

})(jQuery, Drupal);
