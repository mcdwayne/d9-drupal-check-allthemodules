<?php

namespace Drupal\encrypt_content_client\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class ListClientEncryptionPoliciesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'encrypt_content_client_list_client_encryption_policy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('encrypt_content_client.policy.settings');

    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
      if ($config->get($contentType->id())) {
        $contentTypesList[$contentType->id()] .= " - " . count($config->get($contentType->id())) . " fields";
      }
    }

    $form['node'] = [
      '#title' => t("Select which node's policy to edit."),
      '#type' => 'select',
      '#options' => $contentTypesList,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Edit encryption policy'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to the edit page after form submit.
    $form_state->setRedirect('encrypt_content_client.update_policy', [
      'entity' => $form_state->getValue('node'),
    ]);
  }

}
