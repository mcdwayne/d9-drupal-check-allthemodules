<?php

namespace Drupal\migrate_social\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\SourcePluginExtension;

/**
 * Source plugin for retrieving data from social networks.
 *
 * @MigrateSource(
 *   id = "social_network"
 * )
 */
class Social extends SourcePluginBase {

  /**
   * The social network plugin.
   *
   * @var \Drupal\migrate_plus\DataParserPluginInterface
   */
  protected $socialNetworkPlugin;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $this->getSocialNetworkPlugin();
    $this->socialNetworkPlugin->next();
    $result = [];
    foreach ($this->socialNetworkPlugin->current() as $name => $value) {
      $result[$name] = print_r($value,true);

    }

    return $result;
  }

  /**
   * Return a string representing the source URLs.
   *
   * @return string
   *   Comma-separated list of URLs being imported.
   */
  public function __toString() {
    // This could cause a problem when using a lot of urls, may need to hash.
    $urls = implode(', ', [1]);
    return $urls;
  }

  /**
   * Returns the initialized data parser plugin.
   *
   * @return \Drupal\migrate_plus\DataParserPluginInterface
   *   The data parser plugin.
   */
  public function getSocialNetworkPlugin() {
    if (!isset($this->socialNetworkPlugin)) {
      $this->socialNetworkPlugin = \Drupal::service('plugin.manager.migrate_social.social_network')->createInstance($this->configuration['social_network_plugin'], $this->configuration);
    }
    return $this->socialNetworkPlugin;
  }

  /**
   * Creates and returns a filtered Iterator over the documents.
   *
   * @return \Iterator
   *   An iterator over the documents providing source rows that match the
   *   configured item_selector.
   */
  protected function initializeIterator() {
    return $this->getSocialNetworkPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    if (method_exists($this->socialNetworkPlugin, 'getIds')) {
      return $this->socialNetworkPlugin->getIds();
    }
    else {
      return [
         'id' => [
           'type' => 'string',
           'max_length' => 64,
           'is_ascii' => TRUE,
         ],
      ];
    }
  }
}
