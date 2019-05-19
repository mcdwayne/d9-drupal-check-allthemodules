<?php

namespace Drupal\social_migration\Plugin\migrate\process;

use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\social_migration\Services\OgTag as OgTagService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve Open Graph tags from a URL as part of the migration process.
 *
 * Available configuration keys:
 * - source: the URL to scrape for Open Graph tags.
 * - schema: (Optional) the Open Graph schema to use. Defaults to 'og'.
 * - tag_name: (Optional) the specific tag name to retrieve. If not specified,
 *   returns all.
 * - take_first: (Optional) take only the first result. Default is FALSE.
 *
 * The og_tag plugin takes a URL as an input and attempts to fetch the URL. If
 * successful, it will scrape the HTML for Open Graph metatags matching the
 * schema and tag name specified in the configuration. If matching tags are
 * found, they will be returned; if not, the plugin will return an empty array.
 *
 * Examples:
 *
 * @code
 * process:
 *   foo:
 *     plugin: og_tag
 *     source: 'http://www.example.com/test.html'
 *     schema: og
 *     tag_name: image
 *     take_first: 1
 * @endcode
 *
 * If the URL specified by "source" can be scaped and contains a tag like...
 *
 * @code
 * <meta property="og:image" content="http://www.example.com/test-image.png" />
 * @endcode
 *
 * ...the plugin will return the following:
 *
 * @code
 * array(
 *   'http://www.example.com/test-image.png'
 * )
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "og_tag"
 * )
 */
class OgTag extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\social_migration\Services\OgTag definition.
   *
   * @var \Drupal\social_migration\Services\OgTag
   */
  protected $ogTagService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    OgTagService $og_tag_service
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->ogTagService = $og_tag_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('social_migration.og_tag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $url = $value ?: $this->configuration['source'];
    $schema = isset($this->configuration['schema'])
      ? $this->configuration['schema']
      : NULL;
    $tagName = isset($this->configuration['tag_name'])
      ? $this->configuration['tag_name']
      : NULL;
    $takeFirst = isset($this->configuration['take_first'])
      ? (bool) $this->configuration['take_first']
      : FALSE;

    $tags = $this->ogTagService->getTags($url, $schema, $tagName);

    // If the service returned a blank array, return a NULL value.
    if (empty($tags)) {
      return NULL;
    }

    // If the service returned only one element, or if it returned multiple but
    // the user specified they want only the first, grab the value of that
    // element and return the scalar.
    if (count($tags) == 1 || $takeFirst) {
      $scalar = array_shift(array_values($tags));
      return $scalar;
    }

    // Otherwise, return the entire result as-is since the user likely wants to
    // run a sub_process on it.
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
