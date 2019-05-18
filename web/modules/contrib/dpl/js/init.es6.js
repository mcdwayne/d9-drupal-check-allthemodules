/**
 * Initially copied from https://github.com/bradfrost/ish./tree/master/js but adapted over time.
 */

/**
 * Returns a random number between min and max.
 *
 * @param {int} min
 *   The minimal value.
 * @param {int} max
 *   The maximum value.
 *
 * @return {int}
 *   The random number.
 */
const determineRandomSize = (min, max) => {
  const num = Math.random() * (max - min) + min;

  return parseInt(num, 10);
};

const convertFullWidth = (width, fullWidth) => width === -1 ? fullWidth : width;

const getInputSizeRanges = (fullWidth, $, $sizeLinks) => {
  const inputSizes = $sizeLinks.map(function () {
    return $(this).attr("data-dpl-size");
  });
  return Array.from(inputSizes).reduce((acc, dplSize) => {
    const sizes = dplSize.split(':');
    acc[dplSize] = [convertFullWidth(parseInt(sizes[0], 10), fullWidth), convertFullWidth(parseInt(sizes[1], 10), fullWidth)];
    return acc;
  }, {});
};

/**
 * Resize the viewport
 *
 * @param {jQuery} $sgWrapper
 * @param {jQuery} $sgViewport
 * @param {int} size
 *   The target size of the viewport.
 */
const resizeIframe = ($sgWrapper, $sgViewport, size) => {
  const width = size === -1 ? $sgWrapper.width() : size;

  // Resize viewport to desired size
  $sgViewport.width(width);
  $sgViewport.height("800px");
};

((w, $) => {
  // Wrapper around viewport
  const $sgWrapper = $("#sg-gen-container");
  const fullWidth = $sgWrapper.width();

  // Viewport element
  const $sgViewport = $("#sg-viewport");

  const $sizeLinks = $("[data-dpl-size]");
  const inputSizeRanges = getInputSizeRanges(fullWidth, $, $sizeLinks);

  $sizeLinks.on("click", e => {
    e.preventDefault();
    const widthIndex = $(e.target).attr("data-dpl-size");
    const widthRange = inputSizeRanges[widthIndex];
    const width = determineRandomSize(widthRange[0], widthRange[1]);

    $(e)
      .parent()
      .toggleClass("active");
    resizeIframe($sgWrapper, $sgViewport, width);
  });

  // Resize initially based upon the smalltest size.
  const initialWithIndex = $("[data-dpl-default]").attr("data-dpl-size");
  const widthRange = inputSizeRanges[initialWithIndex];
  const width = determineRandomSize(widthRange[0], widthRange[1]);
  resizeIframe($sgWrapper, $sgViewport, width);

  // capture the viewport width that was loaded and modify it so it fits with the pull bar
  const origViewportWidth = $sgViewport.width();
  $sgWrapper.width(origViewportWidth);
  $sgViewport.width(origViewportWidth - 14);
})(this, jQuery);
