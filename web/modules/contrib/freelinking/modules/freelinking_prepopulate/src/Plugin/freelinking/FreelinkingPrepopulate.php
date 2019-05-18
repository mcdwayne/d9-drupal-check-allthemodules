<?php

namespace Drupal\freelinking_prepopulate\Plugin\freelinking;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wikimedia\Composer\Merge\NestedArray;

/**
 * Freelinking prepopulate plugin.
 *
 * @Freelinking(
 *   id = "freelinking_prepopulate",
 *   title = @Translation("Prepopulate"),
 *   settings = {
 *     "default_node_type" = "page",
 *     "advanced" = {
 *       "title" = "0",
 *     },
 *    "failover" = "search",
 *   }
 * )
 *
 * Example usage:
 *
 * This freelinking code
 * @code
 * This freelinking code [[create:pagetitle]]
 * would produce <a href="node/add/page?edit[title][0][value]=pagetitle>pagetitle</a>
 * @endcode
 */
class FreelinkingPrepopulate extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
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
   *   The plugin configuration to set.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    return NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $default = reset($node_types);

    return [
      'settings' => [
        'default_node_type' => $default->id(),
        'advanced' => ['title' => FALSE],
        'failover' => 'search',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/^create(node)?$/';
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Links to a prepopulated node/add form.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $config = $this->getConfiguration();

    $element['failover'] = [
      '#type' => 'select',
      '#title' => $this->t('If path alias is not found'),
      '#description' => $this->t('What should freelinking do when the page is not found?'),
      '#options' => [
        'error' => $this->t('Insert an error message'),
      ],
      '#default_value' => $config['settings']['failover'],
    ];

    if ($this->moduleHandler->moduleExists('search')) {
      $element['failover']['#options']['search'] = $this->t('Add link to search content');
    }

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    $options = array_reduce($node_types, function (&$result, $node_type) {
      /** @var \Drupal\node\Entity\NodeType $node_type */
      $result[$node_type->id()] = $node_type->label();
      return $result;
    }, []);

    $element['default_node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#description' => $this->t('Choose the default node type to use for Node Create links.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $config['settings']['default_node_type'],
    ];

    // @todo Add advanced field options.

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $config = $this->getConfiguration();
    $title = isset($target['text']) ? $target['text'] : $target['dest'];

    if (isset($target['type']) || isset($target['bundle'])) {
      $bundle_name = isset($target['type']) ? $target['type'] : $target['bundle'];
    }
    else {
      $bundle_name = $config['settings']['default_node_type'];
    }

    $route_params = ['node_type' => $bundle_name];
    $options = [
      'query' => [
        // Don't allow any HTML tags for the title field.
        'edit[title][widget][0][value]' => Xss::filter($title, []),
      ],
    ];

    // Get optional query parameters for prepopulate fields.
    // @todo https://www.drupal.org/project/prepopulate/issues/2849432
    $fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle_name);
    $blacklist = ['type', 'bundle', 'text', 'dest'];
    foreach ($fields as $field_name => $field_definition) {
      if (!in_array($field_name, $blacklist) &&
          array_key_exists($field_name, $target) &&
          !$field_definition->isInternal() &&
          !$field_definition->isComputed() &&
          !$field_definition->isReadOnly()) {
        $storage_definition = $field_definition->getFieldStorageDefinition();
        if ($storage_definition) {
          $prop = $storage_definition->getMainPropertyName();
          if ($storage_definition->getType() === 'entity_reference') {
            $key = '[' . $prop . ']';
          }
          else {
            $key = '[0][' . $prop . ']';
          }

          // This won't work for complex field widgets in contributed modules
          // such as select_other lists.
          $query_name = 'edit[' . $field_name . '][widget]' . $key;
          // Use the standard XSS filter for values.
          $options['query'][$query_name] = Xss::filter($target[$field_name]);
        }
      }
    }

    // @todo Implement freelinking_prepopulate_fields_from_page()?
    // @todo Implement freelinking_prepopulate_fields_from_array()?

    // Allow a module to alter query string.
    $this->moduleHandler->alter('freelinking_prepopulate_query', $options['query'], $target);

    $url = Url::fromRoute('node.add', $route_params, $options);

    if ($url->access()) {
      $link = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => $url,
        '#attributes' => [
          'title' => $this->getTip(),
        ],
      ];
    }
    elseif ($config['settings']['failover'] === 'search') {
      $link = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromUserInput(
          '/search',
          [
            'query' => ['keys' => $title],
            'language' => $target['language'],
          ]
        ),
      ];
    }
    else {
      $link = [
        '#theme' => 'freelink_error',
        '#plugin' => 'freelinking_prepopulate',
        '#message' => $this->t('Access denied to create missing content.'),
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
      $container->get('entity_field.manager'),
      $container->get('module_handler')
    );
  }

}
