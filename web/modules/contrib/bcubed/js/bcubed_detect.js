new BCubedEventGeneratorPlugin({
  init: function(arguments) {
    if (document.getElementById(arguments.strings.adblocker_bait)) {
      this.sendEvent('adblockerNotDetected');
    }
    else {
      this.sendEvent('adblockerDetected');
    }
  }
});
