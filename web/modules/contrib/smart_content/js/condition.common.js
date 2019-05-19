(function ($) {
  var SmartContentManager = Drupal.smart_content.SmartContentManager;

  SmartContentManager.plugin = SmartContentManager.plugin || {};
  SmartContentManager.plugin.Field = SmartContentManager.plugin.Field || {};
  SmartContentManager.plugin.Field.commonSmartCondition = {
    initCheckAppeased: function (Field) {
      if (Field.pluginId == 'group') {
        var condition_is_appeased = true;
        Field.appeased = Field.appeased || false;
        if(Field.appeased) {
          return true;
        }
        for (var i = 0; i < Field.conditions.length; i++) {
          var Condition = Field.conditions[i];

          SmartContentManager.invoke('Field', 'initCheckAppeased', Condition.field);
          if(!Condition.appeased && Condition.field.processed) {
            Condition.appeased = true;
          }
          if(!Condition.appeased) {
            condition_is_appeased = false;
          }
        }
        if(condition_is_appeased) {
          Field.appeased = true;
          Field.complete(Field.conditions);
        }
      }
    },
    init: function (Field) {
      if(Field.pluginId == 'true') {
        Field.claim();
        Field.complete(true);
      }
      else if(Field.pluginId == 'group') {
        Field.claim();
        for (var i = 0; i < Field.conditions.length; i++) {
          Field.conditions[i] = new Drupal.smart_content.model.Condition(Field.conditions[i]);
          var field = SmartContentManager.addFieldData(Field.conditions[i].field);
          Field.conditions[i].field = field;
        }
      }

    }
  };

  SmartContentManager.condition_type = SmartContentManager.condition_type || {};
  SmartContentManager.condition_type.group = SmartContentManager.condition_type.group || {};
  SmartContentManager.condition_type.group.ConditionGroup = {
    evaluate: function (values, context) {
      var result = false;
      if(values.op == 'AND') {
        result = true;
        for (var i = 0; i < context.length; i++) {
          context[i].evaluateClientside();
          if(!context[i].result) {
            result = false;
          }
        }
      }
      if(values.op == 'OR') {
        for (var i = 0; i < context.length; i++) {
          context[i].evaluateClientside();
          if(context[i].result) {
            result = true;
          }
        }
      }
      return result;
    }
  }
  
  
})(jQuery);