(function ($) {

  var SmartContentManager = Drupal.smart_content.SmartContentManager;
  SmartContentManager.condition_type = SmartContentManager.condition_type || {};
  SmartContentManager.condition_type.textfield = SmartContentManager.condition_type.textfield || {};
  SmartContentManager.condition_type.textfield.ConditionTypeStandard = {
    evaluate: function (values, context) {
      switch(values['op']) {
        case 'equals':
          return (String(context.toLowerCase()) === values['value'].toLowerCase());
          break;
        case 'starts_with':
          return (String(context).toLowerCase().substring(0, values['value'].length) === values['value'].toLowerCase());
          break;
        case 'empty':
          return (context.length === 0);
          break;
      }
    }
  };
  SmartContentManager.condition_type.key_value = SmartContentManager.condition_type.key_value || {};
  SmartContentManager.condition_type.key_value.ConditionTypeStandard = {
    evaluate: function (values, context) {
      switch(values['op']) {
        case 'equals':
          return (typeof context !== 'undefined') && (String(context.toLowerCase()) === values['value'].toLowerCase());
          break;
        case 'starts_with':
          return (typeof context !== 'undefined') && (String(context).toLowerCase().substring(0, values['value'].length) === values['value'].toLowerCase());
          break;
        case 'empty':
          return (typeof context !== 'undefined') && (context.length === 0);
          break;
        case 'is_set':
          return (typeof context !== 'undefined');
          break;
      }
    }
  };
  SmartContentManager.condition_type.boolean = SmartContentManager.condition_type.boolean || {};
  SmartContentManager.condition_type.boolean.ConditionTypeStandard = {
    evaluate: function (values, context) {
      return Boolean(context);
    }
  };
  SmartContentManager.condition_type.number = SmartContentManager.condition_type.number || {};
  SmartContentManager.condition_type.number.ConditionTypeStandard = {
    evaluate: function (values, context) {
      switch(values['op']) {
        case 'equals':
          return (Number(context) === Number(values['value']));
        case 'gt':
          return (Number(context) > Number(values['value']));
        case 'lt':
          return (Number(context) < Number(values['value']));
        case 'gte':
          return (Number(context) >= Number(values['value']));
        case 'lte':
          return (Number(context) <= Number(values['value']));
      }
    }
  };

  SmartContentManager.condition_type.value = SmartContentManager.condition_type.value || {};
  SmartContentManager.condition_type.value.ConditionTypeStandard = {
    evaluate: function (values, context) {
       return context;
    }
  }
})(jQuery);