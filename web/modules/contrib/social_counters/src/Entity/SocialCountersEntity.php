<?php
/**
 * @file
 * Contains \Drupal\social_counters\Entity\SocialCountersEntity.
 *
 * @deprecated This entity is deprecated and isn't used anymore. We can't uninstall it yet
 *   and remove code because of this issue https://www.drupal.org/node/2655162.
 */

namespace Drupal\social_counters\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\social_counters\SocialCountersEntityInterface;
use Drupal\user\UserInterface;

/**
 * Defines the SocialCountersEntity.
 *
 * @ContentEntityType(
 *   id = "social_counters_entity",
 *   label = @Translation("Social Counter entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\social_counters\Entity\Controller\SocialCountersListBuilder",
 *     "views_data" = "Drupal\social_counters\SocialCountersViewsData",
 *     "form" = {
 *       "add" = "Drupal\social_counters\Form\SocialCountersForm",
 *       "edit" = "Drupal\social_counters\Form\SocialCountersForm",
 *       "delete" = "Drupal\social_counters\Form\SocialCountersDeleteForm",
 *     },
 *     "access" = "Drupal\social_counters\SocialCountersEntityAccessControlHandler",
 *   },
 *   base_table = "social_counters_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/social_counters/{social_counters_entity}/edit",
 *     "delete-form" = "/admin/config/services/social_counters/{social_counters_entity}/delete",
 *     "collection" = "/admin/config/services/social_counters/list"
 *   },
 * )
 */
class SocialCountersEntity extends ContentEntityBase implements SocialCountersEntityInterface {
  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Social Counters entity.'))
      ->setReadOnly(TRUE);

    // Name of the Social Counter entity.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setDescription(t('The name of the Social Counters entity.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['plugin_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin id'))
      ->setDescription(t('The plugin id.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Config'))
      ->setDescription(t('Config.'));

    // Name of the Social Counter entity.
    $fields['counter'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Counter'))
      ->setDescription(t('Number of counters for specific social network.'));

    return $fields;
  }
}
