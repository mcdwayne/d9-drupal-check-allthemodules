/**
 * @file
 * Tasks to run right after View handler building is complete.
 */

(function (ad_entity, behavior, document, settings) {

  // Run attachment on first page load,
  // without waiting for other Drupal behaviors.
  if (!(ad_entity.helpers.isEmptyObject(ad_entity.viewHandlers))) {
    behavior.attach(document, settings);
  }

}(Drupal.ad_entity, Drupal.behaviors.adEntityView, window.document, drupalSettings));
