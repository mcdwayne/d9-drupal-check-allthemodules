(function ($, Drupal) {
  Drupal.behaviors.pagedesigner_init_default_trait = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-default-trait', function (e, editor) {
        const TraitManager = editor.TraitManager;
        // use Object.assign({}, TraitManager.defaultTrait, {...})  to create a new trait type
        // use the getRendeValue function to define how the trait is rendered in preview mode
        var defaultTrait = {
          events:{
            'keyup': 'onChange',
          },
          getRenderValue: function(value){
            return value;
          },
          setValueFromAssetManager: function (value) {
            this.model.set('value', value);
          },
          onValueChange(model, value) {
            var opts = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
            if (opts.fromTarget) {
              value = this.model.get('value');
              this.model.renderValue = this.getRenderValue(value);
              this.setInputValue(value);
            } else {
              var value = this.getValueForTarget();
              this.model.renderValue = this.getRenderValue(value);
              this.model.setTargetValue(value, opts);
            }

            for (var targetField in this.model.attributes.relations ) {
              var sourceKey = this.model.attributes.relations[targetField].source_key;
              var overrideTarget = this.model.attributes.relations[targetField].override;
              var targetTrait = this.target.getTrait(targetField);
              if( sourceKey ){
                var sourceValue = value[sourceKey];
              }else{
                var sourceValue = value;
              }
              var targetKey = this.model.attributes.relations[targetField].target_key;
              if( targetKey ){
                var targetValue = targetTrait.getTargetValue() || {};
                if( overrideTarget || ( !overrideTarget && !targetValue[targetKey] ) ){
                  targetValue[targetKey] = sourceValue;
                  targetTrait.setTargetValue({});
                  targetTrait.setTargetValue( targetValue );
                }
              }else{
                if( overrideTarget || ( !overrideTarget && !targetTrait.getTargetValue() ) ){
                  targetTrait.setTargetValue( sourceValue );
                }
              }
            }
          },
        }
        TraitManager.defaultTrait = defaultTrait;
      });
    }
  };
})(jQuery, Drupal);
