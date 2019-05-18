<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Provides migration source for attributes.
 *
 * @MigrateSource(
 *   id = "uc_attribute",
 *   source_module = "uc_attribute"
 * )
 */
class Attribute extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('uc_attributes', 'uca')->fields('uca');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return
      [
        'aid' => $this->t('Attribute id'),
        'name' => $this->t('Name'),
        'label' => $this->t('Label'),
        'ordering' => $this->t('Attribute display order'),
        'required' => $this->t('Attribute field required'),
        'display' => $this->t('Display type'),
        'description' => $this->t('Attribute description'),
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'aid' => [
        'type' => 'integer',
      ],
    ];
  }

}
