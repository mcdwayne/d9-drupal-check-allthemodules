<?php

namespace Drupal\prepared_data\Plugin\prepared_data\Processor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\prepared_data\PreparedDataInterface;
use Drupal\prepared_data\Processor\ProcessorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MaxValidness processor class.
 *
 * @PreparedDataProcessor(
 *   id = "max_validness",
 *   label = @Translation("Data validness limitation"),
 *   weight = 0,
 *   manageable = false
 * )
 */
class MaxValidness extends ProcessorBase implements ContainerFactoryPluginInterface {

  /**
   * The data validness expiry limit as a timestamp.
   *
   * @var int
   */
  protected $limit;

  /**
   * Constructs MaxValidness object.
   *
   * @param int $max_validness
   *   Time-interval in seconds for the maximum data validness.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct($max_validness, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->limit = time() + $max_validness;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    if ($config_factory = $container->get('config.factory')) {
      /** @var \Drupal\Core\Config\ImmutableConfig $config */
      if ($config = $config_factory->get('prepared_data.settings')) {
        $max_validness = $config->get('max_validness');
      }
    }
    if (empty($max_validness)) {
      $max_validness = 0;
    }
    return new static($max_validness, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(PreparedDataInterface $data) {
    if (!$data->expires() || ($data->expires() < $this->limit)) {
      $data->expires($this->limit);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finish(PreparedDataInterface $data) {
    if ($data->expires() > $this->limit) {
      $data->expires($this->limit);
    }
  }

}
