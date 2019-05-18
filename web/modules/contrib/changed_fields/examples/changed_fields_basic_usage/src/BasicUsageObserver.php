<?php

namespace Drupal\changed_fields_basic_usage;

use Drupal\changed_fields\ObserverInterface;
use SplSubject;

/**
 * Class BasicUsageObserver.
 */
class BasicUsageObserver implements ObserverInterface {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      'node' => [
        'article' => [
          'title',
          'body',
        ],
      ],
      'user' => [
        'user' => [
          'name',
          'mail',
        ],
      ],
      'taxonomy_term' => [
        'tags' => [
          'name',
          'description',
        ],
      ],
      'comment' => [
        'comment' => [
          'subject',
          'comment_body',
        ],
      ],
      'shortcut' => [
        'default' => [
          'title',
          'link',
        ],
      ],
      'menu_link_content' => [
        'menu_link_content' => [
          'title',
          'link',
        ],
      ],
      'media' => [
        'image' => [
          'name',
          'field_media_image',
        ],
      ],
      'block_content' => [
        'basic' => [
          'info',
          'body',
        ],
      ],
      'aggregator_feed' => [
        'aggregator_feed' => [
          'title',
          'refresh',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function update(SplSubject $entity_subject) {
    $entity = $entity_subject->getEntity();
    $changed_fields = $entity_subject->getChangedFields();

    // Do something with $entity depends on $changed_fields.
  }

}
