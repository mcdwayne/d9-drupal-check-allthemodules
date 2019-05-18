<?php

namespace Drupal\loading_bar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Configure loading bar settings for this site.
 */
class LoadingBarSettingsForm extends ConfigFormBase {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a LoadingBarSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager) {
    parent::__construct($config_factory);
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loading_bar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['loading_bar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('loading_bar.settings');

    $scheme_options = $this->streamWrapperManager->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $form['file_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => empty($config->get('file_scheme')) ? key($scheme_options) : $config->get('file_scheme'),
      '#description' => $this->t('Select where the uploaded images should be stored.'),
    ];

    $form['upload_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File directory'),
      '#default_value' => empty($config->get('upload_directory')) ? 'loading_bar' : $config->get('upload_directory'),
      '#description' => $this->t('Optional subdirectory within the upload destination where images will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => [[get_class($this), 'validateDirectory']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form callback: Element validate.
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    // Strip slashes from the beginning and end of $element['file_directory'].
    $value = trim($element['#value'], '\\/');
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('loading_bar.settings');
    $config->set('file_scheme', $form_state->getValue('file_scheme'));
    $config->set('upload_directory', $form_state->getValue('upload_directory'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
