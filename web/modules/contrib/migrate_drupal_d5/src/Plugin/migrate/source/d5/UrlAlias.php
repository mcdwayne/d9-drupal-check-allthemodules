<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal_d5\Plugin\migrate\source\d5\UrlAlias.
 */

namespace Drupal\migrate_drupal_d5\Plugin\migrate\source\d5;

use Drupal\path\Plugin\migrate\source\d6\UrlAlias as UrlAliasBase;

/**
 * Drupal 5 url aliases source from database.
 *
 * @MigrateSource(
 *   id = "d5_url_alias"
 * )
 */
class UrlAlias extends UrlAliasBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('url_alias', 'ua')
      ->fields('ua', array('pid', 'src', 'dst'));
    $query->orderBy('pid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    // No language in D5.
    unset($fields['language']);
    return $fields;
  }

}
