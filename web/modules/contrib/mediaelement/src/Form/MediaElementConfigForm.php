<?php

namespace Drupal\mediaelement\Form;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for MediaElement.js module.
 */
class MediaElementConfigForm extends ConfigFormBase {

  /**
   * The URL to CDNJS's API.
   *
   * @var string
   */
  protected $cndjsUrl = 'https://api.cdnjs.com/libraries/mediaelement';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mediaelement_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mediaelement.settings',
    ];
  }

  /**
   * Get the results from the CDN.
   *
   * @param array $params
   *   Optional query paramaters to pass onto the URL.
   *
   * @return object
   *   The data from the API.
   */
  protected function getApiData($params = []) {
    // @todo: Use dependency injection for this.
    $client = \Drupal::httpClient();
    $url = empty($params)
      ? $this->cndjsUrl
      : $this->cndjsUrl . '?' . http_build_query($params);

    try {
      $res = $client->get($url);
      return json_decode($res->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('mediaelement', $e->getMessage());
    }
  }

  /**
   * Gets the list of available version numbers for the library.
   *
   * @return string[]
   *   The array of version strings.
   */
  protected function getVersionList() {
    $data = $this->getApiData(['fields' => 'assets']);
    return array_map(function ($asset) {
      return $asset->version;
    }, $data->assets);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mediaelement.settings');

    // Create configurations for where the library is loaded from.
    $library_config = $config->get('library_settings');

    $form['library_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Library Settings'),
      '#open' => TRUE,
    ];

    $form['library_settings']['library_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Library Source'),
      '#options' => [
        'local' => $this->t('Local Download'),
        'cdnjs' => $this->t('CDN (Provided by CDNJS.com)'),
      ],
      '#default_value' => $library_config['library_source'] ?? 'local',
      '#required' => TRUE,
    ];

    $form['library_settings']['cdnjs_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CDNJS Settings'),
      '#states' => [
        'visible' => [
          ':input[name="library_source"]' => ['value' => 'cdnjs'],
        ],
      ],
    ];

    $version_options = $this->getVersionList();
    $form['library_settings']['cdnjs_settings']['library_version'] = [
      '#type' => 'select',
      '#title' => $this->t('Library Version'),
      '#options' => array_combine($version_options, $version_options),
      '#default_value' => $library_config['cdnjs_settings']['library_version'] ?? $version_options[0],
    ];

    // Global configuration items for player functionality.
    $global_config = $config->get('global_settings');

    $form['global_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global Settings'),
    ];

    $form['global_settings']['attach_sitewide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable MediaElement.js site wide'),
      '#description' => $this->t('Attach the MediaElement.js library throughtout the entire site. Any <code>audio</code> or <code>video</code> HTML tag will have the player applied to them.'),
      '#default_value' => $global_config['attach_sitewide'] ?? FALSE,
      '#weight' => 0,
    ];

    // Configuration that applies to all types of players.
    $player_config = $config->get('global_settings.player_settings');

    $form['global_settings']['player_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General Player Settings'),
      '#weight' => 1,
    ];

    $form['global_settings']['player_settings']['class_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Prefix'),
      '#description' => $this->t('Class prefix for player elements.'),
      '#default_value' => $player_config['class_prefix'] ?? '',
      '#placeholder' => 'mejs__',
    ];

    $form['global_settings']['player_settings']['set_dimensions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Dimensions'),
      '#description' => $this->t('Set dimensions via JS instead of CSS.'),
      '#default_value' => $player_config['set_dimensions'] ?? TRUE,
    ];

    // Configuration for video players.
    $video_settings = $config->get('global_settings.video_settings');

    $form['global_settings']['video_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Video Player Settings'),
      '#weight' => 2,
    ];

    $form['global_settings']['video_settings']['default_video_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Video Width'),
      '#description' => $this->t('Default width if the <code>&#60;video&#62;</code> width is not specified'),
      '#default_value' => $video_settings['default_video_width'] ?? '',
      '#placeholder' => '480',
    ];

    $form['global_settings']['video_settings']['default_video_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Video Height'),
      '#description' => $this->t('Default width if the <code>&#60;video&#62;</code> height is not specified'),
      '#default_value' => $video_settings['default_video_height'] ?? '',
      '#placeholder' => '270',
    ];

    $form['global_settings']['video_settings']['video_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Video Width'),
      '#description' => $this->t('If set, overrides <code>&#60;video&#62;</code> width'),
      '#default_value' => $video_settings['video_width'] ?? '',
      '#placeholder' => '-1',
    ];

    $form['global_settings']['video_settings']['video_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Video Height'),
      '#description' => $this->t('If set, overrides <code>&#60;video&#62;</code> height'),
      '#default_value' => $video_settings['video_height'] ?? '',
      '#placeholder' => '-1',
    ];

    // Configuration for audio players.
    $audio_settings = $config->get('global_settings.audio_settings');

    $form['global_settings']['audio_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Audio Player Settings'),
      '#weight' => 3,
    ];

    $form['global_settings']['audio_settings']['default_audio_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Audio Width'),
      '#description' => $this->t('Default width if the <code>&#60;audio&#62;</code> width is not specified'),
      '#default_value' => $audio_settings['default_audio_width'] ?? '',
      '#placeholder' => '400',
    ];

    $form['global_settings']['audio_settings']['default_audio_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Audio Height'),
      '#description' => $this->t('Default width if the <code>&#60;audio&#62;</code> height is not specified'),
      '#default_value' => $audio_settings['default_audio_height'] ?? '',
      '#placeholder' => '30',
    ];

    $form['global_settings']['audio_settings']['audio_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Audio Width'),
      '#description' => $this->t('If set, overrides <code>&#60;audio&#62;</code> width'),
      '#default_value' => $audio_settings['audio_width'] ?? '',
      '#placeholder' => '-1',
    ];

    $form['global_settings']['audio_settings']['audio_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Audio Height'),
      '#description' => $this->t('If set, overrides <code>&#60;audio&#62;</code> height'),
      '#default_value' => $audio_settings['audio_height'] ?? '',
      '#placeholder' => '-1',
    ];

    $api_link = Link::fromTextAndUrl(
      $this->t('API Documentation'),
      Url::fromUri('https://github.com/mediaelement/mediaelement/blob/master/docs/api.md#mediaelementplayer')
    );

    $form['global_settings']['api_link'] = [
      '#markup' => $this->t('<small>For a full explaination of configuration options, see the @api_link.</small>', [
        '@api_link' => $api_link->toString(),
      ]),
      '#weight' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Prepare our configuration items for saving.
   *
   * @param array $fields
   *   The field names we want to parse.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The submitted form state.
   *
   * @return array
   *   The settings to save keyed by their name.
   */
  private function getConfigurationValues(array $fields, FormStateInterface $form_state) {
    $values = [];

    foreach ($fields as $field) {
      $value = $form_state->getValue($field);

      if (!empty($value) || $value === 0) {
        $values[$field] = $value;
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('mediaelement.settings');

    $library_settings = [];
    $library_settings['library_source'] = $form_state->getValue('library_source');

    // If connecting to a CDN, save their settings.
    if ($library_settings['library_source'] != 'local') {
      $cdn_name = $library_settings['library_source'];
      $cdn_settings_fields = ['library_version'];
      $library_settings["{$cdn_name}_settings"] = $this->getConfigurationValues(
        $cdn_settings_fields,
        $form_state
      );
    }

    $config->set('library_settings', $library_settings);

    $global_settings = [];
    $global_settings['attach_sitewide'] = $form_state->getValue('attach_sitewide');

    $player_settings_fields = ['class_prefix', 'set_dimensions'];
    $global_settings['player_settings'] = $this->getConfigurationValues(
      $player_settings_fields,
      $form_state
    );

    $video_settings_fields = [
      'default_video_width',
      'default_video_height',
      'video_width',
      'video_height',
    ];
    $global_settings['video_settings'] = $this->getConfigurationValues(
      $video_settings_fields,
      $form_state
    );

    $audio_settings_fields = [
      'default_audio_width',
      'default_audio_height',
      'audio_width',
      'audio_height',
    ];
    $global_settings['audio_settings'] = $this->getConfigurationValues(
      $audio_settings_fields,
      $form_state
    );

    $config->set('global_settings', $global_settings);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
