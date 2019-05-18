<?php

namespace Drupal\private_content\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Migrates private content.
 *
 * @MigrateSource(
 *   id = "d6_private_content"
 * )
 */
class PrivateContent extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('private', 'p')
      ->fields('p', array('nid', 'private'));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'nid' => $this->t('The nid of a node'),
      'private' => $this->t('The private status of a node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

}
