/**
 * @file
 * Creates namespaces and provide utility routines needed by Imager module.
 */

(function ($) {
  'use strict';


  Drupal.AjaxCommands.prototype.imagerCommand = function (ajax, response, status) {
    Drupal.imager[response.component].imagerCommand(response);
  };

  // Create the Drupal.imager namespace and subspaces.
  Drupal.imager = {
    popups: {},
    viewer: {}
  };

  Drupal.imager.coreM = function () {
    var Popups = Drupal.imager.popups;
    var Viewer = Drupal.imager.viewer;

    var displayMessage = function displayMessage(msg) {
      // $imagerMessages.show();
      $('#imager-messages-content').html(msg);
      if (localStorage.imagerDebugMessages === 'FALSE') {
        setTimeout(function () {
          // $imagerMessages.hide();
        }, 5000);
      }
    };

    var getImage = function getImage(resolution, stream) {
      var mimeType = '';
      var pt_canvas_ul = Viewer.pt_canvas_ul;
      var pt_canvas_lr = Viewer.pt_canvas_lr;
      var status = Viewer.getStatus();
      var img = Viewer.getImg();
      var dataurl;

      mimeType = 'image/jpeg';
      if (img.src.match(/\.png$/i)) {
        mimeType = 'image/png';
      }
      else if (img.src.match(/\.jpe*g$/i)) {
        mimeType = 'image/jpeg';
      }
      else if (img.src.match(/\.gif$/i)) {
        mimeType = 'image/gif';
      }

      var ncw;
      var nch;
      switch (resolution) {
        case 'screen':
          dataurl = Viewer.$canvas[0].toDataURL(mimeType);
          break;

        case 'image-cropped':
          Viewer.ctx2.setTransform(1, 0, 0, 1, 0, 0);
          var pt = pointC('tmp');
          var imageSize = Drupal.imager.viewer.calculateDisplayedImage();
          if (status.rotation === 0 || status.rotation === 180) {

            Viewer.$canvas2.attr({
              // Set canvas to same size as image.
              width: imageSize.width,
              height: imageSize.height
            });
            if (status.rotation === 0) {
              pt.setPt(0, 0, Viewer.ctx);
              Viewer.ctx2.drawImage(img,
                  imageSize.offsetx, imageSize.offsety,
                  imageSize.width, imageSize.height,
                  0, 0,
                  imageSize.width, imageSize.height
              );
            }
            else {
//            Viewer.ctx2.translate(ncw, nch);
//            Viewer.ctx2.rotate(angleInRadians(status.rotation));
//            pt.setPt(status.cw, status.ch, Viewer.ctx);
//            Viewer.ctx2.drawImage(img, -pt.getTxPt().x, -pt.getTxPt().y);

              Viewer.ctx2.drawImage(img,
                  imageSize.offsetx, imageSize.offsety,
                  imageSize.width, imageSize.height,
                  0, 0,
                  imageSize.width, imageSize.height
              );
              Viewer.ctx2.translate(imageSize.width, imageSize.height);
              Viewer.ctx2.rotate(angleInRadians(status.rotation));
            }
          }
          else {
            ncw = Math.abs(pt_canvas_lr.getTxPt().y - pt_canvas_ul.getTxPt().y);
            nch = Math.abs(pt_canvas_lr.getTxPt().x - pt_canvas_ul.getTxPt().x);
            Viewer.$canvas2.attr({
              // Set canvas to same size as image.
              width: ncw,
              height: nch
            });
            if (status.rotation === 90) {
              Viewer.ctx2.translate(ncw, 0);
              Viewer.ctx2.rotate(angleInRadians(status.rotation));
              pt.setPt(status.cw, 0, Viewer.ctx);
              // Find Upper left corner of canvas in original image.
              Viewer.ctx2.drawImage(img, -pt.getTxPt().x, -pt.getTxPt().y);
            }
            else {
              Viewer.ctx2.translate(0, nch);
              Viewer.ctx2.rotate(angleInRadians(status.rotation));
              // Find Upper left corner of canvas in original image.
              pt.setPt(0, status.ch, Viewer.ctx);
              Viewer.ctx2.drawImage(img, -pt.getTxPt().x, -pt.getTxPt().y);
            }
          }
          dataurl = Viewer.$canvas2[0].toDataURL(mimeType);
          break;

        case 'image-full':
          var tcw;
          var tch;
          Viewer.ctx2.setTransform(1, 0, 0, 1, 0, 0);
          var image = Viewer.getImage();
          if (status.rotation === 0 || status.rotation === 180) {
            tcw = image.iw;
            Viewer.$canvas2.attr({
              width: tcw,
              // Set canvas to same size as image.
              height: tch
            });
            if (status.rotation === 180) {
              Viewer.ctx2.translate(image.iw, image.ih);
            }
          }
          else {
            tcw = image.ih;
            tch = image.iw;
            Viewer.$canvas2.attr({
              width: tcw,
              // Set canvas to same size as image.
              height: tch
            });
            if (status.rotation === 90) {
              Viewer.ctx2.translate(image.ih, 0);
            }
            else {
              Viewer.ctx2.translate(0, image.iw);
            }
          }
          Viewer.ctx2.rotate(angleInRadians(status.rotation));
          Viewer.ctx2.drawImage(img, 0, 0);
          // Copy full image into canvas.
          dataurl = Viewer.$canvas2[0].toDataURL(mimeType);
          break;
      }
      if (stream) {
        dataurl = dataurl.replace(mimeType, 'image/octet-stream');
      }
      return dataurl;
    };

    /**
     * Process AJAX requests.
     *
     * @param {Object} $callingElement
     *   Element from which this ajax call was initiated.
     * @param {path} url
     *   URL of the AJAX handler - registered with hook_menu().
     * @param {Object} postData
     *   Data needed by the php ajax function.
     * @param {function} processFunc
     *   Function to call after receiving data.
     *
     * @TODO Drupal probably has an API for this.
     */
    var ajaxProcess = function ajaxProcess($callingElement, url, postData, processFunc) {
      Popups.$busy.show();
      postData['filePath'] = Drupal.imager.settings.filePath;
      $.ajax({
        type: 'POST',
        url: url,
        data: JSON.stringify(postData),
        success: function (response) {
          for (var i = 0; i < response.length; i++) {
            if (response[i].command === 'ImagerCommand') {
              if (processFunc) {
                processFunc.call($callingElement, response[i].data);
              }
            }
          }

          /**
          var display = false;
          var out;
          var i;
          out = '<h2>' + response['action'] + ':' + response['status'] + '</h2>';
          if (response['info']) {
            out += '<div class="info">' + response['info'] + '</div>';
            display = true;
          }
          if (response['status'] === 'catch' ||
              (response['debug'] && localStorage.imagerDebugMessages === 'TRUE')) {
            for (i = 0; i < response['debug'].length; i++) {
              out += '<div class="debug">' + response['debug'][i] + '</div>';
            }
            display = true;
          }

          if (display) {
            Popups.messages.dialogOpen();
            $('#imager-messages-content').html(out);
          } */

          /**
          if (localStorage.imagerDebugMessages === 'FALSE') {
            setTimeout(function () {
              Popups.messages.dialogClose();
            }, 3000);
          }
          **/

          Popups.$busy.hide();
        },
        error: function (evt) {
        }
      });
    }; // ajaxProcess()

    var pointC = function pointC(spec) {
      var namex = spec.name + '-x';
      var namey = spec.name + '-y';
      var point = {
        v: {x: 0, y: 0},  // canvas point
        t: {x: 0, y: 0}   // transformed point -- apply ctx.form (SVG)
      };
      var doTransform = spec.transform || true;
      var namext;
      var nameyt;
      if (doTransform) {
        namext = namex + '-tx';
        nameyt = namey + '-tx';
      }

      point.setPt = function setPt(x, y, ctx) {
        point.v.x = x;
        point.v.y = y;
        var vals = {};
        vals[namex] = parseInt(x);
        vals[namey] = parseInt(y);
        if (doTransform) {
          point.t = ctx.transformedPoint(x, y);
          vals[namext] = parseInt(point.t.x);
          vals[nameyt] = parseInt(point.t.y);
        }
        Popups.status.dialogUpdate(vals);
      };

      point.getPt = function getPt() {
        return point.v;
      };
      point.getTxPt = function getTxPt() {
        return point.t;
      };
      return point;
    };

    /**
     * Given the path to a thumbnail determine the path of the full image.
     *
     * If '/styles/' is part of the path then simply remove /styles/
     * and the next two path components.
     * Otherwise look for the parent element to be a link to the full image.
     *
     * @param {type} tsrc
     *   Source path of source image.
     *
     * @return {strin}]
     *   Full file path of original image.
     */
    var getFullPath = function getFullPath(tsrc) {
      var src;
      // If the image has '/styles/' in it's path
      // then extract the large image path by modifying the thumbnail path
      // Kludgy but it works - any better ideas.
      if (tsrc.indexOf('/styles/') === -1) {
        src = tsrc;
      }
      else {
        var sindex = tsrc.indexOf('styles');
        var index = sindex;
        var slashes = 0;
        while (slashes < 3) {
          index++;
          if (tsrc.charAt(index) === '/') {
            slashes++;
          }
        }
        var tindex = tsrc.indexOf('?itok');
        if (tindex) {
          src = tsrc.substr(0, sindex) + tsrc.substr(index + 1, tindex - index - 1);
        }
        else {
          src = tsrc.substr(0, sindex) + tsrc.substr(index + 1);
        }
      }
      return src;
    };

    /**
     * Given an angle degrees, calculate it in radians.
     *
     * @param {number} deg
     *   Angle in degrees
     *
     * @return {number}
     *   Angle in radians
     */
    var angleInRadians = function angleInRadians(deg) {
      return deg * Math.PI / 180;
    };

    return {
      ajaxProcess: ajaxProcess,
      getImage: getImage,
      angleInRadians: angleInRadians,
      displayMessage: displayMessage,
      getFullPath: getFullPath,
      pointC: pointC
    };
  };
})(jQuery);
