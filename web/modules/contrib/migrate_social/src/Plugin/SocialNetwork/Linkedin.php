<?php

namespace Drupal\migrate_social\Plugin\SocialNetwork;

use Drupal\group\Entity\GroupContentType;
use Drupal\plugin_type_example\SandwichBase;
use Drupal\migrate_social\SocialNetworkBase;
use Drupal\views\Views;

/**
 * Provides a twitter migrate plugin
 *
 * @SocialNetwork(
 *   id = "linkedin",
 *   description = @Translation("Linkedin migrate plugin.")
 * )
 */
class Linkedin extends SocialNetworkBase {

  /**
   * {@inheritdoc}
   */
  protected function nextSource() {
    if (!empty($this->configuration['source_type'])) {
      switch ($this->configuration['source_type']) {
        case 'company':
          $source_id = $this->configuration['source_id'];
          $result = $this->instance->setResponseDataType('array')
            ->get("v1/companies/$source_id/updates");

          if (!empty($result['values'])) {
            $this->iterator = new \ArrayIterator($result['values']);
            return TRUE;
          }

          break;
      }


    }

    return FALSE;
  }

  /**
   * Migrate ids.
   */
  public function getIds() {
    return [
      'updateKey' => [
        'type' => 'string',
        'max_length' => 64,
        'is_ascii' => TRUE,
      ],
    ];
  }

}
