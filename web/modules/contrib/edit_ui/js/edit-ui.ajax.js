/**
 * @file
 * Add AJAX commands.
 */

(function (Drupal, $) {
  "use strict";

  /**
   * Command to add new status messages.
   */
  Drupal.AjaxCommands.prototype.editUiAddMessage = function (ajax, response, status) {
    $('.js-edit-ui__system_messages_block').each(function (index, element) {
      var $messageBlockContainer = $(element);
      var $content = $(response.content);

      // Hide and insert.
      $content.hide().appendTo($messageBlockContainer);

      // Show and hide old messages.
      $content.fadeIn(response.speed, function () {
        $messageBlockContainer.children().not(this).fadeOut(response.speed);
      });
    });
  };

  /**
   * Command called after a block modal as been submitted.
   */
  Drupal.AjaxCommands.prototype.editUiAddNewBlock = function (ajax, response, status) {
    if (Drupal.editUi.block.models.newBlockModel instanceof Drupal.editUi.block.BlockModel) {
      // New block created.
      Drupal.editUi.block.models.newBlockModel.set({
        id: response.id,
        plugin_id: response.plugin_id,
        label: response.label,
        status: response.status,
        html_id: response.html_id,
        provider: response.provider
      });
      Drupal.behaviors.editUiBlock.initBlock(
        Drupal.editUi.block.models.newBlockModel,
        $(response.content)
      );
      Drupal.editUi.block.models.newBlockModel = null;
    }
    else {
      // Block updated.
      var block = Drupal.editUi.block.collections.blockCollection.findWhere({id: response.id});
      var region = Drupal.editUi.region.collections.regionCollection.getRegion(response.region);
      block.setContent(response.content);
      region.trigger('addBlock', block, block.get('block'));
    }
  };

  /**
   * edit_ui ajax functions.
   */
  Drupal.editUi = Drupal.editUi || {};
  Drupal.editUi.ajax = {

    /**
     * call native Drupal AjaxCommands.
     */
    callAjaxCommands: function (model, response, options) {
      // big_pipe way of invoking the ajax response manager.
      var ajaxObject = Drupal.ajax({
        url: '',
        base: false,
        element: false,
        progress: false
      });
      ajaxObject.success(response, 'success');
    },

    /**
     * Init AJAX for link element.
     *
     * @param Element link
     *   Collection element.
     * @param Boolean edit
     *   Add or edit.
     */
    initLinkAjax: function (link, edit) {
      var element_settings;
      var $link = $(link);
      var href = $link.attr('href');
      href = href.substr(0, href.indexOf('?'));

      element_settings = {
        element: $link.get(0),
        url: href,
        event: 'click',
        progress: {type: 'throbber'},
        dialogType: 'modal',
        dialog: {
          width: 800
        },
        submit: {
          js: true,
          module: 'edit_ui',
          currentPath: Drupal.editUi.utils.getCurrentPath(),
          edit: +!!edit // Number conversion 0 or 1
        }
      };
      $link.data('edit_ui-ajax', new Drupal.ajax(element_settings));
    }

  };

})(Drupal, jQuery);
