/**
 * @file
 * Content locker js.
 */

(function ($, window, Drupal) {

  /**
   * Insert new content into the DOM.
   * @param {object} ajax object ajax
   * @param {object} response object response
   * @param {object} status object status
   */
  if (Drupal.AjaxCommands) {
    Drupal.AjaxCommands.prototype.contentLockerUpdateEntity = function (ajax, response, status) {
      response.selector = '[data-content-locker-' + response.entityType + '-id=' + response.entityId + ']';
      this.insert(ajax, response, status);
      $(document).trigger('contentLockerUpdateEntity',
        {
          ltype: response.lockerType,
          etype: response.entityType,
          nid: response.entityId
        },
        {
          error: response.error
        }
      );
    };
  }

  Drupal.behaviors.contentLocker = {
    ajaxed: [],
    attach: function attach(context, settings) {
      $(document).once('content-locker', function () {
        Drupal.behaviors.contentLocker.ajaxed = [];
      });
      // Object.keys polyfil.
      if (!Object.keys) {
        Object.keys = (function () {
          var hasOwnProperty = Object.prototype.hasOwnProperty;
          var hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString');
          var dontEnums = [
            'toString',
            'toLocaleString',
            'valueOf',
            'hasOwnProperty',
            'isPrototypeOf',
            'propertyIsEnumerable',
            'constructor'
          ];
          var dontEnumsLength = dontEnums.length;

          return function (obj) {
            if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
              throw new TypeError('Object.keys called on non-object');
            }

            var result = []; var prop; var i;

            for (prop in obj) {
              if (hasOwnProperty.call(obj, prop)) {
                result.push(prop);
              }
            }

            if (hasDontEnumBug) {
              for (i = 0; i < dontEnumsLength; i++) {
                if (hasOwnProperty.call(obj, dontEnums[i])) {
                  result.push(dontEnums[i]);
                }
              }
            }
            return result;
          };
        }());
      }
    }
  };

  /**
   * Constructor
   * @param {object} type locker type
   * @constructor
   */
  Drupal.Locker = function (type) {
    if (type && type.length) {
      this.type = type;
    }
    this.lockevent = 'lock';
    this.unlockevent = 'unlock';
    this.lockedContentClass = '.locked-content';
    this.errorClass = 'locker-error';
  };

  /**
   * Set options
   * @param {object} obj options object
   */
  Drupal.Locker.prototype.setOptions = function (obj) {
    var self = this;
    if (obj) {
      Object.keys(obj).map(function (objKey) {
        self[objKey] = obj[objKey];
      });
    }
  };

  /**
   * Set user id
   * @param {number} uuid user id.
   */
  Drupal.Locker.prototype.setUserId = function (uuid) {
    this.uuid = uuid;
  };

  /**
   * Lock event action
   * @param {event} e event
   * @param {object} obj locker
   */
  Drupal.Locker.prototype.lockEventAction = function (e, obj) {
    var isAjax = (obj.isAjax) ? obj.isAjax : 0;
    var target = $(obj.lockedContentClass, obj.holder);
    if (!isAjax && target && target.length) {
      if (!Drupal.elementIsHidden(target)) {
        target.hide();
      }
    }
  };

  /**
   * Unlock event action.
   * @param {object} e Event
   * @param {object} obj Locker
   */
  Drupal.Locker.prototype.unlockEventAction = function (e, obj) {
    var isAjax = (obj.isAjax) ? obj.isAjax : null;
    var target = $(obj.lockedContentClass, obj.holder);
    var entity;
    if (isAjax && target && target.length) {
      var ajaxed = Drupal.behaviors.contentLocker.ajaxed;
      if (ajaxed.indexOf(obj.type) === -1) {
        ajaxed.push(obj.type);
      }
      var ajaxObject = Drupal.ajax({
        url: '',
        base: false,
        element: false,
        progress: false,
        dialogType: 'content_locker',
        dialog: {types: ajaxed}
      });
      ajaxObject.execute();
    }
    else if (!isAjax && target && target.length) {
      if (Drupal.elementIsHidden(target)) {
        this.hideLocker();
        entity = Drupal.getEntityData();
        if (entity) {
          this.onUpdateNode('unlock', entity.type, entity.id);
        }
        else {
          throw new Error('Can not get entity.');
        }
        target.show();
        this.clearContent(target, obj.holder);
      }
    }
  };

  /**
   * Clear content.
   * @param {element} target html element
   * @param {element} context html element
   */
  Drupal.Locker.prototype.clearContent = function (target, context) {
    $('.' + this.iconClass, context).remove();
    $('.' + this.errorClass, context).remove();
    $('.' + this.contentClass, context).remove();
    $('.' + this.actionClass, context).remove();
    target.unwrap();
  };

  /**
   * Is locked content.
   */
  Drupal.Locker.prototype.isLocked = function () {
  };

  /**
   * Fire event action.
   * @param {string} type event type
   * @param {element} el html element
   * @param {object} value payload
   */
  Drupal.Locker.prototype.fireEvent = function (type, el, value) {
    if (!el) { el = document;}
    $(el).trigger(type, this);
    if (typeof value !== 'undefined' && value === 0) {
      this.rejectEvent(value);
    }
  };


  /**
   * Locker reject action.
   */
  Drupal.Locker.prototype.rejectEvent = function () {
    var err = $('.' + this.errorClass, this.holder);
    var errormessage = 'Locker can not be unlocked.';
    if (this.options
            && this.options.general) {
      errormessage = this.options.general.error;
    }

    if (err && err.length) {
      err.html(errormessage);
      if (err.hasClass('element-hidden')) {
        err.removeClass('element-hidden');
      }
    }
  };

  /**
   * Hide locker action.
   */
  Drupal.Locker.prototype.hideLocker = function () {
    var iconLock = $(this.iconClass, this.holder);
    if (iconLock && iconLock.length) {
      iconLock.hide();
    }
    var lockerError = $(this.errorClass, this.holder);
    if (lockerError && lockerError.length) {
      lockerError.hide();
    }

    var lockerContent = $(this.contentClass, this.holder);
    if (lockerContent && lockerContent.length) {
      lockerContent.hide();
    }
    var wrapper = $('.cl-wrapper', this.holder);
    if (wrapper && wrapper.length) {
      wrapper.hide();
    }
  };

  /**
   * Show locked content action.
   */
  Drupal.Locker.prototype.showLocker = function () {
    var wrapper = $('.cl-wrapper', this.holder);
    if (wrapper && wrapper.length) {
      wrapper.show();
    }
    else {
      throw new Error('Can not find wrapper.');
    }
  };

  /**
   * Event on update node.
   * @param {event} event Event type
   * @param {string} etype Entity type
   * @param {number} nid Node id
   * @return {boolean} Non
   */
  Drupal.Locker.prototype.onUpdateNode = function (event, etype, nid) {
    if (!this.isCookie) {
      return false;
    }
    var options = null;
    if (this.cookieLife) {
      options = {
        expires: parseInt(this.cookieLife),
        path: '/'
      };
    }

    // save entity id to cookie for later;
    var old = {}; var payload; var result = {};
    var cdata = Drupal.cookies('lckr_' + this.type, '', {});
    if (cdata && cdata.length) {
      old = JSON.parse(cdata);
      old[etype][nid] = nid;
      payload = JSON.stringify(old);
      Drupal.cookies('lckr_' + this.type, payload, options);
    }
    else {
      result = {};
      result[etype] = {};
      result[etype][nid] = nid;
      payload = JSON.stringify(result);
      Drupal.cookies('lckr_' + this.type, payload, options);
    }
  };

  /**
   * On error action.
   * @param {event} e Event
   * @param {object} data Payload
   */
  Drupal.Locker.prototype.onError = function (e, data) {
    var lockerType = data.type;
    if (lockerType === this.type) {
      var err = $('.' + this.errorClass, this.holder);
      if (err && err.length) {
        err.html(this.options.general.error);
        err.removeClass('element-hidden');
      }
    }
  };

  /**
   * Hide error action
   */
  Drupal.Locker.prototype.hideError = function () {
    var err = $('.' + this.errorClass, $(this.holder));
    if (!err.hasClass('element-hidden')) {
      err.addClass('element-hidden');
    }
  };

  /**
   * Subscribe to events action.
   * @param {element} el html element
   * @param {object} obj locker
   */
  Drupal.Locker.prototype.subscribeEvents = function (el, obj) {
    var locker = obj;
    $(document).on(this.lockevent, function (e, source) {
      if (locker.type === source.type) {
        locker.lockEventAction(e, locker);
      }
    });
    $(document).on(this.unlockevent, function (e, source) {
      if (locker.type === source.type) {
        locker.unlockEventAction(e, locker, locker.type);
      }
    });
    $(document).on('contentLockerUpdateEntity', function (e, data) {
      locker.onUpdateNode(e, data.etype, data.nid);
      locker.hideLocker();
    });

    $(document).on('contentLockerError', function (e, data) {
      locker.onError(e, data);
    });
  };

  /**
   * Is processed action.
   * @param {element} el Html element
   * @return {boolean} isProcessed
   */
  Drupal.Locker.prototype.isProcessed = function (el) {
    var className = 'content-locker-processed';
    return !!(el.hasClass(className));
  };

  /**
   * Get error message.
   * @return {*} Error message
   */
  Drupal.Locker.prototype.getErrorMessage = function () {
    return this.errorMessage;
  };

  /**
   * Get entity data.
   * @return {*} Object
   */
  Drupal.getEntityData = function () {
    var lockers = $('.content-locker');
    if (lockers && lockers.length) {
      return {
        type: $(lockers[0]).data('content-locker-entitytype'),
        id: $(lockers[0]).data('content-locker-entityid')
      };
    }
    else {
      return false;
    }
  };

  /**
   * Get user id.
   * @return {*} User object
   */
  Drupal.userId = function () {
    var uuid = Drupal.cookies('user_twid', '', {});
    if (!uuid) {
      uuid = Drupal.guid();
      Drupal.cookies('user_twid', uuid, {});
    }
    return uuid;
  };

  /**
   * Generate unique user id.
   * @return {string} Id string
   */
  Drupal.guid = function () {
    function s4() {
      return Math.floor((1 + Math.random()) * 0x10000)
        .toString(16)
        .substring(1);
    }
    return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
      s4() + '-' + s4() + s4() + s4();
  };

  /**
   * Get locker options.
   * @param {object} settings Settings
   * @param {object} locker Locker
   * @param {string} type Locker type
   * @return {{}} Object
   */
  Drupal.getLockerOptions = function (settings, locker, type) {
    switch (type) {
      case 'general':
        return settings.content_locker.plugins[locker].general ? settings.content_locker.plugins[locker].general : {};
      case 'base':
        return settings.content_locker.base ? settings.content_locker.base : {};
      default:
        return settings.content_locker.plugins[locker] ? settings.content_locker.plugins[locker] : {};
    }
  };

  /**
   * Check if value exist.
   * @param {array} arr Array
   * @param {object} obj Object
   * @param {object} payload element
   * @return {boolean} Object
   */
  Drupal.exists = function (arr, obj, payload) {
    var found = false;
    arr.forEach(function (item) {
      if (item[payload] === obj[payload]
            && item.etype === obj.etype
              && obj.uuid === item.uuid) {
        found = true;
      }
    });
    return found;
  };

  /**
   * Convert string to camelCase.
   * @param {string} input Input string
   * @return {string} String
   */
  Drupal.camelCase = function (input) {
    return input.toLowerCase().replace(/-(.)/g, function (match) {
      return match.toUpperCase();
    });
  };

  /**
   * Capitalize first letter.
   * @param {string} input Input string
   * @return {string} String
   */
  Drupal.capitaliseFirstLetter = function (input) {
    return input.charAt(0).toUpperCase() + input.slice(1);
  };

  /**
   * Check if agent is mobile.
   * @return {boolean} isMobile
   */
  Drupal.isMobile = function () {
    if ((/webOS|iPhone|iPod|BlackBerry/i).test(navigator.userAgent)) { return true;}
    if ((/Android/i).test(navigator.userAgent) && (/Mobile/i).test(navigator.userAgent)) { return true; }
    return false;
  };

  /**
   * Get/Set cookies.
   * @param {string} key Cookie key
   * @param {string} value Payload
   * @param {object} options Options
   * @return {*}  Cookie
   */
  Drupal.cookies = function (key, value, options) {
    var defaultOptions = {
      expires: 365,
      path: '/'
    };
    if (!options || options.length === 0) {
      options = defaultOptions;
    }
    // Save cookie
    if (arguments.length > 1 && (!/Object/.test(Object.prototype.toString.call(value)) || value === null || value === 'undefined')) {
      options = $.extend({}, options);
      if (value === null || value === 'undefined') {
        options.expires = -1;
      }
      if (typeof options.expires !== 'number') {
        options.expires = parseInt(options.expires);
      }

      var days = options.expires; var t = options.expires = new Date();
      t.setDate(t.getDate() + days);

      value = String(value);
      document.cookie = [
        encodeURIComponent(key), '=', options.raw ? value : encodeURIComponent(value),
        options.expires ? '; expires=' + options.expires.toUTCString() : '',
        options.path ? '; path=' + options.path : '',
        options.domain ? '; domain=' + options.domain : '',
        options.secure ? '; secure' : ''
      ].join('');
      return document.cookie;
    }

    // Receive cookie.
    options = value || {};
    var decode = options.raw ? function (s) { return s; } : decodeURIComponent;

    var pairs = document.cookie.split('; ');
    for (var i = 0, pair; pair = pairs[i] && pairs[i].split('='); i++) {
      if (decode(pair[0]) === key) { return decode(pair[1] || ''); }
    }
    return null;
  };

  /**
   * Save value to cookie/localStorage
   * @param {string} name Cookie name
   * @param {mixed} value Payload
   * @param {number} expires Time
   */
  Drupal.saveValue = function (name, value, expires) {
    if (localStorage && localStorage.setItem) {
      try {
        localStorage.setItem(name, value);
      }
      catch (e) {
        Drupal.cookie(name, value, {expires: expires, path: '/'});
      }
    }
    else {
      Drupal.cookie(name, value, {expires: expires, path: '/'});
    }
  };

  /**
   * Get value from storage.
   * @param {string} name Cookie name
   * @param {string} defaultValue Default payload
   * @return {*} Result
   */
  Drupal.getValue = function (name, defaultValue) {
    var result = localStorage && localStorage.getItem && localStorage.getItem(name);
    if (!result) {result = Drupal.cookie(name);}
    if (!result) {return defaultValue;}
    return result;
  };

  /**
   * Make first letter as capital one.
   * @param {string} str Input string
   * @return {string} String
   */
  Drupal.ucFirst = function (str) {
    return str.charAt(0).toUpperCase() + str.substr(1, str.length - 1);
  };

  /**
   * Check if an element is not visible.
   * @param {element} el html element
   * @return {boolean} isHidden
   */
  Drupal.elementIsHidden = function (el) {
    return (el.offsetParent === null) ? true : (window.getComputedStyle(el[0]).display === 'none');
  };

  /**
   * Save viditor id.
   * @param {string} value Visitor id
   */
  Drupal.saveVisitorId = function (value) {
    Drupal.cookie('user_twid', value, {
      expires: 1000,
      path: '/'
    });
  };

  /**
   * Update query string
   * @param {string} uri URI
   * @param {string} key Payload
   * @param {string} value Value
   * @return {*} String
   */
  Drupal.updateQueryStr = function (uri, key, value) {
    var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
    var separator = uri.indexOf('?') !== -1 ? '&' : '?';

    if (uri.match(re)) { return uri.replace(re, '$1' + key + '=' + value + '$2'); }
    else { return uri + separator + key + '=' + value; }
  };

  /**
   * Prepare item
   * @param {string} etype Entity type
   * @param {number} uid User id
   * @param {number} nid Node id
   * @return {*} Item
   */
  Drupal.prepareItem = function (etype, uid, nid) {
    var item = {};
    item[etype] = {};
    item[etype][nid] = nid;
    return item;
  };
})(jQuery, window, Drupal);



