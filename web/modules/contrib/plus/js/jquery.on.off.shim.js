/**
 * @file
 * jQuery On/Off Shim (https://github.com/MoonScript/jquery-on-off-shim)
 */
(function ($) {
  'use strict';

  var undelegateOrUnbind = function (events, selector, handler) {
    return ($.type(selector) !== 'string') ? this.unbind(events, handler) : this.undelegate(selector, events, handler);
  };

  // Stub in "on/off" for jQuery >= 1.4.3 and < 1.7
  if ($ && $.fn && !$.fn.on && $.fn.delegate && ($.fn.jquery !== '1.4.2')) {
    $.fn.on = function (events, selector, data, handler) {
      var $collection = this;
      var useBind = $.type(selector) !== 'string';
      // Handle either a string of event names or a map of events-and-callbacks
      // for the 1st parameter
      if ($.isPlainObject(events)) {
        if (useBind) {
          return $collection.bind(events);
        }
        if ($.isPlainObject(data)) {
          $.each(events, function (eventName, callback) {
            $collection.delegate(selector, eventName, data, callback);
          });
          return $collection;
        }
        return $collection.delegate(selector, events);
      }
      if (useBind) {
        return $.fn.bind.apply($collection, arguments);
      }
      return $collection.delegate(selector, events, data, handler);
    };
    $.fn.off = function (events, selector, handler) {
      var $collection = this;
      if ($.isPlainObject(events)) {
        $.each(events, function (eventName, callback) {
          undelegateOrUnbind.apply($collection, [eventName, selector, callback]);
        });
        return $collection;
      }
      return undelegateOrUnbind.apply($collection, arguments);
    };
  }

})(jQuery);
