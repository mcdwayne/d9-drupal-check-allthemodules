<?php

namespace Drupal\partial_multi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for configuring Partial Multi module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'partial_multi_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['partial_multi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['redirect_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect code'),
      '#description' => $this->t('This HTTP status code will be returned when redirecting away from untranslated content.'),
      '#options' => [
        '301' => $this->t('Permanent redirect (301)'),
        '302' => $this->t('Temporary redirect (302)'),
      ],
      '#default_value' => $this->config('partial_multi.settings')->get('redirect_code'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('partial_multi.settings');
    $config->set('redirect_code', $form_state->getValue('redirect_code'));
    $config->save();
  }

}
