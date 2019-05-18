<?php

namespace Drupal\blocktabs;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for tabs.
 *
 * @see \Drupal\blocktabs\Annotation\Tab
 * @see \Drupal\blocktabs\TabInterface
 * @see \Drupal\blocktabs\ConfigurableTabInterface
 * @see \Drupal\blocktabs\ConfigurableTabBase
 * @see \Drupal\blocktabs\TabManager
 * @see plugin_api
 */
abstract class TabBase extends ContextAwarePluginBase implements TabInterface, ContainerFactoryPluginInterface {

  /**
   * The tab ID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The weight of the tab.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * The title of the tab.
   *
   * @var string
   */
  protected $title = '';

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('blocktabs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeExtension($extension) {
    // Most tabs will not change the extension. This base
    // implementation represents this behavior. Override this method if your
    // tab does change the extension.
    return $extension;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => '',
      '#tab' => [
        'id' => $this->pluginDefinition['id'],
        'label' => $this->label(),
        'description' => $this->pluginDefinition['description'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'uuid' => $this->getUuid(),
      'id' => $this->getPluginId(),
      'title' => $this->getTitle(),
      'weight' => $this->getWeight(),
      'data' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'data' => [],
      'uuid' => '',
      'title' => '',
      'weight' => '',
    ];
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    $this->uuid = $configuration['uuid'];
    $this->title = $configuration['title'];
    $this->weight = $configuration['weight'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    $tab_content = '';
    return $tab_content;
  }

}
