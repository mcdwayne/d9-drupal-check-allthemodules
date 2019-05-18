(function ($, Drupal) {
  var initialized;

  function init(editor) {
    if (!initialized) {
      initialized = true;
      const TraitManager = editor.TraitManager;
      var connector = new Restconsumer_Wrapper();
      var am = editor.AssetManager;
      // new trait img
      TraitManager.addType('video',
        Object.assign({}, TraitManager.defaultTrait, {
          events: {
            change: 'onChange',  // trigger parent onChange method on keyup
          },
          getInputEl: function () {
            if (!this.inputEl) {
              var trait = this;
              var button = jQuery('<button type="button" style="width:100%">choose video</button>');
              button.on('click', function (e) {
                editor.runCommand('open-assets', {
                  target: trait
                });
                if ($('.gjs-am-assets-header .gjs-am-add-asset').length == 0) {
                  $('.gjs-am-assets-header').empty().append('<div class="gjs-am-add-asset"></div>');
                }
                Drupal.ajax({ url: '/pagedesigner/form/asset/search/video' })
                  .execute()
                  .done(function (data) {
                    $('.gjs-am-assets-header form').on('submit', function () {
                      e.preventDefault();
                      var data = $(this).serialize();
                      var url = Drupal.restconsumer.addFormat($(this).attr('action')) + '&' + data;
                      Drupal.restconsumer.get(url, true).done(function(response) {
                        var assets = am.getAll();
                        for (var x in assets) {
                          am.getAll().remove(assets[x]);
                        }
                        am.add(response);
                        am.render(am.getAll().filter(
                          asset => asset.get('type') == 'video'
                        ));
                      });
                    });
                    $('.gjs-am-assets-header form .form-actions .form-submit').click();
                  });
              });
              this.inputEl = button.get(0);
            }
            return this.inputEl;
          },
          getRenderValue: function () {
            return this.model.get('value').src;
          },
          setInputValue: function (value) {
            this.model.set('value', value);
          },
          setValueFromAssetManager: function (value) {
            this.model.set('value', value);
          }
        })
      );
      am.add([]);
      am.render();
      $('.gjs-am-file-uploader').before('<div id="pd-asset-edit"></div>');

      // Overwrite video asset type to provide value to trait
      var videoAsset = am.getType('image');
      am.addType(
        'video',
        {
          view:
            videoAsset.view.extend
              (
                {
                  init(o) {
                    const pfx = this.pfx;
                    this.className += ` ${pfx}asset-video`;
                  },
                  getInfo() {
                    const pfx = this.pfx;
                    const model = this.model;
                    let name = model.get('name');
                    let size = Math.round(model.get('size') / 10) / 100;
                    if (size > 1000) {
                      size = Math.round(size / 1000);
                      size += " MB";
                    } else {
                      size += " KB";
                    }

                    name = name || model.getFilename();
                    return `
                      <div class="${pfx}name">${name}</div>
                      <div class="${pfx}size">${size}</div>
                    `;
                  },
                  updateTarget(trait) {
                    trait.setValueFromAssetManager({
                      id: this.model.get('id'),
                      src: this.model.get('src'),
                      alt: this.model.get('alt'),
                    });
                  },
                  onClick() {
                    this.collection.trigger('deselectAll');
                    this.$el.addClass(this.pfx + 'highlight');

                    var id = this.model.attributes.id;
                    if ($('.pd-asset-form').length == 0) {
                      $('.gjs-am-file-uploader').prepend('<div class="pd-asset-form"></div>');

                    }
                    $('.pd-asset-form').empty();
                    if ($('#pd-asset-edit').length == 0) {
                      $('.pd-asset-form').append('<div id="pd-asset-edit"></div>');
                    }
                    Drupal.ajax({ url: '/media/' + id + '/edit', wrapper: 'pd-asset-edit' }).execute().done(function () {
                      var cancelButton = $('<input type="reset" value="Close" class="button button--primary js-form-submit form-submit">');
                      cancelButton.on('click', function () {
                        $('.pd-asset-form').remove();
                      });
                      $('.pd-asset-form .form-actions').append(cancelButton)
                      $('.pd-asset-form input[name=field_media_video_0_remove_button], .pd-asset-form .form-type-vertical-tabs, .pd-asset-form .button--danger').hide();
                    });
                    $('.pd-asset-form').on('submit', 'form', function (e) {
                      e.preventDefault();
                      Drupal.restconsumer.submit($(this), { 'op': 'Save' }).done(function () {
                        $('.gjs-am-assets-header form .form-actions .form-submit').click();
                      });
                      $('.pd-asset-form').remove();
                    })
                  },
                  onDblClick() {
                    const { em, model } = this;
                    $('.pd-asset-form').remove();
                    this.updateTarget(this.collection.target);
                    em && em.get('Modal').close();
                  },
                  getPreview() {
                    return `<div class="gjs-am-preview fas fa-video"></div>`;
                  },
                }
              ),

          isType: function (value) {
            if (typeof value == 'object') {
              if (typeof value.type == 'string' && value.type.startsWith('video/')) {
                return {
                  type: 'video',
                  src: ''
                };
              }
              if (typeof value.bundle == 'string' && value.bundle == 'video') {
                value.type = value.bundle;
                return value;
              }
            }
          },
        },
      );
      am.getType('video').upload = function (file) {
        var reader = new FileReader();
        reader.onload = function () {
          Drupal.restconsumer.upload(
            '/file/upload/media/video/field_media_file',
            file.name,
            reader.result
          )
            .done(function (result) {
              result = JSON.parse(result);
              var mediaData =
              {
                "_links": { "type": { "href": window.location.protocol + '//' + window.location.hostname + '/rest/type/media/video' } },
                "field_media_file": [{ "target_id": result.fid[0].value, 'alt': result.filename[0].value }],
                "name": [{ "value": file.name }]
              };
              Drupal.restconsumer.post('/entity/media', mediaData).done(function () {
                $('.gjs-am-assets-header form .form-actions .form-submit').click();
              });
            }
            );
        }
        reader.readAsArrayBuffer(file);
      }
    }
  }

  Drupal.behaviors.pagedesigner_video = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
