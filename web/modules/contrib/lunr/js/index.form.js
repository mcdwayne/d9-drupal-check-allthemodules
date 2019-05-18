/**
 * @file
 * Provides behaviors for indexing Lunr searches in the UI.
 */

(function ($, Drupal) {

  // Used to determine if pagination is broken or infinite.
  var last_response;

  /**
   * Displays progress to the user.
   *
   * @param {string} text
   *   A progress message to show to the user.
   */
  function showProgress(text) {
    $('.lunr-search-index-progress').text(text);
  }

  /**
   * Displays an error to the user.
   *
   * @param {string} message
   *   An error message to show to the user.
   */
  function showError(message) {
    if (typeof Drupal.Message === 'function') {
      (new Drupal.Message()).add(message, {
        type: 'error'
      });
    }
    else {
      alert(message);
    }
  }

  /**
   * Uploads an index or document to the server.
   *
   * @param {string} content
   *   A JSON string to upload.
   * @param {string} path
   *   The upload route.
   * @param {function} callback
   *   An optional callback.
   */
  function upload(content, path, callback) {
    $.ajax({
      url: path,
      type : 'POST',
      data: content,
      success: function() {
        if (callback) {
          callback(true);
        }
      },
      error: function(request, error, errorThrown) {
        showError(Drupal.t('Error when uploading content at path @path: @errorThrown', {
          '@path': path,
          '@errorThrown': errorThrown
        }));
        if (callback) {
          callback(false);
        }
      }
    });
  }

  /**
   * Indexes a given page number.
   *
   * @param {lunr.Builder} builder
   *   A Lunr builder instance.
   * @param {string} path
   *   The path to the view.
   * @param {string} uploadPath
   *   The base path for uploading files.
   * @param {string} displayField
   *   The display field used in search results.
   * @param {boolean} usePager
   *   Whether or not the view uses paging.
   * @param {number} page
   *   The page to index.
   * @param {function} callback
   *   An optional callback.
   */
  function indexPage(builder, path, uploadPath, displayField, usePager, page, callback) {
    $.ajax({
      url: path + '?page=' + page,
      type : 'GET',
      dataType: 'json',
      success: function(data) {
        var documentData = [];
        data.forEach(function(row) {
          var documentRow = {};
          documentRow.ref = row.ref;
          documentRow[displayField] = row[displayField];
          documentData.push(documentRow);
          builder.add(row);
        });
        var json = JSON.stringify(documentData);
        showProgress(Drupal.t('Indexed @count pages.', {
          '@count': page + 1
        }));
        if (json === last_response) {
          showProgress(Drupal.t('Infinite paging detected! Aborting index.'));
          callback(false);
          return;
        }
        last_response = json;
        if (data.length) {
          upload(json, uploadPath + '/page/' + page);
        }
        if (data.length && usePager) {
          indexPage(builder, path, uploadPath, displayField, usePager, page + 1, callback);
        }
        else {
          callback(true);
        }
      },
      error: function(request, error, errorThrown) {
        showError(Drupal.t('Error when indexing page at path @path: @errorThrown', {
          '@path': path,
          '@errorThrown': errorThrown
        }));
        callback(false);
      }
    });
  }

  /**
   * Indexes the next path.
   *
   * @param {array} paths
   *   A non-empty array of paths.
   * @param {array} uploadPaths
   *   A non-empty array of upload paths.
   * @param {object} indexFields
   *   An object mapping field names to attributes.
   * @param {string} displayField
   *   The display field used in search results.
   * @param {boolean} usePager
   *   Whether or not the view uses paging.
   * @param {function} callback
   *   An optional callback.
   */
  function indexNextPath(paths, uploadPaths, indexFields, displayField, usePager, callback) {
    var builder = new lunr.Builder;
    builder.pipeline.add(
      lunr.trimmer,
      lunr.stopWordFilter,
      lunr.stemmer
    );
    builder.searchPipeline.add(
      lunr.stemmer
    );
    builder.ref('ref');
    for (var field in indexFields) {
      if (indexFields.hasOwnProperty(field)) {
        builder.field(field, indexFields[field]);
      }
    }
    var path = paths.shift();
    var uploadPath = uploadPaths.shift();
    last_response = null;
    indexPage(builder, path, uploadPath, displayField, usePager, 0, function (success) {
      if (success) {
        upload(JSON.stringify(builder.build()), uploadPath, function (success) {
          if (success) {
            if (paths.length && uploadPaths.length) {
              indexNextPath(paths, uploadPaths, indexFields, displayField, usePager, callback);
            }
            else {
              callback();
            }
          }
        });
      }
    });
  }

  /**
   * A Drupal behavior that starts indexing when the index button is clicked.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.lunrIndexForm = {
    attach: function attach(context, settings) {
      $('input.js-lunr-search-index-button:not(disabled)', context).once('lunr-search-index-form').on('click', function (e) {
        e.preventDefault();
        var $button = $(this);
        $(this).attr('disabled', 'disabled');
        var indexSettings = settings.lunr.indexSettings[$(this).attr('data-lunr-search')];
        var paths = indexSettings.paths.concat();
        var uploadPaths = indexSettings.uploadPaths.concat();
        indexNextPath(paths, uploadPaths, indexSettings.indexFields, indexSettings.displayField, indexSettings.usePager, function () {
          showProgress(Drupal.t('Finished indexing! 🎊'));
          $button.closest('form').addClass('lunr-search-indexing-complete');
          $button.removeAttr('disabled');
        });
      });
    }
  };

})(jQuery, Drupal);
