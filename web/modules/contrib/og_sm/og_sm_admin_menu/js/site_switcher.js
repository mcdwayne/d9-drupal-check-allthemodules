/**
 * @file
 * Extends toolbar behaviour to support the site toolbar.
 */

(function ($, Drupal) {

  /**
   * Prevents the site-switcher tab from being open on page load.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.og_sm_admin_menu = {
    attach: function attach(context) {
      if (!window.matchMedia('only screen').matches) {
        return;
      }

      $(context).find('#toolbar-administration').once('site-toolbar').each(function () {
        var activeTab = Drupal.toolbar.models.toolbarModel.get('activeTab');
        if ($(activeTab).data('toolbar-tray') === 'toolbar-item-site-switcher-tray') {
          Drupal.toolbar.models.toolbarModel.set({
            activeTab: $('.toolbar-bar .toolbar-tab:not(.home-toolbar-tab) a').get(0)
          });
        }
      });
    }
  };

}(jQuery, Drupal));
