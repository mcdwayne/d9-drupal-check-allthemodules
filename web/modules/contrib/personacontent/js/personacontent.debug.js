(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Creates class to store History.
   */
  var personaDebugClass = function(options) {

    /**
     * Global Segment and Rule that wins.
     */
    var segmentWinner;
    var ruleWinner;

    /**
     * Sets segment and rule winner.
     */
    this.markSegmentWinner = function(segment) {
      segmentWinner = segment;

      if (root.status()) {
        root.consoleLog('%c SEGMENT WINNER %c' + segment.title, 'padding: 5px; font-weight: bold; background: yellow; color: black;', 'padding: 5px; font-weight: bold; background: aqua; color: black;', segment);
      }
    }

    /**
     * Sets segment and rule winner.
     * Call as window.personaDebug.markRuleWinner().
     */
    this.markRuleWinner = function(rule) {
      ruleWinner = rule;

      if (root.status()) {
        root.consoleLog('%c RULE WINNER', 'padding: 5px; font-weight: bold; background: yellow; color: black;', rule);
      }
    }

    /**
     * Can access this.method
     * inside other methods using
     * root.method()
     */
    var root = this;
    var consoleLogEnabled = false;
    var screenStatus = false;

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
      if (typeof drupalSettings.personacontent !== 'undefined') {
        if (typeof drupalSettings.personacontent.screen_debug_html !== 'undefined') {
          if (drupalSettings.personacontent.screen_debug_html == 1) {
            consoleLogEnabled = true;
          }
        }
      }
    }

    /**
     * Switch personaDebug On/Off.
     */
    this.switchDebug = function (flag) {
      consoleLogEnabled = flag;
      if (flag) {
        root.screen();
      }
    }

    /**
     * Is debug on?
     */
    this.status = function () {
      if (typeof drupalSettings.personacontent !== 'undefined') {
        if (typeof drupalSettings.personacontent.screen_debug_html !== 'undefined') {
          if (drupalSettings.personacontent.screen_debug_html == 1) {
            return true;
          }
        }
      }
      return false;
    }

    /**
     * Shows logs if consoleLogEnabled is enabled.
     */
    this.consoleLog = function () {
      if (consoleLogEnabled) {
        console.log.apply(null, arguments);
      }
    }

    /**
     * Screen Debug.
     */
    this.screen = function () {
      // Make sure we set this just once.
      if (screenStatus) {
        return true;
      }

      if (drupalSettings.personacontent.screen_debug == 0) {
        return true;
      }
      screenStatus = true;

      // Set this up.
      var output = root.screenTheme();
      $('body').prepend(output);
    }

    /**
     * Prints active segment on Screen.
     */
    this.screenPrint = function (segment) {
      if (drupalSettings.personacontent.screen_debug == 1) {
        $('#personaDebugScreen .segment-name').html(segment.title);
        $('#personaDebugScreen .general-rule.matches .value').html(segment.rules_matches);
        $('#personaDebugScreen .general-rule.count .value').html(segment.rules.length);
  
        if (segment.rules.length > 0) {
          $('#personaDebugScreen').addClass('has-rules');
          var ruleOutput = '';
          $(segment.rules).each(function(i, rule) {
            ruleOutput = root.screenThemeRule(rule);
            $('#personaDebugScreen .current-segment > .window > .rules > ul').append(ruleOutput);
          });
        }
      }
    }

    /**
     * Returns Screen Output for one rule.
     */
    this.screenThemeRule = function (rule) {
      var output = [
      '<li class="rule">',
        '<div class="rule-field">',
          '<strong>Type:</strong> <span class="value">' + rule.type + '</span>',
        '</div>',
        '<div class="rule-field">',
          '<strong>Operator:</strong> <span class="value">' + rule.operator + '</span>',
        '</div>',
        '<div class="rule-field">',
          '<strong>Possible Values:</strong>',
          '<ul class="values">'].join('');
      $(rule.values).each(function(j, value) {
        output += '<li><span class="value">' + value + '</span></li>';
      });
      output += [
          '</ul>',
        '</div>',
      '</li>'].join('');
      
      return output;
    }

    /**
     * Returns Screen Output.
     */
    this.screenTheme = function () {
      var output = [
      '<div id="personaDebugScreen">',
        '<div class="container-fluid">',
          '<div class="current-segment">',
            '<h2>Current Segment: <span class="segment-name value"></span></h2>',
            '<a href="#" class="opener"><span class="open-label">Open</span><span class="close-label">Close</span></a>',
            '<div class="window">',
              '<div class="general-rules">',
                '<div class="general-rule matches">',
                  '<strong>Rules Matches:</strong> <span class="value"></span>',
                '</div>',
                '<div class="general-rule count">',
                  '<strong>Rules Count:</strong> <span class="value">0</span>',
                '</div>',
              '</div>',
              '<div class="rules">',
                '<h3>Rules:</h3>',
                '<ul></ul>',
              '</div>',
            '</div>',
          '</div>',
        '</div>',
      '</div>'].join('');

      return output;
    }

    /**
     * Click callback for opening/closing debug.
     */
    $(document).on('click', '#personaDebugScreen .opener', function(e) {
      e.preventDefault();

      if ($('#personaDebugScreen').hasClass('open')) {
        $('#personaDebugScreen').removeClass('open');
      }
      else {
        $('#personaDebugScreen').addClass('open');
      }
    });
  }

  window.personaDebug = new personaDebugClass();
  window.personaDebug.init();

})(jQuery, Drupal, drupalSettings);
