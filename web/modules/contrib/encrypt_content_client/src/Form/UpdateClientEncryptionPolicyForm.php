<?php

namespace Drupal\encrypt_content_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class UpdateClientEncryptionPolicyForm extends ConfigFormBase {

  protected $node;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'encrypt_content_client_update_client_encryption_policy_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'encrypt_content_client.policy.settings',
    ];
  }

  /**
   * Set page title based on selected node type.
   */
  public function setPageTitle($node) {
    return "Edit encryption policy for " . ucfirst($node);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $config = $this->config('encrypt_content_client.policy.settings');
    $this->node = $node;

    $bundle_fields = \Drupal::entityManager()->getFieldDefinitions('node', $node);
    $fields = [];
    foreach ($bundle_fields as $field) {
      if (!$field->isReadOnly()) {
        $fields[$field->getName()] = $field->getLabel();
      }
    }

    $form['fields'] = [
      '#title' => t('Select which fields to encrypt'),
      '#type' => 'checkboxes',
      '#options' => $fields,
    ];

    if ($config->get($node)) {
      $form['fields']['#default_value'] = $config->get($node);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fields = array_keys(array_filter($form_state->getValue('fields')));

    $this->config('encrypt_content_client.policy.settings')
      ->set($this->node, $fields)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
