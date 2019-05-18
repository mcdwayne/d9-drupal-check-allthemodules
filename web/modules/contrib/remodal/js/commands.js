(function ($, Drupal) {

  'use strict';

  var ajaxJqueryObject;
  var ajaxRemodal;

  /**
   * Command to open a remodal dialog.
   *
   * @param {Drupal.Ajax} ajax
   *   The Drupal Ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.openRemodal = function (ajax, response, status) {

    // additional class for internal use
    var remodalClass = 'ajax-remodal';
    if (typeof (response.options.modifier) != 'undefined') {
      response.options.modifier += ' ' + remodalClass;
    }
    else {
      response.options.modifier = remodalClass;
    }


    // If a remodal conatains (and triggers)  another link to create a nested remodal,
    // the previous one should be removed from the DOM
    if (typeof (ajaxRemodal) != 'undefined') {
      ajaxRemodal.destroy();
      ajaxJqueryObject.remove();
    }

    // create remodal
    ajaxJqueryObject = $($.parseHTML('<div>' + response.content + '</div>'));
    ajaxRemodal = ajaxJqueryObject.remodal(response.options);
    ajaxRemodal.open();

    // remove the last ajax remodal from the DOM on close (i.e. if closed manually)
    $(document).on('closed', '.remodal.' + remodalClass, function () {
      ajaxRemodal.destroy();
      ajaxJqueryObject.remove();
    });

    // Reattach behaviours to new content
    // @todo: check if it can be limited to a selector
    Drupal.attachBehaviors();
  };
})(jQuery, Drupal);
