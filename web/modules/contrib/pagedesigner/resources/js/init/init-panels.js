(function ($, Drupal) {
  Drupal.behaviors.pagedesigner_init_base_panels = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-base-panels', function (e, editor) {

        const eConfig = editor.getConfig();

        eConfig.showDevices = 0;

        editor.Panels.getPanels().reset([{
          id: 'commands',
          buttons: [{}],
        }, {
          id: 'options',
          buttons: [{
            id: 'sw-visibility',
            command: 'sw-visibility',
            context: 'sw-visibility',
            className: 'far fa-square',
            attributes: { title: Drupal.t('View components') }
          }, {
            id: 'fullscreen',
            command: 'fullscreen',
            context: 'fullscreen',
            className: 'fas fa-arrows-alt',
            attributes: { title: Drupal.t('Fullscreen') }
          }, {
            id: 'preview',
            context: 'preview',
            command: e => {
              location.href = location.pathname
            },
            className: 'fas fa-times',
            attributes: { title: Drupal.t('Close') }
          }
            //    }, {
            //      id: 'undo',
            //      className: 'fa fa-undo',
            //      command: e => e.runCommand('core:undo'),
            //      attributes: { title: 'Undo' }
            //    }, {
            //      id: 'redo',
            //      className: 'fa fa-repeat',
            //      command: e => e.runCommand('core:redo'),
            //      attributes: { title: 'Redo' }
          ]
        }]);

        // Add devices buttons
        const panelDevices = editor.Panels.addPanel({ id: 'devices-c' });
        panelDevices.get('buttons').add([{
          id: 'set-device-desktop',
          command: 'set-device-desktop',
          className: 'fas fa-desktop',
          active: 1,
          attributes: { title: Drupal.t('Desktop') }
        }, {
          id: 'set-device-tablet',
          command: 'set-device-tablet',
          className: 'fas fa-tablet-alt',
          attributes: { title: Drupal.t('Tablet') }
        }, {
          id: 'set-device-mobile',
          command: 'set-device-mobile',
          className: 'fas fa-mobile-alt',
          attributes: { title: Drupal.t('Mobile') }
        }]);

        // background
        editor.Panels.addPanel({ id: 'views-container' });

        // spinner
        editor.Panels.addPanel({ id: 'spinner-loading' });



        // save / restore
        const panelComponentControls = editor.Panels.addPanel({
          id: 'component-controls',
          visible: false,
          buttons: [{
            id: 'restore-component',
            className: 'button',
            command: 'restore-component',
            attributes: { title: Drupal.t('Cancel') },
          }, {
            id: 'save-component',
            className: 'button save',
            command: 'save-component',
            attributes: { title: Drupal.t('Save') },
          }]
        });

        // sidebar
        const panelSidebar = editor.Panels.addPanel({
          id: 'sidebar',
          visible: false,
          buttons: [{
            id: 'sidebar-open-blocks',
            command: 'open-blocks',
            className: 'fas fa-cubes separator-bottom',
            attributes: { title: Drupal.t('Blocks') }
          }, {
            id: 'sidebar-open-traits',
            className: 'fas fa-pencil-alt',
            command: 'open-tm',
            active: false,
            attributes: { title: Drupal.t('Edit component content') }
          }, {
            id: 'sidebar-open-styles',
            className: 'fas fa-paint-brush separator-bottom',
            command: 'open-sm',
            active: false,
            attributes: { title: Drupal.t('Edit component styling') }
          }, {
            id: 'sidebar-open-layers',
            command: 'open-layers',
            className: 'fas fa-layer-group separator-bottom',
            attributes: { title: Drupal.t('Layers') }
          }, {
            id: 'sidebar-open-settings',
            command: 'open-settings',
            className: 'fas fa-sliders-h separator-bottom',
            attributes: { title: Drupal.t('Pagesettings') }
          }, {
            id: 'sidebar-open-assets',
            command: e => {
              editor.runCommand('open-assets', {
                types: [],
                accept: '*',
              });
            },
            className: 'fas far fa-folder-open',
            attributes: { title: Drupal.t('Asset manager') }
          }, {
            id: 'sidebar-open-help',
            command: 'open-help',
            className: 'fas fa-question-circle align-bottom',
            attributes: { title: Drupal.t('Help') }
          }]
        });

      });
    }
  };
})(jQuery, Drupal);
