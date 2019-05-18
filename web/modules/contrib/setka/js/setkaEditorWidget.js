(function ($, Drupal) {
  Drupal.behaviors.setkaEditorWidget = {
    attach: function (context, settings) {

      function status(response) {
        if (response.status >= 200 && response.status < 300) {
          return Promise.resolve(response)
        } else {
          return Promise.reject(new Error(response.statusText))
        }
      }

      function json(response) {
        return response.json()
      }

      function tryParseJson(jsonString) {
        try {
          var setkaEditorData = JSON.parse(jsonString);
          if (typeof setkaEditorData['postTheme'] !== 'undefined'
            && typeof setkaEditorData['postGrid'] !== 'undefined'
            && typeof setkaEditorData['postHtml'] !== 'undefined') {
            return setkaEditorData;
          }
        }
        catch (e) {
          return false;
        }
        return false;
      }

      $('#setka-editor').once('setka-editor-widget').each(function () {
        var meta = settings.setkaEditorMetaFile;
        var publicToken = settings.setkaEditorPublicToken;
        var entityId = settings.setkaEditorEntityId;
        var entityType = settings.setkaEditorEntityType;
        var entityUuid = settings.setkaEditorEntityUuid;
        var entityImages = settings.setkaEditorEntityImages;
        var headerTopOffset = settings.setkaEditorHeaderTopOffset;
        var initError = settings.setkaEditorInitError;
        var uploadMaxSize = settings.setkaEditorUploadMaxSize;
        var $form = $(this).parents('form');

        if (initError.postTheme === true && initError.postGrid === true) {
          fetch(meta)
            .then(status)
            .then(json)
            .then(function (data) {
              const config = data.config;
              const assets = data.assets;
              if (entityImages) {
                assets.images = entityImages;
              }
              config.public_token = publicToken;
              config.token = publicToken;
              config.headerTopOffset = headerTopOffset;
              config.uploadMaxSize = uploadMaxSize;
              var setkaEditorHtml = false;
              var setkaEditorData = tryParseJson($form.find("textarea[setka-editor='true']").val());
              if (setkaEditorData !== false) {
                config.theme = setkaEditorData['postTheme'];
                config.layout = setkaEditorData['postGrid'];
                setkaEditorHtml = setkaEditorData['postHtml'];
              }
              config.restApiUrl = '/api/setka-editor/';
              config.restApiRequestPayload = {
                entityId: entityId,
                entityType: entityType,
                entityUuid: entityUuid
              };
              SetkaEditor.start(config, assets);
              if (setkaEditorHtml !== false) {
                SetkaEditor.replaceHTML(setkaEditorHtml);
              }
            });
          $form.submit(function () {
            var setkaEditorVal = {};
            var setkaEditorTheme = SetkaEditor.getCurrentTheme();
            setkaEditorVal['postUuid'] = entityUuid;
            setkaEditorVal['postTheme'] = setkaEditorTheme.id;
            if (typeof setkaEditorTheme.kit_id !== 'undefined') {
              setkaEditorVal['postTypeKit'] = setkaEditorTheme.kit_id;
            }
            setkaEditorVal['postGrid'] = SetkaEditor.getCurrentLayout().id;
            setkaEditorVal['postHtml'] = SetkaEditor.getHTML({includeContainer: true});
            $(this).find("textarea[setka-editor='true']").val(JSON.stringify(setkaEditorVal));

          });
        }
        else {
          var setkaEditorData = tryParseJson($form.find("textarea[setka-editor='true']").val());
          if (setkaEditorData !== false) {
            if (typeof setkaEditorData['postHtml'] !== 'undefined') {
              var rawTextarea = document.createElement("textarea");
              rawTextarea.disabled = true;
              rawTextarea.style.width = '100%';
              rawTextarea.style.height = '200px';
              rawTextarea.setAttribute('class', 'form-textarea resize-vertical');
              rawTextarea.innerHTML = setkaEditorData['postHtml'];
              var buttonValue = Drupal.t('Copy raw post');
              var copyRawButton = document.createElement("input");
              copyRawButton.setAttribute('type', 'button');
              copyRawButton.setAttribute('value', buttonValue);
              copyRawButton.setAttribute('class', 'button');
              copyRawButton.addEventListener("click", function () {
                rawTextarea.disabled = false;
                rawTextarea.focus();
                rawTextarea.select();
                document.execCommand('copy');
                rawTextarea.disabled = true;
              });
              $(this).after(copyRawButton);
              $(this).after(rawTextarea);
            }
          }
        }
      });
    }
  };
})(jQuery, Drupal);
