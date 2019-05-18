<?php

namespace Drupal\legal\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 source plugin for Legal T&Cs.
 *
 * @MigrateSource(
 *   id = "d7_legal_conditions",
 *   source_module = "legal",
 * )
 */
class LegalConditions extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('legal_conditions', 'c')
      ->fields('c')
      ->orderBy('c.tc_id');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {

    $fields = [
      'tc_id'  => $this->t('T&C ID'),
      'version'  => $this->t('Version'),
      'revision'  => $this->t('Revision'),
      'language'  => $this->t('Language'),
      'conditions'  => $this->t('Terms & Conditions'),
      'date'  => $this->t('Date'),
      'extras'  => $this->t('Extras'),
      'changes'  => $this->t('Changes'),
      'format'  => $this->t('Format'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tc_id']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $id = $row->getSourceProperty('fid');
    $row->setSourceProperty('condition_id', $id);

    return parent::prepareRow($row);
  }

}
