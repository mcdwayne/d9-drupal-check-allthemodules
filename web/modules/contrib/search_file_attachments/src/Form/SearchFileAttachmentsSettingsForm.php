<?php

namespace Drupal\search_file_attachments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure search file attachments settings.
 */
class SearchFileAttachmentsSettingsForm extends ConfigFormBase {

  protected $javaService;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->javaService = \Drupal::service('search_file_attachments.java');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_file_attachments_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['search_file_attachments.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('search_file_attachments.settings');

    // Apache Tika configuration.
    $form['tika'] = array(
      '#type' => 'fieldset',
      '#title' => t('Apache Tika'),
    );
    $form['tika']['search_file_attachments_tika_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Tika directory path'),
      '#default_value' => $config->get('tika.path'),
      '#description' => t('The full path to tika directory.'),
      '#required' => TRUE,
    );
    $form['tika']['search_file_attachments_tika_jar'] = array(
      '#type' => 'textfield',
      '#title' => t('Tika jar file'),
      '#default_value' => $config->get('tika.jar'),
      '#description' => t('The name of the tika CLI application jar file, e.g. tika-app-1.xx.jar.'),
      '#required' => TRUE,
    );

    if (!$this->javaService->checkJava()) {
      $form['tika']['search_file_attachments_java_path'] = array(
        '#type' => 'textfield',
        '#title' => t('Java path'),
        '#default_value' => $config->get('java_path'),
        '#description' => t('The full path to the Java binary. This setting is only needed if Java could not automatically detected.'),
        '#required' => TRUE,
      );
    }

    // File settings.
    $form['files'] = array(
      '#type' => 'fieldset',
      '#title' => t('File settings'),
    );
    $form['files']['search_file_attachments_include'] = array(
      '#type' => 'textfield',
      '#title' => t('Included file extensions or mimetypes'),
      '#description' => t('A comma-separated list of file extensions or mimetypes that will be included to the file search index.'),
      '#default_value' => $config->get('files.include'),
      '#required' => TRUE,
    );

    // Advanced settings.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#open' => FALSE,
    );
    $form['advanced']['search_file_attachments_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Activate Debugging'),
      '#description' => t('Activate this option only for development and not on production sites.'),
      '#default_value' => $config->get('debug'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tika_path = rtrim($form_state->getValue('search_file_attachments_tika_path'), '/');
    $tika_path = realpath($tika_path);

    if (!file_exists($tika_path . '/' . $form_state->getValue('search_file_attachments_tika_jar'))) {
      $form_state->setErrorByName('search_file_attachments_tika_path', $this->t('Tika jar file not found at this path.'));
    }

    $java_path = $form_state->getValue('search_file_attachments_java_path');
    $this->javaService->setJavaPath($java_path);
    if (!empty($java_path) && !$this->javaService->checkJava()) {
      $form_state->setErrorByName('search_file_attachments_java_path', $this->t('Java was not found or is executable at the specified path.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('search_file_attachments.settings')
      ->set('tika.path', rtrim($form_state->getValue('search_file_attachments_tika_path'), '/'))
      ->set('tika.jar', $form_state->getValue('search_file_attachments_tika_jar'))
      ->set('java_path', $form_state->getValue('search_file_attachments_java_path'))
      ->set('files.include', $form_state->getValue('search_file_attachments_include'))
      ->set('debug', $form_state->getValue('search_file_attachments_debug'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
