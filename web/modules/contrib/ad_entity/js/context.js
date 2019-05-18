/**
 * @file
 * Fundamental JS implementation for applying Advertising context.
 *
 * @deprecated See README.md why this feature is deprecated.
 */

(function (ad_entity) {

  ad_entity.context = ad_entity.context || {};

  ad_entity.contextObjects = ad_entity.contextObjects || [];

  /**
   * Adds all context objects from the given DOM.
   *
   * @param {object} dom
   *   The DOM, usually provided by the Drupal context.
   */
  ad_entity.context.addFrom = function (dom) {
    var items = dom.querySelectorAll('script[data-ad-entity-context]');
    var length = items.length;
    var item;
    var i;
    var context_object;
    for (i = 0; i < length; i++) {
      item = items[i];
      context_object = JSON.parse(item.innerHTML);
      ad_entity.contextObjects.push(context_object);
      item.parentNode.removeChild(item);
    }
  };

  /**
   * Applies all known context objects on the newly collected Advertising containers.
   *
   * @param {object} newcomers
   *   The list of newly collected Advertising containers.
   */
  ad_entity.context.applyOn = function (newcomers) {
    var context_objects = ad_entity.contextObjects;
    var context_object;
    var id;
    var container;
    var to_apply;
    var ad_entity_id;
    var context_id;
    var context_settings;

    while (context_objects.length) {
      context_object = context_objects.shift();
      for (id in newcomers) {
        if (newcomers.hasOwnProperty(id)) {
          container = newcomers[id];

          // Determine whether to apply the given context
          // on the Advertising container.
          to_apply = true;
          if (context_object.hasOwnProperty('apply_on') && context_object.apply_on.length > 0) {
            ad_entity_id = container.data('data-ad-entity');
            if (context_object.apply_on.indexOf(ad_entity_id) < 0) {
              to_apply = false;
            }
          }

          if (to_apply) {
            // When given, let the corresponding implementation
            // of the context plugin perform the appliance.
            context_id = context_object.context_id;
            if (ad_entity.context.hasOwnProperty(context_id)) {
              context_settings = {};
              if (context_object.hasOwnProperty('settings')) {
                context_settings = context_object.settings;
              }
              ad_entity.context[context_id].apply(container, context_settings, newcomers);
            }
          }
        }
      }
    }
  };

}(window.adEntity));
