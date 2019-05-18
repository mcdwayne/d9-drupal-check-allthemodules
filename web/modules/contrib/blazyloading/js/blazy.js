/**
 * @file
 * A fast, small and dependency free lazy load script.
 */
(function (root, blazy) {
  'use strict';
  if (typeof define === 'function' && define.amd) {
    // AMD. Register bLazy as an anonymous module.
    define(blazy);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like environments that support module.exports,
    // like Node.
    module.exports = blazy();
  }
  else {
    // Browser globals. Register bLazy on window.
    root.Blazy = blazy();
  }
})(this, function () {
  'use strict';

  // Private vars.
  var _source;
  var _viewport;
  var _isRetina;
  var _supportClosest;
  var _attrSrc = 'src';
  var _attrSrcset = 'srcset';
  var eachValue = {};

  // Constructor.
  return function Blazy(options) {
    // IE7- fallback for missing querySelectorAll support.
    if (!document.querySelectorAll) {
      var s = document.createStyleSheet();
      document.querySelectorAll = function (r, c, i, j, a) {
        a = document.all;
        c = [];
        r = r.replace(/\[for\b/gi, '[htmlFor').split(',');
        for (i = r.length; i--;) {
          s.addRule(r[i], 'k:v');
          for (j = a.length; j--;) {
            if (a[j].currentStyle.k) { c.push(a[j]); }
          }
          s.removeRule(0);
        }
        return c;
      };
    }

    // Options and helper vars.
    var scope = this;
    var util = scope._util = {};
    util.elements = [];
    util.destroyed = true;
    scope.options = options || {};
    scope.options.error = scope.options.error || false;
    scope.options.offset = scope.options.offset || 100;
    scope.options.root = scope.options.root || document;
    scope.options.success = scope.options.success || false;
    scope.options.selector = scope.options.selector || '.b-lazy';
    scope.options.separator = scope.options.separator || '|';
    scope.options.containerClass = scope.options.container;
    scope.options.container = scope.options.containerClass ? document.querySelectorAll(scope.options.containerClass) : false;
    scope.options.errorClass = scope.options.errorClass || 'b-error';
    scope.options.breakpoints = scope.options.breakpoints || false;
    scope.options.loadInvisible = scope.options.loadInvisible || false;
    scope.options.successClass = scope.options.successClass || 'b-loaded';
    scope.options.validateDelay = scope.options.validateDelay || 25;
    scope.options.saveViewportOffsetDelay = scope.options.saveViewportOffsetDelay || 50;
    scope.options.srcset = scope.options.srcset || 'data-srcset';
    scope.options.src = _source = scope.options.src || 'data-src';
    _supportClosest = Element.prototype.closest;
    _isRetina = window.devicePixelRatio > 1;
    _viewport = {};
    _viewport.top = 0 - scope.options.offset;
    _viewport.left = 0 - scope.options.offset;

    scope.revalidate = function () {
      initialize(scope);
    };
    scope.load = function (elements, force) {
      var opt = this.options;
      if (elements && typeof elements.length === 'undefined') {
        loadElement(elements, force, opt);
      }
      else {
        each(elements, function (element) {
          loadElement(element, force, opt);
        });
      }
    };
    scope.destroy = function () {
      var util = scope._util;
      if (scope.options.container) {
        each(scope.options.container, function (object) {
          unbindEvent(object, 'scroll', util.validateT);
        });
      }
      unbindEvent(window, 'scroll', util.validateT);
      unbindEvent(window, 'resize', util.validateT);
      unbindEvent(window, 'resize', util.saveViewportOffsetT);
      util.count = 0;
      util.elements.length = 0;
      util.destroyed = true;
    };

    // Throttle, ensures that we don't call the functions too often.
    util.validateT = throttle(function () {
      validate(scope);
    }, scope.options.validateDelay, scope);
    util.saveViewportOffsetT = throttle(function () {
      saveViewportOffset(scope.options.offset);
    }, scope.options.saveViewportOffsetDelay, scope);
    saveViewportOffset(scope.options.offset);

    // Handle multi-served image src (obsolete).
    each(scope.options.breakpoints, function (object) {
      if (object.width >= window.screen.width) {
        _source = object.src;
        return false;
      }
    });

    // Start lazy load.
    setTimeout(function () {
      initialize(scope);
    });

  };


  // Private helper functions.
  function initialize(self) {
    var util = self._util;
    // First we create an array of elements to lazy load.
    util.elements = toArray(self.options);
    util.count = util.elements.length;
    // Then we bind resize and scroll events if not already bind.
    if (util.destroyed) {
      util.destroyed = false;
      if (self.options.container) {
        each(self.options.container, function (object) {
          bindEvent(object, 'scroll', util.validateT);
        });
      }
      bindEvent(window, 'resize', util.saveViewportOffsetT);
      bindEvent(window, 'resize', util.validateT);
      bindEvent(window, 'scroll', util.validateT);
    }
    // And finally, we start to lazy load.
    validate(self);
  }

  function validate(self) {
    var util = self._util;
    for (var i = 0; i < util.count; i++) {
      var element = util.elements[i];
      if (elementInView(element, self.options) || hasClass(element, self.options.successClass)) {
        self.load(element);
        util.elements.splice(i, 1);
        util.count--;
        i--;
      }
    }
    if (util.count === 0) {
      self.destroy();
    }
  }

  function elementInView(ele, options) {
    var rect = ele.getBoundingClientRect();

    if (options.container && _supportClosest) {
      // Is element inside a container.
      var elementContainer = ele.closest(options.containerClass);
      if (elementContainer) {
        var containerRect = elementContainer.getBoundingClientRect();
        // Is container in view?
        if (inView(containerRect, _viewport)) {
          var top = containerRect.top - options.offset;
          var right = containerRect.right + options.offset;
          var bottom = containerRect.bottom + options.offset;
          var left = containerRect.left - options.offset;
          var containerRectWithOffset = {
            top: top > _viewport.top ? top : _viewport.top,
            right: right < _viewport.right ? right : _viewport.right,
            bottom: bottom < _viewport.bottom ? bottom : _viewport.bottom,
            left: left > _viewport.left ? left : _viewport.left
          };
          // Is element in view of container.
          return inView(rect, containerRectWithOffset);
        }
        else {
          return false;
        }
      }
    }
    return inView(rect, _viewport);
  }

  function inView(rect, viewport) {
    // Intersection
    return rect.right >= viewport.left &&
      rect.bottom >= viewport.top &&
      rect.left <= viewport.right &&
      rect.top <= viewport.bottom;
  }

  function loadElement(ele, force, options) {
    if (options.cdnServerStatus && options.cdnServerUrl && getAttr(ele, _source) && (getAttr(ele, _source).indexOf('http') >= 0 || getAttr(ele, _source).indexOf('https') >= 0)) {
      // Change the height.
      if (getAttr(ele, 'width')) {
        options.cdnServerUrl = options.cdnServerUrl.replace('cdn_server_width', getAttr(ele, 'width'));
      }
      else {
        options.cdnServerUrl = options.cdnServerUrl.replace('cdn_server_width', '1000');
      }
      // Change the height.
      if (getAttr(ele, 'height')) {
        options.cdnServerUrl = options.cdnServerUrl.replace('cdn_server_height', getAttr(ele, 'height'));
      }
      else {
        options.cdnServerUrl = options.cdnServerUrl.replace('cdn_server_height', '1000');
      }
      // Change the source.
      var sourceUrl = getAttr(ele, _source);
      options.cdnServerUrl = options.cdnServerUrl.replace('source_image_url', sourceUrl);
      setAttr(ele, _source, options.cdnServerUrl);
    }
    // If element is visible, not loaded or forced
    if (!hasClass(ele, options.successClass) && (force || options.loadInvisible || (ele.offsetWidth >= 0 && ele.offsetHeight >= 0))) {
      var dataSrc = getAttr(ele, _source) || getAttr(ele, options.src);
      // Fallback to default data-src.
      if (dataSrc) {
        var dataSrcSplitted = dataSrc.split(options.separator);
        var src = dataSrcSplitted[_isRetina && dataSrcSplitted.length > 1 ? 1 : 0];
        var srcset = getAttr(ele, options.srcset);
        var isImage = equal(ele, 'img');
        var parent = ele.parentNode;
        var isPicture = parent && equal(parent, 'picture');
        // Image or background image
        if (isImage || ele.src === 'undefined') {
          var img = new Image();
          // Using EventListener instead of onerror and onload
          // due to bug introduced in chrome v50.
          var onErrorHandler = function () {
            if (options.error) {
              options.error(ele, 'invalid');
            }
            addClass(ele, options.errorClass);
            unbindEvent(img, 'error', onErrorHandler);
            unbindEvent(img, 'load', onLoadHandler);
          };
          var onLoadHandler = function () {
            // Is element an image.
            if (isImage) {
              if (!isPicture) {
                handleSources(ele, src, srcset);
              }
              // Background-image.
            }
            else {
              ele.style.backgroundImage = 'url("' + src + '")';
            }
            itemLoaded(ele, options);
            unbindEvent(img, 'load', onLoadHandler);
            unbindEvent(img, 'error', onErrorHandler);
          };

          // Picture element.
          if (isPicture) {
            img = ele;
            // Image tag inside picture element won't get preloaded.
            each(parent.getElementsByTagName('source'), function (source) {
              handleSource(source, _attrSrcset, options.srcset);
            });
          }
          bindEvent(img, 'error', onErrorHandler);
          bindEvent(img, 'load', onLoadHandler);
          handleSources(img, src, srcset);

        }
        else {
          // An item with src like iframe, unity games, simpel video etc.
          ele.src = src;
          itemLoaded(ele, options);
        }
      }
      else {
        // Video with child source.
        if (equal(ele, 'video')) {
          each(ele.getElementsByTagName('source'), function (source) {
            handleSource(source, _attrSrc, options.src);
          });
          ele.load();
          itemLoaded(ele, options);
        }
        else {
          if (options.error) {
            options.error(ele, 'missing');
          }
          addClass(ele, options.errorClass);
        }
      }
    }
  }

  function itemLoaded(ele, options) {
    addClass(ele, options.successClass);
    if (options.success) {
      options.success(ele);
    }
    // Cleanup markup, remove data source attributes.
    removeAttr(ele, options.src);
    removeAttr(ele, options.srcset);
    each(options.breakpoints, function (object) {
      removeAttr(ele, object.src);
    });
  }

  function handleSource(ele, attr, dataAttr) {
    var dataSrc = getAttr(ele, dataAttr);
    if (dataSrc) {
      setAttr(ele, attr, dataSrc);
      removeAttr(ele, dataAttr);
    }
  }

  function handleSources(ele, src, srcset) {
    if (srcset) {
      setAttr(ele, _attrSrcset, srcset);
    }
    ele.src = src;
  }

  function setAttr(ele, attr, value) {
    ele.setAttribute(attr, value);
  }

  function getAttr(ele, attr) {
    return ele.getAttribute(attr);
  }

  function removeAttr(ele, attr) {
    ele.removeAttribute(attr);
  }

  function equal(ele, str) {
    return ele.nodeName.toLowerCase() === str;
  }

  function hasClass(ele, className) {
    return (' ' + ele.className + ' ').indexOf(' ' + className + ' ') !== -1;
  }

  function addClass(ele, className) {
    if (!hasClass(ele, className)) {
      ele.className += ' ' + className;
    }
  }

  function toArray(options) {
    var array = [];
    var nodelist = (options.root).querySelectorAll(options.selector);
    for (var i = nodelist.length; i--; array.unshift(nodelist[i])) {
      // array[i] = nodelist[i];.
    }
    return array;
  }

  function saveViewportOffset(offset) {
    _viewport.bottom = (window.innerHeight || document.documentElement.clientHeight) + offset;
    _viewport.right = (window.innerWidth || document.documentElement.clientWidth) + offset;
  }

  function bindEvent(ele, type, fn) {
    if (ele.attachEvent) {
      if (ele.attachEvent) { ele.attachEvent('on' + type, fn); }
    }
    else {
      ele.addEventListener(type, fn, {capture: false, passive: true});
    }
  }

  function unbindEvent(ele, type, fn) {
    if (ele.detachEvent) {
      if (ele.detachEvent) { ele.detachEvent('on' + type, fn); }
    }
    else {
      ele.removeEventListener(type, fn, {capture: false, passive: true});
    }
  }

  function each(object, fn) {
    if (object && fn) {
      var l = object.length;
      for (var i = 0; i < l && fn(object[i], i) !== false; i++) {
        eachValue[i] = true;
      }
    }
  }

  function throttle(fn, minDelay, scope) {
    var lastCall = 0;
    return function () {
      var now = +new Date();
      if (now - lastCall < minDelay) {
        return;
      }
      lastCall = now;
      fn.apply(scope, arguments);
    };
  }
});
