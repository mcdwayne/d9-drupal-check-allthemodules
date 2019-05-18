(function (iframeResizerSettings) {

  'use strict';

  // Set up the iFrame Resizer library's options.
  if (iframeResizerSettings.advancedHosted.heightCalculationMethod === "parent") {
    delete iframeResizerSettings.advancedHosted.heightCalculationMethod;
  }
  if (iframeResizerSettings.advancedHosted.widthCalculationMethod === "parent") {
    delete iframeResizerSettings.advancedHosted.widthCalculationMethod;
  }
  window.iFrameResizer = iframeResizerSettings.advancedHosted;

})(drupalSettings.iframeResizer);
