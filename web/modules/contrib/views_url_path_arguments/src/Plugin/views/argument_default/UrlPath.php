<?php

namespace Drupal\views_url_path_arguments\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Convert an entity id to its url path.
 *
 * @ViewsArgumentDefault(
 *   id = "views_url_path",
 *   title = @Translation("Entity ID converted from URL path alias")
 * )
 */
class UrlPath extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Language manager for retrieving the default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new Tid instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, RouteMatchInterface $routeMatch,  AliasStorageInterface $aliasStorage, LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->routeMatch = $routeMatch;
    $this->aliasStorage = $aliasStorage;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('current_route_match'),
      $container->get('path.alias_storage'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['provide_static_segments'] = ['default' => FALSE];
    $options['segments'] = ['default' => ''];

    return $options;
  }


  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $formState) {
    $form['provide_static_segments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide a static url segment(s) to prefix aliases?'),
      '#default_value' => $this->options['provide_static_segments'],
    ];
    $form['segments'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Segments'),
      '#description' => $this->t('Without leading and/or trailing slashes.'),
      '#default_value' => $this->options['segments'],
      '#states' => [
        'visible' => [
          ':input[name="options[argument_default][views_url_path][provide_static_segments]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $parameter = $this->routeMatch->getRawParameters()->all();
    $lastSegment = array_pop($parameter);

    // Is it already the entity id?
    if (ctype_digit($lastSegment)) {
      return $lastSegment;
    }

    $alias = '/';
    if ($this->options['provide_static_segments']) {
      $alias .= $this->options['segments']. '/';
    }

    $alias .= $lastSegment;
    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    $canonicalPath = $this->aliasStorage->lookupPathSource($alias, $langcode);

    return substr($canonicalPath, strrpos($canonicalPath, '/') + 1);
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
    return ['url'];
  }

}
