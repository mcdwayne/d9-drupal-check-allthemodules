<?php

namespace Drupal\prod_check\Plugin\ProdCheck;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\prod_check\Plugin\ProdCheckInterface;
use Drupal\prod_check\ProdCheck;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all the prod check plugins.
 */
abstract class ProdCheckBase extends PluginBase implements ContainerFactoryPluginInterface, ProdCheckInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * The prod check processor plugin manager.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckProcessorInterface;
   */
  protected $processor;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface;
   */
  protected $destination;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface;
   */
  protected $linkGenerator;

  /**
   * The config factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * The date formatter service
   *
   * @var \Drupal\Core\Datetime\DateFormatter;
   */
  protected $dateFormatter;

  /**
   * The module handler service
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface;
   */
  protected $moduleHandler;

  /**
   * The query Service
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory;
   */
  protected $queryService;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination
   *   The redirect destination service
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $generator
   *   The link generator service
   * @param \Drupal\Core\Config\ConfigFactoryInterface $factory
   *   The config factory service
   * @param \Drupal\Core\Datetime\DateFormatter $formatter
   *   The date formatter service
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RedirectDestinationInterface $destination,
                              LinkGeneratorInterface $generator, ConfigFactoryInterface $factory, DateFormatter $formatter, ModuleHandlerInterface $handler,
                              QueryFactory $query_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (isset($configuration['processor'])) {
      $this->processor = $configuration['processor'];
    }

    $this->destination = $destination;
    $this->linkGenerator = $generator;
    $this->configFactory = $factory;
    $this->dateFormatter = $formatter;
    $this->moduleHandler = $handler;
    $this->queryService = $query_service;

    $this->configuration += $this->defaultConfiguration();

    $this->init();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('redirect.destination'),
      $container->get('link_generator'),
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('module_handler'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init() {
    // Do nothing in the base class
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $definition = $this->getPluginDefinition();
    return $definition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function category() {
    $definition = $this->getPluginDefinition();
    return $definition['category'];
  }

  /**
   * Sets the processor
   */
  public function setProcessor($processor) {
    $this->processor = $processor;
  }

  /**
   * Helper function to generate generic 'settings OK' description.
   */
  protected function generateDescription($title, $route_name, $text = 'Your %link settings are OK for production use.') {
    $url = Url::fromRoute($route_name);
    $url->setOption('attributes', array('title' => $title));

    $destination = \Drupal::destination()->getAsArray();
    $url->setOption('query', $destination);

    return $this->t($text, $this->generateLinkArray($title, $route_name));
  }

  /**
   * Helper function to generate link array to pass to the t() function
   */
  protected function generateLinkArray($title, $route_name, $fragment = NULL) {
    $url = Url::fromRoute($route_name);
    $url->setOption('attributes', array('title' => $title));

    $destination = $this->destination->getAsArray();
    $url->setOption('query', $destination);
    $url->setAbsolute(TRUE);

    return array('%link' => $this->linkGenerator->generate($title, $url));
  }

  /**
   * {@inheritdoc}
   */
  public function severity() {
    switch ($this->configuration['severity']) {
      case ProdCheck::REQUIREMENT_INFO:
        return $this->processor->info();
        break;
      case ProdCheck::REQUIREMENT_ERROR:
        return $this->processor->error();
        break;
      default:
        return $this->processor->warning();
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'severity' => ProdCheck::REQUIREMENT_WARNING
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [
      ProdCheck::REQUIREMENT_INFO => 'Informational message',
      ProdCheck::REQUIREMENT_WARNING => 'Warning message',
      ProdCheck::REQUIREMENT_ERROR => 'Error message'
    ];

    $form['severity'] = array(
      '#type' => 'select',
      '#title' => t('Severity'),
      '#default_value' => $this->configuration['severity'],
      '#options' => $options,
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['severity'] = $form_state->getValue('severity');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function data() {
    return array();
  }

}
