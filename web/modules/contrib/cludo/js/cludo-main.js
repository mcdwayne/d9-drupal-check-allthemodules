(function ($, Drupal) {

  'use strict';

  var CludoLibrary = {
    cludo: window._cludo || [],

    addCmsOverlayScript(version) {
      this.cludo.push({
        event: 'cms',
        data: {
          platform: 'Drupal',
          version: version
        }
      });
    },

    setPageContext(page) {
      this.cludo.push({
        event: 'setPage',
        data: {
          url: page
        }
      });
    }
  };

  Drupal.behaviors.cludo = {
    attach: function (context, settings) {
      $('body', context).once('cludo-overlay').each(function () {
        CludoLibrary.addCmsOverlayScript(settings.cludo.version);

        if (typeof settings.cludo.pageUrl !== 'undefined') {
          CludoLibrary.setPageContext(settings.cludo.pageUrl);
        }
      });
    }
  };
}(jQuery, Drupal));
