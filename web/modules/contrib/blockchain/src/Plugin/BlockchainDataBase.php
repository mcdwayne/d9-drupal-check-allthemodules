<?php

namespace Drupal\blockchain\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use GuzzleHttp\Client;

/**
 * Base class for Importer plugins.
 */
abstract class BlockchainDataBase extends PluginBase implements
    BlockchainDataInterface,
    ContainerFactoryPluginInterface {

  /**
   * Blockchain block data.
   *
   * @var \Drupal\blockchain\Entity\BlockchainBlock
   */
  protected $data;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
  $plugin_id,
                              $plugin_definition,
  EntityTypeManager $entityTypeManager,
                              Client $httpClient,
  RequestStack $requestStack,
                              LoggerChannelFactory $loggerFactory,
                              EntityFormBuilderInterface $entityFormBuilder) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->httpClient = $httpClient;
    $this->requestStack = $requestStack;
    $this->loggerFactory = $loggerFactory;
    $this->entityFormBuilder = $entityFormBuilder;
    if (isset($configuration[static::DATA_KEY])) {
      $this->data = $configuration[static::DATA_KEY];
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('request_stack'),
      $container->get('logger.factory'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Getter for logger.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   Logger object.
   */
  protected function getLogger() {

    return $this->loggerFactory->get(self::LOGGER_CHANNEL);
  }

  /**
   * Prepares data before persistence.
   *
   * @param string $data
   *   Raw data.
   *
   * @return string
   *   Prepared data string.
   */
  protected function dataToSleep($data) {

    return $this->getPluginId() . '::' . $data;
  }

  /**
   * Prepares data after reading.
   *
   * @param string $data
   *   Raw data.
   *
   * @return string
   *   Prepared data string.
   */
  protected function dataWakeUp($data) {

    $prefix = $this->getPluginId() . '::';
    if (strpos($data, $prefix) === 0) {
      return substr($data, strlen($prefix), strlen($data));
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {

    foreach ($items as $key => $item) {
      $this->setData($item->value);
      $item->value = $this->getRawData();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {

    return (bool) $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSettings() {

    return $this->pluginDefinition['settings'];
  }

}
