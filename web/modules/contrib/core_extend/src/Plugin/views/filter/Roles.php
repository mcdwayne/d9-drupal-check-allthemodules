<?php

namespace Drupal\core_extend\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\RoleInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for entity roles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("core_extend_entity_roles")
 */
class Roles extends ManyToOne {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a Roles object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->roleStorage = $this->entityTypeManager->getStorage($this->definition['roles_entity_type']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
        $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = array_map(function ($item) {
      return $item->label();
    }, $this->roleStorage->loadMultiple());

    unset($this->valueOptions[RoleInterface::AUTHENTICATED_ID]);
    return $this->valueOptions;

  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    foreach ($this->value as $role_id) {
      $role = $this->roleStorage->load($role_id);
      $dependencies[$role->getConfigDependencyKey()][] = $role->getConfigDependencyName();
    }
    return $dependencies;
  }

}
