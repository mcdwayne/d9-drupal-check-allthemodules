(function ($, Drupal) {
  Drupal.behaviors.pagedesigner_init_base_components = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-base-components', function (e, editor) {

        const defaultSettings = {
          defaults: {
            editable: false,
            draggable: false,
            droppable: false,
            badgable: false,
            stylable: true,
            highlightable: false,
            copyable: false,
            resizable: false,
            removable: false,
            hoverable: false,
            selectable: false,
          }
        };

        // lock components for editing etc.
        editor.DomComponents.getTypes().forEach(function (componentType) {
          editor.DomComponents.addType(componentType.id, {
            model: defaultSettings
          });
        });

        // basic element definition
        editor.DomComponents.addType('pd_base_element', {
          model: {

            serialize() {
              var styles = {};
              if (this.get('entityId')) {
                var selector = editor.SelectorManager.get('#pd-cp-' + this.get('entityId'));
                if (selector) {
                  editor.DeviceManager.getAll().forEach(function (device) {
                    var style = false;
                    if (device.get('widthMedia').length > 0) {
                      style = editor.CssComposer.get(selector, null, '(max-width: ' + device.get('widthMedia') + ')')
                    } else {
                      style = editor.CssComposer.get(selector);
                    }
                    if (style) {
                      styles[device.get('key')] = style.styleToString();
                    }
                  });
                }
              }

              return {
                fields: this.attributes.attributes,
                styles: styles,
                classes: this.getClasses()
              }
            },

            create() {
              if (!this.get('entityId')) {
                var component = this;
                component.beforeCreate();
                var data = {
                  pattern: component.get('type'),
                  parent: component.parent().get('entityId'),
                  container: parseInt(window.drupalSettings.path.nid)
                };
                Drupal.restconsumer.post('/pagedesigner/element/', data).done(function (response) {
                  component.handleCreateResponse(response);
                  component.afterCreate();
                });
              }

            },

            beforeCreate() {
              // do stuff before component is created
            },

            handleCreateResponse(response) {
              this.set('entityId', parseInt(response['id']));
              this.setAttributes(Object.assign({}, this.getAttributes(), { id: 'pd-cp-' + this.get('entityId') }));
            },

            afterCreate() {
              this.parent().save();
            },

            save() {
              var component = this;
              component.beforeSave();
              var data = component.serialize();
              Drupal.restconsumer.patch('/pagedesigner/element/' + component.get('entityId'), data).done(function (response) {
                component.handleSaveResponse(response);
                component.afterSave();
                component.updateView(this, { force: true });
              });
            },

            beforeSave() {
              // do stuff before component is saved
              this.set('changed', false);
            },

            handleSaveResponse() {
              // do stuff with response from saving
              this.set('previousVersion', this.serialize());

            },

            afterSave() {
              // do stuff after component is saved
            },

            load() {
              var component = this;
              component.beforeLoad();
              if (!isNaN(parseFloat(component.get('entityId'))) && isFinite(component.get('entityId'))) {
                editor.Panels.getPanel('spinner-loading').set('visible', true);
                //          if (!this.get('previousVersion')) {
                Drupal.restconsumer.get('/pagedesigner/element/' + component.get('entityId')).done(function (response) {
                  component.handleLoadResponse(response);
                  component.afterLoad();
                });
                //          }
              }

            },

            beforeLoad() {
              // do stuff before component is loaded
            },

            handleLoadResponse(response) {
              this.setAttributes(Object.assign({}, this.getAttributes(), response['fields']));
              if (response['classes']) {
                this.addClass(response['classes']);
              }
              this.set('previousVersion', this.serialize());
              this.set('changed', false);
            },

            afterLoad() {
              editor.runCommand('edit-component');
              this.get('traits').models.forEach(function (trait) {
                if (trait.view && trait.view.afterInit) {
                  trait.view.afterInit();
                }
              });

              editor.Panels.getPanel('spinner-loading').set('visible', false);
            },

            restore() {

              // needs some love
              var previousData = this.get('previousVersion');
              this.setAttributes(Object.assign({}, this.getAttributes(), previousData['fields']));
              this.removeClass(this.getClasses());

              if (previousData['classes']) {
                this.addClass(previousData['classes']);
              }

              for (var media in previousData['styles']) {
                if (media == 'large') {
                  editor.CssComposer.setIdRule('pd-cp-' + this.get('entityId'), editor.Parser.parseCss('*{' + previousData['styles'][media] + '}')[0].style)
                }
                if (media == 'medium') {
                  editor.CssComposer.setIdRule('pd-cp-' + this.get('entityId'), editor.Parser.parseCss('*{' + previousData['styles'][media] + '}')[0].style, { mediaText: "(max-width: 992px)" })
                }
                if (media == 'small') {
                  editor.CssComposer.setIdRule('pd-cp-' + this.get('entityId'), editor.Parser.parseCss('*{' + previousData['styles'][media] + '}')[0].style, { mediaText: "(max-width: 768px)" })
                }
              }
              this.set('changed', false);
            },

            delete() {
              var component = this;
              var parent = this.parent();
              component.beforeDelete();
              Drupal.restconsumer.delete('/pagedesigner/element/' + component.get('entityId')).done(function (response) {
                component.handleDeleteResponse(response);
                component.afterDelete(parent);
              });
            },

            beforeDelete() {

            },

            handleDeleteResponse(response) {

            },

            afterDelete(parent) {
              if (typeof parent != 'undefined') {
                parent.save();
              }
            },

            clone() {
              var component = this;
              var data = {
                original: component.get('entityId')
              };
              component.beforeClone();
              Drupal.restconsumer.post('/pagedesigner/clone/', data).done(function (response) {
                component.handleCloneResponse(response);
                component.afterClone();
              });
            },


            beforeClone() {

            },

            handleCloneResponse(response) {
              var markup = response.filter(cmd => cmd.command == 'pd_markup')[0].data;
              var styles = response.filter(cmd => cmd.command == 'pd_styles')[0].data;
              this.parent().components().add(markup, { at: this.index() + 1 })
              editor.Parser.parseCss(styles).forEach(function (rule) {
                if (!(Object.keys(rule.style).length === 0 && rule.style.constructor === Object)) {
                  editor.CssComposer.setIdRule(rule.selectors[0], rule.style, { mediaText: rule.mediaText })
                }
              });
            },

            afterClone() {
              this.parent().save();
            },

            renderTwig(values) {
              var tmpl = false;
              tmpl = '{% spaceless %}' + editor.BlockManager.get(this.get('type')).get('pattern').template + '{% endspaceless %}';
              if (tmpl) {
                var twig = Twig.twig({
                  data: tmpl
                });
                return twig.render(values);
              }
            },

            getRenderData(traits) {
              var renderData = {}
              for (var trait of traits) {
                renderData[trait.get('name')] = trait.renderValue;
              }
              return renderData;
            },

            updateView(component, config) {

              if ((config.force === true || this.changed.attributes) && editor.BlockManager.get(this.get('type'))) {
                this.get('classes').models.forEach(function (classname) {
                  classname.set('active', false);
                });

                var block = editor.BlockManager.get(this.get('type'));
                if (config.force === true || typeof block.attributes.additional.autorender == 'undefined' || block.attributes.additional.autorender == true) {
                  var renderData = {}
                  for (var trait of this.get('traits').models) {
                    renderData[trait.get('name')] = trait.renderValue;
                  }
                  var html = jQuery(this.renderTwig(renderData));
                  this.components(html.html());
                }
                this.set('changed', true)
              }
            },
            init() {
              this.set('entityId', parseInt(this.attributes.attributes['data-entity-id']));
              this.listenTo(this, 'change', this.updateView);
            },
            toHTML() {
              if (this.changed.attributes && this.get('template')) {
                var html = this.renderTwig(this.getRenderData(this.get('traits').models));
                return html;
              }
            },


            initToolbar(...args) {
              const { em } = this;
              const model = this;
              const ppfx = (em && em.getConfig('stylePrefix')) || '';

              if (!model.get('toolbar')) {
                var tb = [];
                if (model.collection) {
                  tb.push({
                    attributes: { class: 'fas fa-arrow-up' },
                    command: 'select-parent'
                  });
                }
                if (model.get('draggable')) {
                  tb.push({
                    attributes: {
                      class: `fas fa-arrows-alt ${ppfx}no-touch-actions`,
                      title: Drupal.t('Move component'),
                      draggable: true
                    },
                    //events: hasDnd(this.em) ? { dragstart: 'execCommand' } : '',
                    command: 'tlb-move'
                  });
                }
                if (model.get('copyable')) {
                  tb.push({
                    attributes: {
                      class: 'far fa-copy',
                      title: Drupal.t('Copy component'),
                    },
                    command: 'tlb-copy-component'
                  });
                }
                if (model.get('removable')) {
                  tb.push({
                    attributes: {
                      class: 'far fa-trash-alt',
                      title: Drupal.t('Delete component'),
                    },
                    command: 'tlb-delete-component'
                  });
                }

                tb.push({
                  attributes: {
                    class: 'fas fa-times',
                    title: Drupal.t('Close component'),
                  },
                  command: 'restore-component'
                });

                model.set('toolbar', tb);
              }

            },

            setStylableProperties() {
              var styles = [];
              var stylesFromPattern = this.get('styles');

              if (stylesFromPattern) {
                if (stylesFromPattern.groups) {
                  if (stylesFromPattern.groups.constructor === Array) {
                    stylesFromPattern.groups.forEach(function (group) {
                      if (editor.StyleManager.getProperties(group)) {
                        editor.StyleManager.getProperties(group).models.forEach(function (property) {
                          styles.push(property.get('property'));
                          if (property.get('type') == 'composite' || property.get('type') == 'stack') {
                            property.get('properties').forEach(function (subProperty) {
                              styles.push(subProperty.get('property'));
                            });
                          }
                        });
                      }
                    });
                  } else {
                    editor.StyleManager.getSectors().models.forEach(function (group) {
                      editor.StyleManager.getProperties(group.get('id')).models.forEach(function (property) {
                        styles.push(property.get('property'));
                        if (property.get('type') == 'composite' || property.get('type') == 'stack') {
                          property.get('properties').forEach(function (subProperty) {
                            styles.push(subProperty.get('property'));
                          });
                        }
                      });
                    });
                  }
                }
                if (stylesFromPattern.properties) {
                  styles.push.apply(styles, stylesFromPattern.properties);
                  stylesFromPattern.properties.forEach(function (topProperty) {
                    editor.StyleManager.getSectors().models.forEach(function (group) {
                      editor.StyleManager.getProperties(group.get('id')).models.forEach(function (property) {
                        if (topProperty == property.get('property') && (property.get('type') == 'composite' || property.get('type') == 'stack')) {
                          property.get('properties').forEach(function (subProperty) {
                            styles.push(subProperty.get('property'));
                          });
                        }
                      });
                    });
                  });
                }
                this.set('styles', false);
                this.set('stylable', styles);
              }
            }
          },
          isComponent(element) {
            if (element.nodeType != 3) {
              return {
                type: 'default'
              }
            }
          }
        });

        // define base twig component
        editor.DomComponents.addType('component', {
          extend: 'pd_base_element',
          model: {
            defaults: {
              editable: true,
              badgable: true,
              selectable: true,
              highlightable: true,
              hoverable: true,
              removable: true,
              copyable: true,
              draggable: '[data-grapes-block="content"] [data-gjs-type="cell"]',
            },
            handleCreateResponse(response) {
              this.set('entityId', parseInt(response['id']));
              this.setAttributes(Object.assign({}, this.getAttributes(), { id: 'pd-cp-' + this.get('entityId') }));
              var count = 0;
              this.find('[data-gjs-type="cell"]').forEach(function (cell) {
                cell.set('entityId', parseInt(response.fields[count].id));
                cell.setAttributes(Object.assign({}, cell.getAttributes(), { id: 'pd-cp-' + cell.get('entityId'), 'data-entity-id': cell.get('entityId') }));
                count++;
              });
              this.set('changed', false);
            },
            init() {
              this.set('entityId', parseInt(this.attributes.attributes['data-entity-id']));
              if (!this.components().length && this.get('template')) {
                var renderData = editor.BlockManager.get(this.attributes.type).attributes.preview;
                for (var key in renderData) {
                  if (!renderData[key]) {
                    renderData[key] = '<div data-gjs-type="cell"></div>'
                  }
                }
                this.components(this.renderTwig(renderData));
              }
              this.listenTo(this, 'change', this.updateView);
            },
          }
        });

        // define row
        editor.DomComponents.addType('row', {
          extend: 'component',
          model: {
            defaults: {
              draggable: '[data-grapes-block="content"], [data-grapes-block="content"] [data-gjs-type="cell"]'
            },
            updateView(component, config) {
              if ( this.changed.attributes ) {

                this.set('changed', true)
              }
            },
          }
        });

        // define cells
        editor.DomComponents.addType('cell', {
          extend: 'pd_base_element',
          model: {
            defaults: {
              droppable: true,
              badgable: true,
              hoverable: true
            },
            serialize() {
              var children = [];
              this.components().models.forEach(function (child) {
                if (child.get('entityId')) {
                  children.push(child.get('entityId'));
                }
              });
              return {
                order: children
              }
            },
            load() {
              // dont do enything
            },
            create() {
              // dont do enything
            }
          }
        });

        // define cells
        editor.DomComponents.addType('container', {
          extend: 'cell'
        });

      });
    }
  };
})(jQuery, Drupal);
