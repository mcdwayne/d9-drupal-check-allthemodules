new BCubedConditionPlugin({
    condition: function(args) {
      for(var i = args.settings.length -1; i >= 0 ; i--){
        var cookie = readCookie(args.settings[i].cookiename);
        var compareto = args.settings[i].cookievalue;

        if (cookie) {
          switch(args.settings[i].operator) {
            case "equals":
              if (cookie == compareto)
                break;
              else return false;
            case "notequal":
              if (cookie != compareto)
                break;
              else return false;
            case "lessthan":
              cookie = parseFloat(cookie);
              compareto = parseFloat(compareto);
              if (cookie < compareto)
                break;
              else return false;
            case "greaterthan":
              cookie = parseFloat(cookie);
              compareto = parseFloat(compareto);
              if (cookie > compareto)
                break;
              else return false;
          }
        }
        else {
          // cookie does not exist, return true or false according to config
          if (args.settings[i].notfoundbehavior == 1)
              return false;
        }
      }
      // all conditions met, return true
      return true;
    }
  })
