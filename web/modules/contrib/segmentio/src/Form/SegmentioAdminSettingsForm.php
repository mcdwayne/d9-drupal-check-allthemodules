<?php

namespace Drupal\segmentio\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit Segmentio Settings form.
 */
class SegmentioAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'segmentio_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('segmentio.settings')
      ->set('segmentio_write_key', $form_state->getValue('segmentio_write_key'))
      ->set('segmentio_privacy', $form_state->getValue('segmentio_privacy'))
      ->set('segmentio_track', $form_state->getValue('segmentio_track'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'segmentio.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('segmentio.settings');

    $form['account'] = [
      '#type' => 'fieldset',
      '#title' => t('Basic Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['account']['segmentio_write_key'] = [
      '#title' => t('Write Key'),
      '#type' => 'textfield',
      '#default_value' => $config->get('segmentio_write_key'),
      '#size' => 200,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#description' => t('This Write Key is unique to each Project you have configured in <a href="@segmentio">Segment.io</a>. To get a Write Key, <a href="@analyticsjs">register your Project with Segment.io</a>, or if you already have registered your site, go to your Segment.io Project Settings page.analyticsjs will use this write key to send data to your project.', [
        '@segmentio' => 'https://segment.io/login',
        '@analyticsjs' => 'https://segment.io/login',
      ]),
    ];

    // Advanced segmentio configurations.
    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => t('Advance Configurations'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    // Privacy configurations.
    $form['advanced']['segmentio_privacy'] = [
      '#type' => 'checkbox',
      '#title' => t('Universal web tracking opt-out'),
      '#description' => t('If enabled and your server receives the <a href="@donottrack">Do-Not-Track</a> header from the client browser, the segmentio module will not embed any tracking code into your site. Compliance with Do Not Track could be purely voluntary, enforced by industry self-regulation, or mandated by state or federal law. Please accept your visitors privacy. If they have opt-out from tracking and advertising, you should accept their personal decision. This feature is currently limited to logged in users and disabled page caching.', [
        '@donottrack' => 'http://donottrack.us/',
      ]),
      '#default_value' => $config->get('segmentio_privacy'),
    ];

    // Tracking settings.
    $form['advanced']['segmentio_track'] = [
      '#type' => 'checkboxes',
      '#title' => t('Track Settings'),
      '#options' => \Drupal::moduleHandler()->invokeAll('segmentio_info'),
      '#default_value' => $config->get('segmentio_track'),
      '#description' => t('Select which additional information should be tracked.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
