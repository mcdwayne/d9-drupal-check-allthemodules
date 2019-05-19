<?php

/**
 * @file
 * Contains \Drupal\strava\Form\StravaConfigurationForm.
 */

namespace Drupal\strava\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class StravaConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'strava_configuration_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function getEditableConfigNames() {
    return ['strava_configuration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('strava_configuration.settings');

    $form['client_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Client id'),
      '#default_value' => $config->get('client_id'),
      '#placeholder' => $this->t('Your client Id'),
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('client_secret'),
      '#placeholder' => $this->t('Your Client secret'),
    ];

    $form['scopes'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scopes'),
      '#description' => $this->t('Authorisation scopes'),
      '#default_value' => $config->get('scopes'),
      '#options' => [
        'public' => 'public',
        'write' => 'write',
        'view_private' => 'view_private',
        'view_private,write' => 'view_private,write',
      ],
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // When the scope setting changes, invalidate all existing access tokens.
    $config = $this->config('strava_configuration.settings');
    $scopes = $config->get('scopes');
    if ($scopes !== $form_state->getValue('scopes')) {
      $connection = \Drupal::database();
      $number = $connection->delete('key_value_expire')
        ->condition('collection', 'tempstore.private.strava')
        ->execute();

      $this->messenger()
        ->addStatus(t('Invalidated @number Strava access tokens because the scope setting changed. All users need to reauthorize for the new scope.', ['@number' => $number]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->config('strava_configuration.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('scopes', $values['scopes'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
