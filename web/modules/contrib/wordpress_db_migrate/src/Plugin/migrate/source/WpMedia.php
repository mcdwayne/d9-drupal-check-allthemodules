<?php

namespace Drupal\wordpress_db_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * WordPress media migration source plugin.
 *
 * @MigrateSource(
 *   id = "wp_media"
 * )
 */
class WpMedia extends WpSqlBase {

  /**
   * List of fields available on wp_posts table.
   */
  protected function postFields() {
    return [
      'ID' => $this->t('Post ID.'),
      'post_author' => $this->t('Post Author.'),
      'post_date' => $this->t('Post Date.'),
      'post_date_gmt' => $this->t('Post Date (GMT).'),
      'post_content' => $this->t('Post Content.'),
      'post_title' => $this->t('Post Title.'),
      'post_status' => $this->t('Post Status.'),
      'post_modified' => $this->t('Post Modified.'),
      'post_modified_gmt' => $this->t('Post Modified (GMT).'),
      'post_parent' => $this->t('Post Parent.'),
      'post_mime_type' => $this->t('Post MIME Type.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array_merge($this->postFields(), [
      'post_meta' => $this->t('Post meta information'),
      'filepath' => $this->t('File path.'),
      'filename' => $this->t('File name.'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['ID']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('posts', 'p');
    $query->innerJoin('postmeta', 'm', 'p.ID = m.post_id');
    $query->condition('p.post_type', 'attachment');
    $query->condition('m.meta_key', '_wp_attached_file');
    $query->fields('p', array_keys($this->postFields()));
    $query->addField('m', 'meta_value', 'filepath');
    $query->orderBy('p.post_date');

    if (!empty($this->configuration['post_status'])) {
      $query->condition('p.post_status', (array) $this->configuration['post_status'], 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('post_meta', $this->getMetaValues(
      'postmeta',
      'post_id',
      $row->getSourceProperty('ID')));

    $row->setSourceProperty('filename', basename($row->getSourceProperty('filepath')));

    $row->setSourceProperty('post_date', $this->strToTime($row->getSourceProperty('post_date')));
    $row->setSourceProperty('post_modified', $this->strToTime($row->getSourceProperty('post_modified')));
    $row->setSourceProperty('post_date_gmt', $this->strToTimeUtc($row->getSourceProperty('post_date_gmt')));
    $row->setSourceProperty('post_modified_gmt', $this->strToTimeUtc($row->getSourceProperty('post_modified_gmt')));

    return parent::prepareRow($row);
  }

}
