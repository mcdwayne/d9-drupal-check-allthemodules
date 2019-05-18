<?php

namespace Drupal\entity_grants\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_generic\Entity\Simple;

/**
 * @ContentEntityType(
 *   id = "entity_grant",
 *   label = @Translation("Grant"),
 *   label_singular = @Translation("grant"),
 *   label_plural = @Translation("grants"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grant",
 *     plural = "@count grants"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity\Routing\DefaultHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "entity_grants",
 *   admin_permission = "administer entity_grant",
 *   permission_granularity = "entity_type",
 *   translatable = FALSE,
 *   common_reference_target = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/grant/{entity_grant}",
 *     "add-form" = "/grant/add",
 *     "edit-form" = "/grant/{entity_grant}/edit",
 *     "delete-form" = "/grant/{entity_grant}/delete",
 *     "collection" = "/admin/people/grants"
 *   }
 * )
 */
class Grant extends Simple implements GrantInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Language.
    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(new TranslatableMarkup('Language'))
      ->setDescription(new TranslatableMarkup('The {language}.langcode of this entity'))
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'language_select',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode_fallback'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Language fallback'))
      ->setDescription(new TranslatableMarkup('Boolean indicating whether this record should be used as a fallback if a language condition is not provided.'))
      ->setDefaultValue(TRUE)
      ->setSettings([
        'on_label' => t('Yes'),
        'off_label' => t('No'),
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['realm'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Realm'))
      ->setDescription(new TranslatableMarkup('The realm in which the user must possess the specific grant. Modules can define one or more realms by implementing hook_entity_grants().'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity type'))
      ->setDescription(new TranslatableMarkup('The entity type.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity ID'))
      ->setDescription(new TranslatableMarkup('The entity ID.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('User'))
      ->setDescription(new TranslatableMarkup('The user who will be granted.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\entity_grants\Entity\Grant::getCurrentUserId')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'author',
        'weight' => 5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['grant'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Grant'))
      ->setDescription(new TranslatableMarkup('The grant.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
      ))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    // This override exists to set the operation to the default value "view".
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int[]
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
