<?php

/**
 * @file
 * Preprocessors and theme functions of OwnTracks module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Prepares variables for owntracks_map templates.
 *
 * Default template: owntracks-map.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - track: An array of Lat/Lang coordinates.
 */
function template_preprocess_owntracks_map(array &$variables) {
  $id = Html::getUniqueId('owntracks-map');
  $variables['attributes'] = new Attribute([
    'id' => $id,
    'class' => ['owntracks-map'],
  ]);

  $variables['#attached']['library'][] = 'owntracks/owntracks';
  $variables['#attached']['drupalSettings']['owntracks']['track'][$id] = $variables['track'];

  $config = \Drupal::config('owntracks.map.settings');
  $variables['#attached']['drupalSettings']['owntracks']['map'] = [
    'tileLayerUrl' => Html::escape($config->get('tileLayerUrl')),
    'tileLayerAttribution' => Xss::filterAdmin($config->get('tileLayerAttribution')),
    'polylineColor' => Html::escape($config->get('polylineColor')),
  ];
}
