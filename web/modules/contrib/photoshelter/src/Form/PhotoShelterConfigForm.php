<?php

namespace Drupal\photoshelter\Form;

use DateTime;
use DateTimeZone;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PhotoShelterConfigForm.
 *
 * @package Drupal\photoshelter\Form
 */
class PhotoShelterConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photoshelter_config_form';
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return ['photoshelter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form              = parent::buildForm($form, $form_state);
    $config            = $this->config('photoshelter.settings');
    $form['email']     = [
      '#type'          => 'email',
      '#title'         => $this->t('The email associated with your PhotoShelter account.'),
      '#default_value' => $config->get('email'),
    ];
    $form['password']  = [
      '#type'          => 'password',
      '#title'         => $this->t('Your PhotoShelter account password.'),
      '#description'   => $this->t('You can leave this field empty if it has been set before'),
      '#default_value' => $config->get('password'),
    ];
    $form['api_key']   = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Your PhotoShelter API key'),
      '#default_value' => $config->get('api_key'),
    ];
    $form['allow_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow synchronization of private files'),
      '#default_value' => $config->get('allow_private'),
    ];
    $form['cron_sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set a daily automatic synchronization'),
      '#default_value' => $config->get('cron_sync'),
    ];
    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum width'),
      '#description' => $this->t('Choose the maximum width for the photos in pixels, (ie: 700)'),
      '#required' => TRUE,
      '#min' => 100,
      '#size' => 4,
      '#default_value' => $config->get('max_width'),
    ];
    $form['max_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum height'),
      '#description' => $this->t('Choose the maximum height for the photos in pixels, (ie: 700)'),
      '#required' => TRUE,
      '#min' => 100,
      '#size' => 4,
      '#default_value' => $config->get('max_height'),
    ];
    $container_names = $config->get('containers_names');
    if (isset($container_names) && !empty($container_names)) {
      $collection_options = [];
      $gallery_options = [];
      foreach ($container_names as $container) {
        if ($container['type'] == 'collection') {
          $collection_options[$container['id']] = $container['name'];
        }
        else {
          $gallery_options[$container['id']] = $container['name'];
        }
      }
      $form['collections'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Collections'),
        '#options' => $collection_options,
      ];
      $form['galleries'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Galleries'),
        '#options' => $gallery_options,
      ];
      $collection_values = $config->get('collections');
      if (!empty($collection_values)) {
        $form['collections']['#default_value'] = $collection_values;
      }
      $gallery_values = $config->get('galleries');
      if (!empty($gallery_values)) {
        $form['galleries']['#default_value'] = $gallery_values;
      }
      $form['sync_new'] = [
        '#type'  => 'submit',
        '#value' => t('Sync New Additions'),
        '#submit' => ['::syncNewSubmit'],
      ];
    }
    $form['get_containers'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get root containers names'),
      '#submit' => ['::getCollectionsNames'],
    ];
    return $form;
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
    $this->saveConfig($form_state);
    $ps_service = \Drupal::service('photoshelter.photoshelter_service');
    $token = $ps_service->authenticate();
    if ($token == 'error') {
      $this->messenger()->addError(t('Invalid credentials'));
    }
    else {
      $this->messenger()->addMessage(t('authentication successful'));
    }
  }

  /**
   * Synchronize newly added galleries and images in the selected collections.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   */
  public function syncNewSubmit(array &$form, FormStateInterface $form_state) {
    $config = $this->saveConfig($form_state);
    $time   = $config->get('last_sync');

    // Get the date.
    if ($time === 'Never') {
      $time = new DateTime('1970-01-01', new DateTimeZone('GMT'));
    }
    else {
      $time = DateTime::createFromFormat(DateTime::RFC850, $time,
        new DateTimeZone('GMT'));
    }

    $ps_service = \Drupal::service('photoshelter.photoshelter_service');

    // Get the data.
    $ps_service->getData($time, TRUE);

    // Update time saved in config.
    $ps_service->updateConfigPostSync($config);
  }

  /**
   * Retrieve the Photoshelter collections and save them to the configuration.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   */
  public function getCollectionsNames(array &$form, FormStateInterface $form_state) {
    $config = $this->saveConfig($form_state);
    $ps_service = \Drupal::service('photoshelter.photoshelter_service');
    $containers_names = $ps_service->getContainersNames();
    $config->set('containers_names', $containers_names);
    $config->save();
  }

  /**
   * Save the configuration.
   *
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  private function saveConfig(FormStateInterface $form_state) {
    $config = $this->config('photoshelter.settings');
    $config->set('email', $form_state->getValue('email'));
    if (!empty($form_state->getValue('password'))) {
      $config->set('password', $form_state->getValue('password'));
    }
    $config->set('api_key', $form_state->getValue('api_key'));
    $config->set('allow_private', $form_state->getValue('allow_private'));
    $config->set('cron_sync', $form_state->getValue('cron_sync'));
    $config->set('max_width', $form_state->getValue('max_width'));
    $config->set('max_height', $form_state->getValue('max_height'));
    $config->set('collections', $form_state->getValue('collections'));
    $config->set('galleries', $form_state->getValue('galleries'));
    $config->save();

    return $config;
  }

}
