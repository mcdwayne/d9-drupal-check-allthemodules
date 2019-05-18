(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Creates class to store Events.
   */
  var personaEventsClass = function(options) {

    /**
     * Can access this.method
     * inside other methods using
     * root.method()
     */
    var root = this;
    var key = 'personacontent--events-2';
    var events = new Array();
    var timeagoInstance = timeago();

    /**
     * Constructor
     */
    this.construct = function(options){
      $.extend(vars, options);
    };

    /**
     * Init script.
     */
    this.init = function () {
      events = root.getEvents();
      //root.screenDebug();
    }

    /**
     * Adds Events Debug.
     */
    this.screenDebug = function() {
      console.log('%cscreen_debug = %c' + drupalSettings.personacontent.screen_debug, 'color: inherit;', 'color: yellow;');
      if (drupalSettings.personacontent.screen_debug == 0) {
        return true;
      }

      var output = root.screenTheme();
      $('body').prepend(output);

      // Print all.
      root.screenPrint();
    }

    /**
     * Prints active segment on Screen.
     */
    this.screenPrint = function () {
      var container = $('#personaEventsDebugScreen .current-events .entries > ul');
      var itemTheme = '';

      $(events).each(function(i, event_item) {
        itemTheme = root.screenItemTheme(event_item);
        $(container).append(itemTheme);
      });
    }

    /**
     * Retruns Screen Item output.
     */
    this.screenItemTheme = function(raw) {
      var ago = timeagoInstance.format(parseInt(raw.time * 1000, 10));
      var output = [
      '<li class="event-item hover-here">',
        '<dicv class="inner-wrapper">',
          '<div class="time">' + ago + '</div>',
          '<div class="value" title="' + raw.value + '">' + raw.value + '</div>',
        '</div>',
      '</li>'].join('');
      return output;
    }

    /**
     * Returns Screen Output.
     */
    this.screenTheme = function () {
      var output = [
      '<div id="personaEventsDebugScreen">',
        '<div class="container-fluid">',
          '<div class="current-events">',
            '<h2>Events</h2>',
            '<a href="#" class="opener"><span class="open-label">Open</span><span class="close-label">Close</span></a>',
            '<div class="window">',
              '<div class="entries">',
                '<h3>Entries:</h3>',
                '<ul></ul>',
              '</div>',
            '</div>',
          '</div>',
        '</div>',
      '</div>'].join('');

      return output;
    }

    /**
     * Event for personaContentEvent.
     */
    $(document).on('personaContentEvent', function(e, value) {
      root.registerEvent(value);
    });

    $(document).on('click', 'a[data-click-event-value]', function (e) {
      var value = $(this).attr('data-click-event-value');
      $(document).trigger('personaContentEvent', [value]);
    });

    /**
     * Stores the Event into our own cookie.
     */
    this.registerEvent = function (event_value) {
      var now = Math.round(new Date().getTime()/1000);

      // Search for existing history path.
      var iFound = root.searchEvent(event_value);
      if (iFound == false) {
        // This means its not found.
        events.push({
          'value': event_value,
          'time': now
        });
        var events_cache_json = JSON.stringify(events);
        localStorage.setItem(key, events_cache_json);
      }
      // Update existing path.
      else {
        events[(iFound - 1)].time = now;
      }
    }

    /**
     * Search for URL in existing cache.
     */
    this.searchEvent = function (searchedEvent) {
      searchedEvent = $.trim(searchedEvent);

      if (searchedEvent == 'first-time') {
        return window.personaHistory.firstTime();
      }

      if (parseInt(events.length) == 0) {
        return false;
      }

      searchedEvent = window.personaLib.escapeRegExp(searchedEvent);

      // Search for path.
      var iFound = false;
      $(events).each(function(i, event_item) {
        var re = new RegExp('^' + searchedEvent + '$', 'g');
        var event_value = $.trim(event_item.value);
        var result = event_value.match(re);

        var log = "%cdoes %c" + event_value + ' %cequals %c' + searchedEvent + '%c?';

        if (result == null) {
          //console.log(log + ' No, its null', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
          return true;
        }

        if (result.length > 0) {
          //console.log(log + ' Yes', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
          iFound = (i + 1);
          return false;
        }

        //console.log(log + ' No', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
      });

      return iFound;
    }

    /**
     * Get HistoryPath.
     */
    this.getEvents = function () {
      var events_cache = localStorage.getItem(key);

      // Create array if doesn't exists.
      if (typeof events_cache != 'undefined' && events_cache != null) {
        events_cache = JSON.parse(events_cache);
      }
      // Create array if doesn't exists.  
      else {
        events_cache = new Array();
        var events_cache_json = JSON.stringify(events_cache);
        localStorage.setItem(key, events_cache_json);
      }
      
      return events_cache;
    }

    /**
     * Process a rule.
     */
    this.searchRule = function(rule_raw) {
      var ruleL = JSON.parse(JSON.stringify(rule_raw));
      var result = false;

      $(ruleL.values).each(function (i, value) {
        if (root.searchEvent(value) !== false) {
          result = true;
          return false;
        }
      });

      return result;
    }

  }

  window.personaEvents = new personaEventsClass();
  window.personaEvents.init();

})(jQuery, Drupal, drupalSettings);
