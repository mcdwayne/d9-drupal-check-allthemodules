;(function ($, Drupal) {
  'use strict';

  var EDITOR_BUTTON_CLASS = 'image-tagger-edit-link';
  var EDITOR_BUTTON_SELECTOR = '.' + EDITOR_BUTTON_CLASS;
  var PROCESSED_CLASS = 'image-tagger-processed';
  var IMAGE_CLASS = 'image-mapper-image';
  var IMAGE_SELECTOR = '.' + IMAGE_CLASS;
  var DIALOG_CLASS = 'image-tagger-dialog';
  var DIALOG_SELECTOR = '.' + DIALOG_CLASS;
  var WRAPPER_CLASS = 'image-tagger-wrapper';
  var WRAPPER_SELECTOR = '.' + WRAPPER_CLASS;
  var DATA_FIELD_CLASS = 'image-tagger-data-field';
  var DATA_FIELD_SELECTOR = '.' + DATA_FIELD_CLASS;
  var AUTOCOMPLETE_CLASS = 'image-tagger-autocomplete-wrapper';
  var AUTOCOMPLETE_SELECTOR = '.' + AUTOCOMPLETE_CLASS;

  var currentData = {};
  var retries = 0;
  var currentPointDialog;
  var currentEntityType;

  function closePointDialogIfApplicable() {
    if (currentPointDialog) {
      currentPointDialog.dialog('close');
    }
    if (currentPointDialog) {
      currentPointDialog.dialog('destroy');
    }
  }

  function handleDelete(point) {
    if (!window.confirm(Drupal.t('Are you sure you want to delete this point?'))) {
      return;
    }
    // Find the point by its id in the DOM.
    var $point = Drupal.imageTaggerHelper.findPointDomElement(point.id);
    if (!$point.length) {
      // If this is not set it seems like a horrible error.
      console.error('Could not find the DOM element for the point. This seems like an error');
    }
    // Still safe to call this.
    $point.remove();
    // Now remove the data point as well.
    delete currentData.points[point.id];
    // And then remove the dialog, if it is open.
    closePointDialogIfApplicable();
  }

  function handleSource(request, response) {
    $.ajax({
      url: '/image-tagger/autocomplete',
      data: {
        type: currentEntityType,
        term: request.term
      },
      success: response
    });
  }

  function handleSave(point, $wrapper) {
    var $input = $wrapper.find(AUTOCOMPLETE_SELECTOR).find('input');
    // @todo: What if we add another select?
    var $select = $wrapper.find('select');
    // This will overwrite the point with the original.
    closePointDialogIfApplicable();
    // Now overwrite again.
    point.entity = $input.val();
    point.direction = $select.val();
    currentData.points[point.id] = point;
  }

  function createPointDialog(point) {
    var $wrapper = $('<div class="point-editor"></div>');
    $wrapper.append('<div class="point-editor-inner"></div>');
    var $autocomplete = $('<div class="' + AUTOCOMPLETE_CLASS + ' ui-front"><label>' + Drupal.t('Content reference') + '</label><input type="text" class="image-tagger-point-autocomplete" /></div>');
    $wrapper.append($autocomplete);
    var $input = $autocomplete.find('input');
    if (point.entity) {
      $input.attr('value', point.entity);
    }
    $input.autocomplete({
      source: handleSource
    });
    var $advanced = $('<details class="image-tagger-fieldset"></details>');
    $advanced.append($('<summary>' + Drupal.t('Advanced settings') + '</summary>'));
    var $advancedWrapper = $('<div class="details-wrapper"></div>')
    $advancedWrapper.append($('<label>' + Drupal.t('Direction of tooltip') + '</label>'));
    var $directionOptions = $('<select></select>');
    var options = {
      topLeft: Drupal.t('Top left'),
      topRight: Drupal.t('Top right'),
      bottomLeft: Drupal.t('Bottom left'),
      bottomRight: Drupal.t('Bottom right'),
    };
    for (var prop in options) {
      var selectedString = '';
      if (!point.direction && prop == 'bottomRight') {
        selectedString = 'selected ';
      }
      if (point.direction && prop == point.direction) {
        selectedString = 'selected ';
      }
      $directionOptions.append($('<option ' + selectedString + 'value="' + prop + '">' + options[prop] + '</option>'));
    }
    $advancedWrapper.append($directionOptions)
    $advanced.append($advancedWrapper)
    $wrapper.append($advanced);
    var $buttonsWrapper = $('<div class="image-tagger-buttons"></div>');
    var $saveButton = $('<button class="btn button">' + Drupal.t('Save point') + '</button>');
    $saveButton.click(handleSave.bind(null, point, $wrapper));
    $buttonsWrapper.append($saveButton);
    var $deleteButton = $('<button class="btn button">' + Drupal.t('Delete point') + '</button>');
    $buttonsWrapper.append($deleteButton);
    $deleteButton.click(handleDelete.bind(null, point));
    $wrapper.append($buttonsWrapper);
    return $wrapper;
  }

  function onPointDialogClose($dialog, originalPoint) {
    currentData.points[originalPoint.id] = originalPoint;
    currentPointDialog = null;
  }

  function handlePointClick(point, e) {
    var $dialog = createPointDialog(point);
    var originalPoint = JSON.parse(JSON.stringify(point));
    var dialog = Drupal.dialog($dialog[0], {
      title: Drupal.t('Edit point'),
      close: onPointDialogClose.bind(null, $dialog, originalPoint),
      modal: true
    });
    closePointDialogIfApplicable();
    dialog.showModal();
    currentPointDialog = $dialog;
    // Create another dialog on the top.
    e.preventDefault();
  }

  function placePoint(point, $img) {
    // Hopefully these should be populated, so we can use them to place the
    // points on the image.
    var width = currentData.width;
    var height = currentData.height;
    var bounds = $img[0].getBoundingClientRect();
    // @todo(eirik): We should adjust these things if the size has changed.
    var pointCalc = Drupal.imageTaggerCalculator.getPoint(point.x, point.y, width, height, bounds.width, bounds.height);
    var $point = Drupal.imageTaggerHelper.placePoint($img, pointCalc, point.id);
    $point.click(handlePointClick.bind($point[0], point))
  }

  function placePoints(points, $img) {
    // Wait to place them until the image has dimensions.
    var bounds = $img[0].getBoundingClientRect();
    if (!bounds.width) {
      retries++;
      if (retries > 50) {
        alert(Drupal.t('There was an error showing the point editor. Please retry the editing'));
        return;
      }
      return setTimeout(placePoints.bind(null, points, $img), 50);
    }
    for (var prop in points) {
      placePoint(points[prop], $img)
    }
  }

  function populateData($element, $img) {
    retries = 0;
    // Now find the current points.
    var $dataField = findDataField($element);
    var data = $dataField.val();
    if (data && data.length) {
      try {
        data = JSON.parse(data)
      }
      catch (err) {
        // Bad JSON. Start over.
        data = {};
      }
    }
    if (!data) {
      data = {};
    }
    currentData = data;
    // Then place all of those points.
    if (currentData.points) {
      placePoints(currentData.points, $img)
    }
  }

  function getPoint($el, e) {
    // Get the image offset.
    var bounds = $el[0].getBoundingClientRect();
    var elementX = bounds.left;
    var elementY = bounds.top;
    // Also just make sure we just brute force the height and width every time.
    currentData.height = bounds.height;
    currentData.width = bounds.width;
    // Return x and y relative to this.
    return {
      x: e.originalEvent.clientX - elementX,
      y: e.originalEvent.clientY - elementY,
    }
  }

  function savePoint(point) {
    if (!currentData.points) {
      currentData.points = {};
    }
    currentData.points[point.id] = point;
  }

  function handleImageClick(e) {
    var $el = $(this);
    var point = getPoint($el, e);
    point.id = Date.now();
    placePoint(point, $el);
    savePoint(point);
  }

  function saveData($element) {
    // Save the height and width.
    var $dataField = findDataField($element);
    $dataField.val(JSON.stringify(currentData))
  }

  function findDataField($element) {
    return $element.closest(WRAPPER_SELECTOR).find(DATA_FIELD_SELECTOR);
  }

  function handleDialogClose($element, $dialog) {
    closePointDialogIfApplicable();
    saveData($element);
    $dialog.dialog('destroy');
  }

  function handleClick(e) {
    // Nuke any content currently in the dialog.
    e.preventDefault();
    var $element = $(this);
    var $dialog = $element.parent().find(DIALOG_SELECTOR);
    $dialog.html('');
    // Now add the image as an image src in there.
    var $img = $('<img class="' + IMAGE_CLASS + '" />')
      .attr('src', $element.attr('data-url'));
    // Create a new wrapper that can be relative.
    var $wrapper = Drupal.imageTaggerHelper.getRelativeImageWrapper();
    $dialog.append($wrapper);
    $wrapper.append($img);
    // When the image is clicked, save some values.
    $img.click(handleImageClick);
    var dialogOptions = getDialogOptions($element.attr('data-height'), $element.attr('data-width'));
    currentEntityType = $element.attr('data-entity-type');
    dialogOptions.close = handleDialogClose.bind(null, $element, $dialog);
    dialogOptions.open = populateData.bind(null, $element, $img);
    var dialog = Drupal.dialog($dialog[0], dialogOptions);
    dialog.show();
  }

  function getDialogOptions(height, width) {
    // See if the image is portrait or landscape.
    var isPortrait = height > width;
    var popupHeight = window.innerHeight > height ? height : window.innerHeight - 100;
    var popupWidth = window.innerWidth > width ? width : window.innerWidth - 100;
    var opts = {
      width: (popupHeight / height) * width,
      height: popupHeight + 100,
    };
    if (!isPortrait) {
      opts.width = popupWidth;
      opts.height = ((popupWidth / width) * height) + 100;
    }
    opts.title = Drupal.t('Image tags');
    return opts;
  }

  function processLink(i, n) {
    var $element = $(n);
    if ($element.hasClass(PROCESSED_CLASS)) {
      // This means we have already attached events and so on.
      return;
    }
    $element.addClass(PROCESSED_CLASS);
    $element.click(handleClick);
  }

  Drupal.behaviors.imageTaggerEditor = {
    attach: function (context) {
      var $editorLinks = $(context).find(EDITOR_BUTTON_SELECTOR);
      $editorLinks.each(processLink);
    }
  }

})(jQuery, Drupal);
