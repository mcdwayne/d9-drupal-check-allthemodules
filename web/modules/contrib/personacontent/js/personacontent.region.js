(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Queued variables.
   */
  var keyCache = 'personacontent--region--2';

  Drupal.behaviors.personacontentRegion = {
    attach: function (context, settings) {
      $(context).find('.personacontent--region').once().each(function() {
        var region_id = $(this).attr('data-personacontent-region-id');
        if (typeof region_id !== typeof undefined && region_id !== false) {
          // Check for cache.
          var key = keyCache + region_id;
          var segment_cache = localStorage.getItem(key);
          if (segment_cache != null) {
            segment_cache = JSON.parse(segment_cache);
            var now = Math.round(new Date().getTime()/1000);
  
            if (parseInt(segment_cache.expire, 10) >= now) {
              var element = drupalSettings.personacontent[region_id][segment_cache.i];
              personacontentRegionSegmentRender(element.element, this);
              return true;
            }
          }
  
          var segments = drupalSettings.personacontent[region_id];
          var segment_i = -1;
          if (segments.length > 0) {
            personacontentRegionSegmentProcess(segments, this, region_id, segment_i);
          }
        }
      });
    }
  };

  /**
   * Process region element by id in queued mode.
   */
  function personacontentRegionSegmentProcess(segments, region, region_id, segment_i) {
    segment_i++;

    // Process first segment available in array of segments.
    var segment = segments.shift();

    // If no rules, then this segment is valid already.
    if (segment.rules.length == 0) {
      return personacontentRegionSegmentRender(segment.element, region, region_id, segment_i);
    }

    // Process the segment item.
    segment.rules_validation = [];
    segment.rules_count = parseInt(segment.rules.length, 10);

    // Debug.
    var melapelas = JSON.parse(JSON.stringify(segment));

    // Process.
    personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i);
  }

  /**
   * Process one segment out of the region.
   */
  function personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i) {
    // Process first rule available in array of rules.
    var rule = segment.rules.shift();

    switch (rule.type) {
      // Url comparisons.
      case 'url:current':
        personacontentRegionElementIsSegmentValidUrlCurrent(rule, segment, segments, region, region_id, segment_i);
        break;

      case 'url:referer':
        personacontentRegionElementIsSegmentValidUrlReferer(rule, segment, segments, region, region_id, segment_i);
        break;

      case 'location:city':
      case 'location:state':
      case 'location:region':
      case 'location:country':
      case 'location:zipcodes':
        personacontentRegionElementIsSegmentValidLocation(rule, segment, segments, region, region_id, segment_i);
        break;
    }
  }

  /**
   * Evaluate all rules results after validating all rules.
   */
  function personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i) {
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
      personacontentRegionSegmentRender(segment.element, region, region_id, segment_i);
    }
    else if (segments.length > 0) {
      personacontentRegionSegmentProcess(segments, region, region_id, segment_i);
    }
  }

  /**
   * Process one item and reduces segments array.
   */
  function personacontentRegionSegmentRender(element, region, region_id, segment_i) {
    // Render the element into page.
    $(region).find('.personacontent-render').html(element);
    Drupal.attachBehaviors($(region).find('.personacontent-render')[0]);

    // Store in cache the choosen segment.
    var key = keyCache + region_id;
    var expire = Math.round(new Date().getTime()/1000) + 3600;
    var cache = JSON.stringify({'i': segment_i, 'expire': expire});
    localStorage.setItem(key, cache);

    $.event.trigger({
    	type: "personacontent-region-ready",
    });

    return true;
  }

  /**
   * Validates Location Rule.
   */
  function personacontentRegionElementIsSegmentValidLocation(rule, segment, segments, region, region_id, segment_i) {
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
                personacontentRegionElementIsSegmentValidLocationNext(true, segment, segments, region, region_id, segment_i);
              }
              else {
                personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i);
              }
            }
            else {
              personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i);
            }
          }
          else {
            personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i);
          }
        });
        
      }, function() {
        personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i);
      });
    }
    else {
      personacontentRegionElementIsSegmentValidLocationNext(false, segment, segments, region, region_id, segment_i);
    }
  }

    /**
   * When validating Location proceeds to the next rule.
   */
  function personacontentRegionElementIsSegmentValidLocationNext(valid, segment, segments, region, region_id, segment_i) {
    segment.rules_validation.push(valid);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i);
    }
    else {
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i);
    }
  }

  /**
   * Validates a referer url rule.
   */
  function personacontentRegionElementIsSegmentValidUrlReferer(rule, segment, segments, region, region_id, segment_i) {
    var path = personacontentRegionGetUrlRefererPath();
    var result = personacontentRegionElementIsSegmentValidUrlOperator(rule, path);
    segment.rules_validation.push(result);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i);
    }
    else {
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i);
    }
  }

  /**
   * Validates a current url rule.
   */
  function personacontentRegionElementIsSegmentValidUrlCurrent(rule, segment, segments, region, region_id, segment_i) {
    var path = personacontentRegionGetUrlPath();
    var result = personacontentRegionElementIsSegmentValidUrlOperator(rule, path);
    segment.rules_validation.push(result);

    // Verify if there are more rules to validate.
    if (parseInt(segment.rules.length) > 0) {
      personacontentRegionSegmentProcessRules(segment, segments, region, region_id, segment_i);
    }
    else {
      // Evaluate rules_matches.
      personacontentRegionSegmentProcessItemRulesResult(segment, segments, region, region_id, segment_i);
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
    var string = personacontentRegionGetFirstValue(rule.values);

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
   * Get the referer url path.
   */
  function personacontentRegionGetUrlRefererPath() {
    var referer = document.referrer;
    var path = referer.match(/\/\/.*?\/(.*?)\/?(\?.*)?$/)[1];
    var matches = path.match(/^\/?(.*)/);

    return matches[1];
  }

  /**
   * Get the current url path.
   */
  function personacontentRegionGetUrlPath() {
    var path = window.location.pathname;
    var matches = path.match(/^\/?(.*)/);

    return matches[1];
  }
  

})(jQuery, Drupal, drupalSettings);
