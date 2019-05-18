(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Creates class to store History.
   */
  var personaHistoryClass = function(options) {

    /**
     * Can access this.method
     * inside other methods using
     * root.method()
     */
    var root = this;
    var key = 'personacontent--history-5';
    var history = new Array();
    var pathCurrent = '';
    var timeagoInstance = timeago();
    var firstTimeFlag = false;

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
      pathCurrent = root.getUrlPath();
      history = root.getHistory();
      root.registerPath();

      if (typeof drupalSettings.personacontent !== 'undefined') {
        if (typeof drupalSettings.personacontent.screen_debug !== 'undefined') {
          if (drupalSettings.personacontent.screen_debug == 1) {
            root.screenDebug();
          }
        }
      }
    }

    /**
     * Stores the User History into our own cookie.
     */
    this.registerPath = function () {
      var now = Math.round(new Date().getTime()/1000);

      // Search for existing history path.
      var iFound = root.searchPath(pathCurrent);
      if (iFound == false) {
        // This means its not found.
        history.push({
          'path': pathCurrent,
          'time': now
        });
      }
      // Update existing path.
      else {
        history[(iFound - 1)].time = now;
      }

      history = root.sortHistory();
      var history_cache_json = JSON.stringify(history);
      localStorage.setItem(key, history_cache_json);
    }

    /**
     * Sorts history by date.
     */
    this.sortHistory = function() {
      var dates = new Array();

      $(history).each(function(i, history_item) {
        dates[i] = history_item.time;
      });
    
      var historyL = JSON.parse(JSON.stringify(history));
      window.personaLib.array_multisort(dates, 'SORT_DESC', 'SORT_NUMERIC', historyL);
      return historyL;
    }

    /**
     * Is the firstime on AltaMed?
     */
    this.firstTime = function() {
      if (parseInt(history.length) == 0) {
        return true;
      }

      return false;
    }

    /**
     * Search for URL in existing cache.
     */
    this.searchPath = function (searchedPath) {
      if (parseInt(history.length) == 0) {
        return false;
      }

      searchedPath = $.trim(searchedPath);
      searchedPath = window.personaLib.escapeRegExp(searchedPath);

      // Search for path.
      var iFound = false;
      $(history).each(function(i, history_item) {
        var re = new RegExp('^' + searchedPath + '$', 'g');
        var history_path = $.trim(history_item.path);
        var result = history_path.match(re);

        // var log = "%cdoes %c" + history_path + ' %cequals %c' + searchedPath + '%c?';

        if (result == null) {
          // console.log(log + ' No, its null', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
          return true;
        }

        if (result.length > 0) {
          // console.log(log + ' Yes', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
          iFound = (i + 1);
          return false;
        }

        // console.log(log + ' No', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
      });

      return iFound;
    }

    /**
     * Search for URL in existing cache.
     */
    this.searchPathContains = function (searchedPath) {
      if (parseInt(history.length) == 0) {
        return false;
      }

      //searchedPath = $.trim(searchedPath).replace('?', '\\?').replace('&', '\\&');
      searchedPath = $.trim(searchedPath);
      searchedPath = window.personaLib.escapeRegExp(searchedPath);

      // Search for path.
      var iFound = false;
      $(history).each(function(i, history_item) {
        var re = new RegExp(searchedPath, 'g');
        var history_path = $.trim(history_item.path);
        var result = history_path.match(re);

        // var log = "%cdoes %c" + history_path + ' %ccontains %c' + searchedPath + '%c?';

        if (result == null) {
          // console.log(log + ' No, its null', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
          return true;
        }

        if (result.length > 0) {
          // console.log(log + ' Yes', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
          iFound = (i + 1);
          return false;
        }

        // console.log(log + ' No', "color: inherit;", "color: red;", "color: inherit;", "color: red;", "color: inherit;");
      });

      return iFound;
    }

    /**
     * Get HistoryPath.
     */
    this.getHistory = function () {
      var history_cache = localStorage.getItem(key);

      // Create array if doesn't exists.
      if (typeof history_cache != 'undefined' && history_cache != null) {
        history_cache = JSON.parse(history_cache);
      }
      // Create array if doesn't exists.  
      else {
        history_cache = new Array();
        var history_cache_json = JSON.stringify(history_cache);
        localStorage.setItem(key, history_cache_json);
      }
      
      return history_cache;
    }

    /**
     * Get the current url path.
     */
    this.getUrlPath = function() {
      var path = window.location.pathname;
      var query = window.location.search;
      path += query;
      path = decodeURIComponent(path.replace(/\+/g, ' '));

      return path;
    }

    /**
     * Process a rule.
     */
    this.searchRule = function(rule_raw) {
      var ruleL = JSON.parse(JSON.stringify(rule_raw));
      var iFound;
      var result = false;
      $(ruleL.values).each(function (i, value) {
        iFound = false;
        if (ruleL.operator == 'contains') {
          iFound = root.searchPathContains(value);
        }
        else {
          iFound = root.searchPath(value);
        }

        if (iFound !== false) {
          result = true;
          return false;
        }
      });

      return result;
    }

    /**
     * Adds History Debug.
     */
    this.screenDebug = function() {
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
      var container = $('#personaHistoryDebugScreen .current-history .entries > ul');
      var itemTheme = '';
      $(history).each(function(i, history_item) {
        itemTheme = root.screenItemTheme(history_item);
        $(container).append(itemTheme);
      });
    }

    /**
     * Retruns Screen Item output.
     */
    this.screenItemTheme = function(raw) {
      var ago = timeagoInstance.format(parseInt(raw.time * 1000, 10));
      var output = [
      '<li class="history-item hover-here">',
        '<dicv class="inner-wrapper">',
          '<div class="time">' + ago + '</div>',
          '<div class="path" title="' + raw.path + '">' + raw.path + '</div>',
        '</div>',
      '</li>'].join('');
      return output;
    }

    /**
     * Returns Screen Output.
     */
    this.screenTheme = function () {
      var output = [
      '<div id="personaHistoryDebugScreen">',
        '<div class="container-fluid">',
          '<div class="current-history">',
            '<h2>History</h2>',
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
     * Click callback for opening/closing debug.
     */
    $(document).on('click', '#personaHistoryDebugScreen .opener', function(e) {
      e.preventDefault();

      if ($('#personaHistoryDebugScreen').hasClass('open')) {
        $('#personaHistoryDebugScreen').removeClass('open');
      }
      else {
        $('#personaHistoryDebugScreen').addClass('open');
      }
    });

  }

  window.personaHistory = new personaHistoryClass();
  window.personaHistory.init();

})(jQuery, Drupal, drupalSettings);
