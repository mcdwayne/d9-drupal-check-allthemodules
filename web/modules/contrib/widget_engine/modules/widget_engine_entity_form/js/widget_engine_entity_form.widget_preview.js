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
  Widget.checkEmptyPreview = function () {
    var $imgRebuild = $('.widget-preview-img'),
      throbber = '<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>';

    // Rebuild images for editing widget.
    $imgRebuild.each(function (i, e) {
      var wid = $(this).attr('data-id');

      $(this).html(throbber);
      Widget.getPreview(parseInt(wid));
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
            url = path.baseUrl + path.pathPrefix + 'widget-engine/' + wid + '/save-preview?token=' + token,
            $img_wrapper = $('.widget-preview-img-' + wid),
            throbber = '<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>';

        $img_wrapper.find('img').hide();
        $img_wrapper.html(throbber);
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
                $img_wrapper = $('.widget-preview-img-' + wid);

            $img_wrapper.html(img);
            $img_wrapper.find('img').show();
            $('#widget-iframe').remove();
          }
          else {
            window.location.href = path.baseUrl + path.pathPrefix + drupalSettings.widget_engine.redirect_path;
          }
        });
      },
      allowTaint: true,
      taintTest: false
    });
  };

  /**
   * Widget preview image generate.
   */
  Drupal.behaviors.widgetGeneratePreview = {
    attach: function (context) {
      var selector = $('.widgets-list-wrapper')
        .parent()
        .attr('class');

      $(document).delegate(selector, 'afterSaveWidget', function (ev, data) {
        var $widgetListWrapper = $('.widgets-list-wrapper');

        $widgetListWrapper.once('widgetItem').each(function () {
          Widget.checkEmptyPreview();
        });
      });
    }
  };

  /**
   * Registers behaviours related to entity reference field widget.
   */
  Drupal.behaviors.widgetEntityBrowserEntityReference = {
    attach: function (context) {
      $(context).find('.widgets-list-wrapper').each(function () {
        $(this).find('.entities-list').sortable({
          stop: Drupal.entityBrowserEntityReference.entitiesReordered
        });
      });
    }
  };

  /**
   * Registers behaviours related to secondary controls.
   */
  Drupal.behaviors.widgetEntityBrowserSecondaryControls = {
    attach: function (context) {
      // Add the behavior to open existing widget list.
      $(context).find('.widgets-list-wrapper .open-modal-secondary').once('open-modal-secondary')
        .on('click', function (event) {
          var $wrapper = $(this).closest('.widgets-list-wrapper');
          $(this).addClass('need-focus');
          $wrapper.find('.open-modal-main').trigger('click');
          event.preventDefault();
          return false;
        });
      // Return focus to secondary controls group.
      $(context).find('.widgets-list-wrapper .open-modal-main').once('open-modal-main-focus')
        .on('focus', function (event) {
          var $element_for_focus = $('.widgets-list-wrapper .open-modal-secondary.need-focus');
          if ($element_for_focus.length) {
            $element_for_focus = $('.entity-browser-modal-iframe').parents('div.ui-widget-content');
            event.preventDefault();
            $element_for_focus
                .trigger('focus')
                .removeClass('need-focus');
          }
        });

      // Add the behavior to create new widget..
      $(context).find('.widgets-list-wrapper .open-modal-add-secondary').once('open-modal-add-secondary')
        .on('click', function (event) {
          var $wrapper = $(this).closest('.widgets-list-wrapper');
          $(this).addClass('need-focus');
          $wrapper.find('.open-modal-add-main').trigger('click');
          event.preventDefault();
          return false;
        });
      // Return focus to secondary controls group.
      $(context).find('.widgets-list-wrapper .open-modal-add-main').once('open-modal-add-main-focus')
        .on('focus', function (event) {
          var $element_for_focus = $('.widgets-list-wrapper .open-modal-add-secondary.need-focus');
          if ($element_for_focus.length) {
            event.preventDefault();
            $element_for_focus = $('.entity-browser-modal-iframe').parents('div.ui-widget-content');
            $element_for_focus
                .trigger('focus')
                .removeClass('need-focus');
          }
        });
    }
  };

  /**
   * Widget preview image rebuild.
   */
  Drupal.AjaxCommands.prototype.widgetPreviewImageRebuild = function (ajax, response, status) {
    var wid = response.wid ? response.wid : false;

    if (wid === false) {
      return;
    }

    Widget.getPreview(wid);
  }

}(jQuery, Drupal, drupalSettings, document, html2canvas));
