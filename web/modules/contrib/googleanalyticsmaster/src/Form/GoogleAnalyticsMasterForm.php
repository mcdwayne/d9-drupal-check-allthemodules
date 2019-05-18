<?php

namespace Drupal\googleanalyticsmaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Google_Analytics Master module settings.
 */
class GoogleAnalyticsMasterForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'googleanalyticsmaster_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('googleanalyticsmaster.settings');

    // Insert Textfield.
    $form['tracking_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Insert Traking Id number:'),
      '#default_value' => $config->get('googleanalyticsmaster.tracking_id'),
      '#description' => $this->t('insert your web element tracking id, like UA-********-**.'),
    ];

    // Insert Select field.
    $form['tracking_id_admin'] = [
      '#type' => 'select',
      '#title' => t('Send Admin Statistics'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $config->get('googleanalyticsmaster.tracking_id_admin'),
      '#description' => t('Set this to <em>Yes</em> if you would like send to to google analytics backend admin page hits.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('tracking_id'))) {
      $form_state->setErrorByName('tracking_id', $this->t('Tracking Id can not be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('googleanalyticsmaster.settings');
    $config->set('googleanalyticsmaster.tracking_id', $form_state->getValue('tracking_id'));
    $config->set('googleanalyticsmaster.tracking_id_admin', $form_state->getValue('tracking_id_admin'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'googleanalyticsmaster.settings',
    ];
  }

}
