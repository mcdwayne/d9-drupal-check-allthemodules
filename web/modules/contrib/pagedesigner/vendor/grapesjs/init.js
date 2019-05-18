(function ($) {

  window.initPDGrapes = function (patterns) {
    $('[data-gjs-type="container"] *').each(function () {
      $(this).html($(this).html().trim());
    });

    $('.dialog-off-canvas-main-canvas').attr('id', 'gjs');

    $('[data-entity-id]').each(function () {
      $(this).attr('id', 'pd-cp-' + $(this).attr('data-entity-id'));
    });

    if (!window.grapes_plugins) {
      window.grapes_plugins = [];
    }

    if (!window.grapes_plugin_options) {
      window.grapes_plugin_options = {};
    }

    grapes_plugins.push('grapesjs-pd-base');
    // pagedesigner-drpl.docker-dev.iqual.ch/pagedesigner/pattern

    grapes_plugin_options['grapesjs-pd-base'] = {
      patterns: patterns
    };

    window.classes = {};

    // launch grapes with necessary options
    var editor = grapesjs.init({

      height: '100vh',
      showOffsets: 1,
      noticeOnUnload: 0,
      //      autorender: false,
      multipleSelection: false,
      avoidInlineStyle: true,
      storageManager: {
        autoload: 0,
        //        autosave: 0,
        //        storeStyles: 0,
        //        storeHtml: 0,
      },
      container: '#gjs',
      fromElement: true,

      domComponents: {
        wrapper: {
          components: [],
          badgable: false,
          copyable: false,
          droppable: false,
          highlightable: false,
          hoverable: false,
          selectable: false,
          editable: false,
          propagate: ['editable', 'dropable'],
        }
      },

      deviceManager: {
        devices: [
          {
            name: 'Desktop',
            key: 'large',
            width: ''
          },
          {
            name: 'Tablet',
            width: '769px',
            key: 'medium',
            widthMedia: '992px'
          },
          {
            name: 'Mobile portrait',
            key: 'small',
            width: '320px',
            widthMedia: '768px'
          }
        ]
      },

      traitManager: {
        labelContainer: Drupal.t('Edit component content'),
        textNoElement: Drupal.t('Select an element before editing.')
      },

      blockManager: {
        labelContainer: Drupal.t('Blocks'),
      },

      assetManager: {
        upload: 'https://localhost/assets/upload',
        uploadFile: (e) => {
          var files = e.dataTransfer ? e.dataTransfer.files : e.target.files;
          var types = editor.AssetManager.getTypes();
          for (var y = 0; y < files.length; y++) {
            for (var x = 0; x < types.length; x++) {
              var detection = types[x].isType(files[y]);
              if (typeof detection == 'object' && detection.type == types[x].id) {
                types[x].upload(files[y]);
                break;
              }
            }
          }
        }
      },

      plugins: grapes_plugins,
      pluginsOpts: grapes_plugin_options
    });
    window.editor = editor;
    $(document).trigger('pagedesigner-after-init', [editor]);

    jQuery(".gjs-toolbar").watch({
      // specify CSS styles or attribute names to monitor
      properties: "top,left,display",

      // callback function when a change is detected
      callback: function(data, i) {
        if( editor.getSelected() ){
          jQuery(this).css('margin-left', (jQuery(this).width() - editor.getSelected().view.el.clientWidth) + 'px')
        }
      }
    });

    setTimeout(function () {
      editor.runCommand('sw-visibility');
      const showBorders = editor.Panels.getButton('options', 'sw-visibility');
      showBorders && showBorders.set('active', true);
    }, 1000);
  };
}(jQuery));
