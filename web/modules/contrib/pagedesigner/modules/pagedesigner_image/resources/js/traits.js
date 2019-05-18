(function ($, Drupal) {

  $(document).on('submit', '.pd-asset-form form', function (e) {
    e.preventDefault();

    Drupal.restconsumer.submit($(this), { 'op': $(this).find('[name=op]').val() }, '', {
      success: function(){ return true; },
      complete: function(){ return true; }
    }).done(function (response) {
      $('.gjs-am-assets-header form .form-actions .form-submit').click();
    });
    $('.pd-asset-form').remove();
  })

  var AssetManager = null;
  var assetTarget = null;
  function initImages() {
    if ($('.gjs-am-assets-header .gjs-am-add-asset').length == 0) {
      $('.gjs-am-assets-header').empty().append('<div class="gjs-am-add-asset"></div>');
    }
    Drupal.ajax
      (
        {
          url: '/pagedesigner/form/asset/search/image'
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
              AssetManager.add(response);
              AssetManager.render(AssetManager.getAll().filter(
                function (asset) {
                  return asset.get('type') == 'image';
                }
              ));
            });
          });
          $('.gjs-am-assets-header form .form-actions .form-submit').click();
        }
      );

  }

  function init(editor) {
    const TraitManager = editor.TraitManager;
    AssetManager = editor.AssetManager;

    // new trait img
    TraitManager.addType('image',
      Object.assign({}, TraitManager.defaultTrait, {
        afterInit: function () {
          var trait = this;
          jQuery( this.inputEl ).on(
            'click',
            function (e) {
              AssetManager.add([]);
              AssetManager.render();
              editor.runCommand('open-assets', {
                target: trait
              });
              initImages();
            }
          );
        },
        getInputEl: function () {
          if (!this.inputEl) {
            var button = jQuery('<button type="button" style="width:100%">Choose ' + this.model.get('type') + '</button>');
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
        },
        isMultiSelect: false
      })
    );

    var styleManager = editor.StyleManager;
    var fileAsset = styleManager.getType('file');
    styleManager.addType(
      'file',
      {
        isType: function (value) {
          if (typeof value == 'object') {
            if (typeof value.type == 'string' && value.type.startsWith('image/')) {
              return {
                type: 'image',
                src: ''

              };
            }
            if (typeof value.bundle == 'string' && value.bundle == 'image') {
              value.type = value.bundle;
              return value;
            }
          }
        },
      },
    );
    // am.getType('image').upload = function (file) {
    //   var reader = new FileReader();
    //   reader.onload = function () {
    //     Drupal.restconsumer.upload(
    //       '/file/upload/media/image/field_media_image',
    //       file.name,
    //       reader.result
    //     )
    //       .done(function (result) {
    //         result = JSON.parse(result);
    //         var mediaData =
    //         {
    //           "_links": { "type": { "href": window.location.protocol + '//' + window.location.hostname + '/rest/type/media/image' } },
    //           "field_media_image": [{ "target_id": result.fid[0].value, 'alt': result.filename[0].value }],
    //           "name": [{ "value": file.name }]
    //         };
    //         Drupal.restconsumer.post('/entity/media', mediaData).done(function () {
    //           $('.gjs-am-assets-header form .form-actions .form-submit').click();
    //         });
    //       }
    //       );
    //   }
    //   reader.readAsArrayBuffer(file);
    // }

    var am = editor.AssetManager;
    am.add([]);
    am.render();
    $('.gjs-am-file-uploader').before('<div id="pd-asset-edit"></div>');
    // Overwrite image asset type to provide value to trait
    var imageAsset = am.getType('image');
    var selection = [];
    am.addType(
      'image',
      {
        view:
          imageAsset.view.extend
            (
              {
                init(o) {
                  const pfx = this.pfx;
                  this.className += ` ${pfx}asset-image`;
                  selection = [];
                  $('#pd_add_images').remove();
                  // initImages();
                },
                getPreview() {
                  const pfx = this.pfx;
                  const preview = this.model.get('preview');
                  return `
                  <div class="${pfx}preview" style="background-image: url('${preview}');"></div>
                  <div class="${pfx}preview-bg ${this.ppfx}checker-bg"></div>
                `;
                },
                getInfo() {
                  const pfx = this.pfx;
                  const model = this.model;
                  let name = model.get('name');
                  if (model.get('height') == '') {
                    return `<div class="${pfx}name">${name}</div>`;
                  }
                  let dimensions = model.get('height') + 'x' + model.get('width') + ' px';
                  return `
                      <div class="${pfx}name">${name}</div>
                      <div class="${pfx}dimensions">${dimensions}</div>
                    `;
                },
                updateTarget(trait) {
                  if (trait.setValueFromAssetManager) {
                    trait.setValueFromAssetManager({
                      id: this.model.get('id'),
                      src: this.model.get('src'),
                      alt: this.model.get('alt'),
                      preview: this.model.get('preview'),
                    });
                  } else if (assetTarget && assetTarget.onSelect) {
                    this.target = assetTarget.target;
                    assetTarget.onSelect(this.model.get('src'));
                  }
                },
                onClick() {
                  console.log('onclick');
                  if (this.collection.target && this.collection.target.isMultiSelect) {
                    const { em, model } = this;
                    const target = this.collection.target;
                    this.$el.toggleClass(this.pfx + 'highlight');
                    var id = this.model.get('id');
                    if (this.$el.hasClass(this.pfx + 'highlight')) {
                      selection.push({
                        id: id,
                        src: this.model.get('src'),
                        alt: this.model.get('alt'),
                        preview: this.model.get('preview'),
                      });
                    } else {
                      selection = _.reject(selection, function (el) { return el.id === id; });
                      if (Object.keys(selection).length == 0) {
                        $('#pd_add_images').remove();
                      }
                    }
                    if ($('.pd-asset-form').length == 0 && Object.keys(selection).length > 0) {
                      var button = $('#pd_add_images');
                      if (button.length == 0) {
                        $('.gjs-am-assets').before('<button id="pd_add_images">Add images</button>');
                        button = $('#pd_add_images');
                        button.on('click',
                          // (function (em) {
                          function (e) {
                            $('.pd-asset-form').remove();
                            em && em.get('Modal').close();
                            target.setValueFromAssetManager(selection);
                            selection = {};
                          }
                          // })(em, target)
                        );
                      }
                    }
                  } else {
                    if( !this.$el.hasClass(this.pfx + 'highlight') ){

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
                        var cancelButton = $('<input type="reset" value="Cancel edit" class="button button--primary js-form-submit form-submit">');
                        cancelButton.on('click', function () {
                          $('.pd-asset-form').remove();
                        });
                        $('.pd-asset-form .form-actions').append(cancelButton)
                        $('.pd-asset-form input[name=field_media_image_0_remove_button], .pd-asset-form .form-type-vertical-tabs, .pd-asset-form .button--danger').hide();
                      });



                    }
                  }
                },
                onDblClick() {
                  console.log('onDblClick');
                  const { em, model } = this;
                  if (this.collection.target.isMultiSelect) {
                    this.onClick();
                  } else {
                    $('.pd-asset-form').remove();
                    this.updateTarget(this.collection.target);
                    em && em.get('Modal').close();
                  }
                },
                // onSelect(asset) {
                //   modal.close();
                //   var url = (0, _underscore.isString)(asset) ? asset : asset.get('src');
                //   this.spreadUrl(url);
                // }
              }
            ),
        isType: function (value) {
          if (typeof value == 'object') {
            if (typeof value.type == 'string' && value.type.startsWith('image/')) {
              return {
                type: 'image',
                src: ''
              };
            }
            if (typeof value.bundle == 'string' && value.bundle == 'image') {
              value.type = value.bundle;
              return value;
            }
          }
        },
      },
    );
    am.getType('image').upload = function (file) {
      var reader = new FileReader();
      reader.onload = function () {
        Drupal.restconsumer.upload(
          '/file/upload/media/image/field_media_image',
          file.name,
          reader.result
        )
          .done(function (result) {
            result = JSON.parse(result);
            var mediaData =
            {
              "_links": { "type": { "href": window.location.protocol + '//' + window.location.hostname + '/rest/type/media/image' } },
              "field_media_image": [{ "target_id": result.fid[0].value, 'alt': result.filename[0].value }],
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
    editor.on('run:open-assets',
      (something, config) => {
        if (config.accept == 'image/*') {
          assetTarget = config;
          initImages();
        }
      }
    );
  }

  Drupal.behaviors.pagedesigner_image = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        init(editor);
      });
    }
  };

})(jQuery, Drupal);
