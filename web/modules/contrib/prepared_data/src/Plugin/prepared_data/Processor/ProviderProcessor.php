<?php

namespace Drupal\prepared_data\Plugin\prepared_data\Processor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\prepared_data\PreparedDataInterface;
use Drupal\prepared_data\Processor\ProcessorBase;
use Drupal\prepared_data\Processor\ProcessorInterface;
use Drupal\prepared_data\Provider\ProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provider processor class.
 *
 * @PreparedDataProcessor(
 *   id = "provider",
 *   label = @Translation("Provider processing"),
 *   weight = 1,
 *   manageable = false
 * )
 */
class ProviderProcessor extends ProcessorBase implements ContainerFactoryPluginInterface {

  /**
   * The manager of prepared data providers.
   *
   * @var \Drupal\prepared_data\Provider\ProviderManager
   */
  protected $providerManager;

  /**
   * A list of providers which are also processors.
   *
   * @var \Drupal\prepared_data\Processor\ProcessorInterface[]
   */
  protected $providerProcessors;

  /**
   * Provider processor constructor.
   *
   * @param \Drupal\prepared_data\Provider\ProviderManager $provider_manager
   *   The manager of prepared data providers.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ProviderManager $provider_manager, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\prepared_data\Provider\ProviderManager $provider_manager */
    $provider_manager = $container->get('prepared_data.provider_manager');
    return new static($provider_manager, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(PreparedDataInterface $data) {
    if (!($key = $data->key())) {
      return;
    }
    foreach ($this->getProviderProcessors() as $provider) {
      if ($provider->match($key)) {
        $provider->initialize($data);
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process(PreparedDataInterface $data) {
    if (!($key = $data->key())) {
      return;
    }
    foreach ($this->getProviderProcessors() as $provider) {
      if ($provider->match($key)) {
        $provider->process($data);
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finish(PreparedDataInterface $data) {
    if (!($key = $data->key())) {
      return;
    }
    foreach ($this->getProviderProcessors() as $provider) {
      if ($provider->match($key)) {
        $provider->finish($data);
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(PreparedDataInterface $data) {
    if (!($key = $data->key())) {
      return;
    }
    foreach ($this->getProviderProcessors() as $provider) {
      if ($provider->match($key)) {
        $provider->cleanup($data);
        break;
      }
    }
  }

  /**
   * Get a list of providers which also act as a processor.
   *
   * @return \Drupal\prepared_data\Processor\ProcessorInterface[]
   *   The providers which are also processors.
   */
  protected function getProviderProcessors() {
    if (!isset($this->providerProcessors)) {
      $this->providerProcessors = [];
      foreach ($this->providerManager->getAllProviders() as $provider) {
        if ($provider instanceof ProcessorInterface) {
          $this->providerProcessors[] = $provider;
        }
      }
    }
    return $this->providerProcessors;
  }

}
