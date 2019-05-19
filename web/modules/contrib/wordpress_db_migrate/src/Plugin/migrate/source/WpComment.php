<?php

namespace Drupal\wordpress_db_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;


/**
 * WordPress comment migration source plugin.
 *
 * @MigrateSource(
 *   id = "wp_comment"
 * )
 */
class WpComment extends WpSqlBase {

  /**
   * List of fields available on wp_comments table.
   */
  protected function commentFields() {
    return [
      'comment_ID' => $this->t('Comment ID.'),
      'comment_post_ID' => $this->t('Comment Post ID.'),
      'comment_author' => $this->t('Comment Author.'),
      'comment_author_email' => $this->t('Comment Author Email.'),
      'comment_author_url' => $this->t('Comment Author URL.'),
      'comment_author_IP' => $this->t('Comment Author IP.'),
      'comment_date' => $this->t('Comment Date.'),
      'comment_date_gmt' => $this->t('Comment Date (GMT).'),
      'comment_content' => $this->t('Comment Content.'),
      'comment_karma' => $this->t('Comment Karma.'),
      'comment_approved' => $this->t('Comment Approved.'),
      'comment_agent' => $this->t('Comment Agent.'),
      'comment_type' => $this->t('Comment Type.'),
      'comment_parent' => $this->t('Comment Parent.'),
      'user_id' => $this->t('Comment User ID.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array_merge($this->commentFields(), [
      'comment_meta' => $this->t('Comment Meta.'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['comment_ID']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('comments', 'c');
    $query->fields('c', array_keys($this->commentFields()));
    $query->orderBy('c.comment_date');

    $comment_approved = !empty($this->configuration['comment_approved']) ? $this->configuration['comment_approved'] : 1;
    $query->condition('c.comment_approved', $comment_approved);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('comment_meta', $this->getMetaValues(
      'commentmeta',
      'comment_id',
      $row->getSourceProperty('comment_ID')));

    $row->setSourceProperty('comment_date', $this->strToTime($row->getSourceProperty('comment_date')));
    $row->setSourceProperty('comment_date_gmt', $this->strToTimeUtc($row->getSourceProperty('comment_date_gmt')));

    return parent::prepareRow($row);
  }

}
