/**
 * @file
 * Drupal behavior for the edit_ui_contextual blocks.
 */

(function (Drupal, $) {
  "use strict";

  /**
   * List of uninitialized ids with corresponding element.
   *
   * @type {Object}
   */
  var uninitializedViews = {};

  /**
   * edit_ui_contextual block Backbone objects.
   */
  Drupal.editUi.block.views.contextualVisualView = [];

  /**
   * Add edit_ui_contextual custom behavior on edit_ui blocks.
   *
   * @param {Drupal.editUi.block.BlockModel} block
   *   The edit_ui block model.
   *
   * @listens event:drupalContextualLinkAdded
   */
  $(document).on('drupalContextualLinkAdded.editUiContextual', function (event, params) {
    var block;
    var id = params.$el.parent().attr('id');
    if (id) {
      block = Drupal.editUi.block.collections.blockCollection.findWhere({html_id: id});

      if (block) {
        initView(block, params.$el);
      }
      else {
        uninitializedViews[id] = params.$el;
      }
    }
  });

  /**
   * If views has not been initialized with the previous event it should be initialized here.
   *
   * @listens event:editUiBlockInitBefore
   */
  $(document).one('editUiBlockInitBefore', function () {
    Drupal.editUi.block.collections.blockCollection.on('add', function (block) {
      var id = block.get('html_id');
      if (uninitializedViews[id]) {
        initView(block, uninitializedViews[id]);
      }
    });
  });

  /**
   * Initialize view.
   */
  var initView = function (block, $el) {
    var view = new Drupal.editUi.block.ContextualVisualView({
      model: block,
      el: $el
    });
    Drupal.editUi.block.views.contextualVisualView.push(view);
  };

})(Drupal, jQuery);
