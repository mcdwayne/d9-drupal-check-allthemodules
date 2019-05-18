<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Drupal\freelinking\Plugin\FreelinkingPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Node Title freelinking plugin.
 *
 * @Freelinking(
 *   id = "nodetitle",
 *   title = @Translation("Node title"),
 *   weight = -10,
 *   hidden = false,
 *   settings = {
 *     "nodetypes" = {},
 *     "failover" = "",
 *   }
 * )
 */
class NodeTitle extends FreelinkingPluginBase implements FreelinkingPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   Plugin configugration.
   * @param string $plugin_id
   *   Plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager for getting entity type.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/nt$|nodetitle|title/A';
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
      'settings' => [
        'nodetypes' => [],
        'failover' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $node_type_options = [];

    // Get the node type entities from storage from Entity Type Manager.
    // \Drupal\Core\Entity\EntityTypeBundleInfo::getAllBundleInfo() offers an
    // alter, but increased load times when not cached. It is debatable which
    // should be used in the long term.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $entity) {
      $node_type_options[$entity->id()] = $entity->label();
    }

    $element['nodetypes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Only link to nodes belonging to the following content types:'),
      '#description' => $this->t('Lookup by title to will be restricted to this content type or these content types.'),
      '#options' => $node_type_options,
      '#default_value' => isset($this->configuration['settings']['nodetypes']) ? $this->configuration['settings']['nodetypes'] : [],
    ];

    $failover_options = [
      '_none' => $this->t('Do Nothing'),
      'showtext' => $this->t('Show text (remove markup)'),
    ];

    if ($this->moduleHandler->moduleExists('freelinking_prepopulate')) {
      $failover_options['freelinking_prepopulate'] = $this->t('Add a link to create content when user has access');
    }

    if ($this->moduleHandler->moduleExists('search')) {
      $failover_options['search'] = $this->t('Add link to search content');
    }

    $failover_options['error'] = $this->t('Insert an error message');

    $element['failover'] = [
      '#type' => 'select',
      '#title' => $this->t('If suitable content is not found'),
      '#description' => $this->t('What should freelinking do when the page is not found?'),
      '#options' => $failover_options,
      '#default_value' => isset($this->configuration['settings']['failover']) ? $this->configuration['settings']['failover'] : '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $link = '';

    $failover_option = $this->getConfiguration()['settings']['failover'];

    $result = $this->getQuery($target);
    if ($result && !empty($result)) {
      $nid = array_shift($result);
      $link = [
        '#type' => 'link',
        '#title' => $target['dest'],
        '#url' => Url::fromRoute('entity.node.canonical', ['node' => $nid], ['language' => $target['language']]),
        '#attributes' => [
          'title' => $this->getTip(),
        ],
      ];
    }
    elseif ($failover_option !== 'error' && $failover_option !== '_none') {
      return ['error' => $failover_option];
    }
    elseif ($failover_option === 'error') {
      $link = [
        '#theme' => 'freelink_error',
        '#plugin' => 'nodetitle',
        '#message' => $this->t('Node title %target does not exist', ['%target' => $target['dest']]),
      ];
    }
    else {
      $link = [
        '#markup' => '[[nodetitle:' . $target['target'] . ']]',
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
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Get the allowed node types from configuration.
   *
   * @return array
   *   An indexed array of node types.
   */
  protected function getAllowedNodeTypes() {
    $node_types = $this->configuration['settings']['nodetypes'];
    return array_reduce($node_types, function (&$result, $item) {
      if ($item) {
        $result[] = $item;
      }
      return $result;
    }, []);
  }

  /**
   * Get the node query builder.
   *
   * @param array $target
   *   The target array to construct the query.
   *
   * @return bool|array
   *   An array of results or FALSE if an error occurred.
   */
  protected function getQuery(array $target) {
    try {
      $query = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery('AND');
      $node_types = $this->getAllowedNodeTypes();
      if (!empty($node_types)) {
        $query->condition('type', $node_types, 'IN');
      }
      return $query
        ->condition('title', $target['dest'])
        ->condition('status', 1)
        ->condition('langcode', $target['language']->getId())
        ->accessCheck()
        ->execute();
    }
    catch (InvalidPluginDefinitionException $e) {
      return FALSE;
    }
    catch (PluginNotFoundException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFailoverPluginId() {
    $non_plugins = ['_none', '', 'error', 'showtext'];

    if (!in_array($this->configuration['settings']['failover'], $non_plugins)) {
      return $this->configuration['settings']['failover'];
    }

    return FALSE;
  }

}
