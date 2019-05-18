<?php

namespace Drupal\core_extend\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("core_extend_entity_roles")
 */
class Roles extends PrerenderList {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeDefinition;

  /**
   * A local cache of roles.
   *
   * @var \Drupal\core_extend\Entity\RoleEntityInterface[]
   */
  protected $roles = NULL;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'), $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityTypeDefinition = $this->entityTypeManager->getDefinition($this->getEntityType());

    $base_id = $this->entityTypeDefinition->getKey('id');
    $data_table = $this->entityTypeDefinition->getDataTable();

    $this->additional_fields[$base_id] = ['table' => $data_table, 'field' => $base_id];
  }

  /**
   * Loads the roles.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The role entities.
   */
  public function loadRoles() {
    return $this->entityTypeManager->getStorage($this->configuration['roles_entity_type'])->loadMultiple();
  }

  /**
   * Gets the roles.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The role entities.
   */
  public function getRoles() {
    if (is_null($this->roles)) {
      $this->roles = $this->loadRoles();
    }

    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
    $this->field_alias = $this->aliases['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $ids = [];
    $this->items = [];

    foreach ($values as $result) {
      $ids[] = $this->getValue($result);
    }

    if (!$ids) {
      return [];
    }

    $roles = $this->getRoles();

    $query = $this->database->select($this->options['table'], 'er');
    $query->addField('er', 'entity_id', 'eid');
    $query->addField('er', 'roles_target_id', 'rid');
    $query->condition('er.entity_id', $ids, 'IN');
    $query->condition('er.roles_target_id', array_keys($roles), 'IN');

    if ($result = $query->execute()) {
      foreach ($result as $role) {
        $this->items[$role->eid][$role->rid]['role'] = $roles[$role->rid]->label();
        $this->items[$role->eid][$role->rid]['rid'] = $role->rid;
      }
      // Sort the roles for each user by role weight.
      $ordered_roles = array_flip(array_keys($roles));
      foreach ($this->items as &$entity_roles) {
        // Create an array of rids that the entity has in the role weight order.
        $sorted_keys = array_intersect_key($ordered_roles, $entity_roles);
        // Merge with the unsorted array of role information which has the
        // effect of sorting it.
        $entity_roles = array_merge($sorted_keys, $entity_roles);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    return $item['role'];
  }

  /**
   * {@inheritdoc}
   */
  protected function documentSelfTokens(&$tokens) {
    $tokens['{{ ' . $this->options['id'] . '__role' . ' }}'] = $this->t('The name of the role.');
    $tokens['{{ ' . $this->options['id'] . '__rid' . ' }}'] = $this->t('The role machine-name of the role.');
  }

  /**
   * {@inheritdoc}
   */
  protected function addSelfTokens(&$tokens, $item) {
    if (!empty($item['role'])) {
      $tokens['{{ ' . $this->options['id'] . '__role }}'] = $item['role'];
      $tokens['{{ ' . $this->options['id'] . '__rid }}'] = $item['rid'];
    }
  }

}
