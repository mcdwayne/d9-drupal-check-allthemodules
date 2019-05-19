<?php

namespace Drupal\submission_ip_anonymizer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SubmissionIpAnonymizerForm.
 */
class SubmissionIpAnonymizerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'submission_ip_anonymizer.submissionipanonymizer',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_ip_anonymizer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('submission_ip_anonymizer.submissionipanonymizer');
    $form['show_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Ip'),
      '#description' => $this->t('Showing Ip hashes on submission list view'),
      '#default_value' => $config->get('show_ip'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('submission_ip_anonymizer.submissionipanonymizer')
      ->set('show_ip', $form_state->getValue('show_ip'))
      ->save();
  }

}
