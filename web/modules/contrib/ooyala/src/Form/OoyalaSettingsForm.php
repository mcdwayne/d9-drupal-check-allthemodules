<?php

/**
 * @file
 * Contains Drupal\ooyala\Form\OoyalaSettingsForm.
 */

namespace Drupal\ooyala\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\ooyala\OoyalaManagerInterface;
use Drupal\ooyala\Ajax\OoyalaPlayerResponse;

/**
 * Implements the OoyalaSettingsForm form controller.
 *
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class OoyalaSettingsForm extends ConfigFormBase {

  /**
   * Configuration key used by this module
   */
  const CONFIG_KEY = 'ooyala.config';

  const DEFAULT_PULSE_PLUGIN = '';

  /**
   * Default to 16:9 720 resolution
   */
  const DEFAULT_WIDTH = 720;
  const DEFAULT_HEIGHT = 405;

  /**
   * @var OoyalaManager
   */
  protected $ooyalaManager;

  /**
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ooyala_settings';
  }

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, OoyalaManagerInterface $ooyalaManager) {
    $this->configFactory = $configFactory;
    $this->ooyalaManager = $ooyalaManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ooyala.ooyala_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_KEY);

    $form += [
      'api_key' => [
        '#type' => 'textfield',
        '#default_value' => $config->get('api_key'),
        '#title' => $this->t('Ooyala API Key'),
        '#max_length' => 28,
        '#description' => $this->t('Enter your Ooyala API Key.'),
      ],

      'secret_key' => [
        '#type' => 'textfield',
        '#default_value' => $config->get('secret_key'),
        '#title' => $this->t('Ooyala API Secret'),
        '#max_length' => 40,
        '#description' => $this->t('Enter your Ooyala API Secret.'),
      ],

      'test_api_button' => [
        '#type' => 'button',
        '#value' => $this->t('Check Ooyala API'),
        '#ajax' => [
          'callback' => 'Drupal\ooyala\Form\OoyalaSettingsForm::checkAPI',
          'event' => 'click',
          'wrapper' => 'ooyala-api-status',
        ],
        '#suffix' => '<span id="ooyala-api-status"></span>'
      ],

      'plugins' => [
        '#prefix' => '<h4>' . $this->t('Plugins') . '</h4>',
        '#title' => $this->t('Streaming Plugins'),
        '#description' => $this->t('You must choose at least one streaming plugin.'),
        '#default_value' => $config->get('plugins') ?: ['main_html5'],
        '#type' => 'checkboxes',
        '#required' => TRUE,
      ],

      'ad_plugin' => [
        '#title' => $this->t('Ad Plugins'),
        '#description' => $this->t('You may optionally choose one ad plugin.'),
        '#type' => 'radios',
        '#default_value' => $config->get('ad_plugin') ?: '',
        '#options' => [
          '' => $this->t('None'),
        ],
      ],

      'pulse_options' => [
        '#name' => 'pulse_options',
        '#type' => 'hidden',
        '#title' => $this->t('Additional options for the Pulse plugin'),
        '#theme' => 'ooyala_json_textarea',
        '#default_value' => $config->get('pulse_options'),
        '#rows' => 8,
        '#description' => $this->t('Review the <a href="https://www.dropbox.com/s/70u5h4oen6jlnbo/Ooyala-HTML5playerV4-Pulseintegration_20160406.pdf?dl=0">Pulse integration reference</a> for details on acceptable parameters.'),
        '#states' => [
          'visible' => [
            ':input[name="ad_plugin"]' => ['value' => 'pulse'],
          ],
        ],
      ],

      'pulse_plugin' => [
        '#type' => 'textfield',
        '#title' => $this->t('Override Pulse plugin URL:'),
        '#attributes' => [
          'placeholder' => self::DEFAULT_PULSE_PLUGIN,
        ],
        '#default_value' => $config->get('pulse_plugin'),
        '#states' => [
          'visible' => [
            ':input[name="ad_plugin"]' => ['value' => 'pulse'],
          ],
        ],
      ],

      'optional_plugins' => [
        '#title' => $this->t('Optional Plugins'),
        '#description' => $this->t('Plugins that provide additional functionality to your player.'),
        '#type' => 'checkboxes',
        '#default_value' => array_filter($config->get('optional_plugins') ?: []),
      ],

      'skin_json' => [
        '#name' => 'skin_json',
        '#type' => 'hidden',
        '#title' => $this->t('Additional JSON Skin'),
        '#default_value' => $config->get('skin_json'),
        '#rows' => 8,
        '#theme' => 'ooyala_json_textarea',
        '#description' => $this->t('Review the <a href="http://support.ooyala.com/developers/documentation/reference/pbv4_skin_schema_docs.html">JSON skinning reference</a> for details on acceptable parameters.'),
      ],

      'custom_css' => [
        '#title' => $this->t('Custom CSS Skin'),
        '#type' => 'textarea',
        '#default_value' => $config->get('custom_css'),
        '#placeholder' => $this->t('Additional CSS rules to be applied to all players'),
        '#rows' => 8,
        '#description' => $this->t('Review the <a href="http://support.ooyala.com/developers/documentation/concepts/pbv4_css.html">CSS skinning reference</a> for details on how to style your player using CSS.'),
      ],

    ];

    $this->addPluginOptions($form['plugins']);
    $this->addPluginOptions($form['ad_plugin'], 'ad');
    $this->addPluginOptions($form['optional_plugins'], 'optional');

    $form['#attached']['library'][] = 'ooyala/ooyala_settings';

    return parent::buildForm($form, $form_state);
  }

  /**
   * Check the API and return a friendly response indicating its status.
   */
  public static function checkAPI(array &$form, FormStateInterface $form_state) {
    $manager = \Drupal::getContainer()->get('ooyala.ooyala_manager');

    $manager->setCredentials(trim($form_state->getValue('api_key')), trim($form_state->getValue('secret_key')));

    if ($manager->haveCredentials() && $manager->validCredentials()) {
      $markup = t('Congrats, the API key and secret are valid.');
    }
    elseif ($manager->haveCredentials()) {
      $markup = t('Invalid API key or secret key.');
    }
    elseif ($manager->apiAvailable()) {
      $markup = t('Ooyala API is available. Enter your API key and secret key to validate credentials.');
    }
    else {
      $markup = t('Ooyala API is not available.');
    }

    return [
      '#prefix' => '<strong id="ooyala-api-status">',
      '#markup' => $markup,
      '#suffix' => '</strong>',
    ];

  }

  /**
   * Determine valid players
   */
  public static function getPlayers(array &$form, FormStateInterface $form_state) {
    $manager = \Drupal::getContainer()->get('ooyala.ooyala_manager');

    $manager->setCredentials(trim($form_state->getValue('api_key')), trim($form_state->getValue('secret_key')));

    if (!$manager->haveCredentials()) {
      return [];
    }

    $response = new AjaxResponse();

    $response->addCommand(new OoyalaPlayerResponse('[name="player_id"]', $manager->getPlayers()));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit the vault settings form.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);

    $config->set('api_key', trim($form_state->getValue('api_key')));
    $config->set('secret_key', trim($form_state->getValue('secret_key')));

    $width = (int) $form_state->getValue('video_width');
    $height = (int) $form_state->getValue('video_height');

    if ($width <= 0) {
      $width = self::DEFAULT_WIDTH;
    }
    if ($height <= 0) {
      $height = self::DEFAULT_HEIGHT;
    }

    $config->set('plugins', array_keys(array_filter($form_state->getValue('plugins'))));
    $config->set('ad_plugin', $form_state->getValue('ad_plugin'));
    $config->set('optional_plugins', array_keys(array_filter($form_state->getValue('optional_plugins'))));
    $config->set('pulse_options', $form_state->getValue('pulse_options'));
    $config->set('pulse_plugin', $form_state->getValue('pulse_plugin'));

    $config->set('custom_css', $form_state->getValue('custom_css'));
    $config->set('skin_json', $form_state->getValue('skin_json'));

    if (!$config->get('player_id')) {
      $manager = $this->ooyalaManager;

      $manager->setCredentials(trim($form_state->getValue('api_key')), trim($form_state->getValue('secret_key')));

      if ($manager->haveCredentials()) {
        $players = $manager->getPlayers();

        if (count($players) > 0) {
          $config->set('player_id', reset($players)->id);
        }
      }
    }
    // TODO: Will V4 players ever need to provide a different player ID?
    if ($config->get('player_id_override')) {
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_KEY];
  }

  /**
   * Populates #options and #description for checkbox or radio button form
   * fields from the plugins defined in the library.
   *
   * @param array &$element
   * @param string $type
   */
  protected function addPluginOptions(&$element, $type = '') {
    foreach ($this->ooyalaManager->getPlugins($type) as $plugin_id => $info) {
      if (is_string($info)) {
        $info = ['name' => $info];
      }

      $element['#options'][$plugin_id] = $this->t($info['name']);

      if ($info['description']) {
        $element[$plugin_id] = ['#description' => $info['description']];
      }
    }
  }
}
