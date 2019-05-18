/**
 * @file
 * Declare Imager module Viewer dialog - Drupal.imager.popups.viewerC.
 *
 * @TODO - Break this into smaller modules - viewer, editor, ??
 */

/**
 * Wrap file in JQuery();.
 *
 * @param $
 */
(function ($) {
  'use strict';

  Drupal.imager.viewer = {};

  var widthMargin;
  var heightMargin;

  if (localStorage.imagerBoundsEnable === null) {
    localStorage.imagerBoundsEnable = 'TRUE';
  }

  /**
   * Define the viewerC dialog class.
   *
   * @param {type} spec - settings to override defaults
   *
   * @return {viewerC} popup
   */
  Drupal.imager.popups.viewerC = function viewerC(spec) {
    var Popups = Drupal.imager.popups;
    var Core = Drupal.imager.core;

    // Declare default specs and merge in additional specs.
    var dspec = $.extend({
      name: 'Viewer',
      zIndex: 1010,
      dialogClass: 'imager-viewer-dialog imager-noclose',
      cssId: 'imager-viewer',
      resizable: true,
      draggable: false,
      position: {
        left: 0,
        top: 0
      }
    }, spec);

    var popup = Popups.baseC(dspec); // Initialize viewerC from baseC.
    var image;                       // Current image of imageC.
    var $viewerWrapper;
    var $canvasWrapper;
//  var oimg = document.createElement('IMG'); // Original image
    var img = document.createElement('IMG');  // Image being edited.
    var $imgOverlay;                 // Image that overlays the canvas.
    var clearOverlay = false;        // Clear the overlay with transparent image.
    var $canvas;              // Primary canvas
    var $canvas2;             // Canvas used in
    var ctx;                  // Context for the $canvas
    var ctx2;                 // Context for the $canvas2
    var ias;                  // Image area select object.
    var cw;                   // Canvas width.
    var ch;                   // Canvas height.
    var dw;                   // Viewer dialog width.
    var dh;                   // Viewer dialog height.
    var mw;                   // Maximum canvas width.
    var mh;                   // Maximum canvas height.
    var doInit;               // After loading should we reinitialize.
    var initScale = 1;        // Initial Zoom/scaling factor.
    var scaleFactor = 1.02;   // How much to scale image per click.
    var cscale;               // Current scaling factor.
    var rotation = 0;         // Current rotation.
    var editMode = 'init';    // init, view, crop, brightness, hsl,
                              // download, email, save, clipboard.
    var lastup = new Date();  // Time of last mouse up event.
    var moveMode = 'none';    // none, dragging, zoom.
    var elapsed = 0;          // Time between mouse up events.
    var distance = 0;         // Distance image dragged since mouse down.

    var slideshowInterval = 5;

    // Points of interest
    // Location of last mouse down event.
    var pt_down = Core.pointC({name: 'down'});           // Last mouse down event.
    var pt_last = Core.pointC({name: 'last'});           // Last mouse move event.
    var pt_now = Core.pointC({name: 'now'});             // Mouse currently.
    var pt_crop_ul = Core.pointC({name: 'crop-ul'});     // Upper left crop point.
    var pt_crop_lr = Core.pointC({name: 'crop-lr'});     // Lower right crop point.
    var pt_canvas_ul = Core.pointC({name: 'canvas-ul'}); // Upper left corner of canvas.
    var pt_canvas_lr = Core.pointC({name: 'canvas-lr'}); // Lower right corner of canvas.
    var pt_image_ul = Core.pointC({name: 'image-ul'}); // Upper left corner of canvas.
    var pt_image_lr = Core.pointC({name: 'image-lr'}); // Lower right corner of canvas.

    // Make these points accessible globally.
    Drupal.imager.viewer.pt_canvas_lr = pt_canvas_lr;
    Drupal.imager.viewer.pt_canvas_ul = pt_canvas_ul;

    if (localStorage.imagerFullScreen === 'undefined') {
      localStorage.imagerFullScreen = 'FALSE';
    }

    /**
     * Get current status of imager Viewer
     *
     * @return {Object}
     *   Current popup size, cscale and rotation.
     */
    Drupal.imager.viewer.getStatus = function getStatus() {
      return {
        cw: cw,
        ch: ch,
        cscale: cscale,
        rotation: rotation
      };
    };

    /**
     * Change the current image to a new image.
     *
     * The line -- img.src = image.src -- starts asynchronous loading of an image.
     * Upon completion of loading the 'load' Event Listener is fired.
     *
     * @param {Object} newImage
     *   New image to display of imageC.
     */
    var changeImage = function changeImage(newImage) {
      Popups.busy.show();
      // Set transform matrix to identity matrix.
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      // Clear the canvas.
      ctx.clearRect(0, 0, cw, ch);
      image = newImage;
      doInit = true;
      // Start loading image - 'load' event listener (immediately below) is fired on completion.
//    img.width = null;
//    img.height = null;
      img.src = image.src;
      doInit = true;
    };

    /**
     * Upon completion of image loading, initialize and draw the image to canvas.
     */
    img.addEventListener('load', function () {
      if (doInit) {
        initializeImage();
        doInit = false;
      }
      redraw();
      showInfo();
      Popups.busy.hide();
    }, false);

    /**
     * Calculate canvas and image dimensions, reset variables, initialize transforms
     *
     * @TODO - When calculating the canvas dimensions the borders and padding must be
     * accounted for.  Currently these are constants are made to look good with my theme.
     *
     * @param integer width
     *   Maximum possible canvas width.
     * @param height
     *   Maximum possible canvas height.
     */
    var initializeImage = function initializeImage(width, height, iwidth, iheight) {
      var hscale;
      var vscale;
      rotation = 0;
      if (iwidth) {
        image.iw = iwidth;
        image.ih = iheight;
      } else {
        image.iw = img.width;
        image.ih = img.height;

      }
      console.log('initializeImage  ' + img.width + 'x' + img.height);
      if (localStorage.imagerFullScreen === 'TRUE') {
        // Maximum canvas width and height.
        dw = $(window).width();
        dh = $(window).height();
        mw = $(window).width() - widthMargin;
        mh = $(window).height() - heightMargin;
        cw = mw;
        ch = mh;
        hscale = ch / image.ih;
        vscale = cw / image.iw;
        cscale = (hscale < vscale) ? hscale : vscale;
      }
      else {
        if (width) {
          dw = width;
          dh = height;
        } else {
          dw = $(window).width();
          dh = $(window).height();
        }
        mw = dw - widthMargin;
        mh = dh - heightMargin;
        cw = mw;
        ch = mh;
        hscale = ch / image.ih;
        vscale = cw / image.iw;
        cscale = (hscale < vscale) ? hscale : vscale;
      }
      initScale = cscale; // Save scaling where image fits canvas.
      $canvas.attr({width: cw, height: ch});
      $imgOverlay.width(cw).height(ch);
      $canvasWrapper.width(cw).height(ch);

      if (editMode !== 'slideshow') {
        setEditMode('view');
      }

      // Set transform matrix to identity matrix.
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      // Clear the canvas.

      // Center the image.
      if (hscale > vscale) {       // Center vertically
        ctx.translate(0, ((ch * image.iw / cw - image.ih) / 2));
      }
      else if (hscale < vscale) {  // Center horizontally
        ctx.translate(((cw * image.ih / ch - image.iw) / 2), 0);
      }

      pt_down.setPt(cw / 2, ch / 2, ctx);
      // Initialize last mouse down event to center.
      pt_now.setPt(0, 0, ctx);
      // Initialize last mouse event to upper left.
      scale(cscale, false);
      // Set initial scaling to fit canvas.
    };

    /**
     * Clear canvas and draw image to the canvas
     */
    var redraw = Drupal.imager.viewer.redraw = function redraw() {
      pt_canvas_ul.setPt(0, 0, ctx);
      pt_canvas_lr.setPt($canvas[0].width, $canvas[0].height, ctx);
      // Calculate the scale based on actual.
      if ((rotation === 0) || (rotation === 180)) {
        cscale = cw / Math.abs(pt_canvas_lr.getTxPt().x - pt_canvas_ul.getTxPt().x);
      }
      else {
        cscale = cw / Math.abs(pt_canvas_lr.getTxPt().y - pt_canvas_ul.getTxPt().y);
      }
      ctx.clearRect(
        pt_canvas_ul.getTxPt().x,
        pt_canvas_ul.getTxPt().y,
        pt_canvas_lr.getTxPt().x - pt_canvas_ul.getTxPt().x,
        pt_canvas_lr.getTxPt().y - pt_canvas_ul.getTxPt().y);

      ctx.drawImage(img, 0, 0);
      updateStatus();
    };

    /**
     * Given the screen width and height, calculate the canvas width and height.
     *
     * @param {number} sw - Screen width
     * @param {number} sh - Screen height
     */
    var calcCanvasDims = function calcCanvasDims(sw, sh) {
      ch = sh * mw / sw;
      if (ch < mh) {
        // Width determines max size.
        cw = mw;
      }
      else {
        // Height determines max size.
        ch = mh;
        cw = sw * mh / sh;
      }
      cw = parseInt(cw);
      ch = parseInt(ch);
    };

    /**
     * If enabled show the Information popup, if already showing update the contents
     */
    var showInfo = function showInfo() {
      if (localStorage.imagerShowInfo === 'TRUE') {
        $('#view-info').addClass('checked');
        if (Popups.info.dialogIsOpen()) {
          Popups.info.dialogUpdate();
        }
        else {
          Popups.info.dialogOpen();
        }
      }
    };

    /**
     * Print image to web server.
     */
    var printImage = function printImage() {
      Popups.busy.hide();
      var img = Drupal.imager.core.getImage('image-cropped', false);
      Drupal.imager.core.ajaxProcess(
        $('#file-print'),
        Drupal.imager.settings.actions.printImage.url,
        {
          action: 'file-print',
          uri: image.src,
          printer: localStorage.imagerPrinter,
          imgBase64: img
        }, function (response) {
          var path = response.data.uri;
          window.open(path, '_blank');
        }
      );
      setEditMode('view');
    };

    /**
     * Copy the current image into a second offscreen canvas.
     */
    Drupal.imager.viewer.setInitialImage = function setInitialImage() {
      $canvas2.attr({
        width: image.iw,
        height: image.ih
      });
      ctx2.setTransform(1, 0, 0, 1, 0, 0);
      // Set transform matrix to identity matrix.
      // The original img is kept unrotated.
      ctx2.drawImage($canvas[0], 0, 0);
      // ctx2.drawImage(img, 0, 0); .
    };

    /**
     * Return the current image
     * @returns {*}
     */
    Drupal.imager.viewer.getImage = function getImage() {
      return image;
    };

    /**
     * Apply the HSL or Contrast/Brightness filter.
     *
     * @param filterFunction
     */
    Drupal.imager.viewer.applyFilter = function applyFilter(filterFunction) {
      Popups.busy.show();
      var $canvas3 = $(document.createElement('CANVAS'));

      $canvas2.attr({width: img.width, height: img.height});
      ctx2.setTransform(1, 0, 0, 1, 0, 0);
      ctx2.drawImage(img, 0, 0);

      $canvas3.attr({width: img.width, height: img.height});
      // Colorize while transferring from canvas2 to canvas3.
      filterFunction($canvas2, $canvas3);
      img.src = $canvas3[0].toDataURL();

      redraw();
      setEditMode('view');
      Popups.busy.hide();
    };

    /**
     * Rotate the image.
     *
     * It's too complicated right now. Using the transform matrix makes it
     * difficult to calculate later.
     *
     * @param {number} deg - Number of degrees to rotate - 0, 90, 180, 270
     */
    var rotate = function rotate(deg) {
      var width = img.width;
      var height = img.height;

      $canvas2.attr({width: height, height: width});
      $canvas2.width(height).height(width);
      ctx2.setTransform(1,0,0,1,0,0);
      ctx2.translate(height/2, width/2);
      ctx2.rotate(Core.angleInRadians(deg));
      ctx2.translate(-width/2, -height/2);
      ctx2.drawImage(img, 0, 0);

      ctx.setTransform(1,0,0,1,0,0);
      img.src = $canvas2[0].toDataURL();

      initializeImage(null, null, height, width);
      redraw();
      showPopups();

    };

    /**
     * Crop image.
     */
    var crop = function crop() {
      Popups.busy.show();
      var selection = ias.getSelection();
      pt_crop_ul.setPt(selection.x1, selection.y1, ctx);
      pt_crop_lr.setPt(selection.x2, selection.y2, ctx);
      var niw = parseInt(pt_crop_lr.getTxPt().x - pt_crop_ul.getTxPt().x);
      var nih = parseInt(pt_crop_lr.getTxPt().y - pt_crop_ul.getTxPt().y);

      $canvas.attr({width: niw, height: nih});
      ctx.clearRect(0, 0, cw, ch);
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.drawImage(img,
        pt_crop_ul.getTxPt().x,
        pt_crop_ul.getTxPt().y,
        niw, nih, 0, 0, niw, nih);
      // Copy cropped area from img into canvas.
      img.src = $canvas[0].toDataURL();
      initializeImage(null, null, niw, nih);
      redraw();
      // Copy canvas image back into img.
//    calcCanvasDims(niw, nih);
      // Calculate maximum size of canvas.
//    $canvas.attr({width: cw, height: ch});
//    $imgOverlay.width(cw).height(ch);
//    $canvasWrapper.width(cw).height(ch);
//    $('#imager-viewer').width(cw + widthMargin).height(cw + heightMargin);
      // Scale image to fit canvas.
//    ctx.scale(cw / niw, ch / nih);
      updateStatusGeometries();
      Popups.busy.hide();
    };

    /**
     * Zoom image
     *
     * @param {number} clicks - how much zoom?
     */
    var zoom = function zoom(clicks) {
      scale(Math.pow(scaleFactor, clicks), true);
      redraw();
      // setStatusPoints();
      updateStatusFilters();
      updateStatusGeometries();
    };

    /**
     * Scale the image larger or smaller.
     *
     * @param {number} factor
     *   Scaling factor.
     * @param {boolean} checkScale
     *   Limit scaling to the not be smaller than the viewing area.
     */
    var scale = function scale(factor, checkScale) {
      // Check to see scaling will shrink image smaller than canvas.
      if (checkScale && factor < 1 && localStorage.imagerBoundsEnable === 'TRUE') {
        if (cscale * factor < initScale) {
          factor = initScale / cscale;
        }
      }

      ctx.translate(pt_now.getTxPt().x, pt_now.getTxPt().y);
      ctx.scale(factor, factor);
      ctx.translate(-pt_now.getTxPt().x, -pt_now.getTxPt().y);
//    var npt = outOfBounds();
//    if (npt) {
//      ctx.translate(npt.x, npt.y);
//    }
    };

    /**
     * Check if panning or zooming is causing image to leave a margin at edges.
     *
     * If so calculate the translation necessary to move image back to the edge.
     *
     * @return {Object}
     *   Return a point with offsets to move the image back on screen.
     */
    var outOfBounds = function outOfBounds() {
      var npt = {
        x: 0,
        y: 0
      };
      if (localStorage.imagerFullScreen === 'TRUE' || localStorage.imagerBoundsEnable === 'FALSE') {
        return npt;
      }
      var pw;
      var ph;
      switch (rotation) {
        case 0:
          pt_canvas_ul.setPt(0, 0, ctx);
          pt_canvas_lr.setPt(cw, ch, ctx);
          pw = image.iw * cscale;
          ph = image.ih * cscale;
          break;

        case 90:
          pt_canvas_ul.setPt(cw, 0, ctx);
          pt_canvas_lr.setPt(0, ch, ctx);
          pw = image.ih * cscale;
          ph = image.iw * cscale;
          break;

        case 180:
          pt_canvas_ul.setPt(cw, ch, ctx);
          pt_canvas_lr.setPt(0, 0, ctx);
          pw = image.iw * cscale;
          ph = image.ih * cscale;
          break;

        case 270:
          pt_canvas_ul.setPt(0, ch, ctx);
          pt_canvas_lr.setPt(cw, 0, ctx);
          pw = image.ih * cscale;
          ph = image.iw * cscale;
          break;
      }
      var msg = '<p>outOfBounds - cw: ' + cw + '  pw: ' + pw + '</p>';
      msg += '<p>outOfBounds - ch: ' + ch + '  ph: ' + ph + '</p>';
      var x1 = pt_canvas_ul.getTxPt().x;
      var y1 = pt_canvas_ul.getTxPt().y;
      var x2 = image.iw - pt_canvas_lr.getTxPt().x;
      var y2 = image.ih - pt_canvas_lr.getTxPt().y;
      // @todo - When image is smaller than the canvas the image flips back and forth.
      if (x2 < 0) {
        msg += '<p>outOfBounds - right:' + x2 + '</p>';
        npt.x = -x2;
      }
      if (y2 < 0) {
        msg += '<p>outOfBounds - bottom:' + y2 + '</p>';
        npt.y = -y2;
      }
      if (x1 < 0) {
        msg += '<p>outOfBounds - left:' + pt_canvas_ul.getTxPt().x + '</p>';
        npt.x = pt_canvas_ul.getTxPt().x;
      }
      if (y1 < 0) {
        msg += '<p>outOfBounds - top:' + pt_canvas_ul.getTxPt().y + '</p>';
        npt.y = pt_canvas_ul.getTxPt().y;
      }
      if (msg) {
        $('#imager-messages-content').html(msg);
      }
      if (npt.x > -1 || npt.y > -1) {
        return npt;
      }
      return;
    };

    /**
     * Mouse Down event handler.
     *
     * @param {Event} evt
     *  The event.
     */
    function mouseDown(evt) {
      if (evt.which !== 1) { // Ignore mouse buttons 2 and 3
        return;
      }
      evt.preventDefault();
      setEditMode('view');
      document.body.style.mozUserSelect = 'none';
      document.body.style.webkitUserSelect = 'none';
      document.body.style.userSelect = 'none';
      var y;
      var x = evt.offsetX || (evt.pageX - $canvas[0].offsetLeft);
      // @todo - pageY works intermittently
      // Appears to be related to the div having css 'position: fixed'
      if (evt.offsetY) {
        y = evt.offsetY || (evt.pageY - $canvas[0].offsetTop);
      }
      else {
        y = evt.layerY + $canvas[0].offsetTop;
      }
      pt_down.setPt(x, y, ctx);
      pt_now.setPt(x, y, ctx);
      pt_last.setPt(x, y, ctx);
      if (evt.shiftKey) {
        moveMode = 'zoom';
      }
      else {
        moveMode = 'dragging';
      }
      distance = 0;
      Popups.status.dialogUpdate({distance: parseInt(distance)});
    }

    /**
     * Mouse Move event handler
     *
     * @param {Event} evt
     *  The event.
     */
    function mouseMove(evt) {
      if (evt.which !== 1 || moveMode === 'none') {
        return;
      }
      evt.preventDefault();
      setEditMode('view');
      var x = evt.offsetX || (evt.pageX - $canvas[0].offsetLeft);
      var y;
      if (evt.offsetY) {
        y = evt.offsetY || (evt.pageY - $canvas[0].offsetTop);
      }
      else {
        y = evt.layerY + $canvas[0].offsetTop;
      }
      pt_now.setPt(x, y, ctx);

      if (evt.shiftKey) {
        moveMode = 'zoom';
      }
      else {
        moveMode = 'dragging';
      }
      switch (moveMode) {
        case 'dragging':
          distance = Math.sqrt((Math.pow(pt_down.getPt().x - pt_now.getPt().x, 2)) +
          (Math.pow(pt_down.getPt().y - pt_now.getPt().y, 2)));
          Popups.status.dialogUpdate({distance: parseInt(distance)});
          ctx.save();
          ctx.translate(pt_now.getTxPt().x - pt_down.getTxPt().x,
            pt_now.getTxPt().y - pt_down.getTxPt().y);
          var npt;
          // Recommended x and y motions that won't go out of bounds.
//        npt = outOfBounds();
//        if (npt !== null) {
//          ctx.restore();
//          ctx.translate(pt_now.getTxPt().x - pt_down.getTxPt().x + npt.x,
//            pt_now.getTxPt().y - pt_down.getTxPt().y + npt.y);
//        }
          redraw();
          break;

        case 'zoom':
          var clicks = (pt_last.getPt().y - pt_now.getPt().y) / 3;
          zoom(clicks);
          pt_last.setPt(x, y, ctx);
          break;
      }
    }

    /**
     * Mouse Up event handler
     *
     * @param {Event} evt
     *  The event.
     */
    function mouseUp(evt) {
      if (evt.which !== 1) {
        return;
      }
      evt.preventDefault();
      moveMode = 'none';
      var now = new Date();
      elapsed = now - lastup;
      Popups.status.dialogUpdate({elapsed: elapsed * 1000 / 1000000});
      lastup = now;
      if (distance < 20) {
        // If mouse didn't move between clicks.
        if (evt.ctrlKey) {
          zoom(evt.shiftKey ? -1 : 1);
        }
        else {
          if (elapsed < 500) {
            // If double click.
            if (localStorage.imagerFullScreen === 'TRUE') {
              screenfull.exit();
              hidePopups();
            }
            else {
              hidePopups();
            }
          }
        }
      }
    }

    /**
     * Mouse Wheel event handler - zoom the image
     *
     * @param {type} evt
     *   The event.
     *
     * @return {Boolean}
     *   Stops event propagation.
     */
    function mouseWheel(evt) {
      setEditMode('view');
      var delta = -evt.detail ? evt.detail : 0;
      delta = evt.wheelDelta ? evt.wheelDelta / 25 : delta;
      var y;
      var x = evt.offsetX || (evt.pageX - $canvas[0].offsetLeft);
      // @todo - pageY works intermittently
      // Appears to be related to the div having css 'position: fixed'
      if (evt.offsetY) {
        y = evt.offsetY || (evt.pageY - $canvas[0].offsetTop);
      }
      else {
        y = evt.layerY + $canvas[0].offsetTop;
      }
      pt_now.setPt(x, y, ctx);
      if (delta) {
        zoom(delta);
      }
      evt.stopPropagation();
      evt.preventDefault();
      return false;
    }

    /**
     * Enable event handlers for panning, zooming - disable cropping handlers.
     */
    function enablePanZoom() {
      $imgOverlay[0].addEventListener('mousedown', mouseDown, false);
      $imgOverlay[0].addEventListener('mousemove', mouseMove, false);
      $imgOverlay[0].addEventListener('mouseup', mouseUp, false);
      $imgOverlay[0].addEventListener('DOMMouseScroll', mouseWheel, false);
      $imgOverlay[0].addEventListener('mousewheel', mouseWheel, false);
      ias.setOptions({disable: true, hide: true});
    }

    /**
     * Enable event handlers for cropping - disable handlers for panning and zooming.
     */
    function enableCrop() {

      /* $imgOverlay[0].removeEventListener('contextmenu', contextMenu); */
      /* $imgOverlay[0].removeEventListener('click', click); */
      $imgOverlay[0].removeEventListener('mousedown', mouseDown);
      $imgOverlay[0].removeEventListener('mousemove', mouseMove);
      $imgOverlay[0].removeEventListener('mouseup', mouseUp);
      $imgOverlay[0].removeEventListener('DOMMouseScroll', mouseWheel);
      $imgOverlay[0].removeEventListener('mousewheel', mouseWheel);
      ias.setOptions({
        enable: true,
        hide: false,
        show: true,
        x1: 0,
        x2: 0,
        y1: 0,
        y2: 0
      });
    }

    /**
     * Update status dialog - modes
     */
    var updateStatusModes = function updateStatusModes() {
      Popups.status.dialogUpdate({
        'edit-mode': editMode,
        'full-screen': localStorage.imagerFullScreen
      });
    };

    /**
     * Update status dialog - Filters
     */
    var updateStatusFilters = function updateStatusFilters() {
      Popups.status.dialogUpdate({
        rotation: rotation,
        zoom: parseInt((cscale * 100000) / 1000) / 100
      });
    };

    Drupal.imager.viewer.calculateDisplayedImage = function calculateDisplayedImage() {
      var ul = pt_canvas_ul.getTxPt();
      var lr = pt_canvas_lr.getTxPt();
      var ulx = parseInt((ul.x < 0) ? 0 : ul.x);
      var uly = parseInt((ul.y < 0) ? 0 : ul.y);
      var lrx = parseInt((lr.x > img.width) ? img.width : lr.x);
      var lry = parseInt((lr.y > img.height) ? img.height : lr.y);
      pt_image_ul.setPt(ulx, uly, ctx);
      pt_image_lr.setPt(lrx, lry, ctx);
      return {
        width: Math.abs(lrx - ulx),
        height: Math.abs(lry - uly),
      };
    };

    /**
     * Update Geometries of screen, canvas, image and portion of image displayed.
     */
    var updateStatusGeometries = function updateStatusGeometries() {
      var imageSize = Drupal.imager.viewer.calculateDisplayedImage();
      Popups.status.dialogUpdate({
        'max-canvas-width': parseInt(mw),
        'max-canvas-height': parseInt(mh),
        'actual-canvas-width': parseInt(cw),
        'actual-canvas-height': parseInt(ch),
        'disp-image-width': parseInt(imageSize.width),
        'disp-image-height': parseInt(imageSize.height),
        'full-image-width': parseInt(image.iw),
        'full-image-height': parseInt(image.ih),
      });
    };

    /**
     * Update status dialog
     */
    var updateStatus = Drupal.imager.viewer.updateStatus = function updateStatus() {
      updateStatusModes();
      updateStatusFilters();
      updateStatusGeometries();
    };

    /**
     * Set the Edit mode
     *
     * @param {string} newMode
     *   The desired new mode.
     * @param {string} toggle
     *   Toggle the mode or set it.
     */
    var setEditMode = Drupal.imager.viewer.setEditMode = function setEditMode(newMode, toggle) {
      var oldMode = editMode;
      clearOverlayImg();
      if (newMode === editMode) {
        if (toggle) {
          newMode = 'view';
        }
        else {
          return;
        }
      }
      editMode = newMode;
      if (newMode !== oldMode) {
        switch (oldMode) {
          case 'init':
            enablePanZoom();
            break;

          case 'view':
            break;

          case 'slideshow':
            $('#view-slideshow').removeClass('checked');
            clearInterval(slideshowInterval);
            slideshowInterval = null;
            break;

          case 'crop':
            enablePanZoom();
            $('#mode-crop').removeClass('checked');
            break;

          case 'brightness':
            Popups.brightness.dialogClose();
            break;

          case 'color':
            Popups.color.dialogClose();
            break;

          case 'rotate':
            break;

          case 'save':
            Popups.filesave.dialogClose();
            $('#file-save').removeClass('checked');
            break;

          case 'email':
            Popups.filesave.dialogClose();
            $('#file-email').removeClass('checked');
            break;

          case 'print':
            $('#file-print').removeClass('checked');
            break;

          case 'download':
            Popups.filesave.dialogClose();
            $('#file-download').removeClass('checked');
            break;

          case 'clipboard':
            Popups.filesave.dialogClose();
            $('#file-clipboard').removeClass('checked');
            break;
        }
      }
      switch (editMode) {
        case 'view':
          // No editing.
          break;

        case 'slideshow':
          $('#view-slideshow').addClass('checked');
          changeImage(Drupal.imager.findNextImage(image, 1));
          var interval = (localStorage.imagerSlideshowInterval === 'undefined') ? 5 : localStorage.imagerSlideshowInterval;
          slideshowInterval = setInterval(function () {
            changeImage(Drupal.imager.findNextImage(image, 1));
          }, interval * 1000);
          break;

        case 'crop':
          enableCrop();
          $('#mode-crop').addClass('checked');
          break;

        case 'brightness':
          Popups.brightness.dialogOpen();
          break;

        case 'color':
          Popups.color.dialogOpen();
          break;

        case 'rotate':
          break;

        case 'save':
          Popups.filesave.dialogOpen({saveMode: 'save'});
          $('#file-save').addClass('checked');
          break;

        case 'email':
          Popups.filesave.dialogOpen({saveMode: 'email'});
          $('#file-email').addClass('checked');
          break;

        case 'print':
          $('#file-print').addClass('checked');
          printImage();
          break;

        case 'download':
          Popups.filesave.dialogOpen({saveMode: 'download'});
          $('#file-download').addClass('checked');
          break;

        case 'clipboard':
          Popups.filesave.dialogOpen({saveMode: 'clipboard'});
          $('#file-clipboard').addClass('checked');
          break;
      }
      Popups.status.dialogUpdate({'edit-mode': editMode});
    };

    /**
     * Track history of transforms done to this image.
     *
     * @param {Object} ctx
     *  I'm not sure, I didn't write it.
     */

    function trackTransforms(ctx) {
      var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      var xform = svg.createSVGMatrix();
      var savedTransforms = [];
      var save = ctx.save;
      ctx.save = function () {
        savedTransforms.push(xform.translate(0, 0));
        return save.call(ctx);
      };
      var restore = ctx.restore;
      ctx.restore = function () {
        xform = savedTransforms.pop();
        return restore.call(ctx);
      };

      var pt = svg.createSVGPoint();
      ctx.transformedPoint = function (x, y) {
        pt.x = x;
        pt.y = y;
        return pt.matrixTransform(xform.inverse());
      };

      var scale = ctx.scale;
      ctx.scale = function (sx, sy) {
        xform = xform.scaleNonUniform(sx, sy);
        return scale.call(ctx, sx, sy);
      };
      var translate = ctx.translate;
      ctx.translate = function (dx, dy) {
        xform = xform.translate(dx, dy);
        return translate.call(ctx, dx, dy);
      };
      var rotate = ctx.rotate;
      ctx.rotate = function (radians) {
        xform = xform.rotate(radians * 180 / Math.PI);
        return rotate.call(ctx, radians);
      };

      /* Var transform = ctx.transform;
         ctx.transform = function (a, b, c, d, e, f){
         var m2 = svg.createSVGMatrix();
           m2.a=a; m2.b=b; m2.c=c; m2.d=d; m2.e=e; m2.f=f;
            xform = xform.multiply(m2);
            return transform.call(ctx, a, b, c, d, e, f);
          }; */

      ctx.clearHistory = function () {
        savedTransforms = [];
      };
      var setTransform = ctx.setTransform;
      ctx.setTransform = function (a, b, c, d, e, f) {
        xform.a = a;
        xform.b = b;
        xform.c = c;
        xform.d = d;
        xform.e = e;
        xform.f = f;
        return setTransform.call(ctx, a, b, c, d, e, f);
      };
    }   // trackTransforms

    /**
     * Display any popups that are enabled.
     */
    function showPopups() {
      popup.dialogOpen();
      showInfo();
      if (localStorage.imagerDebugStatus === 'TRUE') {
        $('#debug-status').addClass('checked');
        Popups.status.dialogOpen();
      }
      if (localStorage.imagerDebugMessages === 'TRUE') {
        $('#debug-messages').addClass('checked');
        // Popups.messages.dialogOpen();
      }
      if (localStorage.imagerShowMap === 'TRUE') {
        $('#view-map').addClass('checked');
        Popups.map.dialogOpen();
      }
    }

    /**
     * Hide all popups and the main viewer window.
     */
    var hidePopups = function hidePopups() {
      Popups.viewer.dialogClose();
      Popups.info.dialogClose();
      // $imagerEdit.hide();
      // $imagerMap.hide();
      Popups.status.dialogClose();
      Popups.messages.dialogClose();
      Popups.confirm.dialogClose();
      setEditMode('view');
    };

    var fillOverlayImg = function fillOverlayImg() {
      $imgOverlay[0].src = Drupal.imager.core.getImage('image-cropped', false);
      var start = new Date().getTime();
      var milliseconds = 250;
      for (var i = 0; i < 1e7; i++) {
        if ((new Date().getTime() - start) > milliseconds) {
          break;
        }
      }
      clearOverlay = true;
    };

    var clearOverlayImg = function clearOverlayImg() {
      if (!clearOverlay) {
        return;
      }
      $imgOverlay[0].src = '/' + Drupal.imager.settings.modulePath + '/icons/transparent.png';
      clearOverlay = false;
    };

    // Dialog handling functions.
    popup.dialogOnCreate = function dialogOnCreate() {

      // Initialize the busy indicator
      Popups.busy = Drupal.imager.popups.busyC();

      Drupal.imager.viewer.$canvas = $canvas = $('#imager-canvas');
      $viewerWrapper = $('#imager-viewer');
      $canvasWrapper = $('#imager-canvas-wrapper');

      var buttons = $viewerWrapper.find('#button-wrapper')[0].getBoundingClientRect();
      widthMargin  = buttons.width + buttons.left * 2 + 3;
      heightMargin = buttons.top * 2;

      Drupal.imager.viewer.ctx = ctx = $canvas[0].getContext('2d');
      ias = $canvas.imgAreaSelect({
        // Attach handler to create cropping area.
        instance: true,
        disable: true,
        handles: true,
        autoHide: false,
        hide: true,
        show: true
      });

      // canvas2 is never displayed, used to save images temporarily.
      Drupal.imager.viewer.$canvas2 = $canvas2 = $('#imager-canvas2');
      Drupal.imager.viewer.ctx2 = ctx2 = $canvas2[0].getContext('2d');
      $canvas2.hide();

      // Initialize Dialogs.  @TODO there has to be a better way, rework this.
      Popups.initDialog('color', '#edit-color', function () {
        setEditMode('color', true);
      });
      Popups.initDialog('brightness', '#edit-brightness', function () {
        setEditMode('brightness', true);
      });
      Popups.initDialog('config', '#mode-configure', function () {
        Popups.config.dialogToggle();
      });
      Popups.initDialog('info', '#view-info', function () {
        Popups.info.dialogToggle();
      });
      Popups.initDialog('status', '', function () {
        Popups.status.dialogToggle();
      });
      Popups.initDialog('messages', '', function () {
        Popups.messages.dialogToggle();
      });
      Popups.initDialog('confirm', '#file-delete', function () {
        Popups.confirm.dialogToggle();
      });
      Popups.initDialog('edit', '', function () {
        Popups.edit.dialogToggle();
      });
      Popups.initDialog('filesave', '', null);

      // File Buttons.
      $('#file-save').click(function () {
        setEditMode('save', true);
        Popups.filesave.setSelectButton($(this));
      });

      $('#file-download').click(function () {
        setEditMode('download', true);
        Popups.filesave.setSelectButton($(this));
      });

      /*
      $('#file-clipboard').click(function () {
        setEditMode('clipboard', true);
        Popups.filesave.setSelectButton($(this));
      }); */

      $('#file-print').click(function () {
        setEditMode('print');
      });

      $('#file-image').click(function (evt) {
        $imgOverlay.triggerHandler(evt);
      });

      $('#imager-help').click(function () {
        window.open('/admin/help/imager', '_blank');
      });

      $imgOverlay = $('#imager-image');
      $imgOverlay.on('contextmenu', fillOverlayImg);

      // Edit Buttons.
      $('#mode-crop').click(function () {
        setEditMode('crop', true);
      });
      $('#edit-crop').click(function () {
        crop();
        setEditMode('view');
      });
      $('#edit-ccw').click(function () {
        setEditMode('rotate');
        rotate(-90);
      });
      $('#edit-cw').click(function () {
        setEditMode('rotate');
        rotate(90);
      });
      $('#view-reset').click(function () {
        setEditMode('view');
        changeImage(image);
      });

      // Image Buttons.
      $('#image-left').click(function () {
        setEditMode('view');
        changeImage(Drupal.imager.findNextImage(image, -1));
      });
      $('#image-right').click(function () {
        setEditMode('view');
        changeImage(Drupal.imager.findNextImage(image, 1));
      });
      $('#image-exit').click(function () {
        if (localStorage.imagerFullScreen === 'TRUE') {
          screenfull.exit();
          hidePopups();
        }
        else {
          hidePopups();
        }
      });

      $('#imager-wrapper').hover(function () {
        var x = window.scrollX;
        var y = window.scrollY;
        this.focus();
        window.scrollTo(x, y);
      }, function () {
        this.blur();
      }).keyup(function (event) {
        switch (event.keyCode) {
          case 70: // F
            localStorage.imagerFullScreen = (localStorage.imagerFullScreen === 'TRUE') ? 'FALSE' : 'TRUE';
            screenfull.toggle($('#imager-wrapper')[0]);
            event.preventDefault();
            break;

          case 88: // X
          case 27: // escape
            if (localStorage.imagerFullScreen === 'TRUE') {
              screenfull.exit();
              hidePopups();
            }
            else {
              hidePopups();
            }
            event.preventDefault();
            break;

          case 74: // J
          case 37: // left arrow
//        case 38: // up arrow
            event.stopPropagation();
            event.preventDefault();
            changeImage(Drupal.imager.findNextImage(image, -1));
            break;

          case 75: // K
          case 39: // right arrow
//        case 40: // down arrow
            event.stopPropagation();
            changeImage(Drupal.imager.findNextImage(image, 1));
            break;

          case 82: // R
            setEditMode('view');
            changeImage(image);
            break;
        }
      });

      // If a full screen event happens - resize the image to fit.
      ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange'].forEach(function (e) {
//    ['fullscreenchange'].forEach(function (e) {
        document.addEventListener(e, function (event) {
          setFullScreen((document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement) ? true : false);
//        setFullScreen();
        });
      });

      // Full Screen button.
      $('#mode-fullscreen').click(function () {
        if (screenfull.enabled) {
          if (localStorage.imagerFullScreen === 'TRUE') {
            localStorage.imagerFullScreen = 'FALSE';
            screenfull.exit();
          }
          else {
            localStorage.imagerFullScreen = 'TRUE';
            screenfull.request($('#imager-wrapper')[0]);
          }
        }
      });

      // View Slideshow buttons.
      $('#view-slideshow').click(function () {
        if ($(this).hasClass('checked')) {
          setEditMode('view');
          $(this).removeClass('checked');
        }
        else {
          $(this).addClass('checked');
          setEditMode('slideshow');
        }
      });

      // View image alone in a new browser window.  Good for printing
      $('#view-browser').click(function () {
        Popups.busy.hide();
//      var img = Drupal.imager.core.getImage('image-cropped', false);
        var img = Drupal.imager.core.getImage('screen', false);
        Drupal.imager.core.ajaxProcess(
          this,
          Drupal.imager.settings.actions.viewBrowser.url,
          {
            action: 'view-browser',
            uri: image.src,
            mid: image.mid,
            imgBase64: img
          }, function (response) {
            var path = response['url'];
            window.open(path, '_blank');
          }
        );
      });

      // Zoom image to fit canvas button
      $('#view-zoom-fit').click(function () {
        pt_now.setPt(pt_down.getPt().x, pt_down.getPt().y, ctx);
        zoom(0);
      });

      // Zoom to 1:1 button
      $('#view-zoom-1').click(function () {
        pt_now.setPt(pt_down.getPt().x, pt_down.getPt().y, ctx);
        zoom(0);
      });

      // Zoom in button
      $('#view-zoom-in').click(function () {
        pt_now.setPt(pt_down.getPt().x, pt_down.getPt().y, ctx);
        zoom(1);
      });

      // Zoom out button
      $('#view-zoom-out').click(function () {
        pt_now.setPt(pt_down.getPt().x, pt_down.getPt().y, ctx);
        zoom(-1);
      });

      // Debug status buttons event handlers.
      $('#debug-status').click(function () {
        if (localStorage.imagerShowStatus === 'FALSE') {
          localStorage.imagerShowStatus = 'TRUE';
          $(this).addClass('checked');
          Popups.status.dialogOpen();
        }
        else {
          localStorage.imagerShowStatus = 'FALSE';
          $(this).removeClass('checked');
          Popups.status.dialogClose();
        }
        updateStatus();
      });

      // Debug messages buttons event handlers.
      $('#debug-messages').click(function () {
        localStorage.imagerShowDebug = (localStorage.imagerShowDebug === 'TRUE') ? 'FALSE' : 'TRUE';
        if (localStorage.imagerShowDebug === 'TRUE') {
          $(this).addClass('checked');
          $imagerMessages.show();
          $('#imager-messages-content').empty();
        }
        else {
          $(this).removeClass('checked');
          $imagerMessages.hide();
        }
      });

      trackTransforms(ctx);
      setEditMode('view');

      popup.dialogOpen();
      return popup;
    };

    /**
     * Use full screen to display imager viewer.
     *
     * @param {type} newMode
     *   The new mode.
     */
    function setFullScreen() {

      // Introduce a slight delay so window dimensions can get updated.
      setTimeout(function () {
        if (localStorage.imagerFullScreen === 'TRUE') {
          $canvasWrapper.addClass('fullscreen');
          $('#mode-fullscreen').addClass('checked');
        }
        else {
          $canvasWrapper.removeClass('fullscreen');
          $('#mode-fullscreen').removeClass('checked');
        }
        initializeImage();
        redraw();
      }, 100);
    }

//  /**
//   * Copy the canvas into the current IMG
//   */
//  Drupal.imager.viewer.copyCanvasToImg = function copyCanvasToImg() {
//    img.src = $canvas[0].toDataURL();
//  };

    /**
     * Return the <IMG> element containing the current image.
     * @return {Object} img
     */
    Drupal.imager.viewer.getImg = function getImg() {
      return img;
    };

    /**
     * Viewer dialog has finished opening
     *
     * Grab current scroll location of page,
     * Set the focus to imager,
     * and reset the scroll.
     *
     * @return {viewerC} popup
     */
    popup.dialogOnOpen = function dialogOnOpen() {
      popup.dialogUpdate();
      var x = window.scrollX;
      var y = window.scrollY;
      $('#imager-wrapper').focus();                // Set focus to imager-wrapper
      window.scrollTo(x, y);                       // Restore scroll
      return popup;
    };

    /**
     * Viewer dialog has closed - do nothing
     *
     * @return {viewerC} popup
     */
    popup.dialogOnClose = function dialogOnClose() {
      return popup;
    };

    /**
     * User has resized the dialog.
     *
     * @param Event event
     *   The event.
     *
     * @return array popup
     *   The popup.
     */
    popup.dialogOnResize = function dialogOnResize(event) {

      var rect = $('#imager-viewer #button-wrapper')[0].getBoundingClientRect();
      console.log('buttons ', rect.top, rect.right, rect.bottom, rect.left);

      var rect2 = $canvas[0].getBoundingClientRect();
      console.log('canvas ', rect2.top, rect2.right, rect2.bottom, rect2.left);

      var $bod = $('html body');
      var rect3 = $bod[0].getBoundingClientRect();
      console.log('body ', rect3.top, rect3.right, rect3.bottom, rect3.left);

//    var width = event.target.offsetWidth - widthMargin;
//    var height = event.target.offsetHeight - heightMargin;

//    $canvas.width(width).height(height);
//    $canvasWrapper.width(width).height(height);

      initializeImage(event.target.offsetWidth, event.target.offsetHeight);

      doInit = true;
      redraw();
      showInfo();
      return popup;
    };

    /**
     * Request to update the Viewer dialog.
     *
     * @param {object} settings
     *   These are the settings.
     *
     * @return {viewerC} popup
     */
    popup.dialogUpdate = function dialogUpdate(settings) {
      if (localStorage.imagerFullScreen === 'TRUE') {
        $('#image-exit').addClass('checked');
        screenfull.request($('#imager-wrapper')[0]);
      }
      $.extend(popup.settings, settings);
      changeImage(popup.settings.image);
      return popup;
    };

    return popup;
  };
})(jQuery);
