<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Freelinking path plugin.
 *
 * @Freelinking(
 *   id = "path_alias",
 *   title = @Translation("Path Alias"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {
 *     "failover" = "search"
 *   }
 * )
 */
class PathAlias extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Path Alias Manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Path\AliasManagerInterface $aliasManager
   *   The path alias manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $moduleHandler, AliasManagerInterface $aliasManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $moduleHandler;
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/path|alias/i';
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Click to view a local node.');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'settings' => ['failover' => 'search'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['failover'] = [
      '#type' => 'select',
      '#title' => $this->t('If path alias is not found'),
      '#description' => $this->t('What should freelinking do when the page is not found?'),
      '#options' => [
        'error' => $this->t('Insert an error message'),
      ],
    ];

    if ($this->moduleHandler->moduleExists('search')) {
      $element['failover']['#options']['search'] = $this->t('Add link to search content');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $failover = $this->getConfiguration()['settings']['failover'];

    // All aliases must use a preceding slash.
    $alias = strpos('/', $target['dest']) === 0 ? $target['dest'] : '/' . $target['dest'];
    $path = $this->aliasManager->getPathByAlias($alias, $target['language']);

    // A path  was found.
    if ($path !== $alias) {
      $link = [
        '#type' => 'link',
        '#title' => $target['text'],
        '#url' => Url::fromUserInput($path, ['language' => $target['language']]),
        '#attributes' => [
          'title' => $this->getTip(),
        ],
      ];
    }
    elseif ($failover === 'search' && $this->moduleHandler->moduleExists('search')) {
      $link = [
        '#type' => 'link',
        '#title' => $target['text'],
        '#url' => Url::fromUserInput(
          '/search',
          [
            'query' => ['keys' => $path],
            'language' => $target['language'],
          ]
        ),
        '#attributes' => [
          'title' => $this->getTip(),
        ],
      ];
    }
    else {
      $link = [
        '#theme' => 'freelink_error',
        '#plugin' => 'path_alias',
        '#message' => $this->t('path “%path” not found', ['%path' => $path]),
      ];
    }
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('path.alias_manager')
    );
  }

}
