<?php

namespace Drupal\amp_validator\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Base class for Validator plugins.
 */
abstract class AmpValidatorPluginBase extends PluginBase implements AmpValidatorPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Config settings of AMP Validator module.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Data object which should be validated.
   *
   * @var \Drupal\Core\Url
   */
  protected $data = NULL;

  /**
   * Array of errors if AMP validation is not valid.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * AMP validation is valid or not.
   *
   * @var bool
   */
  protected $valid = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, ConfigFactory $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
    $this->config = $config_factory->get('amp_validator.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return $this->valid;
  }

  /**
   * Set data object.
   *
   * @param \Drupal\Core\Url $url
   *   AMP URL which should be validated.
   */
  public function setData($data) {
    $this->data = $data;
  }

}
