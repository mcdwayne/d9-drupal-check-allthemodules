/**
 * @file
 * Widget preview js.
 */

var Widget = Widget || {};

(function ($, Drupal, drupalSettings, document, html2canvas) {
  "use strict";

  /**
   * Check empty preview image.
   */
  Widget.checkEmptyPreview = function ($wrapper) {
    var $imgRebuild = $('.ief-widget-rebuild-img'),
      throbber = '<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>';

    // Rebuild images for editing widget.
    $imgRebuild.each(function (i, e) {
      var wid = parseInt($(this).find('.inline-entity-form-widget-wid').text()),
        $previewWrapper = $(this).find('.inline-entity-form-widget-widget_preview');

      if ($.isNumeric(wid)) {
        $previewWrapper.html(throbber);
        Widget.getPreview(wid);
      }
      $(this).removeClass('ief-widget-rebuild-img');
    });
  };

  /**
   * Get iframe with preview.
   */
  Widget.getPreview = function (wid) {
    if (typeof drupalSettings.tokens === 'undefined') {
      return false;
    }

    var iframe, d,
      path = drupalSettings.path,
      token = drupalSettings.tokens.token_preview,
      url = path.baseUrl + path.pathPrefix + 'widget-engine/' + wid + '/preview?token=' + token;

    $.get({
      url: url,
      dataType: 'html',
      success: function (html) {
        iframe = document.createElement('iframe');
        $(iframe).width('99%').height('1px');
        $('body').append('<div id="widget-iframe"></div>');
        $('#widget-iframe').css({
          'visibility': 'hidden'
        }).append(iframe);
        d = iframe.contentWindow.document;
        d.open();
        iframe.onload = function() {
          Widget.savePreview(iframe, wid);
        };
        d.write(html);
        d.close();
      }
    });
  };

  /**
   * Save preview image to widget.
   */
  Widget.savePreview = function (iframe, wid) {
    var body = $(iframe).contents().find('body');
    html2canvas(body, {
      onrendered: function (canvas) {
        $('#widget-iframe').append(canvas);
        var path = drupalSettings.path,
          token = drupalSettings.tokens.token_save,
          dataURL = canvas.toDataURL('image/png'),
          url = path.baseUrl + path.pathPrefix + 'widget-engine/' + wid + '/save-preview?token=' + token;
        $.ajax({
          type: 'POST',
          url: url,
          data: {
            imgBase64: dataURL
          }
        }).done(function (data) {
          if (typeof drupalSettings.widget_engine === 'undefined') {
            var img = data.img,
                wid = data.wid,
                $img_wrapper = $('.ief-row-widget-' + wid).find('.inline-entity-form-widget-widget_preview');

            $img_wrapper.html(img);
            $('#widget-iframe').remove();
          }
          else {
            window.location.href = path.baseUrl + path.pathPrefix + drupalSettings.widget_engine.redirect_path;
          }
        });
      },
      taintTest: false,
      useCORS: true
    });
  };

  /**
   *
   * @type {{attach: attach}}
   */
  Drupal.behaviors.widgetUpdatePreview = {
    attach: function (context) {
      $(document).delegate('.ief-widget-table', 'afterSaveWidget', function (ev, data) {
        var $self = $(this);
        $(this).once('widgetItem').each(function () {
          Widget.checkEmptyPreview($self);
        });
      });
    }
  };

  /**
   *
   * @type {{attach: attach}}
   */
  Drupal.behaviors.widgetGeneratePreview = {
    attach: function (context) {
      $('#widget-generate-preview').once('widgetPreview').each(function () {
        if (typeof drupalSettings.widget_engine === 'undefined') {
          return false;
        }
        var wid = drupalSettings.widget_engine.wid;
        Widget.getPreview(wid);
      });
    }
  };

}(jQuery, Drupal, drupalSettings, document, html2canvas));
