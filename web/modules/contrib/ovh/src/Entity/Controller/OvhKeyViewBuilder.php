<?php

namespace Drupal\ovh\Entity\Controller;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ovh\OvhHelper;

/**
 * Base class for entity view builders.
 *
 * @ingroup entity_api
 */
class OvhKeyViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {

    // Get default data.
    $build = parent::view($entity, $view_mode, $langcode);

    // Get and cache.
    $cache_key = 'ovh_api_key:' . $entity->id();
    if ($data = \Drupal::cache()->get($cache_key)) {
      $data = $data->data;
    }
    else {

      // Get data from API.
      try {
        $data = OvhHelper::ovhGet('/auth/currentCredential', $entity->id());
      }
      catch (\Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
      // Set cache.
      $cache_expire = (time() + 60);
      \Drupal::cache()
        ->set($cache_key, $data, $cache_expire, [$cache_key]);
    }

    // Dates info.
    $table = [
      ['creation', $data['creation']],
      ['lastUse', $data['lastUse']],
      ['expiration', $data['expiration']],
    ];
    $build[] = [
      '#caption' => 'Dates',
      "#theme" => 'table',
      "#rows" => $table,
    ];

    // Access rules.
    $table = [];
    foreach ($data['rules'] as $rule) {
      $table[] = [$rule['path'], $rule['method']];
    }
    $build[] = [
      '#caption' => 'Access rules',
      "#theme" => 'table',
      "#rows" => $table,
    ];

    return $build;
  }

}
