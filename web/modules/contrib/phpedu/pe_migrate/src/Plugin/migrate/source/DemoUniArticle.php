<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniArticle.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_article"
 * )
 */
class DemoUniArticle extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_article', 'pena')
      ->fields('pena', ['title', 'body', 'field_image_art', 'promote', 'sticky'])
      ->orderBy('title', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'body' => $this->t('Description'),
      'field_image_art' => $this->t('Image'),
      'promote' => $this->t('Promote'),
      'sticky' => $this->t('Sticky'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'title' => [
        'type' => 'string',
        'alias' => 'pena',
      ],
    ];
  }

}
