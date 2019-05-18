/**
 * This is required because the digital locker code directly references $.
 */
$ = jQuery.noConflict();

/**
 * Callback function to capture and pass on the file shared from digital locker.
 * @param data
 *   The data that is provided to the callback function.
 *
 * @return {string} SUCCESS/FAILURE
 */
function digitallocker_requester_callback(data) {
  'use strict';

  $.post(drupalSettings.DigitalLocker.callbackUrl, {data: data}, function (returnData) {
    var parent = $('#' + returnData.did).parent();
    parent.find("input[name*='fids']").val(returnData.fid);
    parent.find("input[name*='button']").mousedown();
  });
  return 'SUCCESS';
}

(function ($) {
  'use strict';

  // the foll. code can get the dl button showing up in case file uploads are
  // added via ajax. but it has the drawback of attaching clickhandlers twice
  // for the initial buttons.
  Drupal.behaviors.digitallocker_requester = {
    attach: function (context, settings) {
      return;

      if (typeof dgl_share == 'function') {
        var $dlshare = $('#dlshare');
        dgl_share($dlshare.attr('data-app-id'), $dlshare.attr('data-app-hash'), $dlshare.attr('time-stamp'));
      }
    }
  };

})(jQuery);
