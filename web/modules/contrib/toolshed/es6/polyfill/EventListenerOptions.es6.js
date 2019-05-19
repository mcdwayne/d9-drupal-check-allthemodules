
(() => {
  let supportsPassive = false;
  document.createElement('div').addEventListener('testPassive', () => {}, {
    get passive() {
      supportsPassive = true;
      return false;
    },
  });

  if (!supportsPassive) {
    const superPreventDefault = Event.prototype.preventDefault;

    // eslint-disable-next-line no-inner-declarations
    function parseOptions(type, listener, options, action) {
      let needsWrapping = false;
      let useCapture = false;
      let passive = false;
      let fieldId;

      if (options) {
        if (typeof (options) === 'object') {
          passive = Boolean(options.passive);
          useCapture = Boolean(options.useCapture);
        }
        else {
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
    }

    Event.prototype.preventDefault = () => {
      if (this.__passive) {
        // eslint-disable-next-line no-console
        console.warn('Ignored attempt to preventDefault an event from a passive listener');
        return;
      }
      superPreventDefault.apply(this);
    };

    // For IE 10 and below do not define or use the EventTarget class.
    if (window.EventTarget) {
      const superAddEventListener = EventTarget.prototype.addEventListener;
      const superRemoveEventListener = EventTarget.prototype.removeEventListener;

      EventTarget.prototype.addEventListener = (type, listener, options) => {
        const superThis = this;

        parseOptions(type, listener, options, (needsWrapping, fieldId, useCapture, passive) => {
          if (needsWrapping) {
            fieldId = useCapture.toString();
            fieldId += passive.toString();

            if (!this.__event_listeners_options) {
              this.__event_listeners_options = {};
            }
            if (!this.__event_listeners_options[type]) {
              this.__event_listeners_options[type] = {};
            }
            if (!this.__event_listeners_options[type][listener]) {
              this.__event_listeners_options[type][listener] = [];
            }
            if (this.__event_listeners_options[type][listener][fieldId]) {
              return;
            }

            listener = {
              handleEvent: (e) => {
                e.__passive = passive;
                if (typeof (listener) === 'function') {
                  listener(e);
                }
                else {
                  listener.handleEvent(e);
                }
                e.__passive = false;
              },
            };
            this.__event_listeners_options[type][listener][fieldId] = listener;
          }

          superAddEventListener.call(superThis, type, listener, useCapture);
        });
      };

      EventTarget.prototype.removeEventListener = (type, listener, options) => {
        const superThis = this;

        parseOptions(type, listener, options, (needsWrapping, fieldId, useCapture) => {
          if (needsWrapping &&
              this.__event_listeners_options &&
              this.__event_listeners_options[type] &&
              this.__event_listeners_options[type][listener] &&
              this.__event_listeners_options[type][listener][fieldId]) {
            const eventListenerFieldId = this.__event_listeners_options[type][listener][fieldId];
            superRemoveEventListener.call(superThis, type, eventListenerFieldId, false);
            delete this.__event_listeners_options[type][listener][fieldId];
            if (this.__event_listeners_options[type][listener].length === 0) {
              delete this.__event_listeners_options[type][listener];
            }
          }
          else {
            superRemoveEventListener.call(superThis, type, listener, useCapture);
          }
        });
      };
    }
  }
})();
