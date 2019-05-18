/**
 * Shamelessly copied from 'node-extend' to get around jQuery requirement.
 *
 * @author Stefan Thomas
 * @copyright 2014
 * @licence MIT
 *
 * @see https://github.com/justmoon/node-extend/blob/master/index.js
 */
(function (window, isPlainObject) {
  'use strict';

  var extend = function extend() {
    var options, name, src, copy, copyIsArray, clone;
    var target = arguments[0];
    var i = 1;
    var length = arguments.length;
    var deep = false;

    // Handle a deep copy situation
    if (typeof target === 'boolean') {
      deep = target;
      target = arguments[1] || {};
      // skip the boolean and the target
      i = 2;
    }
    if (target == null || (typeof target !== 'object' && typeof target !== 'function')) {
      target = {};
    }

    for (; i < length; ++i) {
      options = arguments[i];
      // Only deal with non-null/undefined values
      if (options != null) {
        // Extend the base object
        for (name in options) {
          src = target[name];
          copy = options[name];

          // Prevent never-ending loop
          if (target !== copy) {
            // Recurse if we're merging plain objects or arrays
            if (deep && copy && (isPlainObject(copy) || (copyIsArray = Array.isArray(copy)))) {
              if (copyIsArray) {
                copyIsArray = false;
                clone = src && Array.isArray(src) ? src : [];
              } else {
                clone = src && isPlainObject(src) ? src : {};
              }

              // Never move original objects, clone them
              target[name] = extend(deep, clone, copy);

              // Don't bring in undefined values
            } else if (typeof copy !== 'undefined') {
              target[name] = copy;
            }
          }
        }
      }
    }

    // Return the modified object
    return target;
  };

  window.extend = window.extend || extend;

})(window, window.isPlainObject);
