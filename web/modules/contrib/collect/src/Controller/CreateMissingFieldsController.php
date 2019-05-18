<?php
/**
 * @file
 * Contains \Drupal\collect\Controller\CreateMissingFieldsController.
 */

namespace Drupal\collect\Controller;

use Drupal\collect\Plugin\collect\Model\CollectJson;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\collect\Model\ModelInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Route controller for creating missing fields and bundles in current system.
 */
class CreateMissingFieldsController extends ControllerBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The injected serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The typed data provider service.
   *
   * @var \Drupal\collect\TypedData\TypedDataProvider
   */
  protected $typedDataProvider;

  /**
   * Constructs a new CreateMissingFieldsController.
   */
  public function __construct(EntityManagerInterface $entity_manager, Serializer $serializer, TypedDataProvider $typed_data_provider) {
    $this->entityManager = $entity_manager;
    $this->serializer = $serializer;
    $this->typedDataProvider = $typed_data_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('serializer'),
      $container->get('collect.typed_data_provider')
    );
  }

  /**
   * Creates missing fields and bundles if they do not exist.
   */
  public function createMissingFields(ModelInterface $collect_model) {
    $missing_fields = $this->getMissingFields($collect_model);
    $matches = CollectJson::matchSchemaUri($collect_model->getUriPattern());
    $entity_type = $matches['entity_type'];
    $bundle = $matches['bundle'] ?: $matches['entity_type'];
    $bundle_info = $this->entityManager->getBundleInfo($entity_type);

    // If bundle doesn't exist in current system, we create it.
    if (!array_key_exists($bundle, $bundle_info)) {
      $bundle_entity_type = $this->entityManager->getDefinition($entity_type)->getBundleEntityType();
      $typed_data = FieldDefinition::getContainerTypedData($collect_model);
      $bundle_data = $typed_data->getParsedData()['bundle'];
      unset($bundle_data['uuid']);
      unset($bundle_data['bundle_key']);

      $this->entityManager->getStorage($bundle_entity_type)->create($bundle_data)->save();
      \Drupal::logger('collect')->info('Missing bundle %bundle is created.', ['%bundle' => $bundle_data['name']]);
      drupal_set_message($this->t('Missing bundle %bundle is created.', ['%bundle' => $bundle_data['name']]));
    }

    foreach ($missing_fields as $field_name => $field_definition) {
      // Normalize the field definition and use it to create a new field storage
      // and field.
      // @todo Support the case of existing storage but new field.
      $normalized_field_definition = $this->serializer->normalize($field_definition);
      $this->entityManager->getStorage('field_storage_config')
        ->create($normalized_field_definition['storage'])
        ->save();
      $this->entityManager->getStorage('field_config')
        ->create($normalized_field_definition)
        ->save();
    }

    drupal_set_message($this->formatPlural(
      count($missing_fields),
      $this->t('Missing field %missing_field is successfully created.', ['%missing_field' => implode(', ', array_keys($missing_fields))]),
      $this->t('Missing fields %missing_fields are successfully created.', ['%missing_fields' => implode(', ', array_keys($missing_fields))])
    ));

    $url = $collect_model->urlInfo('edit-form');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

  /**
   * Returns current Field Definition data.
   */
  public function getCurrentFieldDefinitions(ModelInterface $collect_model) {
    $matches = CollectJson::matchSchemaUri($collect_model->getUriPattern());
    return $this->entityManager->getFieldStorageDefinitions($matches['entity_type']);
  }

  /**
   * Checks whether user has a permission to create missing fields and bundles.
   */
  public function checkAccess(ModelInterface $collect_model) {
    return AccessResult::allowedIf((bool) $this->getMissingFields($collect_model));
  }

  /**
   * Returns fields that do not exist in the current system.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   Field definitions present on the definition container but not on the
   *   system.
   */
  public function getMissingFields(ModelInterface $collect_model) {
    if ($typed_data = FieldDefinition::getContainerTypedData($collect_model)) {
      $stored_field_definition = $typed_data->get('fields')->getValue();
      $stored_field_definition = FieldDefinition::removeEntityReferenceFields($stored_field_definition);
      $current_field_definition = $this->getCurrentFieldDefinitions($collect_model);

      return array_diff_key($stored_field_definition, $current_field_definition);
    }
  }

}
