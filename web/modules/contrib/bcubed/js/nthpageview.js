new BCubedConditionPlugin({
    pageviewrecorded: false,
    condition: function(args){
      // if n is greater than or equal to two, use cookies to determine whether to fire
      if (args.settings.n >= 2) {

        var cookie = readCookie('bcubedpageviews');
        cookie = parseInt(cookie, 10);

        if (!this.pageviewrecorded) {
          // increment cookie only once per pageview
          cookie++;
          this.pageviewrecorded = true;
        }

        if (cookie) {
          if (cookie % args.settings.n == 0) {
            createCookie('bcubedpageviews', String(cookie), 365);
            return true;
          }
          else {
            createCookie('bcubedpageviews', String(cookie), 365);
            this.sendEvent('bcubedNonNthPageView', { conditionset: args.conditionset });
            return false;
          }
        }
        else {
          // cookie does not exist, create and return false
          createCookie('bcubedpageviews', '1', 365);
          this.sendEvent('bcubedNonNthPageView', { conditionset: args.conditionset });
          return false;
        }
      }
      // otherwise, fire every time
      else {
        return true;
      }
    },
  })
