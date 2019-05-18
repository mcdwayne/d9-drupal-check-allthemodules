(function ($, Drupal) {
  Drupal.behaviors.pagedesigner_init_base_blocks = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-base-blocks', function (e, editor, options) {
        for (var pattern_id in options.patterns) {
          if (options.patterns.hasOwnProperty(pattern_id)) {

            // create component
            var traits = [];
            for (var field_name in options.patterns[pattern_id].fields) {
              // TODO: Add check for editable somewhere else

              if (options.patterns[pattern_id].fields.hasOwnProperty(field_name) && editor.TraitManager.getType(options.patterns[pattern_id].fields[field_name].type)) {
                var trait_tmp = {
                  type: options.patterns[pattern_id].fields[field_name].type,
                  label: options.patterns[pattern_id].fields[field_name].label,
                  name: field_name,
                  additional: options.patterns[pattern_id].fields[field_name]
                }

                if( options.patterns[pattern_id].additional && options.patterns[pattern_id].additional.relations && options.patterns[pattern_id].additional.relations[field_name] ){
                  trait_tmp.relations = options.patterns[pattern_id].additional.relations[field_name];
                }

                traits.push(trait_tmp);
              }
            }

            editor.DomComponents.addType(pattern_id, {
              extend: options.patterns[pattern_id].type,
              model: {
                defaults: {
                  name: options.patterns[pattern_id].label,
                  traits: traits,
                  stylable: false,
                  styles: options.patterns[pattern_id].styles,
                  toggable_classes: options.patterns[pattern_id].additional.classes
                },
              }
            });

            for (var className in options.patterns[pattern_id].additional.classes ) {
              editor.SelectorManager.addClass(className);
              editor.SelectorManager.get('.' + className).set('private', true).set('protected', true);
              editor.getConfig().deviceManager.devices.forEach(function( device ){
                editor.SelectorManager.addClass(className+ '-' + device.key);
                editor.SelectorManager.get('.' + className + '-' + device.key ).set('private', true).set('protected', true);
              });
            }

            // create block
            var preview = {};
            for (var field_name in options.patterns[pattern_id].fields) {
              if (options.patterns[pattern_id].fields.hasOwnProperty(field_name)) {
                preview[field_name] = options.patterns[pattern_id].fields[field_name].preview;
              }
            }

            // make this a bit less hacky :)
            if (options.patterns[pattern_id].markup) {
              var tmpl = options.patterns[pattern_id].markup
            } else {
              var tmpl = '';
            }

            var twig = Twig.twig({
              data: '{% spaceless %}' + tmpl + '{% endspaceless %}'
            });
            var markup = twig.render(preview);

            var elem = jQuery(markup).attr('data-gjs-type', pattern_id);
            elem.find('[class*=column]').attr('data-gjs-type', 'cell');

            editor.BlockManager.add(pattern_id, {
              additional: options.patterns[pattern_id].additional,
              label: options.patterns[pattern_id].label,
              pattern: {
                type: pattern_id,
                template: options.patterns[pattern_id].markup
              },

              content: elem[0].outerHTML,

              preview: preview,
              category: options.patterns[pattern_id].category,
              attributes: {
                class: options.patterns[pattern_id].icon
              }
            });
          }
        }
      });
    }
  };
})(jQuery, Drupal);
