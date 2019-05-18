;(function ($, Drupal) {
  'use strict';

  var DATA_ATTRIBUTE_NAME = 'data-point-id';

  function getPoint(x, y, placedImageWidth, placedImageHeight, currentWidth, currentHeight) {
    var ratio = currentWidth / placedImageWidth;
    return {
      x: x * ratio,
      y: y * ratio
    }
  }

  function placePoint($img, point, pointId) {
    var $point = $('<span class="image-tagger-point"></span>');
    $img.parent().append($point);
    $point.css({
      top: point.y,
      left: point.x,
    });
    if (pointId) {
      $point.attr(DATA_ATTRIBUTE_NAME, pointId);
    }
    return $point;
  }

  function getRelativeImageWrapper() {
    return $('<div class="image-tagger-wrapper"></div>');
  }

  function findPointDomElement(id) {
    return $('[' + DATA_ATTRIBUTE_NAME + '="' + id + '"]');
  }

  function getDataAttributeName() {
    return DATA_ATTRIBUTE_NAME;
  }

  Drupal.imageTaggerCalculator = {
    getPoint: getPoint
  };

  Drupal.imageTaggerHelper = {
    getDataAttributeName: getDataAttributeName,
    findPointDomElement: findPointDomElement,
    placePoint: placePoint,
    getRelativeImageWrapper: getRelativeImageWrapper
  }

})(jQuery, Drupal);
