<?php

namespace Drupal\wordpress_db_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * WordPress post migration source plugin.
 *
 * @MigrateSource(
 *   id = "wp_post"
 * )
 */
class WpPost extends WpSqlBase {

  /**
   * @var string
   *
   * Source permalink structure.
   */
  protected $wpPermalinkStructure;

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
      'post_excerpt' => $this->t('Post Excerpt.'),
      'post_status' => $this->t('Post Status.'),
      'comment_status' => $this->t('Comment Status.'),
      'ping_status' => $this->t('Ping Status.'),
      'post_password' => $this->t('Post Password.'),
      'post_name' => $this->t('Post Name.'),
      'to_ping' => $this->t('To Ping.'),
      'pinged' => $this->t('Pinged.'),
      'post_modified' => $this->t('Post Modified.'),
      'post_modified_gmt' => $this->t('Post Modified (GMT).'),
      'post_content_filtered' => $this->t('Post Content (filtered).'),
      'post_parent' => $this->t('Post Parent.'),
      'guid' => $this->t('Post GUID.'),
      'menu_order' => $this->t('Menu Order.'),
      'post_type' => $this->t('Post Type.'),
      'post_mime_type' => $this->t('Post MIME Type.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array_merge($this->postFields(), [
      'post_terms' => $this->t('Post Terms'),
      'post_meta' => $this->t('Post Meta.'),
      'post_permalink' => $this->t('Post permalink.'),
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
    $query->fields('p', array_keys($this->postFields()));
    $query->orderBy('p.post_date');

    if (!empty($this->configuration['post_type'])) {
      $query->condition('p.post_type', (array) $this->configuration['post_type'], 'IN');
    }

    $post_status = !empty($this->configuration['post_status']) ? (array) $this->configuration['post_status'] : ['publish'];
    $query->condition('p.post_status', $post_status, 'IN');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $post_id = $row->getSourceProperty('ID');

    $row->setSourceProperty('post_permalink', $this->generatePathAlias($row));

    $row->setSourceProperty('post_meta', $this->getMetaValues(
      'postmeta',
      'post_id',
      $post_id));

    $row->setSourceProperty('post_date', $this->strToTime($row->getSourceProperty('post_date')));
    $row->setSourceProperty('post_modified', $this->strToTime($row->getSourceProperty('post_modified')));
    $row->setSourceProperty('post_date_gmt', $this->strToTimeUtc($row->getSourceProperty('post_date_gmt')));
    $row->setSourceProperty('post_modified_gmt', $this->strToTimeUtc($row->getSourceProperty('post_modified_gmt')));

    $row->setSourceProperty('post_terms', $this->getRelatedTerms($post_id));

    return parent::prepareRow($row);
  }

  /**
   * Generate path alias via pattern specified in `permalink_structure`.
   *
   * @param \Drupal\migrate\Row $row
   *   The row being processed.
   *
   * @return string
   *   The post's permalink.
   */
  protected function generatePathAlias(Row $row) {
    if (empty($this->wpPermalinkStructure)) {
      $this->wpPermalinkStructure = $this->select('options', 'o')
        ->fields('o', ['option_value'])
        ->condition('o.option_name', 'permalink_structure')
        ->execute()
        ->fetchField();
    }

    $date = new \DateTime($row->getSourceProperty('post_date'));
    $parameters = [
      '%year%' => $date->format('Y'),
      '%monthnum%' => $date->format('m'),
      '%day%' => $date->format('d'),
      '%postname%' => $row->getSourceProperty('post_name'),
    ];

    $url = str_replace(array_keys($parameters), array_values($parameters), $this->wpPermalinkStructure);
    return rtrim($url, '/');
  }

  /**
   * Get all related terms on a post.
   *
   * @param int $post_id
   *   The ID of the post for which the terms have to be found.
   *
   * @return int[]
   *   Array of term id's related to the given post.
   */
  protected function getRelatedTerms($post_id) {
    $query = $this->select('term_relationships', 't');
    $query->fields('t', ['term_taxonomy_id']);
    $query->condition('object_id', $post_id);
    $query->orderBy('term_order');

    return $query->execute()->fetchCol();
  }

}
