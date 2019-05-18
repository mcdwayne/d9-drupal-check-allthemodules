<?php

namespace Drupal\blackbaud_sky_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blackbaud_sky_api\BlackbaudInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Class BlackbaudSkyApiSettingsForm.
 *
 * @package Drupal\blackbaud_sky_api
 */
class BlackbaudSkyApiSettingsForm extends ConfigFormBase implements BlackbaudInterface, ContainerInjectionInterface {

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new BlackbaudSkyApiSettingsForm object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blackbaud_sky_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['blackbaud_sky_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Dev fieldset.
    $form['dev'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Blackbaud API Development Settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    // The Base Oauth URL.
    $odesc = $this->t('This should never change. This is here for future proofing. <a href="@bb" target="_blank">View the OAuth base url just in case</a>.', [
      '@bb' => 'https://apidocs.sky.blackbaud.com/docs/authorization/',
    ]);

    $form['dev']['blackbaud_sky_api_oauth_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blackbaud SKY API Oath URL'),
      '#default_value' => $this->state->get('blackbaud_sky_api_oauth_url', BlackbaudInterface::BLACKBAUD_SKY_API_OAUTH_URL),
      '#description' => $odesc,
      '#required' => TRUE,
    ];

    // The Base API URL.
    $odesc = $this->t('This should also never change. This is here for future proofing as well. <a href="@api" target="_blank">View the API base url just in case</a>.', [
      '@api' => 'https://apidocs.sky.blackbaud.com/docs/basics/',
    ]);

    $form['dev']['blackbaud_sky_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blackbaud SKY API URL'),
      '#default_value' => $this->state->get('blackbaud_sky_api_url', BlackbaudInterface::BLACKBAUD_SKY_API_URL),
      '#description' => $odesc,
      '#required' => TRUE,
    ];

    // The Developer Key.
    $kdesc = $this->t('Your Primary of Secondary Key Works Here. You can grab it from your <a href="@dev" target="_blank">Developer Profile</a>.', [
      '@dev' => 'https://developer.sky.blackbaud.com/developer/',
    ]);
    $form['dev']['blackbaud_sky_api_dev_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blackbaud Developer Key'),
      '#default_value' => $this->state->get('blackbaud_sky_api_dev_key', ''),
      '#description' => $kdesc,
      '#required' => TRUE,
    ];

    // Application fieldset.
    $form['app'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Application Settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    // Descriptive text.
    $tdesc = $this->t('You can obtain the application info from your <a href="@app" target="_blank">My Applications Page</a>.', [
      '@app' => 'https://developerapp.sky.blackbaud.com/applications',
    ]);
    $form['app']['title'] = [
      '#type' => 'item',
      '#markup' => '<span><strong>' . $tdesc . '</strong></span>',
    ];

    // The App ID.
    $form['app']['blackbaud_sky_api_application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID.'),
      '#default_value' => $this->state->get('blackbaud_sky_api_application_id', ''),
      '#required' => TRUE,
    ];

    $form['app']['blackbaud_sky_api_application_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Secret.'),
      '#default_value' => $this->state->get('blackbaud_sky_api_application_secret', ''),
      '#required' => TRUE,
    ];

    // The Redirect URI.
    $rdesc = $this->t('The path without the domain and forward slash ie @path.', [
      '@path' => 'blackbaud/oauth',
    ]);

    $form['app']['blackbaud_sky_api_redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#default_value' => $this->state->get('blackbaud_sky_api_redirect_uri', BlackbaudInterface::BLACKBAUD_SKY_API_REDIRECT_URI),
      '#description' => $rdesc,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'blackbaud_sky_api_') !== FALSE) {
        $this->state->set($key, $value);
      }
    }
    
    parent::submitForm($form, $form_state);
  }

}
