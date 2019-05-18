<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Provides migration source for attribute values.
 *
 * @MigrateSource(
 *   id = "uc_attribute_value",
 *   source_module = "uc_attribute"
 * )
 */
class AttributeValue extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('uc_attribute_options', 'uco')
      ->fields('uca', [
        'aid',
        'label',
        'ordering',
        'required',
        'display',
        'description',
      ])
      ->fields('uco', [
        'aid',
        'oid',
        'cost',
        'price',
        'weight',
        'ordering',
      ])
      // Order migrations to allow for all databases.
      ->orderBy('oid');
    $query->addField('uca', 'aid', 'attribute_aid');
    $query->addField('uca', 'name', 'attribute_name');
    $query->addField('uco', 'aid', 'option_aid');
    $query->addField('uco', 'name', 'option_name');
    $query->leftJoin('uc_attributes', 'uca', 'uco.aid = uca.aid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return
      [
        'aid' => $this->t('Attribute id'),
        'oid' => $this->t('Options id'),
        'attribute_name' => $this->t('Attribute Name'),
        'option_name' => $this->t('Options Name'),
        'label' => $this->t('Label'),
        'ordering' => $this->t('Attribute display order'),
        'required' => $this->t('Attribute field required'),
        'display' => $this->t('Display type'),
        'weight' => $this->t('Option weight'),
        'description' => $this->t('Attribute description'),
        'cost' => $this->t('Cost'),
        'price' => $this->t('Price'),
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'aid' => [
        'type' => 'integer',
        'alias' => 'uca',
      ],
      'oid' => [
        'type' => 'integer',
        'alias' => 'uco',
      ],
    ];
  }

}
