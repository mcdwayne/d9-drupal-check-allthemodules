<?php
/**
 * @file
 * Contains \Drupal\entity_expiration\Entity\EntityExpirationPolicy.
 */

namespace Drupal\entity_expiration\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\entity_expiration\Controller\EntityExpirationController;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the EntityExpirationPolicy entity.
 *
 * @ingroup entity_expiration
 *
 *
 * @ContentEntityType(
 * id = "entity_expiration_policy",
 * label = @Translation("Entity Expiration Policy"),
 * handlers = {
 *   "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   "list_builder" = "Drupal\entity_expiration\Entity\Controller\EntityExpirationPolicyListBuilder",
 *   "views_data" = "Drupal\entity_expiration\Entity\Views\EntityExpirationPolicyViewsData",
 *   "form" = {
 *     "add" = "Drupal\entity_expiration\Form\EntityExpirationPolicyForm",
 *     "edit" = "Drupal\entity_expiration\Form\EntityExpirationPolicyForm",
 *     "delete" = "Drupal\entity_expiration\Form\EntityExpirationPolicyDeleteForm",
 *   },
 *   "access" = "Drupal\entity_expiration\EntityExpirationPolicyAccessControlHandler",
 * },
 * base_table = "entity_expiration_policy",
 * admin_permission = "administer entity_expiration",
 * fieldable = FALSE,
 * entity_keys = {
 *   "id" = "policy_id",
 *   "uuid" = "uuid",
 * },
 * links = {
 *   "canonical" = "/entity_expiration_policy/{entity_expiration_policy}",
 *   "edit-form" = "/entity_expiration_policy/{entity_expiration_policy}/edit",
 *   "delete-form" = "/entity_expiration_policy/{entity_expiration_policy}/delete",
 *   "collection" = "/entity_expiration_policy/list"
 * },
 * )
 */
class EntityExpirationPolicy extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['policy_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the EntityExpirationPolicy entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the EntityExpirationPolicy entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['active'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Active'))
      ->setDescription(t('Active items are evaluated during each cron run.'))
      ->setSettings(array(
        'allowed_values' => array(
          '0' => 'Inactive',
          '1' => 'Active',
        ),
      ))
      ->setDefaultValue('1')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['select_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Selection Method'))
      ->setDescription(t('How will we select entities?.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The entity type affected by the policy'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['expire_age'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Age for expired items'))
      ->setDescription(t('The age of items to expire, in seconds. Remember that 1 day  = 86400 seconds, and 30 days = 2592000 seconds .'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['expire_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Method for expiring items.'))
      ->setDescription(t('The method to use when expiring items.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['expire_max'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Maximum number of items to expire per cron run.'))
      ->setDescription(t('The maximum number of items to expire in a cron run. Note that oldest items (in order of database ID) are expired first.'))
      ->setSettings(array(
        'default_value' => 'default',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    return $fields;
  }

}