<?php

namespace Drupal\field_encrypt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders encrypted fields overview.
 */
class FieldOverviewController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Creates a new FieldOverviewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * Renders overview page of encrypted fields.
   */
  public function overview() {
    $encrypted_fields = $this->getEncryptedFields();
    $build['table'] = array(
      '#type' => 'table',
      '#header' => [
        'field_name' => $this->t('Field'),
        'entity_type' => $this->t('Entity type'),
        'properties' => $this->t('Properties'),
        'encryption_profile' => $this->t('Encryption profile'),
        'count' => $this->t('# encrypted field values'),
        'operations' => $this->t('Operations'),
      ],
      '#title' => 'Overview of encrypted fields',
      '#rows' => array(),
      '#empty' => $this->t('There are no encrypted fields.'),
    );

    foreach ($encrypted_fields as $encrypted_field) {
      $properties = $encrypted_field->getThirdPartySetting('field_encrypt', 'properties', []);
      $entity_type = $encrypted_field->getTargetEntityTypeId();
      $field_name = $encrypted_field->getName();

      $row = [
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'properties' => [
          'data' => [
            '#theme' => 'item_list',
            '#items' => array_filter($properties),
          ],
        ],
        'encryption_profile' => $encrypted_field->getThirdPartySetting('field_encrypt', 'encryption_profile', ''),
        'count' => $this->getEncryptedFieldValueCount($entity_type, $field_name),
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'decrypt' => [
                'title' => $this->t('Decrypt'),
                'url' => Url::fromRoute('field_encrypt.field_decrypt_confirm', ['entity_type' => $entity_type, 'field_name' => $field_name]),
              ],
            ],
          ],
        ],
      ];
      $build['table']['#rows'][$encrypted_field->id()] = $row;
    }
    return $build;
  }

  /**
   * Get a list of encrypted fields' storage entities.
   *
   * @return \Drupal\field\FieldStorageConfigInterface[]
   *   An array of FieldStorageConfig entities for encrypted fields.
   */
  protected function getEncryptedFields() {
    $encrypted_fields = [];
    $storage = $this->entityTypeManager->getStorage('field_storage_config');
    $fields = $storage->loadMultiple();
    foreach ($fields as $field) {
      if ($field->getThirdPartySetting('field_encrypt', 'encrypt', FALSE) == TRUE) {
        $encrypted_fields[] = $field;
      }
    }
    return $encrypted_fields;
  }

  /**
   * Get the number of encrypted field values for a given field on entity type.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $field_name
   *   The field name to check.
   *
   * @return int
   *   The number of encrypted field values.
   */
  protected function getEncryptedFieldValueCount($entity_type, $field_name) {
    $query = $this->entityQuery->get('encrypted_field_value')
      ->condition('entity_type', $entity_type)
      ->condition('field_name', $field_name);
    $values = $query->execute();
    return count($values);
  }

}
