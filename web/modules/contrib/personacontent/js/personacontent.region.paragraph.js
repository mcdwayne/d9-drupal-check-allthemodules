(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Global variables.
   */
  var keyCache = 'personacontent--region--paragraph-5-';

  Drupal.behaviors.personacontentRegionParagraph = {
    attach: function (context, settings) {
      $('html', context).once('personaDebug').each(function () {
        window.personaDebug.switchDebug(true);
      });

      $(context).find('.personacontent--region--paragraph').once().each(function() {
        var region_id = $(this).attr('data-personacontent-region-paragraph-id');
        if (typeof region_id !== typeof undefined && region_id !== false) {
          // Check for cache.
          var key = keyCache + region_id;
          var segment_cache = localStorage.getItem(key);
          if (false && segment_cache != null) {
            segment_cache = JSON.parse(segment_cache);
            var now = Math.round(new Date().getTime()/1000);
  
            if (parseInt(segment_cache.expire, 10) >= now) {
              var segment = drupalSettings.personacontent_paragraph[region_id][segment_cache.i];
              var segmentL = JSON.parse(JSON.stringify(segment));

              personacontentRegionSegmentRender(segment.element, this, region_id, segment_cache.i, segmentL);
              return true;
            }
          }

          var segments = drupalSettings.personacontent_paragraph[region_id];
          var segment_i = -1;
          if (segments.length > 0) {
            consoleLog('personacontent--region--paragraph - Processing ' + segments.length + ' Segments');
            personacontentRegionSegmentProcess(segments, this, region_id, segment_i);
          }
        }
      });
    }
  };

  /**
   * Debug encapsulation for debugging regions.
   */
  function consoleLog() {
    if (typeof drupalSettings.personacontent !== 'undefined') {
      if (typeof drupalSettings.personacontent.screen_debug !== 'undefined') {
        if (drupalSettings.personacontent.screen_debug == 1) {
          console.log.apply(null, arguments);
        }
      }
    }
  }

  function logStyle(style) {
    switch (style) {
      case '1':
        return 'padding: 5px; background: yellow; color: black; font-weight: bold;';

      case '1.2':
        return 'padding: 5px; background: black; color: yellow; font-weight: bold;';

      case '1.3':
        return 'margin-left: 10px; padding: 5px; background: black; color: yellow; font-weight: bold;';

      case '2':
        return 'padding: 5px; background: aqua; color: black; font-weight: bold;';

      case '2.2':
        return 'padding: 5px 5px 5px 0; background: black; color: aqua; font-weight: bold;';
    }
  }

  /**
   * Process region element by id in queued mode.
   */
  function personacontentRegionSegmentProcess(segments, region, region_id, segment_i) {
    segment_i++;

    // Process first segment available in array of segments.
    var segment = segments.shift();
    var segmentL = JSON.parse(JSON.stringify(segment));
    consoleLog('%c SEGMENT ' + segment_i + ' %c' + segmentL.title, logStyle('1'), logStyle('2'), segmentL);

    // If no rules, then this segment is valid already.
    if (segment.rules.length == 0) {
      consoleLog('%c - %c No rules, found valid by default', logStyle('1'), logStyle('1.2'));
      return personacontentRegionSegmentRender(segment.element, region, region_id, segment_i, segmentL);
    }

    // Process the segment item.
    segment.rules_validation = [];
    segment.rules_count = parseInt(segment.rules.length, 10);

    // Process.
    personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL);
  }

  /**
   * Process one segment out of the region.
   */
  function personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL) {
    // Process first rule available in array of rules.
    var rule = segment.rules.shift();
    var ruleL = JSON.parse(JSON.stringify(rule));
    consoleLog('%c * Rule ', logStyle('1.2'), ruleL);

    switch (rule.type) {
      // Url comparisons.
      case 'url:current':
        personacontentRegionElementIsSegmentValidUrlCurrent(rule, segment, segments, region, region_id, segment_i, segmentL);
        break;

      case 'url:referer':
        personacontentRegionElementIsSegmentValidUrlReferer(rule, segment, segments, region, region_id, segment_i, segmentL);
        break;

      case 'url:history':
        personacontentRegionElementIsSegmentValidUrlHistory(rule, segment, segments, region, region_id, segment_i, segmentL);
        break;

      case 'event:click':
        personacontentRegionElementIsSegmentValidEvent(rule, segment, segments, region, region_id, segment_i, segmentL);
        break;

      case 'location:city':
      case 'location:state':
      case 'location:region':
      case 'location:country':
      case 'location:zipcodes':
        personacontentRegionElementIsSegmentValidLocation(rule, segment, segments, region, region_id, segment_i, segmentL);
        break;

      default:
        // Verify if there are more rules to validate.
        var result = false;
        segment.rules_validation.push(result);
        if (parseInt(segment.rules.length) > 0) {
          consoleLog('%c --- %c Result = %c FALSE %c Skiping, Validating next rule', logStyle('1'), logStyle('1.2'), logStyle('2.2'), logStyle('1.2'), false);
          personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL);
        }
        else {
          //personacontentRegionSegmentProcess.
          consoleLog('%c --- %c Result = %c FALSE %c Skiping, Checking Rules Summary', logStyle('1'), logStyle('1.2'), logStyle('2.2'), logStyle('1.2'), false);
          personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i, segmentL);
        }
        break;
    }
  }

  /**
   * Evaluate all rules results after validating all rules.
   */
  function personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i, segmentL) {
    // Evaluate rules_matches.
    var valid = true;

    $(segment.rules_validation).each(function(i, result) {
      if (result) {
        // If we need only one rule to apply, escape each().
        if (segment.rules_matches == 'any') {
          valid = true;
          return false;
        }
        else if (segment.rules_matches == 'none') {
          valid = false;
          return false;
        }
      }
      else {
        valid = false;
  
        // If all rules needs to apply and one fails, escape each().
        if (segment.rules_matches == 'all') {
          return false;
        }
      }
    });

    if (valid) {
      personacontentRegionSegmentRender(segment.element, region, region_id, segment_i, segmentL);
    }
    else if (segments.length > 0) {
      personacontentRegionSegmentProcess(segments, region, region_id, segment_i);
    }
  }

  /**
   * Process one item and reduces segments array.
   */
  function personacontentRegionSegmentRender(element, region, region_id, segment_i, segment) {
    // Render the element into page.
    $(region).find('.personacontent-render').html(element);
    Drupal.attachBehaviors($(region).find('.personacontent-render')[0]);

    // Store in cache the choosen segment.
    var key = keyCache + region_id;
    var expire = Math.round(new Date().getTime()/1000) + 3600;
    var cache = JSON.stringify({'i': segment_i, 'expire': expire});
    localStorage.setItem(key, cache);

    // Screen.
    window.personaDebug.screenPrint(segment);

    $.event.trigger({
    	type: "personacontent-region-paragraph-ready",
    });

    return true;
  }

  /**
   * Validates Location Rule.
   */
  function personacontentRegionElementIsSegmentValidLocation(rule, segment, segments, region, region_id, segment_i, segmentL) {
    var location;

    // Get current location.
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        var latlng = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };

        var geocoder = new google.maps.Geocoder;
        geocoder.geocode({'location': latlng}, function(results, status) {
          if (status === 'OK') {
            if (results[1]) {
              var string = personacontentRegionGetFirstValue(rule.values);
              location = results[1];
              if (personacontentRegionElementIsSegmentValidUrlOperator(rule, location.formatted_address)) {
                personacontentRegionElementIsSegmentValidLocationNext(true, segment, segments, region, region_id, segment_i, segmentL);
              }
              else {
                personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i, segmentL);
              }
            }
            else {
              personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i, segmentL);
            }
          }
          else {
            personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i, segmentL);
          }
        });
        
      }, function() {
        personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i, segmentL);
      });
    }
    else {
      personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i, segmentL);
    }
  }

    /**
   * When validating Location proceeds to the next rule.
   */
  function personacontentRegionElementIsSegmentValidLocationNext(valid, segment, segments, region, region_id, segment_i, segmentL) {
    segment.rules_validation.push(valid);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL);
    }
    else {
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i, segmentL);
    }
  }

  /**
   * Validates a referer url rule.
   */
  function personacontentRegionElementIsSegmentValidUrlReferer(rule, segment, segments, region, region_id, segment_i, segmentL) {
    var path = personacontentRegionGetUrlRefererPath();
    var result = false;
    if (path) {
      result = personacontentRegionElementIsSegmentValidUrlOperator(rule, path);
    }

    var resultString = result ? 'TRUE' : 'FALSE';
    consoleLog('%c --- Result =%c' + resultString, logStyle('1.2'), logStyle('2.2'));
    segment.rules_validation.push(result);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL);
    }
    else {
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i, segmentL);
    }
  }

  /**
   * Validates a current url rule.
   */
  function personacontentRegionElementIsSegmentValidUrlCurrent(rule, segment, segments, region, region_id, segment_i, segmentL) {
    var path = personacontentRegionGetUrlPath();
    var result = personacontentRegionElementIsSegmentValidUrlOperator(rule, path);
    var resultString = result ? 'TRUE' : 'FALSE';
    consoleLog('%c - Rule Result =%c' + resultString, logStyle('1.2'), logStyle('2.2'));
    segment.rules_validation.push(result);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL);
    }
    else {
      // Evaluate rules_matches.
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i, segmentL);
    }
  }

  /**
   * Validates a history url rule.
   */
  function personacontentRegionElementIsSegmentValidUrlHistory(rule, segment, segments, region, region_id, segment_i, segmentL) {
    // Get History.
    var result = window.personaHistory.searchRule(rule);
    var resultString = result ? 'TRUE' : 'FALSE';
    consoleLog('%c - Rule Result =%c' + resultString, logStyle('1.2'), logStyle('2.2'));
    segment.rules_validation.push(result);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL);
    }
    else {
      // Evaluate rules_matches.
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i, segmentL);
    }
  }

  /**
   * Validates an Event.
   */
  function personacontentRegionElementIsSegmentValidEvent(rule, segment, segments, region, region_id, segment_i, segmentL) {
    // Get events.
    var result = window.personaEvents.searchRule(rule);
    var resultString = result ? 'TRUE' : 'FALSE';
    consoleLog('%c - Rule Result =%c' + resultString, logStyle('1.2'), logStyle('2.2'));
    segment.rules_validation.push(result);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i, segmentL);
    }
    else {
      // Evaluate rules_matches.
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i, segmentL);
    }
  }

  /**
   * Get first value out of the values textarea.
   */
  function personacontentRegionGetFirstValue(values) {
    var string = values + '';
    string = string.split("\n");
    string = string[0];
    return string;
  }

  /**
   * Validates a path apply to the operator rule.
   */
  function personacontentRegionElementIsSegmentValidUrlOperator(rule, path) {
    path = $.trim(path).replace('?', '\\?').replace('&', '\\&');
    var result = false;
    $(rule.values).each(function (i, value) {
      var string = $.trim(value);
      var resultString = result ? 'TRUE' : 'FALSE';
      consoleLog('%c > Rule ValidUrlOperator: string=%c"' + string + '"%cvs path=%c"' + path + '"', logStyle('1.3'), logStyle('2.2'), logStyle('1.2'), logStyle('2.2'));

      switch (rule.operator) {
        case 'starts':
          result = path.startsWith(string);
          break;
  
        case 'ends':
          result = path.endsWith(string);
          break;
  
        case 'contains':
          result = path.includes(string);
          break;
      }

      if (result !== false) {
        return false;
      }
    });

    return result;
  }

  /**
   * Get the referer url path.
   */
  function personacontentRegionGetUrlRefererPath() {
    if (document.referrer == '') {
      return false;
    }

    var referer = document.referrer;
    var matches = referer.match(/^\/?(.*)/);
    var path = matches[1];
    path = decodeURIComponent(path.replace(/\+/g, ' '));

    return path;
  }

  /**
   * Get the current url path.
   */
  function personacontentRegionGetUrlPath() {
    var path = window.location.pathname + window.location.search;
    var matches = path.match(/^\/?(.*)/);
    path = matches[1];
    path = decodeURIComponent(path.replace(/\+/g, ' '));

    return path;
  }
  

})(jQuery, Drupal, drupalSettings);
