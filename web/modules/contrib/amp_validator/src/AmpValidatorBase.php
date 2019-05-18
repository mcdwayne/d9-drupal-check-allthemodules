<?php

namespace Drupal\amp_validator;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class AmpValidatorBase provide base methods for AMP validators.
 *
 * @package Drupal\amp_validator
 */
abstract class AmpValidatorBase implements AmpValidatorInterface {

  /**
   * Config settings of AMP validator.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

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
   * AmpValidatorBase constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('amp_validator.settings');
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

}
