<?php

namespace Drupal\google_vision\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class GoogleVisionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_vision_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_vision.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_vision.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Vision API key'),
      '#required' => TRUE,
      '#description' => $this->t(
        'To create API key <ol>
            <li>Visit <a href="@url">Google Console</a> and create a project to use.</li>
            <li>Enable the Cloud Vision API.</li>
            <li>Generate API key with type "Browser key" under the Credentials tab.</li></ol>',
        [
          '@url' => 'https://cloud.google.com/console'
        ]
      ),
      '#default_value' => $config->get('api_key')
    ];

    $form['max_results_labels'] = [
      '#type' => 'number',
      '#title' => $this->t('Max results for Label Detection'),
      '#description' => $this->t('Optional. The default value will be set to 5.'),
      '#default_value' => !empty($config->get('max_results_labels')) ? $config->get('max_results_labels') : 5,
      '#min' => 1,
      '#step' => 1,
    ];

    $form['note'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Note: The labels are the result of the responses returned by Vision API. It may sometimes happen that the number of results is less than this value.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_vision.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('max_results_labels', $form_state->getValue('max_results_labels'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
