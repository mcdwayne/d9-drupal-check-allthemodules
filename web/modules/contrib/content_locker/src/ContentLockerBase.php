<?php

namespace Drupal\content_locker;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines a base content locker implementation.
 */
abstract class ContentLockerBase extends PluginBase implements ContentLockerPluginInterface {

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The base config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $baseConfig;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an ImageToolkitBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->baseConfig = \Drupal::config('content_locker.settings');
    $this->configFactory = \Drupal::config($this->getProvider() . '.settings');
    $this->settings = $this->defaultSettings();
  }

  /**
   * Get plugin type.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Get plugin type label.
   */
  public function getTypeLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Define library.
   */
  public function defaultLibrary() {
    $library = ['content_locker/content_locker'];
    if ($themeLocker = $this->baseConfig->get('basic.skin')) {
      $library[] = 'content_locker/content_locker.' . $themeLocker;
    }
    return $library;
  }

  /**
   * Define settings.
   */
  public function defaultSettings() {
    return $this->configFactory->getRawData();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    return $this->configFactory->get($key);
  }

  /**
   * Pre render content locker element.
   */
  public function preRender(&$element) {
    $element['#attached'] = [
      'library' => $this->defaultLibrary(),
      'drupalSettings' => [
        'content_locker' => [
          'base' => \Drupal::config('content_locker.settings')->get('basic'),
          'plugins' => [
            $this->getId() => $this->defaultSettings(),
          ],
        ],
      ],
    ];
  }

}
