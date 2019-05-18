<?php

namespace Drupal\custom_add_content\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure order for this site.
 */
class AddContentConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_content_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_add_content.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_add_content.config');

    $form['desc'] = [
      '#type' => 'item',
      '#markup' => $this->t('Select your preferred renderer for node/add page'),
    ];

    $form['custom_add_content_renderer'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu renderer'),
      '#options' => [
        0 => $this->t("Drupal's core renderer"),
        1 => $this->t("Module's custom renderer"),
      ],
      '#default_value' => $config->get('custom_add_content_renderer'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('custom_add_content.config')
      ->set('custom_add_content_renderer', $values['custom_add_content_renderer'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
