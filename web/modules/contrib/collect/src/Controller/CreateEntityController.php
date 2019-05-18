<?php
/**
 * @file
 * Contains \Drupal\collect\Controller\CreateEntityController.
 */

namespace Drupal\collect\Controller;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Plugin\collect\Model\CollectJson;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Create Entity Controller.
 */
class CreateEntityController extends ControllerBase {

  /**
   * The injected model manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * Constructs a new CreateEntityController.
   *
   * @param \Drupal\collect\Model\ModelManagerInterface $model_manager
   *   Injected model manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Injected entity manager.
   */
  public function __construct(ModelManagerInterface $model_manager, EntityManagerInterface $entity_manager) {
    $this->modelManager = $model_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.collect.model'),
      $container->get('entity.manager')
    );
  }

  /**
   * Creates an entity.
   */
  public function createEntity(CollectContainerInterface $collect_container) {
    $model = $this->modelManager->loadModelByUri($collect_container->getSchemaUri());

    $bundle = NULL;
    if ($typed_data = FieldDefinition::getContainerTypedData($model)) {
      $entity_type = $typed_data->get('entity_type')->getValue();
      $bundle_data = $typed_data->get('bundle')->getValue();
      if ($bundle_data) {
        $bundle = $bundle_data[$bundle_data['bundle_key']];
      }
    }
    else {
      throw new NotFoundHttpException('The field definition container was not found. In order to recreate an entity, field definition data is needed.');
    }
    $values = array();

    $entity_definition = $this->entityManager->getDefinition($entity_type);

    // We need to specify bundle if the entity has it.
    if ($bundle) {
      // In case the bundle does not exist in the system, entity reconstruction
      // is not possible.
      $bundle_entity = $this->entityManager->getStorage($entity_definition->getBundleEntityType())->load($bundle);
      if (!$bundle_entity) {
        drupal_set_message(t('The entity could not be recreated. The bundle %bundle is missing. Use <a href=:model>Create missing fields</a> button to create it before continuing with this operation.', [
          '%bundle' => $bundle,
          ':model' => $model->url(),
        ]), 'error');

        return new RedirectResponse($collect_container->urlInfo()->setAbsolute()->toString());
      }
      if ($bundle_key = $entity_definition->getKey('bundle')) {
        $values = array($bundle_key => $bundle);
      }
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = entity_create($entity_type, $values);

    $data = Json::decode($collect_container->getData());
    $entity_values = $data['values'];

    // Move the entity reference fields into top level.
    foreach ($entity_values['_links'] as $field_name => $field_value) {
      if ($field_name == 'self') {
        continue;
      }
      $entity_values[$field_name] = $field_value;
    }
    unset($entity_values['_links']);

    // Setting fields and values to the entity.
    // Skipping the fields that are unique.
    $unique_keys = array(
      $entity_definition->getKey('id'),
      $entity_definition->getKey('uuid'),
      $entity_definition->getKey('revision'),
    );
    $entity_values = array_diff_key($entity_values, array_flip($unique_keys));

    $model_plugin = $this->modelManager->createInstanceFromUri($collect_container->getSchemaUri());
    // CollectJSON property definitions are field definitions.
    $model_property_definitions = $model_plugin->getTypedData()->getPropertyDefinitions();
    $message_context = array('%entity_type' => $entity->getEntityType()->getLabel());

    foreach ($entity_values as $field_name => $value) {
      if ($entity->hasField($field_name) && isset($model_property_definitions[$field_name])) {
        // Compare field storage definitions between container (model) and
        // existing entity type.
        if (!isset($model_property_definitions[$field_name])) {
          drupal_set_message($this->t('Unknown data field %field_name in model.', array('%field_name' => $field_name)), 'warning');
          continue;
        }
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $model_property_data_definition */
        $model_property_data_definition = $model_property_definitions[$field_name]->getDataDefinition();
        $container_field_storage_definition = $model_property_data_definition->getFieldStorageDefinition();
        $entity_field_storage_definition = $entity->getFieldDefinition($field_name)->getFieldStorageDefinition();
        if ($container_field_storage_definition->getType() == $entity_field_storage_definition->getType()) {
          $entity->set($field_name, $value);
          if ($container_field_storage_definition->getCardinality() > $entity_field_storage_definition->getCardinality()) {
            drupal_set_message($this->t('Field %field_name has a different cardinality.', array('%field_name' => $field_name)), 'warning');
          }
        }
        else {
          drupal_set_message($this->t('Field %field_name skipped due to type mismatch.', array('%field_name' => $field_name)), 'warning');
        }
      }
      else {
        drupal_set_message($this->t('Field %field_name does not exist.', array('%field_name' => $field_name)), 'warning');
      }
    }

    // Validate the entity before saving.
    $violations = $entity->validate();
    if ($violations->count()) {
      foreach ($violations as $violation) {
        drupal_set_message($this->t('Invalid value for %field: @message', [
          '%field' => $violation->getPropertyPath(),
          '@message' => $violation->getMessage()
        ]), 'error');
      }
      drupal_set_message($this->t('The entity could not be recreated.'), 'error');
      return new RedirectResponse($collect_container->urlInfo()->setAbsolute()->toString());
    }
    $entity->save();

    if ($entity->url()) {
      drupal_set_message($this->t('The %entity_type has been created. You can access it <a href="@view_url">here</a>.', $message_context + array(
        '@view_url' => $entity->url(),
      )));
    }
    else {
      drupal_set_message($this->t('The %entity_type has been created.', $message_context));
    }

    return $this->redirect('entity.collect_container.collection');
  }

  /**
   * Checks whether user has permission to create an entity.
   */
  public function checkAccess(CollectContainerInterface $collect_container) {
    $access = AccessResult::allowedIfHasPermission(\Drupal::currentUser(), 'administer collect');
    $model_plugin = $this->modelManager->createInstanceFromUri($collect_container->getSchemaUri());
    $is_entity = $model_plugin instanceof CollectJson ? TRUE : FALSE;
    return $access->andIf($is_entity ? AccessResult::allowed() : AccessResult::forbidden());
  }
}
