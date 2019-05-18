(function($, moxie) {
  var items = {}
    , pollingInterval = 1200
    , uploaderSettings
  ;

  uploaderSettings = {
    chunk_size: drupalSettings.ooyala.chunkSize,
    max_retries: 2,
    multi_selection: false,

    init: {
      FilesAdded: function(uploader, files) {
        // force only one file in the queue at a time
        uploader.splice(0, Math.max(0, uploader.files.length - 1));
        uploader.item.prepareUpload(files[0]);
      },
      UploadProgress: function(uploader, file) {
        uploader.item.setProgress(file.percent);
      },
      UploadComplete: function(uploader, files) {
        uploader.item.finishUpload();
      },
    },
  };

  // Patch m0xie XMLHttpRequest for the purposes of switching out chunk URLs
  moxie.xhr._XMLHttpRequest = moxie.xhr.XMLHttpRequest;

  // Use the given URL as a key into the uploadQueue, each time the connection is open on that queue,
  // use the next URL in the queue and shift it out
  moxie.xhr.XMLHttpRequest = function() {
    moxie.xhr._XMLHttpRequest.apply(this, arguments);

    this._open = this.open;

    this.open = function(method, url, async, user, password) {
      // Strip query parameters off URL to get an idBase
      var idBase = url.replace(/\?.*/, '');

      if(idBase in items && items[idBase].uploadQueue.length) {
        url = items[idBase].uploadQueue.shift();
      }

      this._open.call(this, method, url, async, user, password);
    }
  }

  moxie.xhr.XMLHttpRequest.prototype = moxie.xhr._XMLHttpRequest.prototype;

  function bindFirst($el, name, fn) {
    $el.on(name, fn);
    $el.each(function() {
      var handlers = $._data(this, 'events')[name.split('.')[0]];
      var handler = handlers.pop();
      handlers.splice(0, 0, handler);
    });
  };

  /**
   * Drive the UI component for an Ooyala item
   */
  function Item($field) {
    var self = this
      , idBase = this.idBase = $field.data('drupal-selector').replace(/-item-select$/, '')
      , instance = this.instance = $field.autocomplete('instance')
      , $value = this.$value = $('[data-drupal-selector="' + idBase + '-item"]')
      , $code = this.$code = $('[data-drupal-selector="' + idBase + '-details-item-code"]')
      , $name = this.$name = $('[data-drupal-selector="' + idBase + '-details-item-name"]')
      , $description = this.$description = $('[data-drupal-selector="' + idBase + '-details-item-description"]')
      , $image = this.$image = $('[data-drupal-selector="' + idBase + '-details-item-image"]')
      , $cancel = this.$cancel = $('[data-drupal-selector="' + idBase + '-details-item-cancel"]')
      , $upload = this.$upload = $('[data-drupal-selector="' + idBase + '-item-upload"]')
    ;

    // Implement "Remove" button
    $cancel.on('click', function(ev) {
      $value.val('').trigger('change');
      ev.preventDefault();
    });

    // Create the uploader for this field
    var uploader = this.uploader = new window.plupload.Uploader($.extend({},
      uploaderSettings,
      // Use the idBase as the key for the uploadQueue
      { url: idBase, browse_button: $upload[0], }
    ));

    uploader.init();
    uploader.idBase = idBase;
    uploader.item = this;

    // Render individual autocomplete items with name, code and thumbnail
    instance._renderItem = function(ul, item) {
      return $('<li class="ooyala-autocomplete-item">')
        .append($('<div class="ooyala-item-preview">').css('background-image', 'url(' + item.preview_image_url + ')'))
        .append($('<div class="ooyala-item-name">').text(item.name))
        .append($('<div class="ooyala-item-code">').text(item.embed_code))
        .appendTo(ul);
    };

    $field.on('autocompleteselect', function(ev, ui) {
      // Trigger the change event for the states API to change the state of the settings
      self.setItem(ui.item);

      ui.item.value = '';

      return false;
    });

    if ($value.val()) {
      self.setItem(JSON.parse($value.val()));
    }

    items[idBase] = this;
  }

  Item.prototype = {

    setItem: function(item) {
      this.item = item;

      this.$code.text(item.embed_code);
      this.$name.text(item.name);
      this.$description.text(item.description);

      // Don't attempt to save items until we have an embed code
      if(item.embed_code) {
        this.$value.val(JSON.stringify(item)).trigger('change');
      }
      else {
        this.$value.val('').trigger('change');
      }

      if(this.uploadForm) {
        this.uploadForm.$name.attr('placeholder', item.name);
      }

      if(item.preview_image_url) {
        this.$image.css('background-image', 'url(' + item.preview_image_url + ')');
      } else {
        this.$image.css('background-image', '');
      }
    },

    setQueue: function(urls) {
      this.uploadQueue = urls;
    },

    pollStatus: function() {
      var self = this;

      if(!this.item) {
        return;
      }

      $.get('/admin/ooyala/status', {
        embed_code: this.item.embed_code
      }).then(function(response) {
        if(['uploaded','processing'].indexOf(response.status) > -1) {
          setTimeout(function() { self.pollStatus(); }, pollingInterval);
        }
        else {
          self.uploader.finish();
        }
      });

    },

    prepareUpload: function(file) {
      var form = this.uploadForm
        , $el = this.$upload.parents('.ooyala-pick-video')
      ;

      if(!form) {
        this.uploadForm = form = {
          $el: $el,
          $button: $el.find('input[type="submit"]'),
          $progress: $('<div class="ooyala-upload-progress">'),
          $bar: $('<div class="ooyala-progress-bar">'),
          $filename: $('<span class="ooyala-filename">'),
          $name: $('<input type="text" class="ooyala-video-name form-text">'),
          $description: $('<input type="text" class="ooyala-video-description form-text" placeholder="' + Drupal.t('Description') + '">'),
          $submit: $('<input type="submit" class="button ooyala-upload" value="' + Drupal.t('Upload') + '">'),
          $cancel: $('<input type="submit" class="button" value="' + Drupal.t('Cancel') + '">'),
        };

        // Change upload button text
        form.$button.originalText = form.$button.val();
        form.$button.val(Drupal.t('Change File...'));
        form.$button.before(form.$progress);

        // Add progress bar elements
        form.$progress.append(form.$bar);
        form.$progress.append(form.$filename);
        form.$bar.append($('<span>'));

        // Default the filename input to the upload filename
        form.$filename.text(file.name);

        // Add fields to the form
        $el.append(form.$name);
        $el.append(form.$description);
        $el.append(form.$submit);
        $el.append(form.$cancel);

        // Bind button click
        form.$submit.click(this.startUpload.bind(this));
        form.$cancel.click(this.cancelUpload.bind(this));

        // Allow name and description to be changed during upload
        var self = this;

        form.$name.on('change', function() {
          self.item.name = form.$name.val();
        });
        form.$description.on('change', function() {
          self.item.description = form.$name.val();
        });
      }

      $el.addClass('ooyala-pick-video__upload');
      form.$bar.width(0);

      this.setUploadFile(file);
    },

    setUploadFile: function(file) {
      this.uploadFile = file;

      this.setItem({
        asset_type: 'video', // TODO: Support different types
        name: file.name,
        file_name: file.file_name,
      });

      if(this.uploadForm) {
        this.uploadForm.$el.removeClass('ooyala-pick-video__cancelled');
        this.uploadForm.$name.attr('placeholder', file.name);
        this.uploadForm.$filename.text(file.name);

        this.uploadForm.$name.focus();
      }
    },

    startUpload: function(ev) {
      var self = this
        , form = this.uploadForm
        , file = this.uploadFile
      ;

      if(!file || file.status == plupload.DONE || file.status == plupload.UPLOADING) {
        return;
      }

      this.uploadForm.$el.removeClass('ooyala-pick-video__cancelled');
      this.uploadForm.$bar.width(0);

      ev.preventDefault();

      file.file_name = file.name;
      file.name = form.$name.val() || file.file_name;

      form.$submit.attr('disabled', 'disabled');
      this.$upload.hide();

      // User has chosen a file, call the backlot API to get the proper
      // upload URLs,
      $.post('/admin/ooyala/upload', { file: JSON.stringify(file) })
        .then(function(response) {
          // Response should contain new object as well as the chunked
          // URLs for uploading
          self.uploadItem = response.item;
          self.setQueue(response.uploading_urls);

          self.uploadForm.$el.addClass('ooyala-pick-video__uploading');

          self.uploader.start();
        })
        .fail(function(response) {
          form.$submit.removeAttr('disabled');
          this.$upload.show();
        });
    },

    setProgress: function(percent) {
      this.uploadForm.$bar.width(percent + '%');
    },

    finishUpload: function() {
      var self = this
        , form = this.uploadForm
      ;

      form.$bar.width('100%');

      $.post('/admin/ooyala/upload/finish', {
        item: JSON.stringify(this.uploadItem)
      }).then(function(response) {
        form.$el.removeClass('ooyala-pick-video__upload');
        form.$el.removeClass('ooyala-pick-video__uploading');
        form.$el.removeClass('ooyala-pick-video__cancelled');
        // Clean up the upload form and replace with the
        // "selected item" form
        self.setItem(self.uploadItem);
        self.destroyUpload();
      });

    },

    destroyUpload: function () {
      if (this.uploadForm) {
        this.uploadForm.$el.removeClass('ooyala-pick-video__upload');
        this.uploadForm.$button.val(this.uploadForm.$button.originalText);

        for (var $input in this.uploadForm) {
          if ($input === '$el') {
            continue;
          }

          if ($input === '$button') {
            this.uploadForm.$button.val(this.uploadForm.$button.originalText);
            continue;
          }

          this.uploadForm[$input].remove();
        }
      }

      if (this.uploader) {
        this.uploader.stop();
      }

      delete this.uploadFile;
      delete this.uploadForm;
      delete this.uploadItem;
    },

    cancelUpload: function(ev) {
      ev && ev.preventDefault();

      // Just cancel the current upload if one is in progress
      if(this.uploadFile && this.uploadFile.status == plupload.UPLOADING) {
        this.uploader.stop();
        this.uploadForm.$submit.removeAttr('disabled');
        this.$upload.show();
        this.uploadForm.$el.addClass('ooyala-pick-video__cancelled');

        var self = this;

        return $.get('/admin/ooyala/upload/cancel', {
          embed_code: this.item.embed_code
        }).then(function(response) {
          delete self.item.embed_code;
          self.setItem(self.item);
        });
      }

      this.destroyUpload();
      this.setItem({});
    },

  };

  Drupal.behaviors.OoyalaWidget = {

    attach: function(context, settings) {
      $('.field--type-ooyala-video', context).each(function() {
        // Capture markup before AJAX postbacks:
        var $field = $(this)
          , $addMore = $field.find('.field-add-more-submit')
        ;

        if(!$addMore.hasClass('ooyala-hook-registered')) {

          bindFirst($addMore, 'mousedown', function(ev) {
            if($field.find('.ooyala-pick-video__upload').length) {
              alert(Drupal.t("Please complete any uploads before adding additional videos."));
              ev.stopImmediatePropagation();
              ev.stopPropagation();
              ev.preventDefault();
            }
          });

          $addMore.addClass('ooyala-hook-registered');
        }

        $(this).find('.ui-autocomplete-input').each(function() {
          new Item($(this));
        });
      });

    },

  };

})(jQuery, moxie);
