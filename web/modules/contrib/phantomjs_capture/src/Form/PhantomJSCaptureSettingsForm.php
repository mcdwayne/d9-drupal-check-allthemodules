<?php

namespace Drupal\phantomjs_capture\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PhantomJSCaptureSettingsForm
 *
 * Provide a settings form for global settings of PhantomJS Capture.
 *
 * @package Drupal\phantomjs_capture\Form
 */
class PhantomJSCaptureSettingsForm extends ConfigFormBase {

  /**
   * PhantomCaptureSettingsForm constructor.
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

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
    return 'phantomjs_capture_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phantomjs_capture.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phantomjs_capture.settings');
    $url = 'http://phantomjs.org';

    $form['binary'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Path to PhantomJS binary'),
      '#description' => $this->t('This module requires that you install PhantomJS on your server and enter the path to the executable. The program is not include in the module due to licensing and operation system constraints. See <a href=":url">:url</a> for more information about downloading.', array(
        ':url' => $url,
      )),
      '#default_value' => $config->get('binary'),
    );

    $form['destination'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Default destination'),
      '#description' => $this->t('The default destination for captures with PhantomJS. Do not include public://. Example, "phantomjs" would be stored as public://phantomjs, or private://phantomjs, based on the site file scheme.'),
      '#default_value' => $config->get('destination'),
    );

    $form['script'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('PhantomJS capture script'),
      '#description' => $this->t('The script used by PhantomJS to capture the screen. It captures full HD images (1920 x 1080).'),
      '#default_value' => $config->get('script'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Check that PhantomJS exists.
    if (!file_exists($values['binary'])) {
      $form_state->setError($form['binary'], $this->t('The PhantomJS binary was not found at the location given.'));
    }

    // Check that destination can be created.
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . $values['destination'];
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      $form_state->setError($form['destination'], t('The path was not writeable or could not be created.'));
    }

    // Check that capture script exists.
    if (!file_exists($values['script'])) {
      $form_state->setError($form['script'], $this->t('PhantomJS script was not found at the location given.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('phantomjs_capture.settings')
      ->set('binary', $values['binary'])
      ->set('destination', $values['destination'])
      ->set('script', $values['script'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}