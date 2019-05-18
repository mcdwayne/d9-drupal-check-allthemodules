<?php

namespace Drupal\agreement\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Agreement type migrate source plugin.
 *
 * @MigrateSource(
 *   id = "agreement_type",
 *   source_module = "agreement"
 * )
 */
class AgreementType extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Unique name'),
      'type' => $this->t('Label'),
      'path' => $this->t('Path'),
      'settings' => $this->t('Settings'),
      'agreement' => $this->t('Agreement'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('agreement_type', 'agreement_type')
      ->fields(
        'agreement_type',
        ['name', 'type', 'path', 'settings', 'agreement']);
  }

}
