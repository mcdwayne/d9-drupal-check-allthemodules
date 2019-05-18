(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Creates class to validate Personalization Rules.
   */
  var personaContentClass = function(options) {

    /**
     * Can access this.method
     * inside other methods using
     * root.method()
     */
    var root = this;
    var segment_i_valid = -1;
    var segment_id = -1;
    var pathCurrent = '';
    var key = '';

    /**
     * Debug encapsulation for debugging regions.
     */
    this.consoleLog = function() {
      if (typeof drupalSettings.personacontent !== 'undefined') {
        if (typeof drupalSettings.personacontent.screen_debug_html !== 'undefined') {
          if (drupalSettings.personacontent.screen_debug_html == 1) {
            console.log.apply(null, arguments);
          }
        }
      }
    }

    /**
     * Returns a valid segment i found.
     */
    this.segmentValidGet = function() {
      if (segment_i_valid > 0) {
        return segment_i_valid;
      }

      return false;
    }

    /**
     * Returns a valid segment i found.
     */
    this.segmentValidIdGet = function() {
      if (segment_id > 0) {
        return segment_id;
      }

      return false;
    }

    /**
     * Constructor
     */
    this.construct = function(options){
      $.extend(vars, options);
    };

    /**
     * Init function.
     */
    this.init = function () {
      if (typeof drupalSettings.personacontent === 'undefined') {
        return false;
      }

      if (typeof drupalSettings.personacontent.segments === 'undefined') {
        return false;
      }

      var segment_i = -1;
      var segments = drupalSettings.personacontent.segments.slice(0);

      // Check for cache.
      pathCurrent = root.getUrlPath();
      key = 'personacontent--html--2--' + pathCurrent;
      var segment_cache = localStorage.getItem(key);

      if (false && segment_cache != null) {
        segment_cache = JSON.parse(segment_cache);
        var now = Math.round(new Date().getTime()/1000);

        if (parseInt(segment_cache.expire, 10) >= now) {
          root.markSegmentHtml(segment_cache.i);
          return true;
        }
      }

      // No cache.
      root.segmentProcess(segments, segment_i);
    }

    /**
     * Saves the found valid segment.
     */
    this.segmentValidFound = function(segment_i) {
      var segment = drupalSettings.personacontent.segments[segment_i];
      root.segment_i_valid = segment_i;

      // Store in cache the choosen segment.
      var expire = Math.round(new Date().getTime()/1000) + 3600;
      var cache = JSON.stringify({'i': segment_i, 'expire': expire});
      localStorage.setItem(key, cache);

      root.markSegmentHtml(segment_i);
  
      return true;
    }

    /**
     * Marks personacontent segment at html tag.
     */
    this.markSegmentHtml = function(segment_i) {
      segment_i_valid = segment_i;
      var segmentValid = drupalSettings.personacontent.segments[segment_i];
      segment_id = segmentValid.id;
      $('html').addClass('personacontent-segment-' + segmentValid.id);
      $('html').attr('personacontent-segment', segmentValid.id);

      window.personaAnalytics.log(segmentValid.title);

      window.personaDebug.markSegmentWinner(segmentValid);

      Drupal.attachBehaviors();
    }

    /**
     * Process segments in queued mode.
     */
    this.segmentProcess = function(segments, segment_i) {
      segment_i++;
  
      // Process first segment available in array of segments.
      var segment = segments.shift();
      var segmentL = JSON.parse(JSON.stringify(segment));
      root.consoleLog('%c ANALYZING ' + segmentL.title, 'background: white; color: black; font-weight: bold;', segmentL);
  
      // If no rules, then this segment is valid already.
      segment.rules_count = parseInt(segment.rules.length, 10);
      if (segment.rules_count == 0) {
        window.personaDebug.markRuleWinner({'title': 'segment-without-rules'});
        return root.segmentValidFound(segment_i);
      }
  
      // Process the segment item.
      segment.rules_validation = [];
  
      // Process.
      root.segmentProcessRules(segment, segments, segment_i);
    }

    /**
     * Process one segment out of the region.
     */
    this.segmentProcessRules = function (segment, segments, segment_i) {
      // Process first rule available in array of rules.
      var rule = segment.rules.shift();
      var ruleL = JSON.parse(JSON.stringify(rule));
  
      switch (rule.type) {
        // Url comparisons.
        case 'url:current':
          root.validateUrlCurrent(rule, segment, segments, segment_i);
          break;
  
        case 'url:referer':
          root.validateUrlReferer(rule, segment, segments, segment_i);
          break;

        case 'url:history':
          root.validateUrlHistory(rule, segment, segments, segment_i);
          break;

        case 'event:click':
          root.validateEvent(rule, segment, segments, segment_i);
          break;
  
        case 'location:city':
        case 'location:state':
        case 'location:region':
        case 'location:country':
        case 'location:zipcodes':
          root.validateLocation(rule, segment, segments, segment_i);
          break;

        default:
          // Verify if there are more rules to validate.
          var result = false;
          segment.rules_validation.push(result);

          if (parseInt(segment.rules.length) > 0) {
            root.segmentProcessRules(segment, segments, segment_i);
          }
          else {
            root.segmentProcessRulesResult(segment, segments, segment_i, ruleL);
          }
          break;
      }
    }

    /**
     * Validates a current url rule.
     */
    this.validateUrlCurrent = function (rule, segment, segments, segment_i) {
      var ruleL = JSON.parse(JSON.stringify(rule));
      var path = root.getUrlPath();
      var result = root.validateUrlOperator(rule, path);
      segment.rules_validation.push(result);
  
      // Verify if there are more rules to validate.
      if (parseInt(segment.rules.length) > 0) {
        root.segmentProcessRules(segment, segments, segment_i);
      }
      else {
        // Evaluate rules_matches.
        root.segmentProcessRulesResult(segment, segments, segment_i, ruleL);
      }
    }

    /**
     * Validates a referer url rule.
     */
    this.validateUrlReferer = function (rule, segment, segments, segment_i) {
      var ruleL = JSON.parse(JSON.stringify(rule));
      var path = root.getUrlRefererPath();
      var result = root.validateUrlOperator(rule, path);
      segment.rules_validation.push(result);
  
      // Verify if there are more rules to validate.
      if (parseInt(segment.rules.length) > 0) {
        root.segmentProcessRules(segment, segments, segment_i);
      }
      else {
        root.segmentProcessRulesResult(segment, segments, segment_i, ruleL);
      }
    }

    /**
     * Validates a history url rule.
     */
    this.validateUrlHistory = function (rule, segment, segments, segment_i) {
      var ruleL = JSON.parse(JSON.stringify(rule));
      // Get History.
      var result = window.personaHistory.searchRule(rule);
      segment.rules_validation.push(result);
  
      // Verify if there are more rules to validate.
      if (parseInt(segment.rules.length) > 0) {
        root.segmentProcessRules(segment, segments, segment_i);
      }
      else {
        root.segmentProcessRulesResult(segment, segments, segment_i, ruleL);
      }
    }

    /**
     * Validates an Event.
     */
    this.validateEvent = function (rule, segment, segments, segment_i) {
      var ruleL = JSON.parse(JSON.stringify(rule));
      // Get events.
      var result = window.personaEvents.searchRule(rule);
      segment.rules_validation.push(result);
  
      // Verify if there are more rules to validate.
      if (parseInt(segment.rules.length) > 0) {
        root.segmentProcessRules(segment, segments, segment_i);
      }
      else {
        root.segmentProcessRulesResult(segment, segments, segment_i, ruleL);
      }
    }

    /**
     * Get the current url path.
     */
    this.getUrlPath = function() {
      var path = window.location.pathname;
      var query = window.location.search;
      //var fragment = window.location.hash;
      //return path + query + fragment;

      return path + query;
    }

    /**
     * Get the referer url path.
     */
    this.getUrlRefererPath = function() {
      var referer = document.referrer;
      if (referer.length > 0) {
        var path = referer.match(/\/\/.*?\/(.*?)\/?(\?.*)?$/)[1];
        var matches = path.match(/^\/?(.*)/);
  
        return matches[1];
      }

      return '';
    }

    /**
     * Validates a path apply to the operator rule.
     */
    this.validateLocationOperator = function(args) {
      var rule = args.rule;
      var string = root.getFirstValue(rule.values);
      var path = args.location;
  
      switch (rule.operator) {
        case 'starts':
          return path.startsWith(string);
          break;
  
        case 'ends':
          return path.endsWith(string);
          break;
  
        case 'contains':
          return path.includes(string);
          break;
      }
  
      return false;
    }

    /**
     * Validates a path apply to the operator rule.
     */
    this.validateUrlOperator = function(rule, path) {
      var string = root.getFirstValue(rule.values);
  
      switch (rule.operator) {
        case 'starts':
          return path.startsWith(string);
          break;
  
        case 'ends':
          return path.endsWith(string);
          break;
  
        case 'contains':
          return path.includes(string);
          break;
      }
  
      return false;
    }

    /**
     * Get first value out of the values textarea.
     */
    this.getFirstValue = function(values) {
      var string = values + '';
      string = string.split("\n");
      string = string[0];
      return string;
    }

    /**
     * Save Location at cache.
     */
    this.cacheLocation = function(latlng, location) {
      // Store in cache the choosen segment.
      var expire = Math.round(new Date().getTime()/1000) + 3600;
      var location_cache = {
        'lat': latlng.lat, 
        'lng': latlng.lng,
        'location': location,
        'expire': expire
      };
      
      localStorage.setItem('personacontent--location', JSON.stringify(location_cache));
    }

    /**
     * Get current location and caches it.
     */
    this.locationGet = function(args, successCallback, failCallback) {
      var location;
      args.valid = false;

      // Look at cache.
      var location_cache = localStorage.getItem('personacontent--location');
      if (typeof location_cache != 'undefined' && location_cache != null) {
        location_cache = JSON.parse(location_cache);
        var now = Math.round(new Date().getTime()/1000);

        if (parseInt(location_cache.expire, 10) >= now) {
          // True Statement.
          args.location = location_cache.location;

          // True statement.
          if (successCallback(args)) {
            args.valid = true;
            failCallback(args);
          }
          else {
            failCallback(args);
          }
          return;
        }
      }

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
                location = results[1];

                // Save to cache.
                root.cacheLocation(latlng, location.formatted_address);
                args.location = location.formatted_address;

                // True statement.
                if (successCallback(args)) {
                  args.valid = true;
                  failCallback(args);
                }
                else {
                  failCallback(args);
                }
              }
              else {
                // False statement.
                failCallback(args);
              }
            }
            else {
              // False statement.
              failCallback(args);
            }
          });
        }, function() {
          // False statement.
          failCallback(args);
        });
      }
      else {
        // False statement.
        failCallback(args);
      }
    }

    /**
     * Validates Location Rule.
     */
    this.validateLocation = function(rule, segment, segments, segment_i) {
      // Load Args.
      var args = {
        'rule': rule,
        'segment': segment,
        'segments': segments,
        'segment_i': segment_i
      };
  
      root.locationGet(args, root.validateLocationOperator, root.validateLocationNext);
    }

    /**
     * When validating Location proceeds to the next rule.
     */
    this.validateLocationNext = function(args) {
      var valid = args.valid;
      var segment = args.segment;
      var segments = args.segments;
      var segment_i = args.segment_i;
      var ruleL = JSON.parse(JSON.stringify(args.rule));

      segment.rules_validation.push(valid);
  
      // Verify if there are more rules to validate.
      if (parseInt(segment.rules.length) > 0) {
        root.segmentProcessRules(segment, segments, segment_i);
      }
      else {
        root.segmentProcessRulesResult(segment, segments, segment_i, ruleL);
      }
    }

    /**
     * Evaluate all rules results after validating all rules.
     */
    this.segmentProcessRulesResult = function(segment, segments, segment_i, ruleL) {
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
        window.personaDebug.markRuleWinner(ruleL);
        root.segmentValidFound(segment_i);
      }
      else if (segments.length > 0) {
        root.segmentProcess(segments, segment_i);
      }
    }

  }

  window.personaContent = new personaContentClass();

})(jQuery, Drupal, drupalSettings);
