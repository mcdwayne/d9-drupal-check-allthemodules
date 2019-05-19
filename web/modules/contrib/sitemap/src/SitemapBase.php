<?php

namespace Drupal\sitemap;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Sitemap plugin implementations.
 *
 * @ingroup sitemap
 */
abstract class SitemapBase extends PluginBase implements SitemapInterface {

  use StringTranslationTrait;

  /**
   * An associative array containing the configured settings of the sitemap_map.
   *
   * @var array
   */
  public $settings = [];

  /**
   * A Boolean indicating whether this mapping is enabled.
   *
   * @var bool
   */
  public $enabled = FALSE;

  /**
   * The weight of this mapping compared to others in the sitemap.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The name of the provider that owns this mapping.
   *
   * @var string
   */
  public $provider;

  /**
   * The global sitemap config.
   *
   * @var object
   */
  protected $sitemapConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->provider = $this->pluginDefinition['provider'];

    if (empty($configuration)) {
      $configuration = $this->defaultConfiguration();
    }

    $this->setConfiguration($configuration);
    $this->sitemapConfig = \Drupal::config('sitemap.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return ['module' => 'sitemap'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Provide a section title field for every mapping plugin.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->settings['title'],
      '#description' => $this->t('If you do not wish to display a title, leave this field blank.'),
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'status' => $this->enabled,
      'weight' => $this->weight,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['enabled'])) {
      $this->enabled = (bool) $configuration['enabled'];
    }
    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'provider' => $this->pluginDefinition['provider'],
      'enabled' => $this->pluginDefinition['enabled'],
      'weight' => isset($this->pluginDefinition['weight']) ? $this->pluginDefinition['weight'] : '',
      'settings' => isset($this->pluginDefinition['settings']) ? $this->pluginDefinition['settings'] : [],
    ];
  }

}
