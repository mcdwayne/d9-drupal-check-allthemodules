<?php

/**
 * @file
 * Contains \Drupal\custom_meta\CustomMetaStorage.
 */

namespace Drupal\custom_meta;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Provides a class for CRUD operations on custom meta tags.
 */
class CustomMetaStorage implements CustomMetaStorageInterface {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Custom meta CRUD object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing custom meta tags.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $connection, ModuleHandlerInterface $module_handler) {
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function save($fields) {
    // Insert or update the custom meta tag.
    if (empty($fields['meta_uid'])) {
      $query = $this->connection->insert('custom_meta')
        ->fields($fields);
    }
    else {
      $query = $this->connection->update('custom_meta')
        ->fields($fields)
        ->condition('meta_uid', $fields['meta_uid']);
    }

    $meta_uid = $query->execute();
    $fields['meta_uid'] = $meta_uid;

    if ($meta_uid) {
      // Clear cache data.
      \Drupal::cache()->invalidate(CUSTOM_META_TAGS_CID);
      return $fields;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function load($conditions) {
    $select = $this->connection->select('custom_meta');
    foreach ($conditions as $field => $value) {
      $select->condition($field, $value);
    }
    return $select
      ->fields('custom_meta')
      ->orderBy('meta_uid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($conditions) {
    $query = $this->connection->delete('custom_meta');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $deleted = $query->execute();

    // Clear cache data.
    \Drupal::cache()->invalidate(CUSTOM_META_TAGS_CID);

    return $deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function tagExists($conditions) {
    $query = $this->connection->select('custom_meta');
    foreach ($conditions as $field => $value) {
      if ($field == 'meta_uid') {
        if ($value) {
          $query->condition($field, $value, '<>');
        }
      }
      else {
        $query->condition($field, $value);
      }
    }
    $query->addExpression('1');
    $query->range(0, 1);
    return (bool) $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomMetaTagsForAdminListing($header) {
    $query = $this->connection->select('custom_meta')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    return $query
      ->fields('custom_meta')
      ->orderByHeader($header)
      ->limit(50)
      ->execute()
      ->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomMetaTagsListing() {
    $meta_tags = FALSE;
    $cache = \Drupal::cache()->get(CUSTOM_META_TAGS_CID);
    if ($cache && $cache->data) {
      $meta_tags = $cache->data;
    }
    else {
      $meta_tags = $this->connection->select('custom_meta')
        ->fields('custom_meta')
        ->execute()
        ->fetchAll();
      \Drupal::cache()->set(CUSTOM_META_TAGS_CID, $meta_tags);
    }

    return $meta_tags;
  }
}
