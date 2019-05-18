<?php

namespace Drupal\amazon_sns\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure SNS settings for this site.
 *
 * @codeCoverageIgnore
 *   This class is tested with a Functional test that can't be covered.
 */
class SnsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazon_sns_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  protected function getEditableConfigNames() {
    return ['amazon_sns.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form base.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of given form.
   *
   * @return array
   *   The config form for Amazon SNS.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amazon_sns.settings');

    $form['log_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging of all inbound SNS notifications'),
      '#default_value' => $config->get('log_notifications'),
      '#description' => $this->t("Turn on logging to help debug SNS problems. This will log message IDs and the associated SNS topic."),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save configuration',
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $settings = $this->config('amazon_sns.settings');
    $settings->set('log_notifications', $values['log_notifications']);
    $settings->save();

    parent::submitForm($form, $form_state);
  }

}
