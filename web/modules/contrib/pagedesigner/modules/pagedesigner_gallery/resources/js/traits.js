(function ($, Drupal) {
  var AssetManager = null;
  class GalleryTrait {

    constructor(settings) {
      this.container = null;
      this.settings = settings;
      this.galleryList = $('<select></select>');
      this.items = $('<div class="pd_gallery_items"></div>');
      this.items.sortable();
      this.items.on('sortstop',
        (function (self) {
          return function (e, element) {
            var items = self.value.items;
            self.value.items = [];
            self.items.find('.pd_gallery_item').each(function (i, item) {
              var id = $(item).attr('data-image-id');
              for (var entry of items) {
                if (entry.id == id) {
                  self.value.items.push(entry);
                  break;
                }
              }
            });
            self.trait.model.set('value', null);
            self.trait.model.set('value', self.value);
          }
        })(this)
      );
      this.trait = null;
      this.value = { id: null, name: '', items: [] };
      this.galleryname = $('<input type="text" />');
    }

    getContainer(trait) {
      if (this.container == null) {
        this.trait = trait;
        this.container = $('<div></div>');
        this.galleryList.append('<option value="null" selected="selected">Create new gallery</option>');
        for (var id in this.settings.pagedesigner_gallery.galleries) {
          this.galleryList.append('<option value="' + id + '">' + this.settings.pagedesigner_gallery.galleries[id] + '</option>');
        }
        this.galleryList.on('change',
          (function (self) {
            return function (e) {
              var galleryId = $(this).val();
              if (galleryId != 'null') {
                Drupal.restconsumer.get('/pagedesigner/element/' + galleryId).done(function (data) {
                  self.items.empty();
                  self.value = { id: galleryId, name: self.settings.pagedesigner_gallery.galleries[galleryId], items: [] };
                  for (var entry of data) {
                    self.addImage(entry);
                  }
                  self.galleryname.val(self.value.name);
                  self.trait.model.set('value', null);
                  self.trait.model.set('value', self.value);
                });
              } else {
                self.items.empty();
                self.value = { id: null, name: '', items: [] };
                self.galleryname.val(self.value.name);
                self.trait.model.set('value', null);
                self.trait.model.set('value', self.value);
              }
            }
          })(this)
        );
        this.container.append(this.galleryList);
        var gallerynameLabel = $('<label class="gjs-label">Gallery name</label>');

        this.galleryname.on('blur',
          (function (self) {
            return function () {
              self.value.name = self.galleryname.val();
              self.trait.model.set('value', null);
              self.trait.model.set('value', self.value);
            }
          })(this)
        );
        this.container.append(gallerynameLabel);
        this.container.append(this.galleryname);
        var button = $('<button type="button" style="width:100%">Add image</button>');
        button.on('click', function (e) {
          editor.runCommand('open-assets', {
            target: trait
          });
          if ($('.gjs-am-assets-header .gjs-am-add-asset').length == 0) {
            $('.gjs-am-assets-header').empty().append('<div class="gjs-am-add-asset"></div>');
          }
          Drupal.ajax({ url: '/pagedesigner/form/asset/search/image' })
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
                    asset => asset.get('type') == 'image'
                  ));
                });
              });
              $('.gjs-am-assets-header form .form-actions .form-submit').click();
            });
        })
        this.container.append(button);
        this.container.append(this.items);
      }
      return this.container.get(0);
    };

    addImage(image) {
      this.value.items.push(image);
      var item = $('<div class="pd_gallery_item" data-image-id="' + image.id + '"></div>');
      var img = $('<img src="' + image.preview + '" />');
      img.on('click', (
        function (self) {
          return function (e) {
            if (confirm('Delete the image from the gallery?')) {
              var id = $(this).parent().attr('data-image-id');
              self.value.items = _.reject(self.value.items, function (el) { return el.id === id; });
              self.trait.model.set('value', null);
              self.trait.model.set('value', self.value);
              $(this).parent().remove();
            }
          }
        })(this)
      );
      var text = $('<input type="text" placeholder="description" />');
      text.val(image.alt);
      text.on('blur', (
        function (self) {
          return function (e) {
            var id = $(this).parent().attr('data-image-id');
            for (var x in self.value.items) {
              if (self.value.items[x].id == id) {
                self.value.items[x].alt = $(this).val();
              }
            }
            self.trait.model.set('value', null);
            self.trait.model.set('value', self.value);
          }
        })(this)
      );
      item.append(img);
      item.append(text);
      this.items.append(item);
      this.trait.model.set('value', null);
      this.trait.model.set('value', this.value);
    };

    getValue() {
      return this.value;
    };

    setValue(value, onchange = false) {
      this.value.id = value.id;
      this.value.name = value.name;
      if (value.items) {
        for (var entry of value.items) {
          this.addImage(entry);
        }
      }
      this.galleryname.val(this.value.name);
      if (this.value.id && !onchange) {
        this.galleryList.val(this.value.id);
      }
    }
  };

  function trait(editor, settings) {
    const TraitManager = editor.TraitManager;
    AssetManager = editor.AssetManager;
    var galleryTrait = null;

    // new trait gallery
    TraitManager.addType('gallery',
      Object.assign({}, TraitManager.defaultTrait, {
        events: {
          change: 'onChange',  // trigger parent onChange method on keyup
        },
        getInputEl: function () {
          if (!this.inputEl) {
            galleryTrait = new GalleryTrait(settings);
            this.inputEl = galleryTrait.getContainer(this);
            var value = this.model.get('value');
            if (value && value.id && value.name) {
              galleryTrait.setValue(value);
            }
            AssetManager.getType('image').multiSelect = true;
          }
          return this.inputEl;
        },
        getRenderValue: function (value) {
          return galleryTrait.getValue().items;
        },
        setInputValue: function (value) {
          galleryTrait.setValue(value);
        },
        setValueFromAssetManager: function (value) {
          for (var x in value) {
            galleryTrait.addImage(value[x]);
          }
        },
        isMultiSelect: true
      })
    );
  }

  Drupal.behaviors.pagedesigner_gallery = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-traits', function (e, editor) {
        trait(editor, settings);
      });
    }
  };

})(jQuery, Drupal);
