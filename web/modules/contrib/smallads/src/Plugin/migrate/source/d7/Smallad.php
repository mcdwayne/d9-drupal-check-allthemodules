<?php

namespace Drupal\smallads\Plugin\migrate\source\d7;

use Drupal\node\Plugin\migrate\source\d7\Node;
/**
 * Drupal 7 proposition nodes in source db.
 *
 * @MigrateSource(
 *   id = "d7_smallad",
 *   source_provider = "offers_wants"
 * )
 */
class Smallad extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->configuration['node_type'] = 'proposition';
    $query = parent::query();
    $want = $this->configuration['smallad_type'] == 'want';
    $query->innerJoin('offers_wants', 'ow', 'ow.nid = n.nid');
    $query->condition('ow.want', intval($want));
    $query->addField('ow', 'want');
    $query->addField('ow', 'end');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'end' => $this->t('Expiry date'),
      'want' => $this->t('Smallad bundle'),
    ] + parent::fields();
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n'
      ]
    ];
  }

}
