<?php

namespace Drupal\getresponse_forms\Plugin\GetresponseFormsField;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\getresponse_forms\ConfigurableFieldInterface;

/**
 * Provides all available custom fields.
 *
 * @GetresponseFormsField(
 *   id = "getresponse_forms_custom_field",
 *   admin_label = @Translation("Custom field"),
 *   deriver = "Drupal\getresponse_forms\Plugin\Derivative\CustomField"
 * )
 */
class CustomField extends PluginBase implements ConfigurableFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'getresponse_forms_custom_field:' . $this->customFieldId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'getresponse_forms_custom_field:' . $this->customFieldId;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->configuration['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->configuration['weight'] = $weight;
    return $this;
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
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   * TODO update/use or remove
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'image_resize_summary',
      '#data' => $this->configuration,
    ];
    $summary += parent::getSummary();

    return $summary;
}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'name' => NULL,
      'label' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'text',
      '#title' => t('Label'),
      '#default_value' => $this->configuration['label'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['label'] = $form_state->getValue('label');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
