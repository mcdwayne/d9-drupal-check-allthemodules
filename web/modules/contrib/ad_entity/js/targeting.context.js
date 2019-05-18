/**
 * @file
 * JS handler implementation for the 'targeting' context.
 *
 * @deprecated See README.md why this feature is deprecated.
 */

(function (ad_entity) {

  ad_entity.context = ad_entity.context || {};

  ad_entity.context.targeting = {
    apply: function (container, context_settings, newcomers) {
      var targeting;
      var context_targeting;
      if (context_settings.hasOwnProperty('targeting')) {
        context_targeting = context_settings.targeting;
        if (typeof context_targeting === 'object') {
          targeting = container.data('data-ad-entity-targeting');
          if (typeof targeting !== 'object') {
            targeting = {};
          }

          this.merge(targeting, context_targeting);

          container.data('data-ad-entity-targeting', targeting);
          container.el.setAttribute('data-ad-entity-targeting', JSON.stringify(targeting));
        }
      }
    },
    merge: function (targeting, context_targeting) {
      // Merge the targeting with the given context targeting.
      var key;
      var item_length;
      var i;
      for (key in context_targeting) {
        if (context_targeting.hasOwnProperty(key)) {
          if (targeting.hasOwnProperty(key)) {
            if (targeting[key] === context_targeting[key]) {
              continue;
            }
            if (!(Array.isArray(targeting[key]))) {
              targeting[key] = [targeting[key]];
            }
            if (!(Array.isArray(context_targeting[key]))) {
              context_targeting[key] = [context_targeting[key]];
            }
            item_length = context_targeting[key].length;
            for (i = 0; i < item_length; i++) {
              if (targeting[key].indexOf(context_targeting[key][i]) < 0) {
                targeting[key].push(context_targeting[key][i]);
              }
            }
          }
          else {
            targeting[key] = context_targeting[key];
          }
        }
      }
    }
  };

}(window.adEntity));
