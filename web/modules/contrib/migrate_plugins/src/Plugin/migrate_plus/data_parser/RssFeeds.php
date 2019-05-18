<?php

namespace Drupal\migrate_plugins\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Xml;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Obtain XML data for migration.
 *
 * @DataParser(
 *   id = "rss_feeds",
 *   title = @Translation("RSS Feeds")
 * )
 */
class RssFeeds extends Xml implements ContainerFactoryPluginInterface {

  /**
   * Active Rss Feed entity source.
   *
   * @var array
   */
  protected $activeFeedEntity;

  /**
   * Active Feed Link source.
   *
   * @var array
   */
  protected $activeFeedLink;

  /**
   * Entity manager instance.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entityManager;

    if (!isset($configuration['entity_type'])) {
      throw new MigrateException('An entity_type configuration must be provided.');
    }

    if (!isset($configuration['entity_rss_field'])) {
      throw new MigrateException('An entity_rss_field configuration must be provided.');
    }

    // Entity storage for configured entity type.
    $storage = $this->entityManager->getStorage($configuration['entity_type']);
    $query = $storage->getQuery();

    // Ignore entities with empty rss field.
    $column_name = $configuration['entity_rss_field'] . '.uri';
    $query->condition($column_name, 'http', 'CONTAINS');

    // Filter the entities by bundle, if provided.
    if (isset($configuration['bundle'])) {
      $query->condition('type', $configuration['bundle']);
    }

    // Get the entity IDS that matched.
    $eids = $query->execute();
    $rss_feed_sources = [];

    foreach ($eids as $eid) {
      // @var \Drupal\Core\Entity\EntityInterface $entity
      if ($entity = $storage->load($eid)) {
        // Get the RSS field URLS and build the feeds list.
        $rss_field_values = $entity->{$configuration['entity_rss_field']};
        // @var \Drupal\link\Plugin\Field\FieldType\LinkItem $rss_field_value
        foreach ($rss_field_values as $rss_field_value) {
          if ($rss_field_value instanceof LinkItem) {
            $link = $rss_field_value->toArray();
            $rss_feed_sources[] = [
              'link' => $link,
              'entity' => $entity->toArray(),
            ];
          }
        }
      }
    }

    // Override the default feed source urls with entities RSS feed values.
    $this->urls = $rss_feed_sources;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * Advances the feed sources and keep source entity reference.
   *
   * @return bool
   *   TRUE if a valid source URL was opened
   */
  protected function nextSource() {
    while ($this->activeUrl === NULL || (count($this->urls) - 1) > $this->activeUrl) {
      if (is_null($this->activeUrl)) {
        $this->activeUrl = 0;
      }
      else {
        // Increment the activeUrl so we try to load the next source.
        $this->activeUrl = $this->activeUrl + 1;
        if ($this->activeUrl >= count($this->urls)) {
          return FALSE;
        }
      }

      // Set the active feed source entity.
      $this->activeFeedEntity = $this->urls[$this->activeUrl]['entity'];
      // Set the active feed source link.
      $this->activeFeedLink = $this->urls[$this->activeUrl]['link'];

      if ($this->openSourceUrl($this->urls[$this->activeUrl]['link']['uri'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    $current_item = $this->currentItem;
    // Add the feed source fields to current row item.
    if ($this->activeFeedEntity && is_array($this->activeFeedEntity)) {
      $current_item['source_entity'] = $this->urls[$this->activeUrl]['entity'];
      $current_item['source_link'] = $this->urls[$this->activeUrl]['link'];
    }

    return $current_item;
  }

}
