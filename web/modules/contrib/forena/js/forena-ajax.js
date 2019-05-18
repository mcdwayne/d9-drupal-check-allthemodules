/**
 * @file
 * Legacy forena behaviors.  These are deprecated. 
 */
(function ($) {
  ForenaAjax = {
    listening : 0,

    init: function(context) {
      // Initialization functions here.
    },

    backHandler: function (event) {
      if (event.state) {
        window.location = event.state.url;
      }
      else {
        window.location = document.location;
      }
    },

    changeUrl: function (url, title) {
      var state = {
        "url" : url
      };
      history.pushState(state, title, url);
      this.listening = 1;
    }
  };

  // Autoload include links.
  $.fn.forenaAutoload = function () {
    this.hide();
    this.addClass(".forena-autoload-processed");
    this.click();
  };

  $.fn.forenaAjaxChangeUrl = function(url, title) {
    if (this.length > 0) {
      ForenaAjax.changeUrl(url, title);
    }
  };

  $.fn.forenaModalDraggable = function() {
    // This is done in a function after the modal is painted and is
    // used primarily when autoresize is set (which is the default).
    jQuery( "#drupal-modal" ).dialog( "option", "draggable", true );
  };

  // jQuery plugin for adding a select class to element
  $.fn.forenaSelect = function(selector) {
    $('.selected', this).removeClass('selected');
    $(selector, this).addClass('selected');
  };

  Drupal.behaviors.forenaAjax = {
    attach: function (context, settings) {
      // Auto click the reports for ajax.
      $('.use-ajax.ajax-processed.forena-autoload:not(.forena-autoload-processed)', context).forenaAutoload();
    }
  };

  window.onpopstate =  ForenaAjax.backHandler;

  Drupal.behaviors.forena_ajax_xlink = {
    attach: function (context, settings) {
      // Copied from drupal ajax.js to allow clicking on
      // SVG Images.
      // Bind Ajax behaviors to all items showing the class.
      $('.use-ajax-xlink').once('ajax').each(function () {
        element_settings = {};
        // Clicked links look better with the throbber than the progress bar.
        element_settings.progress = {type: 'fullscreen'};

        // For anchor tags, these will go to the target of the anchor rather
        // than the usual location.
        href = $(this).attr('xlink:href');
        if (href) {
          element_settings.url = href;
          element_settings.event = 'click';
        }
        element_settings.dialogType = $(this).data('dialog-type');
        element_settings.dialog = $(this).data('dialog-options');
        element_settings.base = $(this).attr('id');
        element_settings.element = this;
        Drupal.ajax(element_settings);
      });
    }
  }

})(jQuery);

