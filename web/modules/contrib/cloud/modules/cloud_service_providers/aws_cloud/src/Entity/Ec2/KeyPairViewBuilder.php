<?php

namespace Drupal\aws_cloud\Entity\Ec2;

/**
 * Provides the key pair view builders.
 */
class KeyPairViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'key_pair',
        'title' => t('Key Pair'),
        'open' => TRUE,
        'fields' => [
          'key_fingerprint',
          'key_material',
        ],
      ],
      [
        'name' => 'others',
        'title' => t('Others'),
        'open' => FALSE,
        'fields' => [
          'cloud_context',
          'uid',
        ],
      ],
    ];
  }

}
