<?php

/**
 * @file
 * Advertising Entity theme implementations.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Crypt;

/**
 * Preprocess implementation for viewing an Advertising entity.
 *
 * @param array &$variables
 *   An array of variables.
 */
function template_preprocess_ad_entity(array &$variables) {
  /** @var \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity */
  $ad_entity = $variables['ad_entity'];
  $view_handler = $ad_entity->getViewPlugin();

  if (empty($view_handler)) {
    // Without a view handler, there's no output definition.
    return;
  }

  $definition = $view_handler->getPluginDefinition();
  // Pass through information about which container to use.
  $variables['container'] = $definition['container'];

  // Let the assigned view handler build the content.
  $variables['content'] = $view_handler->build($ad_entity);

  // It only makes sense to generate attributes and assets
  // when the ad container is being rendered as Html.
  if ($variables['container'] !== 'html') {
    return;
  }

  // Pass through global frontend settings.
  $variables['settings'] = _ad_entity_get_js_settings();

  // Generate attributes.
  $id = 'ad-entity-' . Crypt::randomBytesBase64(8);
  $attributes = new Attribute(['id' => $id]);
  $attributes->addClass('ad-entity-container');
  if ($ad_entity->get('disable_initialization')) {
    $attributes->addClass('initialization-disabled');
  }
  $attributes->addClass('not-initialized');
  $attributes->setAttribute('data-ad-entity', $ad_entity->id());
  $attributes->setAttribute('data-ad-entity-type', $ad_entity->get('type_plugin_id'));
  $attributes->setAttribute('data-ad-entity-view', $ad_entity->get('view_plugin_id'));

  // Let attributes defined by other components
  // add or override the generated ones.
  if (!empty($ad_entity->_attributes)) {
    foreach ($ad_entity->_attributes as $name => $value) {
      if ($name == 'class') {
        $attributes->addClass($value);
      }
      else {
        $attributes->setAttribute($name, $value);
      }
    }
  }
  $variables['attributes'] = $attributes;

  // Insert targeting from backend context data.
  $targeting_collection = $ad_entity->getTargetingFromContextData();
  if (!$targeting_collection->isEmpty()) {
    // Filter the targeting information before being displayed.
    $targeting_collection->filter();
    $variables['targeting'] = $targeting_collection->toJson();
  }
  else {
    $variables['targeting'] = '{}';
  }

  // Attach the initial JS for viewing Advertising entities.
  $variables['#attached']['library'][] = 'ad_entity/view';
  // Attach the JS implementation for the view handler.
  if (!empty($definition['library'])) {
    $variables['#attached']['library'][] = $definition['library'];
    // If it makes sense, also attach the viewready.js library.
    if (isset($definition['requiresDomready']) && ($definition['requiresDomready'] === FALSE)) {
      $variables['#attached']['library'][] = 'ad_entity/viewready';
    }
  }
}

/**
 * Preprocess implementation for delivering Advertising context.
 *
 * @param array &$variables
 *   An array of variables.
 */
function template_preprocess_ad_entity_context(array &$variables) {
  $item = $variables['item'];
  $definition = $variables['definition'];
  $id = $definition['id'];
  $context_settings = $item->get('context_settings')->getValue();
  $apply_on = $item->get('apply_on')->getValue();

  $variables['context_id'] = $id;
  // Build the context object.
  $variables['context'] = ['context_id' => $id];
  if (!empty($apply_on)) {
    $variables['context']['apply_on'] = $apply_on;
  }
  if (!empty($context_settings[$id])) {
    $variables['context']['settings'] = $context_settings[$id];
  }

  // Attach the fundamental JS implementation for applying Advertising context.
  $variables['#attached']['library'][] = 'ad_entity/context';
  // Attach the JS implementation for the context plugin.
  $variables['#attached']['library'][] = $definition['library'];
}

/**
 * Preprocess implementation for viewing a display config as iFrame.
 *
 * @param array &$variables
 *   An array of variables.
 */
function template_preprocess_ad_display_iframe(array &$variables) {
  /** @var \Drupal\ad_entity\Entity\AdDisplayInterface $ad_display */
  $ad_display = $variables['ad_display'];
  $attributes = [
    'width' => $variables['width'],
    'height' => $variables['height'],
    'class' => ['ad-display-iframe'],
  ];
  if ($ad_display->isNew()) {
    \Drupal::logger('ad_entity')->error(t('Tried to view a new, unsaved Advertising display config via iFrame. Only saved configs can be viewed via iFrame.'));
    $variables['#cache']['max-age'] = 0;
    $attributes['srcdoc'] = t('The display config can only be viewed when it is saved.');
  }
  else {
    $attributes['src'] = $ad_display->toUrl('canonical')->setAbsolute(TRUE)->toString();
  }
  $variables['attributes'] = new Attribute($attributes);
}
