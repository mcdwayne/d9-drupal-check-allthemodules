<?php

/**
 * @file
 * Defines tokens for Cumul.io dashboards.
 *
 * Cumulio.tokens.inc.
 */

use Cumulio\Cumulio;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_token_info().
 */
function cumulio_token_info() {
  $types['cumulio'] = [
    'name' => t('Cumulio tokens'),
    'description' => t('Tokens related to the cumulio integration.'),
  ];

  // Id token.
  $cumulio['id'] = [
    'name' => t("Dashboard token"),
    'description' => t('The unique ID of the cumulio dashboard'),
    'dynamic' => TRUE,
  ];

  $query = \Drupal::database()->select('cumulio_entity', 'ce');
  $query->fields('ce', ['id', 'name']);
  $entities = $query->execute()->fetchAll();

  foreach ($entities as $entity) {
    // @TODO: add tokens to subgroup.
    $cumulio['entity_id_' . $entity->id] = [
      'name' => $entity->name,
      'description' => t('Content entity') . ' ' . $entity->name,
    ];
  }

  return [
    'types' => $types,
    'tokens' => [
      'cumulio' => $cumulio,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function cumulio_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'cumulio') {
    if ($query_tokens = \Drupal::token()->findWithPrefix($tokens, 'id')) {
      foreach ($query_tokens as $dashboardId => $original) {
        // We get the iframe for the dashboardId.
        $replacements[$original] = cumulio_replace_id($dashboardId);
        $bubbleable_metadata->addCacheableDependency('cumulio_form');
      }
    }

    // Entity tokens.
    foreach ($tokens as $token => $original) {
      if (strpos($token, 'entity_id_') === 0) {
        $id = explode('_', $token)[2];

        // Build up cumulio entity.
        $entity = \Drupal::entityTypeManager()->getStorage('cumulio_entity')->load($id);
        if ($entity) {
          $view_builder = \Drupal::entityTypeManager()->getViewBuilder('cumulio_entity');
          $build = $view_builder->view($entity);

          // Render entity as a replacement.
          $replacements[$original] = render($build);
        }
      }
    }
  }

  return $replacements;
}

/**
 * Method to get the iframe from the dashboard.
 */
function cumulio_replace_id($dashboardId) {
  $config = \Drupal::config('cumulio.settings');
  $token = $config->get('api_token');
  $key = $config->get('api_key');
  $authorization = [
    'id' => $key,
    'token' => $token,
  ];
  $cumulio = Cumulio::initialize($key, $token);
  // Create connection to the api and get the dashboard for the correct id.
  $iframe = $cumulio->iframe($dashboardId, $authorization);
  $markup = '<iframe style="border: none;" width="100%" height="400px" src="' . $iframe . '"></iframe>';
  return Markup::create($markup);
}
