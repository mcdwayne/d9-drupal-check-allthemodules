<?php

namespace Drupal\config_overridden\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines the FormOverriderBase abstract class.
 */
abstract class ConfigFormOverriderBase extends PluginBase implements ConfigFormOverriderInterface {

  /**
   * For support for $this->t();
   */
  use StringTranslationTrait;

  /**
   * @var array
   */
  protected $form;

  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $form_state;

  /**
   * @var string
   */
  protected $form_id;

  /**
   * Define variable for LoggerChannelInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Define variable for ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Drupal\saipolfm_sms_service\SmsServicePluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger_factory->get($plugin_definition['id']);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory')
    );
  }

  /**
   * To check form is applicable or not.
   */
  public function isApplicable() {
    if (static::needsObject()) {
      throw new \Exception('This plugin needs an object. Please, decleare isApplicable() method');
    }

    return FALSE;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param null $form_id
   */
  public function setForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    $this->form = &$form;
    $this->form_state = $form_state;
    $this->form_id = $form_id ? $form_id : $form_state->getFormObject()->getFormId();
  }
}
