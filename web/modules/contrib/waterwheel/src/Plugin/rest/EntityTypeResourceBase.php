<?php

namespace Drupal\waterwheel\Plugin\rest;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class for resources returning entity type information.
 */
abstract class EntityTypeResourceBase extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The rest resource manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourceManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_manager
   *   The rest resource manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ResourcePluginManager $resource_manager, EntityFieldManagerInterface $field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceManager = $resource_manager;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('waterwheel'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.rest'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Get the meta type of the entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return string The type of entity either content, config, or other.
   *   The type of entity either content, config, or other.
   */
  protected function getMetaEntityType(EntityTypeInterface $entity_type) {
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $meta_type = 'content';
      return $meta_type;
    }
    elseif ($entity_type instanceof ConfigEntityTypeInterface) {
      $meta_type = 'config';
      return $meta_type;
    }
    else {
      $meta_type = 'other';
      return $meta_type;
    }
  }

  /**
   * Gets the REST methods and their paths for the entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The REST methods.
   *
   *   The keys are the REST methods and the values are the paths.
   */
  protected function getEntityMethods($entity_type_id) {
    $resource_methods = [];
    $entity_resource_key = "entity:$entity_type_id";
    /** @var \Drupal\rest\Entity\RestResourceConfig $rest_resource */
    if ($rest_resource = $this->entityTypeManager->getStorage('rest_resource_config')->load("entity.$entity_type_id")) {
      $enabled_methods = $rest_resource->getMethods();
      /** @var \Drupal\rest\Plugin\rest\resource\EntityResource $entity_resource */
      $entity_resource = $this->resourceManager->createInstance($entity_resource_key);

      $routes = $entity_resource->routes();
      foreach ($enabled_methods as $method) {
        /** @var \Symfony\Component\Routing\Route $route */
        foreach ($routes as $route) {
          if (in_array($method, $route->getMethods())) {
            $resource_methods[$method] = $route->getPath();
            break;
          }
        }
      }
    }
    return $resource_methods;
  }

  /**
   * Gets information on all the fields on the bundle.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return array
   *   The information about the bundle's fields.
   */
  protected function getBundleFields($entity_type_id, $bundle_name) {
    $fields = [];
    $field_definitions = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle_name);
    foreach ($field_definitions as $field_name => $field_definition) {
      $field_type = $field_definition->getType();

      $field_info = [
        'label' => $field_definition->getLabel(),
        'type' => $field_type,
        'data_type' => $field_definition->getDataType(),
        'required' => $field_definition->isRequired(),
        'readonly' => $field_definition->isReadOnly(),
        'cardinality' => $field_definition->getFieldStorageDefinition()->getCardinality(),
        'settings' => $field_definition->getSettings(),
      ];
      if ($this->isReferenceField($field_definition)) {
        $field_info['is_reference']  = TRUE;
        // @todo Pull reference entity type and bundles out of settings for easier access?
      }
      else {
        $field_info['is_reference']  = FALSE;
      }
      $fields[$field_name] = $field_info;
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    return [
      'waterwheel GET site configuration' => [
        'title' => $this->t('Access site configuration through Waterwheel endpoints'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    // \Drupal\rest\Plugin\ResourceBase::getBaseRoute does NOT use
    // \Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase::permissions().
    // to create permissions for the route.
    // @todo @see https://www.drupal.org/node/2664780 use new
    //   \Drupal\rest\Plugin\ResourceBase::getBaseRouteRequirements().
    $permissions = array_keys($this->permissions());
    $permission = array_shift($permissions);
    $route->setRequirement('_permission', $permission);
    return $route;
  }

  /**
   * Determines if a field is a reference type field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   True if the field is a reference field.
   */
  protected function isReferenceField(FieldDefinitionInterface $field_definition) {
    // @todo Is there an easier to check if field is reference
    // @todo Dependency injection
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_manager */
    $field_manager = \Drupal::getContainer()->get('plugin.manager.field.field_type');
    $plugin_definition = $field_manager->getDefinition($field_definition->getType());
    $class = $plugin_definition['class'];
    $reference_class = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';
    if (is_subclass_of($class, $reference_class) || $class == $reference_class) {
      return TRUE;
    }
    return FALSE;
  }

}
