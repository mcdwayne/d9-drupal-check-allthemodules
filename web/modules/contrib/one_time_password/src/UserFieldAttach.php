<?php

namespace Drupal\one_time_password;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage attaching the new field to the user entity.
 */
class UserFieldAttach implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * Create an instance of UserFieldAttach.
   */
  public function __construct(EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager) {
    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.definition_update_manager')
    );
  }

  /**
   * Get the base field definition for the one time password field.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   A base field definition for the one time password field.
   */
  protected function getBaseFieldDefinition() {
    return BaseFieldDefinition::create('one_time_password_provisioning_uri')
      ->setLabel($this->t('Two Factor Authentication'))
      ->setName('one_time_password')
      ->setProvider('one_time_password')
      ->setTargetEntityTypeId('user')
      ->setDescription($this->t('Setup a two factor authentication.'))
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ]);
  }

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  public function entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type) {
    if ($entity_type->id() !== 'user') {
      return;
    }
    $fields['one_time_password'] = $this->getBaseFieldDefinition();
  }

  /**
   * Install the field definition.
   */
  public function installFieldDefinition() {
    $this->entityDefinitionUpdateManager->installFieldStorageDefinition('one_time_password', 'user', 'user', $this->getBaseFieldDefinition());
  }

  /**
   * Implements hook_entity_field_access().
   */
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($field_definition->getName() === 'one_time_password') {
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

}
