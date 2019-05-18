<?php

namespace Drupal\migrate_social;

use Drupal\Component\Plugin\PluginBase;
use Drupal\migrate_social\SocialNetworkInterface;

/**
 * A base class to help developers implement their own plugins.
 *
 * @see \Drupal\migrate_social\Annotation\RelatedContent
 * @see \Drupal\migrate_social\RelatedContentInterface
 */
abstract class SocialNetworkBase extends PluginBase implements SocialNetworkInterface {
  private $currentItem;
  protected $instance;
  protected $iterator;

  /**
   * @inheritdoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->instance = sdk($this->pluginId);
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    // Retrieve the @description property from the annotation and return it.
    return $this->pluginDefinition['description'];
  }


  /**
   * Implementation of Iterator::next().
   */
  public function next() {
    $this->currentItem = $this->currentId = NULL;
    if (empty($this->iterator)) {
      if (!$this->nextSource()) {
        // No data to import.
        return;
      }
    }
    // At this point, we have a valid open source url, try to fetch a row from
    // it.
    $this->fetchNextRow();
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    $current = $this->iterator->current();
     if ($current) {
       foreach ($current as $field_name => $field_date) {
         $this->currentItem[$field_name] = $field_date;
       }
      $this->iterator->next();
    }
  }

  /**
   * Advances the data parser to the next source url.
   *
   * @return bool
   *   TRUE if a valid source URL was opened
   */
  abstract protected function nextSource();
  /**
   * {@inheritdoc}
   */
  public function current() {
    return $this->currentItem;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->currentId;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return !empty($this->currentItem);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->activeUrl = NULL;
    $this->next();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    if (empty($this->iterator)) {
      if (!$this->nextSource()) {
        // No data to import.
        return -1;
      }
    }
    return $this->iterator->count();
  }
}
