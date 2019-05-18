<?php

namespace Drupal\migrate_social\Plugin\SocialNetwork;

use Drupal\group\Entity\GroupContentType;
use Drupal\plugin_type_example\SandwichBase;
use Drupal\migrate_social\SocialNetworkBase;
use Drupal\views\Views;

/**
 * Provides a group related content plugin.
 *
 * @SocialNetwork(
 *   id = "instagram",
 *   description = @Translation("Related content by group.")
 * )
 */
class Instagram extends SocialNetworkBase {

  /**
   * {@inheritdoc}
   */
  protected function nextSource() {
    $body = $this->instance->getUserMedia();

    // Instagram API return messy of stdClass and arrays.
    $array = json_decode(json_encode($body, JSON_FORCE_OBJECT), TRUE);

    if (!empty($array['data'])) {
      $this->iterator = new \ArrayIterator($array['data']);
      return TRUE;
    }

  }

}
