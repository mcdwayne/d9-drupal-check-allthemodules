<?php

namespace Drupal\bestreply\Forms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a configuration form for bestreply settings.
 */
class BestReplyConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bestreply_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bestreply.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bestreply_config = $this->config('bestreply.settings');

    $form['bestreply_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $bestreply_config->get('name'),
      '#description' => $this->t('The name you wish to use for bestreply.'),
      '#required' => TRUE,
    ];
    $form['bestreply_change'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show best reply link'),
      '#default_value' => $bestreply_config->get('change'),
      '#options' => ['1' => 'yes', '0' => 'no'],
      '#description' => $this->t('Show the change bestreply link, when a bestreply already exists.'),
    ];
    $form['bestreply_node_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Node types'),
      '#description' => $this->t('Check the node types you want to be able to mark a comment as the @bestreply.', ['@bestreply' => $bestreply_config->get('name')]),
      '#open' => TRUE,
    ];
    $form['bestreply_node_types']['bestreply_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Types'),
      '#default_value' => $bestreply_config->get('types'),
      '#options' => node_type_get_names(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bestreply.settings')
      ->set('name', $form_state->getValue('bestreply_name'))
      ->set('change', $form_state->getValue('bestreply_change'))
      ->set('types', $form_state->getValue('bestreply_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
