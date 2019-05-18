/**
 * @file
 * Provides various helper functions for ad_entity components.
 */

(function (adEntity, window) {

  /**
   * Triggers a custom event at the given target.
   *
   * @param {EventTarget} target
   *   The event target.
   * @param {string} type
   *   Is a DOMString containing the name of the event.
   * @param {boolean} canBubble
   *   Indicating whether the event bubbles up through the DOM or not.
   * @param {boolean} cancelable
   *   Indicating whether the event is cancelable.
   * @param {(object|number|string|boolean)} detail
   *   The data passed in when initializing the event.
   */
  adEntity.helpers.trigger = function (target, type, canBubble, cancelable, detail) {
    // This is deprecated but needed for IE compatibility.
    var event = window.document.createEvent('CustomEvent');
    if (typeof detail === 'undefined') {
      detail = null;
    }
    event.initCustomEvent(type, canBubble, cancelable, detail);
    target.dispatchEvent(event);
  };

  /**
   * Whether the given object is empty or not.
   *
   * @param {object} obj
   *   The object to check.
   *
   * @return {boolean}
   *   Returns true if the object is empty, false otherwise.
   */
  adEntity.helpers.isEmptyObject = function (obj) {
    var k;
    for (k in obj) {
      if (obj.hasOwnProperty(k)) {
        return false;
      }
    }
    return true;
  };

  /**
   * Adds a class name to the given DOM element.
   *
   * @param {Element} el
   *   The DOM element.
   * @param {string} className
   *   The class name to add.
   */
  adEntity.helpers.addClass = function (el, className) {
    if (el.classList) {
      el.classList.add(className);
    }
    else {
      el.className += ' ' + className;
    }
  };

  /**
   * Removes a class name to the given DOM element.
   *
   * @param {Element} el
   *   The DOM element.
   * @param {string} className
   *   The class name to remove.
   */
  adEntity.helpers.removeClass = function (el, className) {
    if (el.classList) {
      el.classList.remove(className);
    }
    else {
      el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
    }
  };

  /**
   * Checks whether the element has the given class name.
   *
   * @param {Element} el
   *   The DOM element to check for.
   * @param {string} className
   *   The class name to check.
   *
   * @return {boolean}
   *   Returns true in case the element has the class name, false otherwise.
   */
  adEntity.helpers.hasClass = function (el, className) {
    if (el.classList) {
      return el.classList.contains(className);
    }
    else {
      return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
    }
  };

  /**
   * Get or set arbitrary metadata to the given source and target.
   *
   * This method replaces the usage of jQuery's .data()
   * function inside all ad_entity components. Note that this
   * function is not converting data attributes to camel case.
   *
   * @param {object} source
   *   The source object to get metadata for. This is usually a DOM element.
   * @param {object} target
   *   The target object where to store the retrieved metadata.
   * @param {string} key
   *   (Optional) A string naming the piece of data to get or set.
   * @param {(object|number|string|boolean|function)} value
   *   (Optional) The new data value; this can be any Javascript type except undefined.
   *
   * @return {(object|number|string|boolean|function|undefined)}
   *   Returns the value if the key is given, or the whole metadata object if not.
   */
  adEntity.helpers.metadata = function (source, target, key, value) {
    var metadata;
    var length;
    var i;
    var attribute;
    var attribute_value;

    // Initialize metadata at first time access.
    if (typeof target.__ad_entity_metadata === 'undefined') {
      metadata = {};
      if ((typeof source.attributes === 'object') && (typeof source.getAttribute === 'function')) {
        length = source.attributes.length;
        for (i = 0; i < length; i++) {
          attribute = source.attributes[i];
          try {
            attribute_value = JSON.parse(attribute.value);
          }
          catch (e) {
            attribute_value = attribute.value;
          }
          metadata[attribute.name] = attribute_value;
        }
      }
      target.__ad_entity_metadata = metadata;
    }

    metadata = target.__ad_entity_metadata;
    if (typeof key === 'undefined') {
      return metadata;
    }

    if (typeof value !== 'undefined') {
      metadata[key] = value;
    }

    if (metadata.hasOwnProperty(key)) {
      return metadata[key];
    }
  };

}(window.adEntity, window));
