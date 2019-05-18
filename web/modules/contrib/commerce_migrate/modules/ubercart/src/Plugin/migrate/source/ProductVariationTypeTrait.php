<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Sets traits property for dimensions and weight.
 */
trait ProductVariationTypeTrait {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // The dimensions and weights are relevant if commerce_shipping is enabled
    // on the destination.
    if ($this->getModuleHandler()->moduleExists('commerce_shipping')) {
      $row->setSourceProperty('has_dimensions', FALSE);
      $row->setSourceProperty('shippable', FALSE);

      // If any dimension column has non zero data then set has_dimensions true.
      $current_type = $row->getSourceProperty('type');
      $query = $this->select('node', 'n')
        ->fields('n')
        ->condition('type', $current_type);
      $query->leftJoin('uc_products', 'uc', 'n.nid = uc.nid AND n.vid=uc.vid');
      $or = $query->orConditionGroup()
        ->condition('length', 0, '!=')
        ->condition('width', 0, '!=')
        ->condition('height', 0, '!=');
      $query->condition($or);
      $num_rows = $query->countQuery()->execute()->fetchField();
      if ($num_rows) {
        $row->setSourceProperty('has_dimensions', TRUE);
      }

      // If the weight column has non zero data then set shippable true.
      $query = $this->select('node', 'n')
        ->fields('n')
        ->condition('type', $current_type);
      $query->leftJoin('uc_products', 'uc', 'n.nid = uc.nid AND n.vid=uc.vid');
      $query->condition('weight', 0, '!=');
      $num_rows = $query->countQuery()->execute()->fetchField();
      if ($num_rows) {
        $row->setSourceProperty('shippable', TRUE);
      }
    }
    parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [];
    if ($this->getModuleHandler()->moduleExists('commerce_shipping')) {
      $fields = [
        'has_dimensions' => $this->t('Set if this type has dimensions'),
        'shippable' => $this->t('Set if this type is shippable'),
      ];
    }
    return parent::fields() + $fields;
  }

}
