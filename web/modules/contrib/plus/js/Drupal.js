
var Drupal = Drupal || { 'settings': {}, 'behaviors': {}, 'locale': {} };

// Allow other JavaScript libraries to use $.
jQuery.noConflict();

(function ($) {

  /**
   * Override jQuery.fn.init to guard against XSS attacks.
   *
   * See http://bugs.jquery.com/ticket/9521
   */
  var jquery_init = $.fn.init;
  $.fn.init = function (selector, context, rootjQuery) {
    // If the string contains a "#" before a "<", treat it as invalid HTML.
    if (selector && typeof selector === 'string') {
      var hash_position = selector.indexOf('#');
      if (hash_position >= 0) {
        var bracket_position = selector.indexOf('<');
        if (bracket_position > hash_position) {
          throw 'Syntax error, unrecognized expression: ' + selector;
        }
      }
    }
    return jquery_init.call(this, selector, context, rootjQuery);
  };
  $.fn.init.prototype = jquery_init.prototype;

  /**
   * Attach all registered behaviors to a page element.
   *
   * Behaviors are event-triggered actions that attach to page elements, enhancing
   * default non-JavaScript UIs. Behaviors are registered in the Drupal.behaviors
   * object using the method 'attach' and optionally also 'detach' as follows:
   * @code
   *    Drupal.behaviors.behaviorName = {
 *      attach: function (context, settings) {
 *        ...
 *      },
 *      detach: function (context, settings, trigger) {
 *        ...
 *      }
 *    };
   * @endcode
   *
   * Drupal.attachBehaviors is added below to the jQuery ready event and so
   * runs on initial page load. Developers implementing AHAH/Ajax in their
   * solutions should also call this function after new page content has been
   * loaded, feeding in an element to be processed, in order to attach all
   * behaviors to the new content.
   *
   * Behaviors should use
   * @code
   *   $(selector).once('behavior-name', function () {
 *     ...
 *   });
   * @endcode
   * to ensure the behavior is attached only once to a given element. (Doing so
   * enables the reprocessing of given elements, which may be needed on occasion
   * despite the ability to limit behavior attachment to a particular element.)
   *
   * @param context
   *   An element to attach behaviors to. If none is given, the document element
   *   is used.
   * @param settings
   *   An object containing settings for the current context. If none given, the
   *   global Drupal.settings object is used.
   */
  Drupal.attachBehaviors = function (context, settings) {
    context = context || document;
    settings = settings || Drupal.settings;
    // Execute all of them.
    $.each(Drupal.behaviors, function () {
      if ($.isFunction(this.attach)) {
        this.attach(context, settings);
      }
    });
  };

  /**
   * Detach registered behaviors from a page element.
   *
   * Developers implementing AHAH/Ajax in their solutions should call this
   * function before page content is about to be removed, feeding in an element
   * to be processed, in order to allow special behaviors to detach from the
   * content.
   *
   * Such implementations should look for the class name that was added in their
   * corresponding Drupal.behaviors.behaviorName.attach implementation, i.e.
   * behaviorName-processed, to ensure the behavior is detached only from
   * previously processed elements.
   *
   * @param context
   *   An element to detach behaviors from. If none is given, the document element
   *   is used.
   * @param settings
   *   An object containing settings for the current context. If none given, the
   *   global Drupal.settings object is used.
   * @param trigger
   *   A string containing what's causing the behaviors to be detached. The
   *   possible triggers are:
   *   - unload: (default) The context element is being removed from the DOM.
   *   - move: The element is about to be moved within the DOM (for example,
   *     during a tabledrag row swap). After the move is completed,
   *     Drupal.attachBehaviors() is called, so that the behavior can undo
   *     whatever it did in response to the move. Many behaviors won't need to
   *     do anything simply in response to the element being moved, but because
   *     IFRAME elements reload their "src" when being moved within the DOM,
   *     behaviors bound to IFRAME elements (like WYSIWYG editors) may need to
   *     take some action.
   *   - serialize: When an Ajax form is submitted, this is called with the
   *     form as the context. This provides every behavior within the form an
   *     opportunity to ensure that the field elements have correct content
   *     in them before the form is serialized. The canonical use-case is so
   *     that WYSIWYG editors can update the hidden textarea to which they are
   *     bound.
   *
   * @see Drupal.attachBehaviors
   */
  Drupal.detachBehaviors = function (context, settings, trigger) {
    context = context || document;
    settings = settings || Drupal.settings;
    trigger = trigger || 'unload';
    // Execute all of them.
    $.each(Drupal.behaviors, function () {
      if ($.isFunction(this.detach)) {
        this.detach(context, settings, trigger);
      }
    });
  };

  /**
   * Encode special characters in a plain-text string for display as HTML.
   *
   * @ingroup sanitization
   */
  Drupal.checkPlain = function (str) {
    var character, regex,
      replace = { '&': '&amp;', '"': '&quot;', '<': '&lt;', '>': '&gt;' };
    str = String(str);
    for (character in replace) {
      if (replace.hasOwnProperty(character)) {
        regex = new RegExp(character, 'g');
        str = str.replace(regex, replace[character]);
      }
    }
    return str;
  };

  /**
   * Replace placeholders with sanitized values in a string.
   *
   * @param str
   *   A string with placeholders.
   * @param args
   *   An object of replacements pairs to make. Incidences of any key in this
   *   array are replaced with the corresponding value. Based on the first
   *   character of the key, the value is escaped and/or themed:
   *    - !variable: inserted as is
   *    - @variable: escape plain text to HTML (Drupal.checkPlain)
   *    - %variable: escape text and theme as a placeholder for user-submitted
   *      content (checkPlain + Drupal.theme('placeholder'))
   *
   * @see Drupal.t()
   * @ingroup sanitization
   */
  Drupal.formatString = function(str, args) {
    // Transform arguments before inserting them.
    for (var key in args) {
      if (args.hasOwnProperty(key)) {
        switch (key.charAt(0)) {
          // Escaped only.
          case '@':
            args[key] = Drupal.checkPlain(args[key]);
            break;
          // Pass-through.
          case '!':
            break;
          // Escaped and placeholder.
          default:
            args[key] = Drupal.theme('placeholder', args[key]);
            break;
        }
      }
    }

    return Drupal.stringReplace(str, args, null);
  };

  /**
   * Replace substring.
   *
   * The longest keys will be tried first. Once a substring has been replaced,
   * its new value will not be searched again.
   *
   * @param {String} str
   *   A string with placeholders.
   * @param {Object} args
   *   Key-value pairs.
   * @param {Array|null} keys
   *   Array of keys from the "args".  Internal use only.
   *
   * @return {String}
   *   Returns the replaced string.
   */
  Drupal.stringReplace = function (str, args, keys) {
    if (str.length === 0) {
      return str;
    }

    // If the array of keys is not passed then collect the keys from the args.
    if (!$.isArray(keys)) {
      keys = [];
      for (var k in args) {
        if (args.hasOwnProperty(k)) {
          keys.push(k);
        }
      }

      // Order the keys by the character length. The shortest one is the first.
      keys.sort(function (a, b) { return a.length - b.length; });
    }

    if (keys.length === 0) {
      return str;
    }

    // Take next longest one from the end.
    var key = keys.pop();
    var fragments = str.split(key);

    if (keys.length) {
      for (var i = 0; i < fragments.length; i++) {
        // Process each fragment with a copy of remaining keys.
        fragments[i] = Drupal.stringReplace(fragments[i], args, keys.slice(0));
      }
    }

    return fragments.join(args[key]);
  };

  /**
   * Translate strings to the page language or a given language.
   *
   * See the documentation of the server-side t() function for further details.
   *
   * @param str
   *   A string containing the English string to translate.
   * @param args
   *   An object of replacements pairs to make after translation. Incidences
   *   of any key in this array are replaced with the corresponding value.
   *   See Drupal.formatString().
   *
   * @param options
   *   - 'context' (defaults to the empty context): The context the source string
   *     belongs to.
   *
   * @return
   *   The translated string.
   */
  Drupal.t = function (str, args, options) {
    options = options || {};
    options.context = options.context || '';

    // Fetch the localized version of the string.
    if (Drupal.locale.strings && Drupal.locale.strings[options.context] && Drupal.locale.strings[options.context][str]) {
      str = Drupal.locale.strings[options.context][str];
    }

    if (args) {
      str = Drupal.formatString(str, args);
    }
    return str;
  };

  /**
   * Format a string containing a count of items.
   *
   * This function ensures that the string is pluralized correctly. Since Drupal.t() is
   * called by this function, make sure not to pass already-localized strings to it.
   *
   * See the documentation of the server-side format_plural() function for further details.
   *
   * @param count
   *   The item count to display.
   * @param singular
   *   The string for the singular case. Please make sure it is clear this is
   *   singular, to ease translation (e.g. use "1 new comment" instead of "1 new").
   *   Do not use @count in the singular string.
   * @param plural
   *   The string for the plural case. Please make sure it is clear this is plural,
   *   to ease translation. Use @count in place of the item count, as in "@count
   *   new comments".
   * @param args
   *   An object of replacements pairs to make after translation. Incidences
   *   of any key in this array are replaced with the corresponding value.
   *   See Drupal.formatString().
   *   Note that you do not need to include @count in this array.
   *   This replacement is done automatically for the plural case.
   * @param options
   *   The options to pass to the Drupal.t() function.
   * @return
   *   A translated string.
   */
  Drupal.formatPlural = function (count, singular, plural, args, options) {
    args = args || {};
    args['@count'] = count;
    // Determine the index of the plural form.
    var index = Drupal.locale.pluralFormula ? Drupal.locale.pluralFormula(args['@count']) : ((args['@count'] == 1) ? 0 : 1);

    if (index == 0) {
      return Drupal.t(singular, args, options);
    }
    else if (index == 1) {
      return Drupal.t(plural, args, options);
    }
    else {
      args['@count[' + index + ']'] = args['@count'];
      delete args['@count'];
      return Drupal.t(plural.replace('@count', '@count[' + index + ']'), args, options);
    }
  };

  /**
   * Returns the passed in URL as an absolute URL.
   *
   * @param url
   *   The URL string to be normalized to an absolute URL.
   *
   * @return
   *   The normalized, absolute URL.
   *
   * @see https://github.com/angular/angular.js/blob/v1.4.4/src/ng/urlUtils.js
   * @see https://grack.com/blog/2009/11/17/absolutizing-url-in-javascript
   * @see https://github.com/jquery/jquery-ui/blob/1.11.4/ui/tabs.js#L53
   */
  Drupal.absoluteUrl = function (url) {
    var urlParsingNode = document.createElement('a');

    // Decode the URL first; this is required by IE <= 6. Decoding non-UTF-8
    // strings may throw an exception.
    try {
      url = decodeURIComponent(url);
    } catch (e) {}

    urlParsingNode.setAttribute('href', url);

    // IE <= 7 normalizes the URL when assigned to the anchor node similar to
    // the other browsers.
    return urlParsingNode.cloneNode(false).href;
  };

  /**
   * Returns true if the URL is within Drupal's base path.
   *
   * @param url
   *   The URL string to be tested.
   *
   * @return
   *   Boolean true if local.
   *
   * @see https://github.com/jquery/jquery-ui/blob/1.11.4/ui/tabs.js#L58
   */
  Drupal.urlIsLocal = function (url) {
    // Always use browser-derived absolute URLs in the comparison, to avoid
    // attempts to break out of the base path using directory traversal.
    var absoluteUrl = Drupal.absoluteUrl(url);
    var protocol = location.protocol;

    // Consider URLs that match this site's base URL but use HTTPS instead of HTTP
    // as local as well.
    if (protocol === 'http:' && absoluteUrl.indexOf('https:') === 0) {
      protocol = 'https:';
    }
    var baseUrl = protocol + '//' + location.host + Drupal.settings.basePath.slice(0, -1);

    // Decoding non-UTF-8 strings may throw an exception.
    try {
      absoluteUrl = decodeURIComponent(absoluteUrl);
    } catch (e) {}
    try {
      baseUrl = decodeURIComponent(baseUrl);
    } catch (e) {}

    // The given URL matches the site's base URL, or has a path under the site's
    // base URL.
    return absoluteUrl === baseUrl || absoluteUrl.indexOf(baseUrl + '/') === 0;
  };

  /**
   * Generate the themed representation of a Drupal object.
   *
   * All requests for themed output must go through this function. It examines
   * the request and routes it to the appropriate theme function. If the current
   * theme does not provide an override function, the generic theme function is
   * called.
   *
   * For example, to retrieve the HTML for text that should be emphasized and
   * displayed as a placeholder inside a sentence, call
   * Drupal.theme('placeholder', text).
   *
   * @param func
   *   The name of the theme function to call.
   * @param ...
   *   Additional arguments to pass along to the theme function.
   * @return
   *   Any data the theme function returns. This could be a plain HTML string,
   *   but also a complex object.
   */
  Drupal.theme = function (func) {
    var args = Array.prototype.slice.apply(arguments, [1]);

    return (Drupal.theme[func] || Drupal.theme.prototype[func]).apply(this, args);
  };

  /**
   * Freeze the current body height (as minimum height). Used to prevent
   * unnecessary upwards scrolling when doing DOM manipulations.
   */
  Drupal.freezeHeight = function () {
    Drupal.unfreezeHeight();
    $('<div id="freeze-height"></div>').css({
      position: 'absolute',
      top: '0px',
      left: '0px',
      width: '1px',
      height: $('body').css('height')
    }).appendTo('body');
  };

  /**
   * Unfreeze the body height.
   */
  Drupal.unfreezeHeight = function () {
    $('#freeze-height').remove();
  };

  /**
   * Encodes a Drupal path for use in a URL.
   *
   * For aesthetic reasons slashes are not escaped.
   */
  Drupal.encodePath = function (item, uri) {
    uri = uri || location.href;
    return encodeURIComponent(item).replace(/%2F/g, '/');
  };

  /**
   * Get the text selection in a textarea.
   */
  Drupal.getSelection = function (element) {
    if (typeof element.selectionStart != 'number' && document.selection) {
      // The current selection.
      var range1 = document.selection.createRange();
      var range2 = range1.duplicate();
      // Select all text.
      range2.moveToElementText(element);
      // Now move 'dummy' end point to end point of original range.
      range2.setEndPoint('EndToEnd', range1);
      // Now we can calculate start and end points.
      var start = range2.text.length - range1.text.length;
      var end = start + range1.text.length;
      return { 'start': start, 'end': end };
    }
    return { 'start': element.selectionStart, 'end': element.selectionEnd };
  };

  /**
   * Add a global variable which determines if the window is being unloaded.
   *
   * This is primarily used by Drupal.displayAjaxError().
   */
  Drupal.beforeUnloadCalled = false;
  $(window).bind('beforeunload pagehide', function () {
    Drupal.beforeUnloadCalled = true;
  });

  /**
   * Displays a JavaScript error from an Ajax response when appropriate to do so.
   */
  Drupal.displayAjaxError = function (message) {
    // Skip displaying the message if the user deliberately aborted (for example,
    // by reloading the page or navigating to a different page) while the Ajax
    // request was still ongoing. See, for example, the discussion at
    // http://stackoverflow.com/questions/699941/handle-ajax-error-when-a-user-clicks-refresh.
    if (!Drupal.beforeUnloadCalled) {
      alert(message);
    }
  };

  /**
   * Build an error message from an Ajax response.
   */
  Drupal.ajaxError = function (xmlhttp, uri, customMessage) {
    var statusCode, statusText, pathText, responseText, readyStateText, message;
    if (xmlhttp.status) {
      statusCode = "\n" + Drupal.t("An AJAX HTTP error occurred.") +  "\n" + Drupal.t("HTTP Result Code: !status", {'!status': xmlhttp.status});
    }
    else {
      statusCode = "\n" + Drupal.t("An AJAX HTTP request terminated abnormally.");
    }
    statusCode += "\n" + Drupal.t("Debugging information follows.");
    pathText = "\n" + Drupal.t("Path: !uri", {'!uri': uri} );
    statusText = '';
    // In some cases, when statusCode == 0, xmlhttp.statusText may not be defined.
    // Unfortunately, testing for it with typeof, etc, doesn't seem to catch that
    // and the test causes an exception. So we need to catch the exception here.
    try {
      statusText = "\n" + Drupal.t("StatusText: !statusText", {'!statusText': $.trim(xmlhttp.statusText)});
    }
    catch (e) {}

    responseText = '';
    // Again, we don't have a way to know for sure whether accessing
    // xmlhttp.responseText is going to throw an exception. So we'll catch it.
    try {
      responseText = "\n" + Drupal.t("ResponseText: !responseText", {'!responseText': $.trim(xmlhttp.responseText) } );
    } catch (e) {}

    // Make the responseText more readable by stripping HTML tags and newlines.
    responseText = responseText.replace(/<("[^"]*"|'[^']*'|[^'">])*>/gi,"");
    responseText = responseText.replace(/[\n]+\s+/g,"\n");

    // We don't need readyState except for status == 0.
    readyStateText = xmlhttp.status == 0 ? ("\n" + Drupal.t("ReadyState: !readyState", {'!readyState': xmlhttp.readyState})) : "";

    // Additional message beyond what the xmlhttp object provides.
    customMessage = customMessage ? ("\n" + Drupal.t("CustomMessage: !customMessage", {'!customMessage': customMessage})) : "";

    message = statusCode + pathText + statusText + customMessage + responseText + readyStateText;
    return message;
  };

