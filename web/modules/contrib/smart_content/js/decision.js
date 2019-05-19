(function ($) {

  var SmartContentManager = {};
  SmartContentManager.instance = {};
  SmartContentManager.instance.decisions = [];
  SmartContentManager.instance.fields = [];
  SmartContentManager.plugin = SmartContentManager.plugin || {};
  SmartContentManager.needs_processing = SmartContentManager.needs_processing || false;

  Drupal.smart_content = {};
  Drupal.smart_content.model = {};

  SmartContentManager.invoke = function(type, hook, object) {
    if(typeof SmartContentManager.plugin[type] !== 'undefined') {
      for (var name in SmartContentManager.plugin[type]) {
        if(typeof SmartContentManager.plugin[type][name][hook] === "function") {
          SmartContentManager.plugin[type][name][hook](object);
        }
      }
    }
  };

  SmartContentManager.getField = function (pluginId) {
    for (var i = 0; i < SmartContentManager.instance.fields.length; i++) {
      if(SmartContentManager.instance.fields[i].pluginId == pluginId) {
        return SmartContentManager.instance.fields[i];
      }
    }
  };

  SmartContentManager.addFieldData = function (field_data, skip_check) {
    field_data.unique = field_data.unique || false;
    var field;
    if(skip_check === true || field_data.unique || field_data.unique == 'true') {
      field = new Field(field_data);
      SmartContentManager.addField(field);
    }
    else {
      field = SmartContentManager.getField(field_data.pluginId);
      if(typeof field === 'undefined') {
        field = new Field(field_data);
        SmartContentManager.addField(field);
      }
    }
    return field;
  };

  SmartContentManager.addField = function (field) {
    SmartContentManager.instance.fields.push(field);
  };

  SmartContentManager.getDecision = function (name) {
    for (var i = 0; i < SmartContentManager.instance.decisions.length; i++) {
      if(SmartContentManager.instance.decisions[i].name == name) {
        return SmartContentManager.instance.decisions[i];
      }
    }
  };
  SmartContentManager.attach = function(data_attribute, context) {
    $('[' + data_attribute + ']', context).once('ajax-build').each(function (i, el) {
      var id = $(this).attr(data_attribute);
      if (typeof drupalSettings.smartContentDecisions !== 'undefined') {
        for (var d in drupalSettings.smartContentDecisions) {
          if (drupalSettings.smartContentDecisions[d].name == id) {
            var a = [].filter.call(this.attributes, function(at) { return /^data-context-/.test(at.name); })
            var context = {};
            Object.keys(a).forEach(function(key) {
              context[a[key].name.replace(/^(data-context-)/,"")] = a[key].value;
            })
            drupalSettings.smartContentDecisions[d].context = context;
            drupalSettings.smartContentDecisions[d].default_variation = this.getAttribute('data-default-id');
            Drupal.smart_content.SmartContentManager.init(drupalSettings.smartContentDecisions[d]);
            return;
          }
        }
      }
    });
  };


  SmartContentManager.addDecision = function (decision) {
    SmartContentManager.instance.decisions.push(decision);
  };

  SmartContentManager.init = function (settings) {
    if(!SmartContentManager.getDecision(settings.name)) {
      var decision = new Decision(settings);
      for (var i = 0; i < decision.variations.length; i++) {
        decision.variations[i] = new Variation(decision.variations[i]);
        for (var ii = 0; ii < decision.variations[i].conditions.length; ii++) {
          decision.variations[i].conditions[ii] = new Condition(decision.variations[i].conditions[ii]);
          var field = SmartContentManager.addFieldData(decision.variations[i].conditions[ii].field);
          decision.variations[i].conditions[ii].field = field;
        }
      }
      SmartContentManager.addDecision(decision);
      decision.process();
    } else {
      //@todo: allow for previous made decisions to be reused.
    }
  };

  SmartContentManager.queueProcessAll = function (status) {
    if(typeof status === 'undefined') {
      status = true;
    }
    SmartContentManager.needs_processing = status;
  };
  SmartContentManager.processAll = function () {
    //@todo: determine best way to queue processing
    for (var i = 0; i < SmartContentManager.instance.decisions.length; i++) {
      SmartContentManager.instance.decisions[i].process();
    }
    if(SmartContentManager.needs_processing) {
      SmartContentManager.queueProcessAll(false);
      SmartContentManager.processAll();
    }
  };


  var Decision = function (settings) {
    defaults = {
      name: '',
      agent: '',
      processed: false,
      executed: false,
      variations: [],
      params: [],
    };
    $.extend(this, defaults, settings);
    SmartContentManager.invoke('Decision', 'init', this);
  };
  Decision.prototype.process = function () {
    SmartContentManager.invoke('Decision', 'process', this);
    SmartContentManager.invoke(this.agent, 'process', this);
    if(this.processed && !this.executed) {
      this.execute();
    }
  };

  Decision.prototype.execute = function () {
    this.executed = true;
    SmartContentManager.invoke(this.agent, 'execute', this);
  };

  Drupal.smart_content.model.Decision = Decision;

  var Variation = function (settings) {
    defaults = {
      id: '',
      conditions: [],
      appeased: false,
    };
    $.extend(this, defaults, settings);
    SmartContentManager.invoke('Variation', 'init', this);
  };

  Variation.prototype.checkAppeased = function () {
    var variation_is_appeased = true;
    // Loop through all conditions to see if fields are processed.
    for (var ii = 0; ii < this.conditions.length; ii++) {
      var Condition = this.conditions[ii];
      SmartContentManager.invoke('Field', 'initCheckAppeased', Condition.field);
      if(!Condition.appeased && Condition.field.processed) {
        Condition.appeased = true;
      }
      // If still not appeassed, keep Variation appeased set to false.
      if(!Condition.appeased) {
        variation_is_appeased = false;
      }
    }
    this.appeased = variation_is_appeased;
  };

  Variation.prototype.evaluateClientside = function () {
    var result = true;
    for (var ii = 0; ii < this.conditions.length; ii++) {
      this.conditions[ii].evaluateClientside();
      if(!this.conditions[ii].result) {
        result = false;
      }
    }
    this.result = result;
  };

  Drupal.smart_content.model.Variation = Variation;

  var Condition = function (settings) {
    defaults = {
      field: {},
      appeased: false,
    };
    $.extend(this, defaults, settings);
    SmartContentManager.invoke('Condition', 'init', this);
  };

  Condition.prototype.evaluateClientside = function () {
    this.result = false;
    //@todo: determine if this needs to be alterable
    if(typeof this.field.type === 'undefined') {
      this.field.type = this.field.pluginId;
    }

    if(typeof SmartContentManager.condition_type[this.field.type] !== 'undefined'
      && typeof this.settings !== 'undefined'
    ) {
        for (var name in SmartContentManager.condition_type[this.field.type]) {
          if(typeof SmartContentManager.condition_type[this.field.type][name].evaluate === 'function') {
            var result =  SmartContentManager.condition_type[this.field.type][name].evaluate(this.settings, this.field.context);
            //@todo: standardize negate type.
            if(this.settings.negate === true || this.settings.negate === '1' || this.settings.negate === 1) {
              this.result = !result;
            } else {
              this.result = result;
            }
            if(this.result) {
              return;
            }
          }
        }
      }
    else {

    }

  };
  Drupal.smart_content.model.Condition = Condition;

  var Field = function (settings) {
    defaults = {
      pluginId: '',
      claimed: false,
      processed: false,
      context: '',
    };
    $.extend(this, defaults, settings);
    SmartContentManager.invoke('Field', 'init', this);
    if(!this.claimed) {
      this.claim();
      this.complete();
    }
  };


  Field.prototype.claim = function () {
    this.claimed = true;
  };

  Field.prototype.complete = function (context, force_processing) {
    this.processed = true;
    this.context = context;
    if(typeof force_processing !== 'undefined' && force_processing) {
      SmartContentManager.processAll();
    }
    else {
      SmartContentManager.queueProcessAll();
    }
  };


  Drupal.smart_content.model.Field = Field;





  //
  // Drupal.smart_content.model.Decision = function (name, agent) {
  //   this.name = name;
  //   this.agent = agent;
  //   this.variations = [];
  // }
  //
  // Drupal.smart_content.model.Decision.prototype.setVariation = function (id, override) {
  //   if(typeof override == undefined) {
  //     override = false;
  //   }
  // }


  //
  //
  // Drupal.behaviors.smartContentDecisions = {
  //   attach: function (context, settings) {
  //     // @todo: move all of this to individual decisions
  //     var found = false;
  //     $('[data-smart-content-decision]', context).once('ajax-build').each(function () {
  //       found = true;
  //       var $wrapper = $(this);
  //       Drupal.SmartDecisionManager.addDecision($wrapper.attr('data-smart-content-decision'));
  //     });
  //     if(found) {
  //       Drupal.SmartDecisionManager.execute();
  //     }
  //   }
  // }
  //
  //
  // Drupal.SmartDecisionManager = {}
  // Drupal.smart_content = Drupal.smart_content || {};
  // Drupal.smart_content.decisions = Drupal.smart_content.decisions || [];
  //
  // Drupal.SmartDecisionManager.addDecision = function (id) {
  //   if(Drupal.smart_content.decisions.indexOf(id) < 0) {
  //     Drupal.smart_content.decisions.push(id);
  //   }
  // }
  //
  // Drupal.SmartDecisionManager.execute  = function () {
  //   var decisions = Drupal.smart_content.decisions;
  //   for (d=0; d < decisions.length;d++) {
  //     var entity_id = decisions[d].split(/\.(.+)/)[1];
  //     var query = {data: {'condition_default:browser.language' : 'en', test2 : 'testttyeah'}};
  //     var url = '/ajax/smart-content/decision/config/' + entity_id;
  //     var ajaxObject = new Drupal.ajax({
  //       url: url,
  //       progress: false,
  //       success: function (response, status) {
  //         for (var i in response) {
  //           if (response.hasOwnProperty(i) && response[i].command && this.commands[response[i].command]) {
  //             this.commands[response[i].command](this, response[i], status);
  //           }
  //         }
  //       }
  //     });
  //     ajaxObject.options.data['context'] = $.param(query);
  //
  //     ajaxObject.execute();
  //   }
  // }



  // Drupal.SmartDecisionManager = function () {
  //   this.decisions = (typeof this.decisions === 'undefined') ? [] : this.decisions;
  // }
  // Drupal.SmartDecisionManager.prototype = {
  //
  //   /**
  //    * Command to insert new content into the DOM.
  //    *
  //    * @param {Drupal.Ajax} ajax
  //    *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
  //    * @param {object} response
  //    *   The response from the Ajax request.
  //    * @param {string} response.data
  //    *   The data to use with the jQuery method.
  //    * @param {string} [response.method]
  //    *   The jQuery DOM manipulation method to be used.
  //    * @param {string} [response.selector]
  //    *   A optional jQuery selector string.
  //    * @param {object} [response.settings]
  //    *   An optional array of settings that will be used.
  //    * @param {number} [status]
  //    *   The XMLHttpRequest status.
  //    */
  //   addDecision: function (id) {
  //
  //   }
  // }

  Drupal.smart_content.SmartContentManager = SmartContentManager;

})(jQuery);

// // addRequirement(force)
// var requirements = [
//   'field.name.1'
//   'field.name.3'
// ];
//
// // claimRequirement
// var processing = [
//   'field.name.2'
// ];
//
// // fulfillRequirement
// var processed = [
//   'field.name.4'
//   'field.name.5'
// ];
//
// var fields = [
//   {
//     field_name: 'field.name.4'
//     source: 'browser'
//     type: 'textfield'
//     status: 'unclaimed|claimed|completed'
//     context: ''
//   }
// ];
//
// // on fulfillment, checkReadytoExecute
// var decisionsClient = [
//   {
//     name: 'placeholder.value1'
//     variations: [
//       {
//         id: <variation uuid>
//       conditions: [
//   {
//     field: {
//       field_name: 'field.name.4'
//       source: 'browser'
//       type: 'textfield'
//       status: 'unclaimed|claimed|completed'
//       context: ''
//     }
//     settings: {}
//     negated: false
//   }
// ]
// requirements: []
// fallback: {}
// }
// ]
// ]