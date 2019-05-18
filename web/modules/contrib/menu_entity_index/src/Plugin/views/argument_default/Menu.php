<?php

namespace Drupal\menu_entity_index\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_entity_index\TrackerInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The menu argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "menu",
 *   title = @Translation("Menu")
 * )
 */
class Menu extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The Menu Entity Index Tracker service.
   *
   * @var \Drupal\menu_entity_index\TrackerInterface
   */
  protected $tracker;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\menu_entity_index\TrackerInterface $tracker
   *   The Menu Entity Index Tracker service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TrackerInterface $tracker) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_entity_index.tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['argument'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['argument'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#options' => $this->tracker->getAvailableMenus(),
      '#default_value' => $this->options['argument'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->options['argument'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

}
