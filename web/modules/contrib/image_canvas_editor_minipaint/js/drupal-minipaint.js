;(function ($) {
  'use strict';
  var IFRAME_ID = 'minipaint-frame';
  var IFRAME_SELECTOR = '#' + IFRAME_ID;
  var FRAME_ACTIVE = 'minipaint-active';

  function open_image(image) {
    if (typeof image == 'string'){
      image = document.getElementById(image);
    }
    var Layers = document.getElementById('minipaint-frame').contentWindow.Layers;
    var name = image.src.replace(/^.*[\\\/]/, '');
    var new_layer = {
      name: name,
      type: 'image',
      data: image,
      width: image.naturalWidth || image.width,
      height: image.naturalHeight || image.height,
      width_original: image.naturalWidth || image.width,
      height_original: image.naturalHeight || image.height,
    };
    Layers.insert(new_layer);
  }

  var triedToAttach = 0;
  var imageLoaded = false;
  function attachEditor(frame) {
    var Layers = frame.contentWindow.Layers;
    if (!Layers || !imageLoaded) {
      triedToAttach++;
      if (triedToAttach > 50) {
        alert(Drupal.t('Some error occurred with the editor'));
        return;
      }
      setTimeout(attachEditor.bind(null, frame), 100);
      return;
    }
    open_image('minipaint-src');
    $('#minipaint-src').hide();
    Drupal.behaviors.imageCanvas.setImageCallback(miniPaintSave);
  }

  function miniPaintSave() {
    var $frame = $(IFRAME_SELECTOR);
    if ($frame.length === 0) {
      alert(Drupal.t('There was an error saving the image'));
      return;
    }
    var Layers = $frame[0].contentWindow.Layers;
    var tempCanvas = document.createElement("canvas");
    var tempCtx = tempCanvas.getContext("2d");
    var dim = Layers.get_dimensions();
    tempCanvas.width = dim.width;
    tempCanvas.height = dim.height;
    Layers.convert_layers_to_canvas(tempCtx);
    var data = tempCanvas.toDataURL();
    return data;
  }

  Drupal.behaviors.drupalMinipaint = {
    attach: function(context) {
      imageLoaded = false;
      var $img = $(context).find('#minipaint-src');
      if (!$img.length) {
        return;
      }
      var originalLoad = null;
      if ($img[0].onload) {
        originalLoad = $img[0].onload;
      }
      $img[0].onload = function () {
        imageLoaded = true
        if (originalLoad) {
          originalLoad();
        }
      }
      if (!$(context).find(IFRAME_SELECTOR).length) {
        return;
      }
      var $frame = $(context).find(IFRAME_SELECTOR);
      if ($frame.hasClass(FRAME_ACTIVE)) {
        return;
      }
      triedToAttach = 0;
      $frame.addClass(FRAME_ACTIVE);
      attachEditor($frame[0])
    }
  }
})(jQuery);
