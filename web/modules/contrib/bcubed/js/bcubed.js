(function() {

  function BCubedPlugin(obj) {
    // blank
  }

  BCubedPlugin.prototype.sendEvent = function(eventname, propertiesobj) {
    if (window.CustomEvent) {
      var event = typeof propertiesobj !== 'undefined' ? new CustomEvent(eventname, {detail: propertiesobj}) : new CustomEvent(eventname);
    } else {
      var event = document.createEvent('CustomEvent');
      if (typeof propertiesobj !== 'undefined') {
        event.initCustomEvent(eventname, true, true, propertiesobj);
      } else {
        event.initCustomEvent(eventname, true, true);
      }
    }
    document.body.dispatchEvent(event);
  };

  function BCubedEventGeneratorPlugin(obj) {
    BCubedPlugin.call(obj);
    for (var prop in obj) {
      if (obj.hasOwnProperty(prop)) {
        this[prop] = obj[prop];
      }
    }
  }

  BCubedEventGeneratorPlugin.prototype = Object.create(BCubedPlugin.prototype);
  BCubedEventGeneratorPlugin.prototype.constructor = BCubedEventGeneratorPlugin;

  BCubedEventGeneratorPlugin.prototype.allow_multiple_exec = false;
  BCubedEventGeneratorPlugin.prototype.executed = false;

  BCubedEventGeneratorPlugin.prototype.exec = function(args) {
    if (!this.executed || this.allow_multiple_exec) {
      this.executed = true;
      this.init(args);
    }
  };

  function BCubedConditionPlugin(obj) {
    BCubedPlugin.call(obj);
    for (var prop in obj) {
      if (obj.hasOwnProperty(prop)) {
        this[prop] = obj[prop];
      }
    }
  }

  BCubedConditionPlugin.prototype = Object.create(BCubedPlugin.prototype);
  BCubedConditionPlugin.prototype.constructor = BCubedConditionPlugin;

  function BCubedActionPlugin(obj) {
    BCubedPlugin.call(obj);
    for (var prop in obj) {
      if (obj.hasOwnProperty(prop)) {
        this[prop] = obj[prop];
      }
    }
  }
  BCubedActionPlugin.prototype = Object.create(BCubedPlugin.prototype);
  BCubedActionPlugin.prototype.constructor = BCubedActionPlugin;

  var plugins = {};

  /* plugins added here */

  var data = JSON.parse(atob(drupalSettings.bcubed));
  var exec_queue = [];
  for (var conditionset in data.conditionsets) {
    if (data.conditionsets.hasOwnProperty(conditionset)) {
      for (var event in data.conditionsets[conditionset].events) {
        if (data.conditionsets[conditionset].events.hasOwnProperty(event)) {
          document.body.addEventListener(data.conditionsets[conditionset].events[event].bcubed_js_event, eventHandlerFactory(conditionset, event), false);
          if (data.conditionsets[conditionset].events[event].plugin) {
            exec_queue.push({plugin: plugins[data.conditionsets[conditionset].events[event].plugin], args: {settings: data.conditionsets[conditionset].events[event].settings, strings: data.conditionsets[conditionset].events[event].generated_strings, pagetype: data.pagetype, conditionset: conditionset}});
          }
        }
      }
    }
  }

  var i, len = exec_queue.length;
  for (i=0; i<len; ++i) {
    exec_queue[i].plugin.exec(exec_queue[i].args);
  }

  function eventHandlerFactory(conditionset, event){
    return function(e) {
      e = e || window.event;
      // build events array
      var receivedevents = [];
      // set conditionset event received
      data.conditionsets[conditionset].events[event].received = e;
      // check if all of the events in conditionset have been received
      for (var testevent in data.conditionsets[conditionset].events) {
        if (data.conditionsets[conditionset].events.hasOwnProperty(testevent) && (data.conditionsets[conditionset].events[testevent].received == undefined)) {
          return false;
        }
        else {
          receivedevents.push(data.conditionsets[conditionset].events[testevent].received);
        }
      }
      // check conditions
      for (var condition in data.conditionsets[conditionset].conditions) {
        if (data.conditionsets[conditionset].conditions.hasOwnProperty(condition)) {
          if (data.conditionsets[conditionset].conditions[condition].plugin != null) {
            var val = plugins[data.conditionsets[conditionset].conditions[condition].plugin].condition({settings: data.conditionsets[conditionset].conditions[condition].settings, events: receivedevents, strings: data.conditionsets[conditionset].conditions[condition].generated_strings, pagetype: data.pagetype, conditionset: conditionset});

            if (!val) {
              return false;
            }

          }
        }
      }
      // all conditions met, execute actions
      for (var action in data.conditionsets[conditionset].actions) {
        if (data.conditionsets[conditionset].actions.hasOwnProperty(action)) {
          plugins[data.conditionsets[conditionset].actions[action].plugin].action({settings: data.conditionsets[conditionset].actions[action].settings, events: receivedevents, strings: data.conditionsets[conditionset].actions[action].generated_strings, pagetype: data.pagetype, conditionset: conditionset});
        }
      }
    }
  }

})();
