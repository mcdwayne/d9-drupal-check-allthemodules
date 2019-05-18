new BCubedActionPlugin({
    action: function(args) {
      for(var i = args.settings.length -1; i >= 0 ; i--){
        if (args.settings[i].expires != 0){
          createCookie(args.settings[i].cookiename, args.settings[i].cookievalue, args.settings[i].expires);
        }
        else {
          createCookie(args.settings[i].cookiename, args.settings[i].cookievalue);
        }
      }
    }
  })
