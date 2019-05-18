<?php

namespace Drupal\vuejs\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Vue.js settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The library.discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs a form object.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The discovery service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LibraryDiscoveryInterface $library_discovery) {
    $this->libraryDiscovery = $library_discovery;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vuejs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vuejs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $registered_libraries = $this->libraryDiscovery->getLibrariesByExtension('vuejs');

    $libraries = $this->config('vuejs.settings')->get('libraries');

    foreach ($libraries as $library_name => $library) {
      $form[$library_name] = [
        '#type' => 'fieldset',
        '#title' => str_replace('_', '-', $library_name),
        '#tree' => TRUE,
      ];

      $form[$library_name]['installation'] = [
        '#type' => 'select',
        '#title' => t('Installation'),
        '#options' => [
          'local' => t('Local'),
          'cdnjs' => t('CDNJS'),
          'jsdelivr' => t('jsDelivr'),
        ],
        '#default_value' => $library['installation'],
      ];

      $form[$library_name]['development'] = [
        '#type' => 'checkbox',
        '#title' => t('Development version'),
        '#default_value' => $library['development'],
      ];

      $form[$library_name]['version'] = [
        '#type' => 'textfield',
        '#title' => t('Version'),
        '#size' => 9,
        '#required' => TRUE,
        '#default_value' => $library['version'],
      ];

      $path = $registered_libraries[$library_name]['js'][0]['data'];
      $options = [
        'absolute' => TRUE,
        'attributes' => ['target' => '_blank'],
      ];
      if (strpos($path, 'libraries') === 0) {
        $url = Url::fromUserInput('/' . $path, $options);
      }
      else {
        $url = Url::fromUri($path, $options);
      }

      $form[$library_name]['url'] = [
        '#type' => 'item',
        '#title' => t('URL'),
        '#markup' => (new Link($url->toString(), $url))->toString(),
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $libraries = $this->config('vuejs.settings')->get('libraries');
    foreach ($libraries as $library_name => $library) {

      $value = $form_state->getValue($library_name);

      if (!preg_match('/^\d+\.\d+\.\d+$/', $value['version'])) {
        $form_state->setErrorByName($library_name . '][version', $this->t('Version format is not correct.'));
      }

      $form_state->setValue($library_name, $value);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vuejs.settings')
      ->set('libraries.vue', $form_state->getValue('vue'))
      ->set('libraries.vue_router', $form_state->getValue('vue_router'))
      ->set('libraries.vue_resource', $form_state->getValue('vue_resource'))
      ->save();

    $this->libraryDiscovery->clearCachedDefinitions();
    parent::submitForm($form, $form_state);
  }

}
