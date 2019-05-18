new BCubedConditionPlugin({
    condition: function(args){
      console.log(args);
      for (var event in args.events) {
        if (args.events.hasOwnProperty(event)) {
          if (typeof args.events[event].detail !== undefined && args.events[event].detail !== null && typeof args.events[event].detail.conditionset !== undefined) {
            if (args.events[event].detail.conditionset == args.settings.condition_set) {
              return true;
            }
            else {
              return false;
            }
          }
        }
      }
      return true;
    },
  })
