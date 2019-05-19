(function ($, Drupal) {
  Drupal.smart_content.SmartContentManager = Drupal.smart_content.SmartContentManager || {};
  Drupal.smart_content.SmartContentManager.plugin = Drupal.smart_content.SmartContentManager.plugin || {};
  Drupal.smart_content.SmartContentManager.plugin.Field = Drupal.smart_content.SmartContentManager.plugin.Field || {};
  Drupal.smart_content.SmartContentManager.plugin.Field.segmentSmartCondition = {
    initCheckAppeased: function (Field) {
      // pluginId[0] = base plugin ID
      // pluginId[1] = Demandbase field key
      let pluginId = Field.pluginId.split(':');
      if(pluginId[0] !== 'smart_segment') {
        return;
      }
      let segment_is_appeased = true;
      Field.appeased = Field.appeased || false;
      if(Field.appeased) {
        return true;
      }
      for (let i = 0; i < Field.conditions.length; i++) {
        let Condition = Field.conditions[i];
        Drupal.smart_content.SmartContentManager.invoke('Field', 'initCheckAppeased', Condition.field);
        if(!Condition.appeased && Condition.field.processed) {
          Condition.appeased = true;
        }
        if(!Condition.appeased) {
          segment_is_appeased = false;
        }
      }
      if(segment_is_appeased) {
        Field.appeased = true;
        Field.complete(Field.conditions);
      }
    },
    init: function (Field) {
      // pluginId[0] = base plugin ID
      // pluginId[1] = Demandbase field key
      let pluginId = Field.pluginId.split(':');
      if(pluginId[0] !== 'smart_segment') {
        return;
      }
      console.log(Field);
      Field.claim();
      Field.type = 'smart_segment';
      for (let i = 0; i < Field.conditions.length; i++) {
        Field.conditions[i] = new Drupal.smart_content.model.Condition(Field.conditions[i]);
        let field = Drupal.smart_content.SmartContentManager.addFieldData(Field.conditions[i].field);
        Field.conditions[i].field = field;
      }
    }
  };

  Drupal.smart_content.SmartContentManager.condition_type = Drupal.smart_content.SmartContentManager.condition_type || {};
  Drupal.smart_content.SmartContentManager.condition_type.smart_segment = Drupal.smart_content.SmartContentManager.condition_type.smart_segment || {};
  Drupal.smart_content.SmartContentManager.condition_type.smart_segment.ConditionTypeSmartSegment = {
    evaluate: function (values, context) {
      let result = true;
      for (let i = 0; i < context.length; i++) {
        context[i].evaluateClientside();
        if(!context[i].result) {
          result = false;
        }
      }
      return result;
    }
  };

})(jQuery, Drupal);
