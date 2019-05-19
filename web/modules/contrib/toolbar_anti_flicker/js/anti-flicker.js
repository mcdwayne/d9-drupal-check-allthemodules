(function($, Drupal) {
  Drupal.behaviors.toolbarAntiFlicker = {
    attach: function attach(context) {
      if (
        $(context)
          .find('#toolbar-administration')
          .once('toolbarAntiFlicker').length
      ) {
        // Remove placeholder
        $('#toolbar-tray-anti-flicker')
          .parent('.toolbar-tab')
          .remove();

        // Vertical fixes.
        var ChildView = Drupal.toolbar.ToolbarVisualView.extend({
          initialize: function() {
            var model = Drupal.toolbar.views.toolbarVisualView.model;
            this.listenTo(
              model,
              'change:activeTab change:orientation change:isOriented change:isTrayToggleVisible',
              function() {
                // Avoid flickering.
                $.cookie(
                  'toolbar',
                  Drupal.toolbar.models.toolbarModel.get('orientation'),
                  {
                    path: '/',
                  }
                );

                var isToolbarActiveTab = 0;
                if ($(model.get('activeTab')).length > 0) {
                  isToolbarActiveTab = 1;
                }

                $.cookie('toolbarActiveTab', isToolbarActiveTab, {
                  path: '/',
                });
              }
            );
          },
        });

        new ChildView();
      }
    },
  };
})(jQuery, Drupal);
