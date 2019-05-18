<?php

namespace Drupal\node_subs\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TextSettingsForm.
 */
class TextSettingsForm extends ConfigFormBase {

  private $reference;

  private $definition;

  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManager $config_typed) {
    parent::__construct($config_factory);

    $this->definition = $config_typed->getDefinition('node_subs.textsettings');

    $this->reference = [
      'email' => 'email',
      'text' => 'textarea',
      'string' => 'textfield',
    ];
  }

  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_subs.textsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_subs_settings_text_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_subs.textsettings');
    foreach ($this->definition['mapping'] as $config_name => $config_value) {
      if ($config_value['type'] == '_core_config_info' || $config_value['label'] == 'Language code') {
        continue;
      }
      $form[$config_name] = [
        '#type' => $this->reference[$config_value['type']] ?? 'textfield',
        '#title' => $this->t($config_value['label']),
        '#default_value' => $config->get($config_name),
    ];
    }
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

    $settings = $this->config('node_subs.textsettings');

    foreach ($this->definition['mapping'] as $setting_name => $setting_definition) {
      $settings->set($setting_name, $form_state->getValue($setting_name));
    }
    $settings->save();

  }

}
