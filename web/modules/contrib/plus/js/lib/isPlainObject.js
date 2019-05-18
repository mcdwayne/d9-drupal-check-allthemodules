/**
 * Shamelessly copied from 'node-extend' to get around jQuery requirement.
 *
 * @author Stefan Thomas
 * @copyright 2014
 * @licence MIT
 *
 * @see https://github.com/justmoon/node-extend/blob/master/index.js
 */
(function (window) {
  'use strict';

  var hasOwn = Object.prototype.hasOwnProperty;
  var toStr = Object.prototype.toString;

  var isPlainObject = function isPlainObject(obj) {
    if (!obj || toStr.call(obj) !== '[object Object]') {
      return false;
    }

    var hasOwnConstructor = hasOwn.call(obj, 'constructor');
    var hasIsPrototypeOf = obj.constructor && obj.constructor.prototype && hasOwn.call(obj.constructor.prototype, 'isPrototypeOf');
    // Not own constructor property must be Object
    if (obj.constructor && !hasOwnConstructor && !hasIsPrototypeOf) {
      return false;
    }

    // Own properties are enumerated firstly, so to speed up,
    // if last one is own, then all properties are own.
    var key;
    for (key in obj) { /**/ }

    return typeof key === 'undefined' || hasOwn.call(obj, key);
  };

  window.isPlainObject = window.isPlainObject || isPlainObject;

})(window);
