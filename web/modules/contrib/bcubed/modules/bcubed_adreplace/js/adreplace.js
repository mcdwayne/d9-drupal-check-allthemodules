new BCubedActionPlugin({
    action: function(args) {
      var that = this;
      jQuery(function ($) {
        var zones = '';

        for(var i = args.settings.length -1; i >= 0 ; i--){
          if ($(args.settings[i].selector).length) {
            if(args.settings[i].bcubed) {
              zones = args.settings[i][args.pagetype + '_zone'] + '|' + zones;
            }
            else {
              zones = args.settings[i].zone + '|' + zones;
            }
          }
          else {
            args.settings.splice(i, 1);
          }
        }
        if (args.settings.length) {
          zones = zones.slice(0, -1);
          var adblocker = false;
          for(var i = args.events.length -1; i >= 0 ; i--){
            if (args.events[i].type == "adblockerDetected" || (args.events[i].type == "advancedAdblockerDetection" && args.events[i].detail.detected)){
              adblocker = true;
              break;
            }
          }
          if (adblocker){
            $.get('/' + args.strings.element_proxy + '?ids=' + zones, function(data) {
              for (var i = 0; i < args.settings.length; i++) {
                var newad = $(data[i]);
                $(args.settings[i].selector).append(newad);
                var adselector = args.settings[i].selector + '> a.'+ args.strings.link_class +' > img';
                $(adselector).on('load', function(e) {
                  that.sendEvent('replacementAdLoaded', { conditionset: args.conditionset, element: adselector });
                });
                $(args.settings[i].selector).on('click', 'a.'+ args.strings.link_class, function(e){
                  // ajax call to clicktracking callback
                  var banner = $(this).data(args.strings.banner_identifier.toLowerCase());
                  $.get('/'+ args.strings.click_tracking_proxy +'/' + banner, function(data){
                    // handle success or failure to track click
                  })
                });
              }
            });
          }
          else {
            // fetch ads directly if event != adblocker_detected
            url = 'https://bcubed.adtumbler.com/www/delivery/spcjson.php?zones=' + zones;
            if (window.location) url+="&amp;loc="+escape(window.location);
            if (document.referrer) url+="&amp;referer="+escape(document.referrer);
            url +=(document.charset ? '&amp;charset='+document.charset : (document.characterSet ? '&amp;charset='+document.characterSet : ''));
            $.get(url, function(data) {
              for (var i = 0; i < args.settings.length; i++) {
                var newad = $(data[i]['html']);
                $(args.settings[i].selector).append(newad);
                var adselector = args.settings[i].selector + ' a > img';
                $(adselector).on('load', function(e) {
                  that.sendEvent('replacementAdLoaded', { conditionset: args.conditionset, element: adselector });
                });
              }
            });
          }

        }
      });
    }
  })
