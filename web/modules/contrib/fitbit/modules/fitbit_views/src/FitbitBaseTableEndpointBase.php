<?php

namespace Drupal\fitbit_views;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\fitbit\FitbitClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Fitbit base table endpoint plugins.
 */
abstract class FitbitBaseTableEndpointBase extends PluginBase implements FitbitBaseTableEndpointInterface, ContainerFactoryPluginInterface  {
  use StringTranslationTrait;

  /**
   * Fitbit client.
   *
   * @var \Drupal\fitbit\FitbitClient
   */
  protected $fitbitClient;

  /**
   * All endpoints will require a FitbitClient to do their work, save them all
   * from having to get the serivice from the container.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param FitbitClient $fitbit_client
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FitbitClient $fitbit_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fitbitClient = $fitbit_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('fitbit.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseKey() {
    return $this->pluginDefinition['response_key'];
  }

  /**
   * Helper function to filter the input array using an array of paths
   * delimited by colans.
   *
   * @param array $array
   *   Multidimensional array with string keys.
   * @param array $paths
   *   Array of string paths, path parts delimited by colons denoting which
   *   elements of $array are desired.
   *
   * @return array
   *   Return an array keyed by the $paths values. Only return the values of
   *   $array that are matched by a path in $paths.
   */
  protected function filterArrayByPath($array, $paths = []) {
    $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array), \RecursiveIteratorIterator::SELF_FIRST);
    $result = [];
    foreach ($iterator as $leaf) {
      $keys = [];
      // RecursiveIteratorIterator takes us all the way down to the leaves at a
      // certain depth. Iterate over the depths to collect the string keys that
      // got us here.
      foreach (range(0, $iterator->getDepth()) as $depth) {
        $keys[] = $iterator->getSubIterator($depth)->key();
      }
      // Check that the path we are at is being asked for, if not, ignore it.
      $path = join(':', $keys);
      if (in_array($path, $paths)) {
        $result[$path] = $leaf;
      }
    }
    return $result;
  }
}
