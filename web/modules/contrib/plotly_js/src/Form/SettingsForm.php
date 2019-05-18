<?php

namespace Drupal\plotly_js\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\File\FileSystem;

/**
 * Defines a form that configures plotly_js settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal LibraryDiscovery service container.
   *
   * @var Drupal\Core\Asset\LibraryDiscovery
   */
  protected $libraryDiscovery;

  /**
   * Drupal FileSystem service container.
   *
   * @var Drupal\Core\File\FileSystem
   */
  protected $fileSystemService;

  /**
   * {@inheritdoc}
   */
  public function __construct(LibraryDiscovery $library_discovery, FileSystem $file_system_service) {
    $this->libraryDiscovery = $library_discovery;
    $this->fileSystemService = $file_system_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('library.discovery'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'plotly_js_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'plotly_js.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get current settings.
    $plotly_js_config = $this->config('plotly_js.settings');

    // Load the plotly library so we can use its definitions here.
    $plotly_library = $this->libraryDiscovery->getLibraryByName('plotly_js', 'plotly_js.plotly');

    $form['external'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('External file configuration'),
      '#description' => $this->t('These settings control the method by which the plotly.js file is loaded. You can choose to use an external file by selecting a URL below, or you can use a local version of the file by leaving the box unchecked and downloading the file <a href=":remoteurl">:remoteurl</a> and installing locally at %installpath', [
        ':remoteurl' => $plotly_library['remote'],
        '%installpath' => 'libraries/plotly/plotly-latest.min.js',
      ]),
      'use_external' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use external file?'),
        '#description' => $this->t('Checking this box will cause the Plotly.js javascript file to be loaded externally rather than from the local library file.'),
        '#default_value' => $plotly_js_config->get('use_external'),
      ],
      'external_location' => [
        '#type' => 'textfield',
        '#title' => $this->t('External File Location'),
        '#default_value' => $plotly_js_config->get('external_location'),
        '#description' => $this->t('Enter a source URL for the external plotly.js file you wish to use. If you wish to use the plotly.js CDN, the location is %remoteurl.', [
          '%remoteurl' => $plotly_library['remote'],
        ]),
        '#states' => [
          'disabled' => [
            ':input[name="use_external"]' => ['checked' => FALSE],
          ],
        ],
      ],
    ];
    $form['graph_template_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Graph Templates Path'),
      '#default_value' => $plotly_js_config->get('graph_template_path'),
      '#description' => $this->t('Enter a local scheme path for graph template files. These files can be modified to change the display and order of form entries in the field widget for entering graph information.'),
    ];
    $form['mapbox_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mapbox Access Token'),
      '#default_value' => $plotly_js_config->get('mapbox_access_token'),
      '#size' => 100,
      '#description' => $this->t('Enter your mapbox access token. Required to be able to use mapbox within plotly.js graphs. See <a href=":mapboxurl">the mapbox website</a> and <a href=":plotlymapboxurl">the plotly.js mapbox documentation</a> for more details.', [
        ':mapboxurl' => 'https://www.mapbox.com',
        ':plotlymapboxurl' => 'https://plot.ly/javascript/scattermapbox/',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!$this->fileSystemService->validScheme($this->fileSystemService->uriScheme($values['graph_template_path']))) {
      $form_state->setErrorByName('graph_template_path', $this->t('Invalid scheme name %scheme.', [
        '%scheme' => $this->fileSystemService->uriScheme($values['graph_template_path']),
      ]));
    }

    // Validate URL.
    if (!UrlHelper::isValid($values['external_location'])) {
      $form_state->setErrorByName('external_location', $this->t('Invalid external file location.'));
    }

    // Make sure there is a file location if its turned on.
    if ($values['use_external'] && empty($values['external_location'])) {
      $form_state->setErrorByName('external_location', $this->t('You must set an external file location or disable external file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Clear the library cache so we can use the correct plotly stuff.
    $this->libraryDiscovery->clearCachedDefinitions();

    $this->config('plotly_js.settings')
      ->set('use_external', $values['use_external'])
      ->set('external_location', $values['external_location'])
      ->set('graph_template_path', $values['graph_template_path'])
      ->set('mapbox_access_token', $values['mapbox_access_token'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
