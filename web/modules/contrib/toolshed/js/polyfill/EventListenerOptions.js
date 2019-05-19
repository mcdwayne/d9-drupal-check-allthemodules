'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

(function () {
  var supportsPassive = false;
  document.createElement('div').addEventListener('testPassive', function () {}, {
    get passive() {
      supportsPassive = true;
      return false;
    }
  });

  if (!supportsPassive) {

    // eslint-disable-next-line no-inner-declarations
    var parseOptions = function parseOptions(type, listener, options, action) {
      var needsWrapping = false;
      var useCapture = false;
      var passive = false;
      var fieldId = void 0;

      if (options) {
        if ((typeof options === 'undefined' ? 'undefined' : _typeof(options)) === 'object') {
          passive = Boolean(options.passive);
          useCapture = Boolean(options.useCapture);
        } else {
          useCapture = options;
        }
      }

      if (passive) {
        needsWrapping = true;
      }
      if (needsWrapping) {
        fieldId = useCapture.toString() + passive.toString();
      }
      action(needsWrapping, fieldId, useCapture, passive);
    };

    var superPreventDefault = Event.prototype.preventDefault;

    Event.prototype.preventDefault = function () {
      if (undefined.__passive) {
        // eslint-disable-next-line no-console
        console.warn('Ignored attempt to preventDefault an event from a passive listener');
        return;
      }
      superPreventDefault.apply(undefined);
    };

    // For IE 10 and below do not define or use the EventTarget class.
    if (window.EventTarget) {
      var superAddEventListener = EventTarget.prototype.addEventListener;
      var superRemoveEventListener = EventTarget.prototype.removeEventListener;

      EventTarget.prototype.addEventListener = function (type, listener, options) {
        var superThis = undefined;

        parseOptions(type, listener, options, function (needsWrapping, fieldId, useCapture, passive) {
          if (needsWrapping) {
            fieldId = useCapture.toString();
            fieldId += passive.toString();

            if (!undefined.__event_listeners_options) {
              undefined.__event_listeners_options = {};
            }
            if (!undefined.__event_listeners_options[type]) {
              undefined.__event_listeners_options[type] = {};
            }
            if (!undefined.__event_listeners_options[type][listener]) {
              undefined.__event_listeners_options[type][listener] = [];
            }
            if (undefined.__event_listeners_options[type][listener][fieldId]) {
              return;
            }

            listener = {
              handleEvent: function handleEvent(e) {
                e.__passive = passive;
                if (typeof listener === 'function') {
                  listener(e);
                } else {
                  listener.handleEvent(e);
                }
                e.__passive = false;
              }
            };
            undefined.__event_listeners_options[type][listener][fieldId] = listener;
          }

          superAddEventListener.call(superThis, type, listener, useCapture);
        });
      };

      EventTarget.prototype.removeEventListener = function (type, listener, options) {
        var superThis = undefined;

        parseOptions(type, listener, options, function (needsWrapping, fieldId, useCapture) {
          if (needsWrapping && undefined.__event_listeners_options && undefined.__event_listeners_options[type] && undefined.__event_listeners_options[type][listener] && undefined.__event_listeners_options[type][listener][fieldId]) {
            var eventListenerFieldId = undefined.__event_listeners_options[type][listener][fieldId];
            superRemoveEventListener.call(superThis, type, eventListenerFieldId, false);
            delete undefined.__event_listeners_options[type][listener][fieldId];
            if (undefined.__event_listeners_options[type][listener].length === 0) {
              delete undefined.__event_listeners_options[type][listener];
            }
          } else {
            superRemoveEventListener.call(superThis, type, listener, useCapture);
          }
        });
      };
    }
  }
})();
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInBvbHlmaWxsL0V2ZW50TGlzdGVuZXJPcHRpb25zLmVzNi5qcyJdLCJuYW1lcyI6WyJzdXBwb3J0c1Bhc3NpdmUiLCJkb2N1bWVudCIsImNyZWF0ZUVsZW1lbnQiLCJhZGRFdmVudExpc3RlbmVyIiwicGFzc2l2ZSIsInBhcnNlT3B0aW9ucyIsInR5cGUiLCJsaXN0ZW5lciIsIm9wdGlvbnMiLCJhY3Rpb24iLCJuZWVkc1dyYXBwaW5nIiwidXNlQ2FwdHVyZSIsImZpZWxkSWQiLCJCb29sZWFuIiwidG9TdHJpbmciLCJzdXBlclByZXZlbnREZWZhdWx0IiwiRXZlbnQiLCJwcm90b3R5cGUiLCJwcmV2ZW50RGVmYXVsdCIsIl9fcGFzc2l2ZSIsImNvbnNvbGUiLCJ3YXJuIiwiYXBwbHkiLCJ3aW5kb3ciLCJFdmVudFRhcmdldCIsInN1cGVyQWRkRXZlbnRMaXN0ZW5lciIsInN1cGVyUmVtb3ZlRXZlbnRMaXN0ZW5lciIsInJlbW92ZUV2ZW50TGlzdGVuZXIiLCJzdXBlclRoaXMiLCJfX2V2ZW50X2xpc3RlbmVyc19vcHRpb25zIiwiaGFuZGxlRXZlbnQiLCJlIiwiY2FsbCIsImV2ZW50TGlzdGVuZXJGaWVsZElkIiwibGVuZ3RoIl0sIm1hcHBpbmdzIjoiOzs7O0FBQ0EsQ0FBQyxZQUFNO0FBQ0wsTUFBSUEsa0JBQWtCLEtBQXRCO0FBQ0FDLFdBQVNDLGFBQVQsQ0FBdUIsS0FBdkIsRUFBOEJDLGdCQUE5QixDQUErQyxhQUEvQyxFQUE4RCxZQUFNLENBQUUsQ0FBdEUsRUFBd0U7QUFDdEUsUUFBSUMsT0FBSixHQUFjO0FBQ1pKLHdCQUFrQixJQUFsQjtBQUNBLGFBQU8sS0FBUDtBQUNEO0FBSnFFLEdBQXhFOztBQU9BLE1BQUksQ0FBQ0EsZUFBTCxFQUFzQjs7QUFHcEI7QUFIb0IsUUFJWEssWUFKVyxHQUlwQixTQUFTQSxZQUFULENBQXNCQyxJQUF0QixFQUE0QkMsUUFBNUIsRUFBc0NDLE9BQXRDLEVBQStDQyxNQUEvQyxFQUF1RDtBQUNyRCxVQUFJQyxn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QkQ7QUFpQkQsT0FwQkQ7QUFxQkQ7QUFDRjtBQUNGLENBbkhEIiwiZmlsZSI6InBvbHlmaWxsL0V2ZW50TGlzdGVuZXJPcHRpb25zLmpzIiwic291cmNlc0NvbnRlbnQiOlsiXG4oKCkgPT4ge1xuICBsZXQgc3VwcG9ydHNQYXNzaXZlID0gZmFsc2U7XG4gIGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpLmFkZEV2ZW50TGlzdGVuZXIoJ3Rlc3RQYXNzaXZlJywgKCkgPT4ge30sIHtcbiAgICBnZXQgcGFzc2l2ZSgpIHtcbiAgICAgIHN1cHBvcnRzUGFzc2l2ZSA9IHRydWU7XG4gICAgICByZXR1cm4gZmFsc2U7XG4gICAgfSxcbiAgfSk7XG5cbiAgaWYgKCFzdXBwb3J0c1Bhc3NpdmUpIHtcbiAgICBjb25zdCBzdXBlclByZXZlbnREZWZhdWx0ID0gRXZlbnQucHJvdG90eXBlLnByZXZlbnREZWZhdWx0O1xuXG4gICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLWlubmVyLWRlY2xhcmF0aW9uc1xuICAgIGZ1bmN0aW9uIHBhcnNlT3B0aW9ucyh0eXBlLCBsaXN0ZW5lciwgb3B0aW9ucywgYWN0aW9uKSB7XG4gICAgICBsZXQgbmVlZHNXcmFwcGluZyA9IGZhbHNlO1xuICAgICAgbGV0IHVzZUNhcHR1cmUgPSBmYWxzZTtcbiAgICAgIGxldCBwYXNzaXZlID0gZmFsc2U7XG4gICAgICBsZXQgZmllbGRJZDtcblxuICAgICAgaWYgKG9wdGlvbnMpIHtcbiAgICAgICAgaWYgKHR5cGVvZiAob3B0aW9ucykgPT09ICdvYmplY3QnKSB7XG4gICAgICAgICAgcGFzc2l2ZSA9IEJvb2xlYW4ob3B0aW9ucy5wYXNzaXZlKTtcbiAgICAgICAgICB1c2VDYXB0dXJlID0gQm9vbGVhbihvcHRpb25zLnVzZUNhcHR1cmUpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgIHVzZUNhcHR1cmUgPSBvcHRpb25zO1xuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIGlmIChwYXNzaXZlKSB7XG4gICAgICAgIG5lZWRzV3JhcHBpbmcgPSB0cnVlO1xuICAgICAgfVxuICAgICAgaWYgKG5lZWRzV3JhcHBpbmcpIHtcbiAgICAgICAgZmllbGRJZCA9IHVzZUNhcHR1cmUudG9TdHJpbmcoKSArIHBhc3NpdmUudG9TdHJpbmcoKTtcbiAgICAgIH1cbiAgICAgIGFjdGlvbihuZWVkc1dyYXBwaW5nLCBmaWVsZElkLCB1c2VDYXB0dXJlLCBwYXNzaXZlKTtcbiAgICB9XG5cbiAgICBFdmVudC5wcm90b3R5cGUucHJldmVudERlZmF1bHQgPSAoKSA9PiB7XG4gICAgICBpZiAodGhpcy5fX3Bhc3NpdmUpIHtcbiAgICAgICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLWNvbnNvbGVcbiAgICAgICAgY29uc29sZS53YXJuKCdJZ25vcmVkIGF0dGVtcHQgdG8gcHJldmVudERlZmF1bHQgYW4gZXZlbnQgZnJvbSBhIHBhc3NpdmUgbGlzdGVuZXInKTtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuICAgICAgc3VwZXJQcmV2ZW50RGVmYXVsdC5hcHBseSh0aGlzKTtcbiAgICB9O1xuXG4gICAgLy8gRm9yIElFIDEwIGFuZCBiZWxvdyBkbyBub3QgZGVmaW5lIG9yIHVzZSB0aGUgRXZlbnRUYXJnZXQgY2xhc3MuXG4gICAgaWYgKHdpbmRvdy5FdmVudFRhcmdldCkge1xuICAgICAgY29uc3Qgc3VwZXJBZGRFdmVudExpc3RlbmVyID0gRXZlbnRUYXJnZXQucHJvdG90eXBlLmFkZEV2ZW50TGlzdGVuZXI7XG4gICAgICBjb25zdCBzdXBlclJlbW92ZUV2ZW50TGlzdGVuZXIgPSBFdmVudFRhcmdldC5wcm90b3R5cGUucmVtb3ZlRXZlbnRMaXN0ZW5lcjtcblxuICAgICAgRXZlbnRUYXJnZXQucHJvdG90eXBlLmFkZEV2ZW50TGlzdGVuZXIgPSAodHlwZSwgbGlzdGVuZXIsIG9wdGlvbnMpID0+IHtcbiAgICAgICAgY29uc3Qgc3VwZXJUaGlzID0gdGhpcztcblxuICAgICAgICBwYXJzZU9wdGlvbnModHlwZSwgbGlzdGVuZXIsIG9wdGlvbnMsIChuZWVkc1dyYXBwaW5nLCBmaWVsZElkLCB1c2VDYXB0dXJlLCBwYXNzaXZlKSA9PiB7XG4gICAgICAgICAgaWYgKG5lZWRzV3JhcHBpbmcpIHtcbiAgICAgICAgICAgIGZpZWxkSWQgPSB1c2VDYXB0dXJlLnRvU3RyaW5nKCk7XG4gICAgICAgICAgICBmaWVsZElkICs9IHBhc3NpdmUudG9TdHJpbmcoKTtcblxuICAgICAgICAgICAgaWYgKCF0aGlzLl9fZXZlbnRfbGlzdGVuZXJzX29wdGlvbnMpIHtcbiAgICAgICAgICAgICAgdGhpcy5fX2V2ZW50X2xpc3RlbmVyc19vcHRpb25zID0ge307XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoIXRoaXMuX19ldmVudF9saXN0ZW5lcnNfb3B0aW9uc1t0eXBlXSkge1xuICAgICAgICAgICAgICB0aGlzLl9fZXZlbnRfbGlzdGVuZXJzX29wdGlvbnNbdHlwZV0gPSB7fTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICghdGhpcy5fX2V2ZW50X2xpc3RlbmVyc19vcHRpb25zW3R5cGVdW2xpc3RlbmVyXSkge1xuICAgICAgICAgICAgICB0aGlzLl9fZXZlbnRfbGlzdGVuZXJzX29wdGlvbnNbdHlwZV1bbGlzdGVuZXJdID0gW107XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodGhpcy5fX2V2ZW50X2xpc3RlbmVyc19vcHRpb25zW3R5cGVdW2xpc3RlbmVyXVtmaWVsZElkXSkge1xuICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGxpc3RlbmVyID0ge1xuICAgICAgICAgICAgICBoYW5kbGVFdmVudDogKGUpID0+IHtcbiAgICAgICAgICAgICAgICBlLl9fcGFzc2l2ZSA9IHBhc3NpdmU7XG4gICAgICAgICAgICAgICAgaWYgKHR5cGVvZiAobGlzdGVuZXIpID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICAgICAgICBsaXN0ZW5lcihlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICBsaXN0ZW5lci5oYW5kbGVFdmVudChlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZS5fX3Bhc3NpdmUgPSBmYWxzZTtcbiAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIH07XG4gICAgICAgICAgICB0aGlzLl9fZXZlbnRfbGlzdGVuZXJzX29wdGlvbnNbdHlwZV1bbGlzdGVuZXJdW2ZpZWxkSWRdID0gbGlzdGVuZXI7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgc3VwZXJBZGRFdmVudExpc3RlbmVyLmNhbGwoc3VwZXJUaGlzLCB0eXBlLCBsaXN0ZW5lciwgdXNlQ2FwdHVyZSk7XG4gICAgICAgIH0pO1xuICAgICAgfTtcblxuICAgICAgRXZlbnRUYXJnZXQucHJvdG90eXBlLnJlbW92ZUV2ZW50TGlzdGVuZXIgPSAodHlwZSwgbGlzdGVuZXIsIG9wdGlvbnMpID0+IHtcbiAgICAgICAgY29uc3Qgc3VwZXJUaGlzID0gdGhpcztcblxuICAgICAgICBwYXJzZU9wdGlvbnModHlwZSwgbGlzdGVuZXIsIG9wdGlvbnMsIChuZWVkc1dyYXBwaW5nLCBmaWVsZElkLCB1c2VDYXB0dXJlKSA9PiB7XG4gICAgICAgICAgaWYgKG5lZWRzV3JhcHBpbmcgJiZcbiAgICAgICAgICAgICAgdGhpcy5fX2V2ZW50X2xpc3RlbmVyc19vcHRpb25zICYmXG4gICAgICAgICAgICAgIHRoaXMuX19ldmVudF9saXN0ZW5lcnNfb3B0aW9uc1t0eXBlXSAmJlxuICAgICAgICAgICAgICB0aGlzLl9fZXZlbnRfbGlzdGVuZXJzX29wdGlvbnNbdHlwZV1bbGlzdGVuZXJdICYmXG4gICAgICAgICAgICAgIHRoaXMuX19ldmVudF9saXN0ZW5lcnNfb3B0aW9uc1t0eXBlXVtsaXN0ZW5lcl1bZmllbGRJZF0pIHtcbiAgICAgICAgICAgIGNvbnN0IGV2ZW50TGlzdGVuZXJGaWVsZElkID0gdGhpcy5fX2V2ZW50X2xpc3RlbmVyc19vcHRpb25zW3R5cGVdW2xpc3RlbmVyXVtmaWVsZElkXTtcbiAgICAgICAgICAgIHN1cGVyUmVtb3ZlRXZlbnRMaXN0ZW5lci5jYWxsKHN1cGVyVGhpcywgdHlwZSwgZXZlbnRMaXN0ZW5lckZpZWxkSWQsIGZhbHNlKTtcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLl9fZXZlbnRfbGlzdGVuZXJzX29wdGlvbnNbdHlwZV1bbGlzdGVuZXJdW2ZpZWxkSWRdO1xuICAgICAgICAgICAgaWYgKHRoaXMuX19ldmVudF9saXN0ZW5lcnNfb3B0aW9uc1t0eXBlXVtsaXN0ZW5lcl0ubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICAgIGRlbGV0ZSB0aGlzLl9fZXZlbnRfbGlzdGVuZXJzX29wdGlvbnNbdHlwZV1bbGlzdGVuZXJdO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHN1cGVyUmVtb3ZlRXZlbnRMaXN0ZW5lci5jYWxsKHN1cGVyVGhpcywgdHlwZSwgbGlzdGVuZXIsIHVzZUNhcHR1cmUpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICB9O1xuICAgIH1cbiAgfVxufSkoKTtcbiJdfQ==
