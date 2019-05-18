new BCubedActionPlugin({
  action: function(args) {
    if (args.settings.proxy == true) {
      var url = '/' + args.strings.proxy + '?ec=' + encodeURIComponent(args.settings.category) + '&ea=' + encodeURIComponent(args.settings.action) + '&dl=' + encodeURIComponent(document.location.origin + document.location.pathname + document.location.search) + '&dt=' + encodeURIComponent(document.title) + '&dr=' + encodeURIComponent(document.referrer);
      if (args.settings.label != '') url += "&el=" + encodeURIComponent(args.settings.label);
      if (args.settings.interaction == false) url += "&ni=1";
      var request = new XMLHttpRequest();
      request.open('GET', url, true);
      request.send();
    }
    else {
      if (args.settings.interaction == false) {
        if(typeof ga !=='undefined'){
          ga('send','event',args.settings.category, args.settings.action, args.settings.label, {'nonInteraction':1});
        } else if(typeof _gaq !=='undefined'){
          _gaq.push(['_trackEvent',args.settings.category, args.settings.action, args.settings.label, true]);
        }
      }
      else {
        if(typeof ga !=='undefined'){
          ga('send','event',args.settings.category, args.settings.action, args.settings.label);
        } else if(typeof _gaq !=='undefined'){
          _gaq.push(['_trackEvent',args.settings.category, args.settings.action, args.settings.label]);
        }
      }
    }
  }
});
