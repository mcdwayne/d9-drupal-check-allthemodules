<?php

namespace Drupal\stats\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\stats\StatExecution;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Stat source plugins.
 */
abstract class StatPluginBase extends PluginBase implements ContainerFactoryPluginInterface {

  const PROPERTY_SEPARATOR = '/';

  /**
   * @var \Drupal\stats\StatExecution
   */
  protected $statExecution;

  /**
   * StatSourceBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\stats\StatExecution $statExecution
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StatExecution $statExecution) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->statExecution = $statExecution;
  }

  /**
   * Implements ContainerFactoryPluginInterface so we can load services to our
   * plugins.
   *
   * @param \Drupal\stats\Plugin\ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\stats\StatExecution|NULL $execution
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, StatExecution $execution = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $execution
    );
  }

  /**
   * Retrieves a property from given input.
   *
   * @param mixed $input
   * @param string $property
   *   A property on the source.
   *
   * @return mixed|null
   *   The found returned property or NULL if not found.
   */
  public function getProperty($input, $property) {
    // @todo: utilise type_data module's data_fetcher instead.
    if (!is_array($input) && method_exists($input, 'toArray')) {
      $input = $input->toArray();
    }

    $return = NestedArray::getValue($input, explode(static::PROPERTY_SEPARATOR, $property), $key_exists);
    if ($key_exists) {
      return $return;
    }
  }

}
