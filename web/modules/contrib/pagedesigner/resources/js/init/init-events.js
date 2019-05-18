(function ($, Drupal) {
  Drupal.behaviors.pagedesigner_init_events = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-events', function (e, editor, options) {

        editor.on('load', () => {

          // hacks - grapes bugs??
          editor.Panels.getPanel('component-controls').set('visible', true);
          editor.Panels.getPanel('component-controls').set('visible', false);

          editor.Panels.getPanel('views-container').set('visible', true);
          editor.Panels.getPanel('views-container').set('visible', false);


          editor.Panels.getPanel('spinner-loading').set('visible', true);
          editor.Panels.getPanel('spinner-loading').set('visible', false);

          const openStyles = editor.Panels.getButton('sidebar', 'sidebar-open-styles');
          openStyles && openStyles.set('active', true);

          const closeStyles = editor.Panels.getButton('sidebar', 'sidebar-open-styles');
          closeStyles && closeStyles.set('active', false);


          // prevent margin and padding from being grouped into shorthand definition
          editor.StyleManager.removeProperty('dimension', 'margin');
          editor.StyleManager.addProperty('dimension',{
            name: 'Margin top',
            property: 'margin-top',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });

          editor.StyleManager.addProperty('dimension',{
            name: 'Margin right',
            property: 'margin-right',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });

          editor.StyleManager.addProperty('dimension',{
            name: 'Margin bottom',
            property: 'margin-bottom',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });

          editor.StyleManager.addProperty('dimension',{
            name: 'Margin left',
            property: 'margin-left',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });


          editor.StyleManager.removeProperty('dimension', 'padding');
          editor.StyleManager.addProperty('dimension',{
            name: 'Padding top',
            property: 'padding-top',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });

          editor.StyleManager.addProperty('dimension',{
            name: 'Padding right',
            property: 'padding-right',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });

          editor.StyleManager.addProperty('dimension',{
            name: 'Padding bottom',
            property: 'padding-bottom',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });

          editor.StyleManager.addProperty('dimension',{
            name: 'Padding left',
            property: 'padding-left',
            type: 'integer',
            units: ['px','%','em'],
            unit: 'px',
          });


          const panels = editor.Panels.getPanel('spinner-loading');
          var spinner_content = document.createElement('div');
          spinner_content.appendChild(jQuery('<i class="fas fa-spinner fa-spin"></i>').get(0));
          panels.set('appendContent', spinner_content).trigger('change:appendContent');


          // load styles and generate rules
          var pd_styles = jQuery('#pd_styles').html();
          if (pd_styles) {

            editor.Parser.parseCss(pd_styles).forEach(function (rule) {
              if (!(Object.keys(rule.style).length === 0 && rule.style.constructor === Object)) {
                editor.CssComposer.setIdRule(rule.selectors[0], rule.style, { mediaText: rule.mediaText })
              }
            });
            jQuery('#pd_styles').remove();
          }

          // load stylesheets into canvas iframe and attach body classes
          document.querySelectorAll("link[rel=stylesheet]").forEach(function (css_file) {
            document.querySelector("iframe.gjs-frame").contentWindow.document.head.appendChild(css_file.cloneNode(true))
          });
          Array.from(document.querySelectorAll("style")).forEach(function (style) {
            document.querySelector("iframe.gjs-frame").contentWindow.document.head.appendChild(style.cloneNode(true));
          });
          document.querySelector("iframe.gjs-frame").contentWindow.document.body.className += document.body.className;

          document.querySelector("iframe.gjs-frame").contentWindow.drupalSettings = window.drupalSettings;

          // load scripts into canvas
          document.querySelectorAll('script[src]:not([src*="grapes"])').forEach(function (script) {
            if (
              script.src.indexOf('/drupalSettingsLoader') === -1 &&
              script.src.indexOf('/pagedesigner.js') === -1 &&
              script.src.indexOf('/dialog.js') === -1
            ) {
              var newScript = document.createElement('script');
              newScript.setAttribute('src', script.src);
              document.querySelector("iframe.gjs-frame").contentWindow.document.body.appendChild(newScript)
            }
          });

          // prevent links from being opened inside editor canvas
          document.querySelector("iframe.gjs-frame").contentWindow.document.querySelectorAll("a").forEach(function (link) {
            link.target = '_parent';
          });

          editor.LayerManager.setRoot('[data-grapes-block="content"]');

          jQuery(document.querySelector("iframe.gjs-frame").contentWindow.document.querySelectorAll("[data-gjs-type='container']")).on('click', 'a', function (e) {
            e.preventDefault();
          });

          // lock all classes from patterns from being editable
          var t0 = performance.now();
          var classes = [];
          var patterns = '';
          editor.BlockManager.getAll().models.forEach(function (block) {
            patterns += block.get('pattern').template;
          })

          var matches = patterns.match(/ class=\"([A-Za-z0-9\- _]*)\"/g);
          if (matches) {
            matches.forEach(function (match) {
              classes = classes.concat(match.substr(8, match.length - 9).split(" "));
            });
          }

          classes = Array.from(new Set(classes));

          classes.forEach(function (className) {
            editor.SelectorManager.addClass(className);
            editor.SelectorManager.get('.' + className).set('private', true).set('protected', true);
          })
          var t1 = performance.now();


          jQuery(document).on('change', '[data-toggle-class]', function () {
            var className = jQuery(this).val();
            if (jQuery(this).data('responsive')) {
              className += '-' + editor.getConfig().deviceManager.devices.filter(device => device.name == editor.getDevice()).key;
            }
            editor.SelectorManager.addClass(className);
            editor.SelectorManager.get('.' + className).set('private', true).set('protected', true).set('active', false);
            if (editor.getSelected().getClasses().includes(className)) {
              editor.getSelected().removeClass(className);
            } else {
              editor.getSelected().addClass(className);
            }
          });

        });

        editor.on('component:selected', (component, sender) => {

          component.setStylableProperties();

          if (component.load && Drupal.restconsumer) {
            component.load();
          }

          // Begin Class toggle
          if (jQuery('.gjs-clm-class-toggle').length == 0) {
            jQuery('<div class="gjs-clm-class-toggle gjs-one-bg gjs-two-color"><div data-class-toggle-container></div></div>').insertBefore('.gjs-clm-tags')
          }

          jQuery('[data-class-toggle-container]').html('');

          var classesGlobal = '';
          var classesResponsive = '';
          for (var className in editor.getSelected().get('toggable_classes')) {
            var classTMP = editor.getSelected().get('toggable_classes')[className];
            if (classTMP.responsive) {
              classesResponsive += '<div class="responsive-class"><p>' + classTMP.label + ' <a class="info" title="' + classTMP.description + '"><i class="fas fa-info-circle"></i></a></p>';
              editor.getConfig().deviceManager.devices.forEach(function (device) {
                var checked = '';
                if (editor.getSelected().getClasses().includes(className + '-' + device.key)) {
                  checked = 'checked="checked" '
                }
                var deviceactive = '';
                if (editor.getDevice() == device.name) {
                  deviceactive = ' active';
                }

                if( device.name == 'Desktop'){
                  var icon = device.name.toLowerCase();
                }else if( device.name == 'Tablet'){
                  var icon = device.name.toLowerCase() + '-alt';
                }else{
                  var icon = 'mobile-alt';
                }



                classesResponsive += '<label data-device="' + device.name + '" class="inline-label' + deviceactive + '"><input type="checkbox" data-toggle-class name="toggable_classes[]" ' + checked + ' value="' + className + '-' + device.key + '"/><span title="' + device.name + '" class="gjs-pn-btn fas fa-' + icon + '"></span></label>';
              });
              classesResponsive += '</div>';
            } else {
              var strTMP = 'value="' + className + '" ';
              if (editor.getSelected().getClasses().includes(className)) {
                strTMP += 'checked="checked" '
              }
              classesGlobal += '<label title="' + classTMP.description + '"><input type="checkbox" data-toggle-class name="toggable_classes[]" ' + strTMP + '/>' + classTMP.label + ' <a class="info" title="' + classTMP.description + '"><i class="fas fa-info-circle"></i></a></label>';
            }
          }

          if (classesGlobal) {
            jQuery('[data-class-toggle-container]').append('<div class="options-holder"><p class="sidebar-subtitle">Global styling options</p>' + classesGlobal + '</div>');
          }

          if (classesResponsive) {
            jQuery('[data-class-toggle-container]').append('<div class="options-holder" data-responsive-options><p class="sidebar-subtitle">Responsive styling options</p>' + classesResponsive + '</div>');
          }

          // End Class toggle


          //    }
        });

        editor.on('component:deselected', (component, sender) => {
          if (component.get('changed')) {
            if (confirm("Do you want to save your changes?")) {
              component.save();
            } else {
              component.restore();
            }
          }
        });

        editor.on('component:update:classes', component => {
          component.set('changed', true);
        });

        editor.on('run:open-tm', component => {
          editor.Panels.getPanel('views-container').set('visible', true);
          editor.Panels.getPanel('component-controls').set('visible', true);

          var traitsHolder = $('.gjs-blocks-cs').parent();
          if( traitsHolder.find('.sidebar-title').length == 0 ){
            traitsHolder.prepend('<p class="sidebar-title">' + Drupal.t('Edit component content') + '</p>')
          }

        });

        editor.on('stop:open-tm', component => {
          editor.Panels.getPanel('views-container').set('visible', false);
          editor.Panels.getPanel('component-controls').set('visible', false);
          var traitsHolder = $('.gjs-trt-traits').parent();
          if( traitsHolder.find('.sidebar-title').length == 0 ){
            $('.gjs-traits-label').remove();
            traitsHolder.prepend('<p class="sidebar-title">' + editor.TraitManager.getConfig().labelContainer + '</p>')
          }
        });

        editor.on('run:open-sm', component => {
          editor.Panels.getPanel('views-container').set('visible', true);
          editor.Panels.getPanel('component-controls').set('visible', true);

          var stylesHolder = $('.gjs-clm-tags').parent();
          if( stylesHolder.find('.sidebar-title').length == 0 ){
            $('.gjs-traits-label').remove();
            stylesHolder.prepend('<p class="sidebar-title">' + Drupal.t('Edit component styling') + '</p>');
            $('#gjs-clm-label').addClass('sidebar-subtitle').text( Drupal.t('Custom CSS classes') );

            if( $('.gjs-sm-sectors').find('.sidebar-subtitle').length == 0 ){
              $('.gjs-sm-sectors').prepend('<div class="sectors-title-holder"><p class="sidebar-subtitle">' + Drupal.t('Custom styling') + '</p></div>' );
            }

          }
        });

        editor.on('stop:open-sm', component => {
          editor.Panels.getPanel('views-container').set('visible', false);
          editor.Panels.getPanel('component-controls').set('visible', false);
        });



        editor.on('run:open-blocks', component => {
          editor.Panels.getPanel('views-container').set('visible', true);
          var blocksHolder = $('.gjs-blocks-cs').parent();
          if( blocksHolder.find('.sidebar-title').length == 0 ){
            blocksHolder.prepend('<p class="sidebar-title">' + editor.BlockManager.getConfig().labelContainer + '</p>')
          }
        });

        editor.on('stop:open-blocks', component => {
          editor.Panels.getPanel('views-container').set('visible', false);
        });

        // prevent layer manager from selecting components when toggling children
        editor.on('run:open-layers', component => {

          var layerssHolder = $('.gjs-layer:first').parent();
          if( layerssHolder.find('.sidebar-title').length == 0 ){
            layerssHolder.prepend('<p class="sidebar-title">' + Drupal.t('Layers') + '</p>')
          }

          jQuery('.gjs-layer [data-toggle-select]').removeAttr('data-toggle-select');
          jQuery('.gjs-layer [data-name]').attr('data-toggle-select', 1);
          jQuery('.fa.fa-arrows').addClass('fa-arrows-alt');
          editor.Panels.getPanel('views-container').set('visible', true);
        });

        editor.on('stop:open-layers', component => {
          editor.Panels.getPanel('views-container').set('visible', false);
        });

        editor.on('component:add', component => {
          if (component.create) {
            component.create();
            editor.select(component);
          }
        });

        editor.on('component:update:components', (component, previousAttributes) => {
          if (component.get('entityId') && component.attributes.droppable && Object.keys(previousAttributes.components()._byId).length > 0 && component.save) {
            component.save();
          }
        });



      });
    }
  };
})(jQuery, Drupal);