// Class indicating that JS is enabled; used for styling purpose.
  $('html').addClass('js');

// 'js enabled' cookie.
  document.cookie = 'has_js=1; path=/';

  /**
   * Additions to jQuery.support.
   */
  $(function () {
    /**
     * Boolean indicating whether or not position:fixed is supported.
     */
    if (jQuery.support.positionFixed === undefined) {
      var el = $('<div style="position:fixed; top:10px" />').appendTo(document.body);
      jQuery.support.positionFixed = el[0].offsetTop === 10;
      el.remove();
    }
  });

//Attach all behaviors.
  $(function () {
    Drupal.attachBehaviors(document, Drupal.settings);
  });

  /**
   * The default themes.
   */
  Drupal.theme.prototype = {

    /**
     * Formats text for emphasized display in a placeholder inside a sentence.
     *
     * @param str
     *   The text to format (plain-text).
     * @return
     *   The formatted text (html).
     */
    placeholder: function (str) {
      return '<em class="placeholder">' + Drupal.checkPlain(str) + '</em>';
    }
  };

})(jQuery);




/**
 * @file
 * Provides extended functionality commonly needed in a Drupal site.
 */
(function ($, Drupal) {
  'use strict';

  var behaviorsInvoked = false;
  var behaviorsWait = false;
  var readyInvoked = false;
  var readyWait = false;

  /**
   * A jQuery object of the body DOM element.
   *
   * This is primarily used for performance reasons so a new jQuery object
   * doesn't have to be instantiated each time.
   *
   * @type {jQuery}
   */
  Drupal.$body = $ && $(document.body);

  /**
   * A jQuery object of the document.
   *
   * This is primarily used for performance reasons so a new jQuery object
   * doesn't have to be instantiated each time.
   *
   * @type {jQuery}
   */
  Drupal.$document = $ && $(document);

  /**
   * The HTML element.
   *
   * This is primarily used for performance reasons so a new jQuery object
   * doesn't have to be instantiated each time.
   *
   * @type {jQuery}
   */
  Drupal.$html = $ && $(document.documentElement);

  /**
   * An empty jQuery object.
   *
   * This is primarily used for performance reasons so a new jQuery object
   * doesn't have to be instantiated each time.
   *
   * @type {jQuery}
   */
  Drupal.$noop = $ && $();

  /**
   * The scrollable viewport element.
   *
   * This is primarily used for performance reasons so a new jQuery object
   * doesn't have to be instantiated each time.
   *
   * @type {jQuery}
   */
  Drupal.$viewport = $ && $('html, body');

  /**
   * A jQuery object of the body DOM element.
   *
   * This is primarily used for performance reasons so a new jQuery object
   * doesn't have to be instantiated each time.
   *
   * @type {jQuery}
   */
  Drupal.$window = $ && $(window);

  Drupal.assetPlaceholder = document.createComment('Drupal+ Asset Placeholder');

  // Insert after the script that is adding the asset (if supported).
  if (document.currentScript) {
    document.currentScript.parentNode[document.currentScript.nextSibling ? 'insertBefore' : 'appendChild'](Drupal.assetPlaceholder, document.currentScript.nextSibling);
  }
  // Otherwise, insert at the end of the of the HEAD DOM element.
  else {
    document.head.appendChild(Drupal.assetPlaceholder);
  }

  Drupal.addToDom = function addToHeader(url, options) {
    return new Promise(function (resolve, reject) {
      var element;

      // Add common properties.
      options = options || {};

      function getAssetType(data) {
        // Detect libraries.
        if (/^@[\w]+/.test(data)) {
          return [type.replace(/^@/, ''), 'library'];
        }
        var match = data.replace(/\?.*/, '').replace(/#.*$/, '').match(/^(css|js):\/\/|\.(css|js)$/);
        return match && (match[1] || match[2]);
      }

      options.assetType = getAssetType(url);
      options.retry = parseInt(options.retry, 10) || 3;
      options.attempts = parseInt(options.attempts, 10) || 0;
      options.url = url.replace(/^(css|js):\/\//, '');

      // Increase number of attempts.
      options.attempts++;

      switch (options.assetType) {
        case 'css':
          element = document.createElement('link');
          element.rel = 'stylesheet';
          element.href = url;
          break;

        case 'js':
          element = document.createElement('script');
          element.type = options.type || 'text/javascript';
          element.src = url;

          // Defer script if explicitly specified to, resetting default async.
          if (options.defer && !options.async) {
            element.async = false;
            element.defer = true;
          }
          // Async was explicitly specified, use its value.
          else if (options.async !== void 0) {
            element.async = !!options.async;
            // If element is async, it cannot be deferred.
            if (element.async) {
              element.defer = false;
            }
          }
          // Otherwise, attempt to intelligently default to the same values
          // as the current script (if supported).
          else if (document.currentScript) {
            element.async = document.currentScript.async && !document.currentScript.defer;
            element.defer = !document.currentScript.async && document.currentScript.defer;
          }

          if (options.crossOrigin !== void 0 || options.crossorigin !== void 0) {
            element.crossOrigin = options.crossOrigin || options.crossorigin;
          }

          if (options.integrity !== void 0) {
            element.integrity = options.integrity;
          }

          break;

        default:
          Drupal.fatal(Drupal.t('Unsupported asset type: @url'), {
            '@url': url
          });
          return;
      }

      var done = function (error, e, handler) {
        // Invoke custom success handler, if any.
        if (typeof handler === 'function') {
          handler(error, element, e);
        }
        if (typeof options.complete === 'function') {
          options.complete(error, element, e);
        }
        if (error) {
          // Remove the element from the DOM.
          element.parentNode.removeChild(element);

          // Handle retries.
          if (options.attempts < options.retry) {
            // Invoke custom retry handler, if any.
            if (typeof options.retry === 'function') {
              options.retry(url, options);
            }
            return resolve(AssetManager.addToDom(url, options));
          }
          return reject(error);
        }
        resolve(element);
      };

      // Register error handler.
      element.onerror = function (e, error) {
        done(error, e, options.error);
      };

      // Register load handler.
      element.onload = element.onbeforeload = function (e) {
        var error = null;

        // Handle css errors. Some browsers don't support onerror properly.
        if (options.assetType === 'css' && 'hideFocus' in element) {
          try {
            if (!element.sheet.cssText.length) {
              error = new Error(Drupal.t('Stylesheet was empty after being loaded: @file', {'@file': url}));
            }
          }
          catch (e) {
            error = new Error(Drupal.t('Unable to load stylesheet: @file', {'@file': url}));
          }
        }

        // Always invoke a custom complete handler, if any.
        done(error, e, options.success);
      };

      // Add element to header (unless before callback returns `false`).
      if (typeof options.before !== 'function' || options.before(element, url) !== false) {
        Drupal.assetPlaceholder.parentNode.insertBefore(element, Drupal.assetPlaceholder);
      }
    });
  };

  var originalAttachBehaviors = Drupal.attachBehaviors;
  var originalDetachBehaviors = Drupal.detachBehaviors;

  /**
   * {@inheritdoc}
   */
  Drupal.attachBehaviors = function attachBehaviors(context, settings) {
    // Immediately return if attaching behaviors should wait.
    if (behaviorsWait) {
      return;
    }

    // If there is no behaviorsQueue, call the original method.
    if (!this.behaviorsQueue) {
      return originalAttachBehaviors.apply(this, arguments);
    }

    this.behaviorsQueue
      .error(this.throwErrors.bind(this))
      .process('attach', context || document, settings || Drupal.settings);

    // Throw any errors generated.
    this.throwErrors(this.behaviorsQueue.getErrors());
  };

  /**
   * {@inheritdoc}
   */
  Drupal.detachBehaviors = function detachBehaviors(context, settings, trigger) {
    // If there is no behaviorsQueue, call the original method.
    if (!this.behaviorsQueue) {
      return originalDetachBehaviors.apply(this, arguments);
    }

    this.behaviorsQueue
      .error(this.throwErrors.bind(this))
      .process('detach', context || document, settings || Drupal.settings, trigger || 'unload');
  };

  /**
   * Backport of Drupal 8's debounce.
   *
   * Limits the invocations of a function in a given time frame.
   *
   * The debounce function wrapper should be used sparingly. One clear use
   * case is limiting the invocation of a callback attached to the window
   * resize event.
   *
   * Before using the debounce function wrapper, consider first whether the
   * callback could be attached to an event that fires less frequently or if
   * the function can be written in such a way that it is only invoked under
   * specific conditions.
   *
   * @param {Function} func
   *   The function to be invoked.
   * @param {Number} wait
   *   The time period within which the callback function should only be
   *   invoked once. For example if the wait period is 250ms, then the
   *   callback will only be called at most 4 times per second.
   * @param {Boolean} [immediate]
   *   Whether we wait at the beginning or end to execute the function.
   *
   * @return {function}
   *   The debounced function.
   *
   * @see http://cgit.drupalcode.org/drupal/tree/core/misc/debounce.es6.js
   */
  Drupal.debounce = function debounce(func, wait, immediate) {
    var timeout = void 0;
    var result = void 0;
    return function () {
      for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      var context = this;
      var later = function later() {
        timeout = null;
        if (!immediate) {
          result = func.apply(context, args);
        }
      };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) {
        result = func.apply(context, args);
      }
      return result;
    };
  };

  /**
   * Displays a debug message, if the Browser console is available.
   *
   * @param {String} message
   *   The message to display.
   * @param {Object} [args]
   *   An arguments to use in message.
   */
  Drupal.debug = function debug(message, args) {
    window.console && window.console.debug && window.console.debug.call(window.console, this.formatString(message, this.sanitizeObject(args)).replace(/&quot;/g, '"'));
  };

  /**
   * Displays an error message, if the Browser console is available.
   *
   * @param {String} message
   *   The message to display.
   * @param {Object} [args]
   *   An arguments to use in message.
   */
  Drupal.error = function error(message, args) {
    window.console && window.console.error && window.console.error.call(window.console, this.formatString(message, this.sanitizeObject(args)).replace(/&quot;/g, '"'));
  };

  /**
   * Provide a helper method for displaying when something is went wrong.
   *
   * @param {String|Error} message
   *   The message to display.
   * @param {Object} [args]
   *   An arguments to use in message.
   *
   * @return {Boolean<false>}
   *   Always returns FALSE.
   */
  Drupal.fatal = function fatal(message, args) {
    if (console.warn) {
      if (!(message instanceof Error)) {
        message = new Error(this.formatString(message, this.sanitizeObject(args)).replace(/&quot;/g, '"'));
      }
      this.throwError(message);
    }
    return false;
  };

  /**
   * Allows the attachment of behaviors to be deferred until ready to do so.
   *
   * This is similar to jQuery.holdReady, but this method is only available
   * in jQuery 1.6+. Since core ships with 1.4.4, an alternative is needed.
   *
   * @param {Boolean} hold
   *   Flag indicating whether or not to hold the attachment of behaviors.
   *   Once held, the behaviors will be attached as soon as the value is
   *   reset to false.
   */
  Drupal.holdBehaviors = function holdBehaviors(hold) {
    // Immediately return if behaviors have already been invoked.
    if (behaviorsInvoked) {
      return;
    }
    var previous = behaviorsWait;
    behaviorsWait = hold;

    // Initiate the behaviors.
    if (previous === true && behaviorsWait === false) {
      behaviorsInvoked = true;
      this.attachBehaviors(document, Drupal.settings);
    }
  };

  /**
   * Allows DOM ready handlers to be deferred until ready to do so.
   *
   * This is similar to jQuery.holdReady, but this method is only available
   * in jQuery 1.6+. Since core ships with 1.4.4, an alternative is needed.
   *
   * @param {Boolean} hold
   *   Flag indicating whether or not to hold the attachment of behaviors.
   *   Once held, the behaviors will be attached as soon as the value is
   *   reset to false.
   */
  Drupal.holdReady = function holdReady(hold) {
    // Immediately return if ready handlers have already been invoked.
    if (readyInvoked) {
      return;
    }
    var previous = readyWait;
    readyWait = hold;

    // Initiate the behaviors.
    if (previous === true && readyWait === false) {
      readyInvoked = true;
      this.ready();
    }

    // Hold jQuery "ready" from firing before additional assets have loaded.
    // Since this is Drupal 7, jQuery version may be below 1.6 which is when
    // $.holdReady was introduced. Only invoke this if it's available.
    if ($ && typeof $.holdReady === 'function') {
      $.holdReady(hold);
    }
  };

  /**
   * Displays an informative message, if the Browser console is available.
   *
   * @param {String} message
   *   The message to display.
   * @param {Object} [args]
   *   An arguments to use in message.
   */
  Drupal.info = function info(message, args) {
    window.console && window.console.info && window.console.info.call(window.console, this.formatString(message, this.sanitizeObject(args)).replace(/&quot;/g, '"'));
  };

  /**
   * Checks whether passed value is a class.
   *
   * @param {Function|Object} func
   *   The function to test.
   *
   * @return {Boolean}
   *   TRUE or FALSE
   */
  Drupal.isClass = function isClass(func) {
    return typeof func === 'function' && /^class\s/.test(Function.prototype.toString.call(func));
  };

  /**
   * Determines if passed value is a constructor function.
   *
   * Constructor functions must be instantiated with the "new" keyword.
   *
   * @param {Function|Object} func
   *   The function to test.
   *
   * @return {Boolean}
   *   TRUE or FALSE
   */
  Drupal.isConstructor = function isConstructor(func) {
    return this.isClass(func) || typeof func === 'function' && func.prototype !== void 0;
  };

  /**
   * Retrieves a machine name version of a string.
   *
   * @param {String} string
   *   The string to parse.
   *
   * @return {string}
   *   The machine name.
   */
  Drupal.machineName = function machineName(string) {
    return string.replace(/([A-Z]+[^A-Z]+)/g, '_$1').toLowerCase().replace(/[^a-z0-9-]+/g, '_').replace(/_+/g, '_').replace(/(^_|_$)/g, '');
  };

  /**
   * An empty function.
   *
   * This is primarily used for convenience.
   *
   * @type {Function}
   */
  Drupal.noop = function () {
  };

  /**
   * Proxies a method to an object.
   *
   * @param {Object} object
   *   An object to invoke a method on.
   * @param {String} method
   *   The method to invoke.
   * @param {Array|Object<arguments>} [args]
   *   The arguments to pass.
   *
   * @return {*}
   *   Returns whatever the proxy method returns.
   */
  Drupal.proxy = function proxy(object, method, args) {
    if (!object) {
      return this.fatal(Drupal.t('Empty object. Unable to proxy the method: @method'), {
        '@method': String(method),
      });
    }

    if (typeof object[method] !== 'function') {
      return this.fatal(Drupal.t('Unknown method. The passed object does not have the method: @method'), {'@method': String(method)});
    }

    return object[method].apply(object, args);
  };

  /**
   * Generates a random string.
   *
   * Taken from http://stackoverflow.com/a/18120932.
   *
   * @param {String} [prefix]
   *   A prefix to use.
   * @param {Number} [length=10]
   *   The length of the string.
   * @param {String} [suffix]
   *   A suffix to use.
   *
   * @return {String}
   *   The randomly generated string.
   */
  Drupal.random = function random(prefix, length, suffix) {
    var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    prefix = prefix || '';
    suffix = suffix || '';
    return prefix + Array.apply(null, new Array(length || 10)).map(function () {
      return possible[Math.floor(Math.random() * possible.length)];
    }).join('') + suffix;
  };

  /**
   * Simple DOM ready helper method in case jQuery isn't available.
   *
   * @param {Function} handler
   *   The callback handler to execute.
   */
  Drupal.ready = function ready(handler) {
    Drupal.readyHandlers.push(handler);
  };

  /**
   * Container for registered DOM ready handlers.
   *
   * @type {Function[]}
   */
  Drupal.readyHandlers = [];

  /**
   * Sanitizes an object for string output.
   *
   * @param {Object} obj
   *   An object to sanitize.
   * @param {Boolean|*} [functions = true]
   *   Optional. Flag indicating whether or not to include functions.
   *
   * @return {Object}
   *   A sanitized object.
   */
  Drupal.sanitizeObject = function sanitizeObject(obj, functions) {
    var ret = typeof obj === 'function' ? new Function() : {};
    var sanitize = function sanitize(value) {
      if (value instanceof Node) {
        var node = value;
        var path;
        while (node) {
          var name = node.localName || node.nodeName;
          if (!name) {
            break;
          }
          name = name.toLowerCase();
          var parent = node.parentNode;
          if (parent) {
            var children = Array.prototype.filter.call(parent.querySelectorAll(name), function (e) {
              return e.parentNode === parent;
            });
            var index = Array.prototype.indexOf.call(children, node) + 1;
            if (children.length > 1) {
              name += ':nth-child(' + index + ')';
            }
          }
          path = name + (path ? ' > ' + path : '');
          node = parent;
        }
        return path.replace(/^#document > /, '');
      }
      return JSON && typeof JSON.stringify === 'function' && JSON.stringify(value) || String(value);
    };
    if (obj instanceof Node) {
      return sanitize(obj);
    }
    for (var p in obj) {
      if (!obj.hasOwnProperty(p)) {
        continue;
      }
      if (typeof obj[p] === 'function' && (functions || functions === void 0)) {
        ret[p] = String((obj[p].constructor && obj[p].constructor.name) || obj[p].name || obj[p]);
      }
      else if (typeof obj[p] === 'object') {
        ret[p] = sanitize(this.sanitizeObject(obj[p]));
      }
      else {
        ret[p] = this.checkPlain(obj[p]);
      }
    }
    return ret;
  };

  /**
   * Scrolls to a specific element.
   *
   * @param {*} element
   *   A jQuery object, HTMLElement, selector, or event containing the
   *   element that should be scrolled to.
   * @param {Number} [offset = 0]
   *   Additional offset value to subtract from the element's top position.
   * @param {Number|String} [duration = 400]
   *   A string or number determining how long the animation will run.
   * @param {Number|String} [easing = 'swing']
   *   A string indicating which easing function to use for the transition.
   * @param {Function} [callback]
   *   A function to call once the animation is complete.
   */
  Drupal.scrollTo = function scrollTo(element, offset, duration, easing, callback) {
    // Immediately return if there is no jQuery.
    if (!$) {
      return;
    }

    // Handle an event if this was was the callback bound to one.
    if (element instanceof $.Event || element instanceof Event) {
      element.preventDefault();
      element.stopPropagation();
      element = element.currentTarget;
    }

    var $element = element instanceof $ ? element : $(element);
    var $target = null;

    // Handle data-toggle="scrollTop" anchor elements that use href for
    // target.
    var target;
    if ($element.is('a[href^=#][data-toggle="scrollTo"]:not([data-target])')) {
      target = $element.attr('href');
    }
    // Handle explicit target.
    else if ($element.is('[data-target]')) {
      target = $element.data('target');
    }

    // Target was specified.
    if (target) {
      // Attempt to find target by selector.
      $target = this.$body.find(target);
      // If no target could be found and it has a leading hash, the target
      // may be an anchor name.
      if (!$target[0] && /^#/.test(target)) {
        $target = this.$body.find('[name="' + target.replace(/^#/, '') + '"]');
      }
    }
    // Otherwise, target is the element itself.
    else {
      $target = $element;
    }

    // Return if there is no valid target.
    if (!$target || !$target[0]) {
      return;
    }

    duration = $target.data('duration') || $element.data('duration') || (duration === void 0 || duration === null ? 400 : duration);
    easing = $target.data('easing') || $element.data('easing') || (easing === void 0 || easing === null ? 'swing' : easing);
    offset = $target.data('offset') || $element.data('offset') || (offset === void 0 || offset === null ? 0 : offset);
    this.$viewport.animate({scrollTop: $target.offset().top - offset}, duration, easing, callback);
  };

  /**
   * Detect which kind of browser this is.
   *
   * Note: any "test" that relies on the string set as the "UserAgent" are
   * subject to being spoofed and should not be considered "reliable". These
   * are merely here for rudimentary detection, at best. If you need a
   * comprehensive and reliable solution, you should use a feature detection
   * library like Modernizr.
   *
   *
   * @see https://stackoverflow.com/a/9851769
   * @see https://stackoverflow.com/a/9039885
   * @see https://stackoverflow.com/a/3540295
   */
  var browser = {};
  browser.android = /Android/i.test(navigator.userAgent);
  browser.blackberry = /BlackBerry/i.test(navigator.userAgent);
  browser.chrome = !!window.chrome && !!window.chrome.webstore;
  browser.firefox = typeof InstallTrigger !== 'undefined';
  browser.safari = /constructor/i.test(window.HTMLElement) || (function (p) {
    return p.toString() === "[object SafariRemoteNotification]";
  })(!window['safari'] || (typeof safari !== 'undefined' && safari.pushNotification));
  browser.ie = /*@cc_on!@*/false || !!document.documentMode;
  browser.edge = !browser.ie && !!window.StyleMedia;
  browser.ieMobile = /IEMobile/i.test(navigator.userAgent);
  browser.opera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
  browser.operaMini = /OperaMini/i.test(navigator.userAgent);
  browser.ipad = !!(navigator.platform && /iPad/.test(navigator.platform));
  browser.iphone = !!(navigator.platform && /iPhone/.test(navigator.platform));
  browser.ipod = !!(navigator.platform && /iPod/.test(navigator.platform));
  browser.ios = !!(browser.ipad || browser.iphone || browser.ipod);
  browser.blink = (browser.chrome || browser.opera) && !!window.CSS;
  browser.mobile = !!(browser.android || browser.blackberry || browser.ieMobile || browser.operaMini || browser.ios);

  /**
   * Provides general support information about the browser.
   *
   * @type {Object}
   */
  Drupal.support = {
    browser: browser,

    /**
     * Checks whether the browser natively supports ES6.
     *
     * @see https://gist.github.com/DaBs/89ccc2ffd1d435efdacff05248514f38
     *
     * @return {Boolean}
     *   TRUE or FALSE
     */
    es6: (function es6support() {
      try {
        new Function('class ಠ_ಠ extends Array {constructor(j = "a", ...c) {const q = (({u: e}) => {return { [`s${c}`]: Symbol(j) };})({});super(j, q, ...c);}}' +
          'new Promise((f) => {const a = function* (){return "\u{20BB7}".match(/./u)[0].length === 2 || true;};for (let vre of a()) {' +
          'const [uw, as, he, re] = [new Set(), new WeakSet(), new Map(), new WeakMap()];break;}f(new Proxy({}, {get: (han, h) => h in han ? han[h] ' +
          ': "42".repeat(0o10)}));}).then(bi => new ಠ_ಠ(bi.rd));');
        return true;
      }
      catch (e) {
        return false;
      }
    })(),

    /**
     * The current langcode of the browser.
     *
     * @type {String}
     */
    langcode: window.navigator.userLanguage || window.navigator.language || 'en-US',

  };

  /**
   * Backport of Drupal 8's throwError.
   *
   * Helper to rethrow errors asynchronously.
   *
   * This way Errors bubbles up outside of the original callstack, making it
   * easier to debug errors in the browser.
   *
   * @param {Error|String} error
   *   The error to be thrown.
   *
   * @see http://cgit.drupalcode.org/drupal/tree/core/misc/drupal.es6.js
   */
  Drupal.throwError = function throwError(error) {
    setTimeout(function () {
      throw error instanceof Error ? error : new Error(error);
    }, 0);
    return false;
  };

  /**
   * Helper to throw errors asynchronously.
   *
   * @param {Error[]|String[]} errors
   *   An array of errors to throw.
   */
  Drupal.throwErrors = function throwErrors(errors) {
    for (var i = 0, l = errors.length; i < l; i++) {
      this.throwError(errors[i]);
    }
  };

  /**
   * Executes a callback in an asynchronous manner as soon as possible.
   *
   * @param {Function} callback
   *   The callback handler to invoke.
   * @param {...*} [args]
   *   Additional arguments to pass to the callback.
   *
   * @return {Number}
   *   The identifier of the worker.
   */
  Drupal.tick = function (callback, args) {
    args = Array.prototype.slice.call(arguments);
    var fn = typeof window.setImmediate === 'function' ? window.setImmediate : window.setTimeout;
    return fn.apply(null, [args.shift(), args.shift() || 0].concat(args));
  };

  /**
   * Truncates a string to a given length.
   *
   * @param {String} string
   *   The string to truncate.
   * @param {Number} length
   *   The length to truncate the string to.
   * @param {Boolean} [useWordBoundary]
   *   Flag indicating whether or not to truncate on a word boundary.
   *
   * @return {String}
   *   The truncated string.
   */
  Drupal.truncateString = function truncateString(string, length, useWordBoundary) {
    var toLong = string.length > length;
    var truncated = toLong ? string.substr(0, length - 1) : string;
    return useWordBoundary && toLong && /\s/.test(truncated) ? truncated.substr(0, truncated.lastIndexOf(' ')) : truncated;
  };

  /**
   * Type casts a scalar value from an existing scalar value.
   *
   * @param {*} existing
   *   The existing value to test against.
   * @param {*} value
   *   The value to convert, if needed.
   *
   * @return {*}
   *   The type casted value.
   */
  Drupal.typeCast = function typeCast(existing, value) {
    switch (typeof existing) {
      case 'boolean':
        value = !!value;
        break;
      case 'number':
        // Attempt to parse the value, falling back to the existing value if it
        // failed (the existing is usually a default value anyway).
        value = parseFloat(value) || existing;
        break;
      case 'string':
        value = value === null ? existing : String(value);
        break;
    }
    return value;
  };

  /**
   * Displays a warning message, if the Browser console is available.
   *
   * @param {String} message
   *   The message to display.
   * @param {Object} [args]
   *   An arguments to use in message.
   */
  Drupal.warning = function warning(message, args) {
    window.console && window.console.warn && window.console.warn.call(window.console, this.formatString(message, this.sanitizeObject(args)).replace(/&quot;/g, '"'));
  };

  var initialize = function () {
    // Restore Drupal 7 properties for BC reasons.
    Object.defineProperty(window.drupalSettings, 'basePath', {
      get: function () {
        return this.path.baseUrl;
      }
    });

    Object.defineProperty(window.drupalSettings, 'pathPrefix', {
      get: function () {
        return this.path.pathPrefix;
      }
    });

    var url = (window.drupalSettings.loaderFiles || []).shift();
    if (url) {
      Drupal.addToDom(url);
    }

    // Hold Drupal ready and behavior handlers from being invoked if there are
    // additional ES6 files to be loaded.
    if (window.drupalSettings.plusEs6Files && window.drupalSettings.plusEs6Files.length) {
      Drupal.holdReady(true);
      Drupal.holdBehaviors(true);
    }

    var readyHandler = function () {
      for (var i = 0, l = Drupal.readyHandlers.length; i < l; i++) {
        Drupal.readyHandlers[i].call({});
      }
    };

    var ready = function ready() {
      if (document.readyState !== 'loading') {
        fn();
      }
      else if (document.addEventListener) {
        document.addEventListener('DOMContentLoaded', fn);
      }
      else {
        document.attachEvent('onreadystatechange', function () {
          if (document.readyState !== 'loading') {
            fn();
          }
        });
      }
    };

  };

  /**
   * Backport of Drupal 8's drupalSettings.
   * 
   * @type {Object}
   */
  window.drupalSettings = {};
  var settingsElement = document.querySelector('head > script[type="application/json"][data-drupal-selector="drupal-settings-json"], body > script[type="application/json"][data-drupal-selector="drupal-settings-json"]');
  if (settingsElement !== null) {
    // Parse base64 encoded and gzipped settings.
    if ((settingsElement.dataset && settingsElement.dataset.drupalGzip) || settingsElement.getAttribute('data-drupal-gzip') === 'true') {
      Drupal.addToDom('https://cdn.jsdelivr.net/npm/pako@1.0.6/dist/pako.min.js', {
        crossOrigin: 'anonymous',
        integrity: 'sha256-9TLeW6tAsEKUUCX9AbSDY6A9F+O/p0mDFwLJEDvn5C8=',
        success: function () {
          try {
            var data = atob(settingsElement.textContent);
            var uncompressedData = window.pako.ungzip(data, {to: 'string'});
            var json = JSON.parse(uncompressedData);
            Drupal.settings = window.drupalSettings = json;
            initialize();
          }
          catch (e) {
            Drupal.fatal(e);
          }
        }
      });
    }
    // Parse normal JSON.
    else {
      try {
        var json = JSON.parse(settingsElement.textContent);
        Drupal.settings = window.drupalSettings = json;
        initialize();
      }
      catch (e) {
        Drupal.fatal(e);
      }
    }
  }

  // Determine if ES6 is not supported natively by the browser.
  if (!Drupal.support.es6) {
    Drupal.error(Drupal.t('This browser does not natively support ES6 JavaScript. A modern browser is required to access full functionality on this site. Please upgrade to a newer browser: @url'), {
      '@url': 'http://outdatedbrowser.com'
    });

    // Add babel-polyfill.
    Drupal.addToDom('https://cdn.jsdelivr.net/npm/babel-polyfill@6.26.0/dist/polyfill.min.js', {
      crossOrigin: 'anonymous',
      integrity: 'sha256-WRc/eG3R84AverJv0zmqxAmdwQxstUpqkiE+avJ3WSo='
    });

    // Show outdatedbrowser, if necessary.
    Drupal.ready(function () {
      var now = function () {
        return (new Date()).getTime();
      };

      // Immediately return if outdatedbrowser should explicitly not show or
      // if it was already closed and the elapsed time is less than a week.
      if (Drupal.settings.outdatedbrowser === false || (window.localStorage && now() - (parseInt(window.localStorage.getItem('outdatedbrowser'), 10) || 0) < (7 * 24 * 60 * 60 * 1000))) {
        return;
      }

      // Add necessary JS and CSS assets.
      Drupal.addToDom('https://cdn.jsdelivr.net/npm/outdatedbrowser@1.1.5/outdatedbrowser/outdatedbrowser.min.js', {
        crossOrigin: 'anonymous',
        integrity: 'sha256-yV0saZESxHBqfSfNncH044y3GHbsxLZJbQQmuxrXv90=',
        success: function () {
          Drupal.addToDom('https://cdn.jsdelivr.net/npm/outdatedbrowser@1.1.5/outdatedbrowser/outdatedbrowser.min.css', {
            crossOrigin: 'anonymous',
            integrity: 'sha256-KNfTksp/+PcmJJ0owdo8yBLi/SVMQrH/PNPm25nR/pI=',
            success: function () {
              if (outdatedBrowser) {
                var languages = ['ar', 'cs', 'da', 'de', 'el', 'en', 'es', 'es-pe', 'et', 'fa', 'fi', 'fr', 'hr', 'hu', 'id', 'it', 'ja', 'ko', 'lt', 'nb', 'nl', 'pl', 'pt', 'pt-br', 'ro', 'ru', 'sk', 'sl', 'sv', 'tr', 'uk', 'zh-cn', 'zh-tw'];
                var langcode = 'en';
                for (var i = 0, l = languages.length; i < l; i++) {
                  var language = languages[i];
                  if (language === Drupal.support.langcode) {
                    langcode = language;
                    break;
                  }
                  language = language.replace(/-.*$/, '');
                  if (language === Drupal.support.langcode) {
                    langcode = language;
                    break;
                  }
                }

                var div = document.createElement('div');
                div.id = 'outdated';
                document.body.insertBefore(div, document.body.firstChild);

                outdatedBrowser({
                  bgColor: '#f25648',
                  color: '#ffffff',
                  // Explicitly specify a a custom unsupported property so the
                  // message will always show (this "check" was already done).
                  lowerThan: 'js:Drupal.support.es6.false',
                  languagePath: 'https://cdn.jsdelivr.net/npm/outdatedbrowser@1.1.5/outdatedbrowser/lang/' + langcode + '.html'
                });

                // If there is jQuery, bind a click on the close button to
                // remember the state.
                if (Drupal.$document && window.localStorage) {
                  Drupal.$document
                    .on('click', '#btnUpdateBrowser', function (e) {
                      window.open(this.href);
                      e.preventDefault();
                      e.stopPropagation();
                    })
                    .on('mousedown', '#btnCloseUpdateBrowser', function () {
                      window.localStorage.setItem('outdatedbrowser', '' + now());
                    });
                }
              }
            }
          });
        }
      });
    });

    return;
  }

  // Polyfill setImmediate.
  if (typeof window.setImmediate !== 'function') {
    Drupal.addToDom('https://cdn.jsdelivr.net/npm/setimmediate@1.0.5/setImmediate.js', {
      crossOrigin: 'anonymous',
      integrity: 'sha256-ncLrj/g/FvQvpdlyG8FaxaCxLsqR0YW3hA2s8zgbPNc='
    });
  }

  // Polyfill Promise.done().
  // Note: it's easier to implement this polyfill  here rather than loading
  // an external resource for such a small LOC.
  // @see https://www.promisejs.org/polyfills/promise-done-7.0.4.min.js
  if (typeof window.Promise.prototype.done !== 'function') {
    Promise.prototype.done=function(t,n){var o=arguments.length?this.then.apply(this,arguments):this;o.then(null,function(t){setImmediate(function(){throw t})})};
  }

})(window.jQuery, window.Drupal);
