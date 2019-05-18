<?php

namespace Drupal\node_read_time\Calculate;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Calculates the reading time of a node.
 */
class ReadingTime {
  /**
   * The node that should be checked for reading time.
   *
   * @var mixed
   */
  private $node;

  /**
   * Number of words per minute.
   *
   * @var int
   */
  private $wordsPerMinute;

  /**
   * The reading time value.
   *
   * @var int
   */
  private $readingTime;

  /**
   * The words from all the fields.
   *
   * @var string
   */
  private $words;

  /**
   * EntityTypeManager object.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Sets the target node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity object.
   *
   * @return \Drupal\node_read_time\Calculate\ReadingTime
   *   Returns this class object.
   */
  public function setNode(Node $node) {
    $this->node = $node;

    return $this;
  }

  /**
   * Sets the number of words.
   *
   * @param int
   *   The number of collected words.
   *
   */
  public function setWords($words) {
    $this->words = $words;

  }

  /**
   * Sets words per minute.
   *
   * @param int $wordsPerMinute
   *   Set the number of words per minute.
   *
   * @return \Drupal\node_read_time\Calculate\ReadingTime
   *   Returns this class object.
   */
  public function setWordsPerMinute($wordsPerMinute) {
    $this->wordsPerMinute = $wordsPerMinute;

    return $this;
  }

  /**
   * Gets the reading time value.
   *
   * @return int|null
   *   Returns the reading time value.
   */
  public function getReadingTime() {
    return $this->readingTime;
  }

  /**
   * Sets the reading time value.
   *
   * @@param int
   *   Sets the reading time value.
   */
  public function setReadingTime($readingTime) {
    $this->readingTime = $readingTime;
  }

  /**
   * Gets the fields from allowed types.
   *
   * @param object $entity
   *   The Entity that should be checked for textfields.
   *
   * @return \Drupal\node_read_time\Calculate\ReadingTime
   *   Returns this class object.
   */
  public function collectWords($entity) {

    $entity_fields = !empty($entity) ? $entity->getFieldDefinitions() : NULL;

    $allowedTypes = [
      'text',
      'text_long',
      'text_with_summary',
      'string_long',
      'entity_reference_revisions',
    ];

    if ($entity_fields) {
      // Clean the unnecessary fields.
      foreach ($entity_fields as $k => $field) {
        if (!in_array($field->getType(), $allowedTypes)) {
          unset($entity_fields[$k]);
        }
        // Remove revision fields.
        if (strpos($k, 'revision') !== FALSE) {
          unset($entity_fields[$k]);
        }
      }

      foreach ($entity_fields as $k => $field) {
        if (!empty($entity->get($k)->getValue()[0]['value'])) {
          $this->words .= $entity->get($k)->getValue()[0]['value'];
        }
        elseif ($field->getType() == 'entity_reference_revisions') {

          $fieldStorage = $entity_fields[$k]
            ->get('fieldStorage');
          if ($fieldStorage) {
            $entityType = $fieldStorage->get('settings')['target_type'];
            $list = $entity
              ->get($k)
              ->getValue();

            foreach ($list as $item) {
              $referenceRevisionEntity = $this->entityTypeManager
                ->getStorage($entityType)
                ->load($item['target_id']);

              $this->collectWords($referenceRevisionEntity);
            }
          }

        }
      }
    }
    return $this;
  }

  /**
   * Calculate the reading time.
   *
   * @return $this
   */
  public function calculateReadingTime() {
    $words_count = count(preg_split('/\s+/', (strip_tags($this->words))));
    if ($words_count > 1) {
      $reading_time = ceil($words_count / $this->wordsPerMinute);
    }
    else {
      $reading_time = 0;
    }

    $this->readingTime = $reading_time;

    return $this;
  }
}
