<?php

namespace Drupal\ext_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ext_redirect\Service\ExtRedirectConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExtRedirectSettingsForm.
 */
class ExtRedirectSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\ext_redirect\Service\ExtRedirectConfig
   */
  private $extRedirectConfig;

  /**
   * Constructs a new ExtRedirectSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ExtRedirectConfig $extRedirectConfig) {
    $this->extRedirectConfig = $extRedirectConfig;
    parent::__construct($configFactory);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ext_redirect.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ext_redirect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ext_redirect_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['primary_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary host'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->extRedirectConfig->getPrimaryHost(),
    ];

    $form['allowed_host_aliases'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed host aliases'),
      '#default_value' => $this->extRedirectConfig->getAllowedHostAliasesAsString(),
      '#description' => $this->t('Separate aliases by new line'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->extRedirectConfig->setPrimaryHost($form_state->getValue('primary_host'));
    $this->extRedirectConfig->setAllowedHostAliasesFromString($form_state->getValue('allowed_host_aliases'));
    $this->extRedirectConfig->save();

  }

}
