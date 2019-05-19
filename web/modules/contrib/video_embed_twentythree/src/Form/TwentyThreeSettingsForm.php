<?php

namespace Drupal\video_embed_twentythree\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Video Embed TwentyThree settings.
 */
class TwentyThreeSettingsForm extends ConfigFormBase {

  /**
   * The video_embed_twentythree configuration.
   *
   * @var array
   *
   * @see \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a CropWidgetForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->config = $this->config('video_embed_twentythree.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_embed_twentythree_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['video_embed_twentythree.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['video_domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom video domains'),
      '#description' => $this->t('Fill in the TwentyThree video domains allowed on this site. E.g. customer.domain.tld, domain.tld etc. One domain per line. Write the domains without http(s):// and trailing slash.'),
      '#default_value' => $this->config->get('video_domains'),
    ];
    $form['automute_autoplay_videos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically mute auto-playing videos'),
      '#description' => $this->t('More and more browsers block auto-playing videos if the sound is not muted. Enable this setting to automatically mute videos where auto-play is enabled.'),
      '#default_value' => $this->config->get('automute_autoplay_videos'),
    ];
    $form['enable_query_parameters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable query string parameters'),
      '#description' => $this->t('Check to allow query string parameters in video URL\'s. If disable are query parameters stripped. autoPlay is configured in the field formatter and not allowed as query parameter.'),
      '#default_value' => $this->config->get('enable_query_parameters'),
    ];
    $form['allowed_query_parameters'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed query parameters'),
      '#description' => $this->t('Choose which query parameters are allowed.'),
      '#default_value' => $this->config->get('allowed_query_parameters'),
      '#states' => [
        'visible' => [
          ':checkbox[name="enable_query_parameters"]' => ['checked' => TRUE],
        ],
      ],
      '#options' => [
        'showDescriptions' => $this->t('Hide/show video title (showDescriptions=<0|1>)'),
        'showLogo' => $this->t('Hide/show logo (showLogo=<0|1>)'),
        'hideBigPlay' => $this->t('Hide big play button (hideBigPlay=1)'),
        'socialSharing' => $this->t('Hide/show sharing options (socialSharing=<0|1>)'),
        'showBrowse' => $this->t('Hide/show recommendations (showBrowse=<0|1>)'),
        'loop' => $this->t('Disable/enable looping (loop=<0|1>)'),
        'showTray' => $this->t('Hide/show controls (showTray=<0|1>)'),
        'autoMute' => $this->t('Disable/enable mute (autoMute=<0|1>)'),
        'start' => $this->t('Set starting point of video (start=)'),
        'defaultQuality' => $this->t('Set default quality of video (defaultQuality=)'),
        'ambient' => $this->t('Autoplay, loop and mute video (ambient=1)'),
        'token' => $this->t('Token parameter used when sharing private videos.'),
        'source' => $this->t('Source of the embed video.'),
      ]
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Clean up string with domains.
    $domains = $form_state->getValue('video_domains');
    $domains = explode("\n", $domains);
    $domains = array_map('trim', $domains);
    $domains = array_filter($domains);
    $domains = implode("\n", $domains);

    $this->config->set("video_domains", $domains);
    $this->config->set("automute_autoplay_videos", $form_state->getValue('automute_autoplay_videos'));
    $this->config->set("enable_query_parameters", $form_state->getValue('enable_query_parameters'));
    $allowed_query_parameters = array_filter($form_state->getValue('allowed_query_parameters'));
    $this->config->set("allowed_query_parameters", $allowed_query_parameters);
    $this->config->save();
  }

  /**
   * Get list of default values for the allowed query parameters field.
   *
   * @return array
   */
  private function getAllowedQueryParametersDefaultValues() {
    return [
      'showDescriptions' => 'showDescriptions',
      'showLogo' => 'showLogo',
      'hideBigPlay' => 'hideBigPlay',
      'socialSharing' => 'socialSharing',
      'showBrowse' => 'showBrowse',
      'loop' => 'loop',
      'showTray' => 'showTray',
      'autoMute' => 'autoMute',
      'start' => 'start',
      'defaultQuality' => 'defaultQuality',
      'ambient' => 'ambient',
      'token' => 'token',
      'source' => 'source'
    ];
  }

}
