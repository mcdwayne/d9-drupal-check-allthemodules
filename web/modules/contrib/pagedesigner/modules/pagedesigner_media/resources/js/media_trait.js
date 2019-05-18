(function ($, Drupal) {
  var AssetManager = null;
  function init(editor) {
    const TraitManager = editor.TraitManager;
    AssetManager = editor.AssetManager;

    // Add media trait base definition
    TraitManager.mediaTrait =
      Object.assign(
        {},
        TraitManager.defaultTrait,
        {
          getInputEl: function () {
            if (!this.inputEl) {
              var trait = this;
              var button = jQuery('<button type="button" style="width:100%">Choose ' + this.model.get('type') + '</button>');
              button.on('click', function (e) {
                editor.runCommand('open-assets', {
                  target: trait
                });
                if ($('.gjs-am-assets-header .gjs-am-add-asset').length == 0) {
                  $('.gjs-am-assets-header').empty().append('<div class="gjs-am-add-asset"></div>');
                }
                AssetManager.add([]);
                AssetManager.render();
                Drupal.ajax({ url: '/pagedesigner/form/asset/search/' + trait.model.get('type') })
                  .execute()
                  .done(function (data) {
                    $('.gjs-am-assets-header form').on('submit', function () {
                      e.preventDefault();
                      var data = $(this).serialize();
                      var url = Drupal.restconsumer.addFormat($(this).attr('action')) + '&' + data;
                      Drupal.restconsumer.get(url, true).done(function (response) {
                        var assets = AssetManager.getAll();
                        for (var x in assets) {
                          AssetManager.getAll().remove(assets[x]);
                        }
                        AssetManager.add(response);
                        AssetManager.render(AssetManager.getAll().filter(
                          function (asset) {
                            return asset.get('type') == trait.model.get('type')
                          }
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
        }
      );
    AssetManager.add([]);
    AssetManager.render();
    $('.gjs-am-file-uploader').before('<div id="pd-asset-edit"></div>');
    var imageAsset = AssetManager.getType('image');
    AssetManager.mediaAsset =
      {
        view:
          imageAsset.view.extend
            (
              {
                init(o) {
                  const pfx = this.pfx;
                  this.className += ` ${pfx}asset-` + this.model.get('type');
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
                    $('.pd-asset-form input[name=field_media_image_0_remove_button], .pd-asset-form .form-type-vertical-tabs, .pd-asset-form .button--danger').hide();
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
                  return `<div class="gjs-am-preview fas fa-file"></div>`;
                },
              }
            ),
        isType: function (value) {
          if (typeof value == 'object') {
            if (typeof value.bundle == 'string') {
              value.type = value.bundle;
              return value;
            }
          } else {
            return null
          }
        },
        upload: function (file) {
          var reader = new FileReader();
          var type = this.id;
          var field = this.field;
          reader.onload = function () {
            Drupal.restconsumer.upload(
              '/file/upload/media/' + type + '/' + field,
              file.name,
              reader.result
            )
              .done(function (result) {
                result = JSON.parse(result);
                var mediaData =
                {
                  "_links": { "type": { "href": window.location.protocol + '//' + window.location.hostname + '/rest/type/media/' + type } },
                  "name": [{ "value": file.name }]
                };
                mediaData[field] = [{ "target_id": result.fid[0].value, 'alt': result.filename[0].value }];
                Drupal.restconsumer.post('/entity/media', mediaData).done(function () {
                  $('.gjs-am-assets-header form .form-actions .form-submit').click();
                });
              }
              );
          }
          reader.readAsArrayBuffer(file);
        }
      };

    editor.on('run:open-assets',
      (something, config) => {
        if (config.accept == '*') {
          initAssets();
        }
      }
    );

    function initAssets() {
      if ($('.gjs-am-assets-header .gjs-am-add-asset').length == 0) {
        $('.gjs-am-assets-header').empty().append('<div class="gjs-am-add-asset"></div>');
      }
      Drupal.ajax
        (
          {
            url: '/pagedesigner/form/asset/search/media'
          }
        )
        .execute()
        .done(
          function (data) {
            $('.gjs-am-assets-header form').on('submit', function (e) {
              e.preventDefault();
              var data = $(this).serialize();
              var url = Drupal.restconsumer.addFormat($(this).attr('action')) + '&' + data;
              Drupal.restconsumer.get(url, true).done(function (response) {
                var assets = AssetManager.getAll();
                for (var x in assets) {
                  AssetManager.getAll().remove(assets[x]);
                }
                console.log(response);
                AssetManager.add(response);
                AssetManager.render(AssetManager.getAll().filter(
                  function (asset) {
                    return true;
                  }
                ));
              });
            });
            $('.gjs-am-assets-header form .form-actions .form-submit').click();
          }
        );

    }
  }

  Drupal.behaviors.pagedesigner_media = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
