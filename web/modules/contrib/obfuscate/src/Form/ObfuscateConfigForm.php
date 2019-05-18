<?php

namespace Drupal\obfuscate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\obfuscate\ObfuscateMailFactory;

/**
 * Class ObfuscateConfigForm.
 *
 * @package Drupal\obfuscate\Form
 */
class ObfuscateConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'obfuscate.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'obfuscate_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // @todo use trait for shared settings form between per field override and formatter
    // @todo provide settings description

    $config = $this->config('obfuscate.settings');
    $form['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('System wide obfuscation method'),
      '#description' => $this->t('This configuration is used by the Twig extension and the text filter. It applies also to the email field formatter and can be overridden with a per field instance configuration.'),
      '#options' => [ObfuscateMailFactory::HTML_ENTITY => $this->t('HTML entity (PHP only)'), ObfuscateMailFactory::ROT_13 => $this->t('ROT 13 and reversed text (PHP/Javascript ROT 13, with reversed text CSS fallback)')],
      '#default_value' => $config->get('obfuscate.method'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('obfuscate.settings')
      ->set('obfuscate.method', $form_state->getValue('method'))
      ->save();
    drupal_flush_all_caches();
  }

}
