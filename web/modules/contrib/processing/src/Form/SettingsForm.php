<?php

namespace Drupal\processing\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a settings form to allow configuration of processing library path.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Drupal root path i.e. DRUPAL_ROOT.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system interface.
   * @param string $app_root
   *   The Drupal root path.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, $app_root) {
    parent::__construct($config_factory);
    $this->fileSystem = $file_system;
    $this->appRoot = $app_root;
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('app.root')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['processing.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'processing_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('processing.settings');
    $form['defaults'] = [
      '#type' => 'details',
      '#title' => $this->t('Default Configuration'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['defaults']['processing_js_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Processing.js Library Path'),
      '#description' => $this->t('The relative path to the processing.js library file from your Drupal installation root.'),
      '#default_value' => $config->get('defaults.processing_js_path'),
      '#attributes' => [
        'placeholder' => '/libraries/processing.js/processing.min.js',
      ],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $element = ['#parents' => ['defaults', 'processing_js_path']];
    $path = $this->appRoot . $form_state->getValue($element['#parents']);
    $real_path = $this->fileSystem->realpath($path);
    if (!$real_path) {
      $form_state->setError($element, $this->t('Invalid path to the processing.js library.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = ['#parents' => ['defaults', 'processing_js_path']];

    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->config('processing.settings');
    $config->set('defaults.processing_js_path', $form_state->getValue($element['#parents']));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
