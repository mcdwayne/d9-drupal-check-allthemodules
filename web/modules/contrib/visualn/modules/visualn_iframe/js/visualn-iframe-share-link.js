(function ($, Drupal) {
  Drupal.behaviors.visualnIframeBehaviour = {
    attach: function (context, settings) {
      $(context).find('body').once('visualn-iframe').each(function () {
        // @todo: hide all share boxes on a click on page outer space
        $('.visualn-iframe-share-link a').click(function(e){
          e.preventDefault();
          if (typeof $(this).data('iframeShareBox') === "undefined") {
            var link_uid = $(this).attr('rel');
            var link_url = settings.visualn_iframe.share_iframe_links[link_uid];
            var offset = $(this).offset();
            // @todo: provide an option to select between <embed> and <iframe> tags
            // attach an overlay textarea with the share iframe code with the iframe url
            // @todo: check for other attributes (e.g. allow etc.)
            //   check sandbox iframe property to avoid malicious iframes containing fishing forms
            // @todo: frameborder not supported in html5, use style="border:none;" instead
            var overlay = $('<div><textarea style="width: 400px; height: 100px;"><iframe width="1000" height="600" src="'+link_url+'" frameborder="0"></iframe></textarea></div>');
            //var overlay = $('<div><textarea style="width: 400px; height: 100px;"><embed width="1000" height="600" src="'+link_url+'"></embed></textarea></div>');
            overlay.css("position", "absolute");
            overlay.css("left", offset.left);
            overlay.css("top", offset.top + $(this).height() + 5);
            overlay.css("z-index", 1000);
            // store reference for the overlay to toggle it on subsequent clicks
            $(this).data('iframeShareBox', overlay);
            // add share box to the bottom of the page for the absolute position to work correctly
            // since absolute inside relative doen't work well
            $("body").append(overlay);
            //$(this).after(overlay);
          }
          else {
            $(this).data('iframeShareBox').toggle();
          }
        });
      });
    }
  };
})(jQuery, Drupal);
