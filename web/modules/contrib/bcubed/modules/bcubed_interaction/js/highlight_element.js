new BCubedActionPlugin({
  action: function(args) {
    var that = this;
    jQuery(function ($) {

      function dismissCallback(){
        $(window).disablescroll("undo");
        that.sendEvent('ElementHighlightDismissed', { conditionset: args.conditionset });
      }

      function secondButtonCallback() {
        that.sendEvent('ElementHighlightCustomButton', { conditionset: args.conditionset });
      }

      var actions = [
        {label: args.settings.dismiss_text, id: 'dismiss'}
      ];

      if (args.settings.second_button_text != '') {
        actions.push({label: args.settings.second_button_text, callback: secondButtonCallback});
      }

      var overlay = $('body').highlightOverlay(
        {
          dismissCallback: dismissCallback,
          exitOnOverlayClick: args.settings.dismiss_overlay_click,
          overlayOpacity: 0.7,
          actions: actions
       }
      );

      // determine if there is a passed selector available
      var selector = '';
      for(var i = args.events.length -1; i >= 0 ; i--){
        if (args.events[i].type == "replacementAdLoaded"){
          selector = args.events[i].detail.element;
          break;
        }
      }
      // if there is a passed selector, check whether configured to use it
      if (!(selector != '' && args.settings.use_passed_selector)) {
        selector = args.settings.selector;
      }

      if (args.settings.wait != 0){
        setTimeout(function(){
          if ($(selector).length){
            $(window).disablescroll({handleScrollbar: false});
            overlay.highlight(selector, args.settings.message);
          }
        }, args.settings.wait);
      }
      else {
        if ($(selector).length) {
          $(window).disablescroll({handleScrollbar: false});
          overlay.highlight(selector, args.settings.message);
        }
      }

    });
  }
});
