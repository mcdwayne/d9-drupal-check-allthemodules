/**
 * @file
 * Declare Busy dialog - Drupal.imager.popups.busyC.
 */

(function ($) {
  'use strict';

  /**
   * Define the color dialog - Hue/Saturation/Lightness.
   *
   * @param {object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {dialog}
   *   Return the color dialog.
   */
  Drupal.imager.popups.busyC = function busyC() {
    var $busy;
    var basepath = drupalSettings.path.baseUrl;

    // Create the busy <img> and save it.
    $busy = $(document.createElement('img')).attr('id', 'imager-busy').attr('src', basepath + 'modules/custom/imager/icons/busy.gif');
    Drupal.imager.$wrapper.append($busy);

    $busy.show();
//  $busy.hide();

    var show = function show() {
      $busy.show();
    };

    var hide = function hide() {
      $busy.hide();
    };

    return {
      show: show,
      hide: hide
    };
  };

})(jQuery);
