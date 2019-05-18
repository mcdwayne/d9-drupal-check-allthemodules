(function ($, drupalSettings) {
  Drupal.behaviors.FlipClock = {
    attach: function (context, settings) {
      // Init all clocks.
      for (id in settings.flipClock.instances) {
        _flipclock_init(id, settings.flipClock.instances[id], context);
      }
    }
  };

  function _flipclock_init(id, optionset, context) {
    var timestamp = optionset.timestamp * 1000;
    var seconds_to_go = (new Date(timestamp).getTime() - new Date().getTime()) / 1000;
    // Init timer.
    $('#' + id).FlipClock(seconds_to_go, optionset.options);
  }
})(jQuery, drupalSettings);