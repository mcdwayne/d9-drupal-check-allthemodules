(function () {

  'use strict';

  CKEDITOR.plugins.add('imgur',
      {
        lang: ['zh', 'en'],
        init: function (editor) {
          var ClientId = editor.config.imgurClientId;
          if (!ClientId) {
            alert(editor.lang.imgur.clientIdMissing);
          }

          var count = 0;
          var $placeholder = jQuery("<div></div>").css({
            position: 'absolute',
            bottom: 0,
            left: 0,
            right: 0,
            backgroundColor: "rgba(20, 20, 20, .6)",
            padding: 5,
            color: "#fff"
          }).hide();

          editor.on("instanceReady", function () {
            if (editor.window.getFrame()) {
              var $w = jQuery(editor.window.getFrame().$).parent();
              $w.css({position: 'relative'});
              $placeholder.appendTo($w);
            }
          });

          editor.ui.addButton('Imgur',
              {
                label: "Imgur",
                toolbar: 'insert',
                command: 'imgur',
                icon: this.path + 'images/icon.png'
              });

          editor.addCommand('imgur', {
            exec: function () {
              var $input = jQuery('<input type="file" multiple/>');
              $input.on("change", function (e) {
                var files = e.target.files;
                jQuery.each(files, function (i, file) {
                  count++;
                  var form = new FormData();
                  form.append('image', file);
                  jQuery.ajax({
                    url: 'https://api.imgur.com/3/image',
                    headers: {Authorization: "Client-ID " + ClientId},
                    type: 'POST',
                    data: form,
                    cache: false,
                    contentType: false,
                    processData: false
                  }).always(function (jqXHR) {
                    count--;
                    $placeholder.text(count + editor.lang.imgur.uploading).toggle(count != 0);

                    if (jqXHR.status != 200) {
                      var res = jQuery.parseJSON(jqXHR.responseText);
                    }
                    else {
                      var res = jqXHR;
                    }

                    if (res.data.error) {
                      alert(editor.lang.imgur.failToUpload + res.data.error);
                    }
                    else {
                      var content = '<img src="' + res.data.link + '"/>';
                      var element = CKEDITOR.dom.element.createFromHtml(content);
                      editor.insertElement(element);
                    }

                  });
                });
                $placeholder.text(count + editor.lang.imgur.uploading).fadeIn();
              });

              $input.click();

            }
          });

        }
      });
})();

