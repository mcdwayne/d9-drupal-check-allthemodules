<?php

namespace Drupal\sdk;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sdk\Entity\Sdk;

/**
 * Base configuration form of SDK.
 */
abstract class SdkPluginConfigurationFormBase {

  use ExternalLink;
  use StringTranslationTrait;

  /**
   * SDK configuration.
   *
   * @var \Drupal\sdk\Entity\Sdk
   */
  protected $config;

  /**
   * SdkPluginConfigurationFormBase constructor.
   *
   * @param \Drupal\sdk\Entity\Sdk $config
   *   SDK configuration.
   *
   * @see \Drupal\sdk\SdkPluginBase::getConfigurationForm()
   */
  public function __construct(Sdk $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function form(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
  }

}
