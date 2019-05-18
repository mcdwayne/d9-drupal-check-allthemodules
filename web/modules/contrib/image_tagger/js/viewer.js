;(function ($, Drupal, drupalSettings) {
  'use strict';

  var IMAGE_CLASS = 'image-tagger-image-wrapper';
  var IMAGE_SELECTOR = '.' + IMAGE_CLASS;
  var PROCESSED_CLASS = 'image-tagger-view-processed';
  var POINT_HOVERED_CLASS = 'image-point-hovering';
  var POINT_HOVERING_BODY_CLASS = 'image-mapper-point-hovering';
  var CLOSE_POINT_EVENT = 'image-mapper-close';

  function onPointClose() {
    var $el = $(this);
    // Remove the point class.
    var pointId = $el.attr(Drupal.imageTaggerHelper.getDataAttributeName());
    var $point = Drupal.imageTaggerHelper.findPointDomElement(pointId);
    $point.removeClass(POINT_HOVERED_CLASS);
    $el.remove();
    // Remove the body class. If this is triggered from opening another point,
    // it will be re-added later.
    $('body').removeClass(POINT_HOVERING_BODY_CLASS);
  }

  function setCorrectOffset($renderedWrapper, point) {
    var newPoint = {};
    var pos = $renderedWrapper.position();
    switch (point.direction) {
      case 'topRight':
        newPoint.left = pos.left;
        newPoint.top = pos.top + $renderedWrapper.height();
        break;

      case 'topLeft':
        newPoint.left = pos.left - $renderedWrapper.width();
        newPoint.top = pos.top - $renderedWrapper.height();
        break;

      case 'bottomLeft':
        newPoint.left = pos.left - $renderedWrapper.width();
        newPoint.top = pos.top;
        break;
    }
    $renderedWrapper.css('left', newPoint.left);
    $renderedWrapper.css('top', newPoint.top);
  }

  function hoverPoint(point) {
    var $point = $(this);
    if ($point.hasClass(POINT_HOVERED_CLASS)) {
      return;
    }
    $point.addClass(POINT_HOVERED_CLASS);
    var $renderedWrapper = $('<div class="image-tagger-point-item-rendered"></div>');
    $renderedWrapper.css({
      top: $point.css('top'),
      left: $point.css('left'),
      position: 'absolute',
      background: 'white',
    });
    // See what kind of settings this point has.
    $renderedWrapper.attr(Drupal.imageTaggerHelper.getDataAttributeName(), point.id);
    // Copy pasted this from the DOM of a jQuery UI dialog.
    var $closeButton = $('<button type="button" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close"><span class="ui-button-icon ui-icon ui-icon-closethick"></span><span class="ui-button-icon-space"> </span>Close</button>');
    $closeButton.on('click', onPointClose.bind($renderedWrapper[0]));
    $renderedWrapper.append($closeButton);
    $renderedWrapper.append($(point.rendered));
    // Make sure all other points are clicked away.
    $('.image-tagger-point-item-rendered').trigger(CLOSE_POINT_EVENT);
    $renderedWrapper.on(CLOSE_POINT_EVENT, onPointClose);
    $point.after($renderedWrapper);
    if (point.direction && point.direction != 'bottomRight') {
      setCorrectOffset($renderedWrapper, point);
    }
    Drupal.attachBehaviors($renderedWrapper[0]);
    // Also add a body class, so we can "click it" away.
    $('body').addClass(POINT_HOVERING_BODY_CLASS);
  }

  function placePoint($el, point, width, height) {
    if (!point.rendered || !point.rendered.length) {
      return;
    }
    var bounds = $el[0].getBoundingClientRect();
    var pointCalc = Drupal.imageTaggerCalculator.getPoint(point.x, point.y, width, height, bounds.width, bounds.height);
    var $point = Drupal.imageTaggerHelper.placePoint($el, pointCalc, point.id);
    // @todo: Make it possible to configure?
    $point.hover(hoverPoint.bind($point[0], point));
  }

  function placePoints($el, pointData) {
    for (var prop in pointData.points.points) {
      placePoint($el, pointData.points.points[prop], pointData.points.width, pointData.points.height)
    }
  }

  function findPoints($el) {
    // First find its id.
    var id = $el.attr('data-id');
    if (!id) {
      console.error('No id found for element. This seems like an error. This is the element:');
      console.log($el);
      return;
    }
    // Now find it inside drupalSettings.
    if (!drupalSettings.imageTagger) {
      console.error('No imageTagger settings found in Drupal settings. This seems like an error');
      return;
    }
    if (!drupalSettings.imageTagger.points) {
      console.error('No points found in Drupal settings for imageTagger. This seems like an error');
      return;
    }
    if (!drupalSettings.imageTagger.points[id]) {
      console.error('The id ' + id + ' was not found in the settings where expected. This seems like an error');
      return;
    }
    return drupalSettings.imageTagger.points[id];
  }

  function processImage(i, el) {
    var $el = $(el);
    if ($el.hasClass(PROCESSED_CLASS)) {
      return;
    }
    $el.addClass(PROCESSED_CLASS);
    var points = findPoints($el);
    // Now find the image.
    var $img = $el.find('img');
    // And wrap it in a relative wrapper.
    var $wrapper = Drupal.imageTaggerHelper.getRelativeImageWrapper();
    $img.before($wrapper);
    $img.remove();
    $wrapper.append($img);
    placePoints($img, points);
  }

  function handleBodyClick() {
    var $body = $(this);
    if (!$body.hasClass(POINT_HOVERING_BODY_CLASS)) {
      return;
    }
    $('.image-tagger-point-item-rendered').trigger(CLOSE_POINT_EVENT);
  }

  function processBody($body) {
    if ($body.hasClass(PROCESSED_CLASS)) {
      return;
    }
    $body.addClass(PROCESSED_CLASS);
    $body.click(handleBodyClick);
  }

  Drupal.behaviors.imageTaggerViewer = {
    attach: function (context) {
      // Find all elements we want to place points on top of.
      $(context).find(IMAGE_SELECTOR).each(processImage);
      var $body = $(context).find('body')
      processBody($body);
    }
  }
})(jQuery, Drupal, drupalSettings);
