/**
 * @file
 * Provides UI for viewing hotspots on image.
 */

'use strict';
(function ($, Drupal) {
  Drupal.behaviors.imageHotspotView = {
    attach: function (context, settings) {
      $('.image-hotspots-wrapper:not(.init-view)', context).once('imageHotspotView').each(function () {
        var $wrapper = $(this);
        var $imageWrapper = $wrapper.find('.image-wrapper');
        var $labelsWrapper = $wrapper.find('.labels');

        var field_name = this.dataset.fieldName;
        var image_style = this.dataset.imageStyle;
        var fid = this.dataset.fid;
        var hotspots = settings.image_hotspots[field_name][fid][image_style].hotspots;

        $.each(hotspots, function (hid, hotspot) {
          var data = hotspot;
          data.hid = hid;

          Drupal.behaviors.imageHotspotView.createHotspotLabel($labelsWrapper, data);
          Drupal.behaviors.imageHotspotView.createHotspotBox($imageWrapper, data);
        });
        $wrapper.addClass('.init-view');
      });
    },

    createHotspotLabel: function ($labelsWrapper, data) {
      if (data.link != '' && data.link != null) {
        var html = '<a href="' + data.link + '" target="_blank">' + data.title + '</a>';
      }
      else {
        html = '<span>' + data.title + '</span>';
      }
      html = '<div class="label-title">' + html + '</div>';
      var title = (data.description !== '') ? data.description : data.title;
      var $label = $('<div />', {
        class: 'label',
        title: title,
        'data-hid': data.hid,
        html: html,
        on: {
          mouseenter: function (event) {
            var hid = this.dataset.hid;
            $(this).parent().parent().find('.overlay[data-hid="' + hid + '"]').fadeIn(200);
          },
          mouseleave: function (event) {
            var hid = this.dataset.hid;
            $(this).parent().parent().find('.overlay[data-hid="' + hid + '"]').fadeOut(200);
          }
        }
      });
      $label.appendTo($labelsWrapper);
      return $label;
    },

    createHotspotBox: function ($imageWrapper, data) {
      var $image = $imageWrapper.children('img');
      var scale = {
        width: 100 / $image.attr('width'),
        height: 100 / $image.attr('height')
      };
      var dimensions = {
        width: scale.width * (data.x2 - data.x),
        height: scale.height * (data.y2 - data.y)
      };
      var position = {
        top: scale.height * data.y,
        left: scale.width * data.x
      };
      var overlayAttributes = {
        class: 'overlay',
        'data-hid': data.hid
      };

      // Build hotspot box.
      var tipTipText = (data.description !== '') ? data.description : data.title;
      var $box = $('<div />', {
        class: 'hotspot-box',
        'data-hid': data.hid
      }).css({
        top: position.top + '%',
        left: position.left + '%',
        width: dimensions.width + '%',
        height: dimensions.height + '%'
      }).wrap($('<a />', {'href': data.link, 'target': '_blank'}))
        .tipTip({
          content: '<div class="image-hotspots-tooltip">' + tipTipText + '</div>',
          activation: 'hover',
          keepAlive: false,
      });
      $box.parent().appendTo($imageWrapper);

      // Build hotspot overlays.
      $('<div />', overlayAttributes).css({
        top: 0,
        left: 0,
        width: position.left + '%',
        height: 100 + '%'
      }).appendTo($imageWrapper);
      $('<div />', overlayAttributes).css({
        top: 0,
        left: position.left + '%',
        width: dimensions.width + '%',
        height: position.top + '%'
      }).appendTo($imageWrapper);
      $('<div />', overlayAttributes).css({
        top: position.top + dimensions.height + '%',
        left: position.left + '%',
        width: dimensions.width + '%',
        height: 100 - position.top - dimensions.height + '%'
      }).appendTo($imageWrapper);
      $('<div />', overlayAttributes).css({
        top: 0,
        left: position.left + dimensions.width + '%',
        width: 100 - position.left - dimensions.width + '%',
        height: 100 + '%'
      }).appendTo($imageWrapper);

      return $box;
    }
  };
})(jQuery, Drupal);
