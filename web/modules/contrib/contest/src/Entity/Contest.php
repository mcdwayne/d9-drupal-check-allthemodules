<?php

namespace Drupal\contest\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\contest\ContestInterface;
use Drupal\contest\ContestHelper;
use Drupal\contest\ContestStorage;

/**
 * Defines the contest entity class.
 *
 * @ContentEntityType(
 *   id = "contest",
 *   label = @Translation("Contest"),
 *   handlers = {
 *     "access" = "\Drupal\contest\ContestAccessControlHandler",
 *     "storage" = "Drupal\contest\ContestStorage",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "list_builder" = "Drupal\contest\ContestListBuilder",
 *     "view_builder" = "Drupal\contest\ContestViewBuilder",
 *     "views_data" = "Drupal\contest\ContestViewData",
 *     "form" = {
 *       "default" = "Drupal\contest\Form\ContestForm",
 *       "edit" = "Drupal\contest\Form\ContestForm",
 *       "delete" = "Drupal\contest\Form\ContestDeleteForm",
 *     }
 *   },
 *   links = {
 *     "canonical" = "/contest/{contest}",
 *     "edit-form" = "/contest/{contest}/edit",
 *     "delete-form" = "/contest/{contest}/delete",
 *     "admin-form" = "/contest/{contest}/admin"
 *   },
 *   base_table = "contest",
 *   data_table = "contest_field_data",
 *   admin_permission = "administer contests",
 *   field_ui_base_route = "contest.contest_list",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   }
 * )
 */
class Contest extends ContentEntityBase implements ContestInterface {

  /**
   * Declare an entity's fields.
   *
   * @param Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   An array describing the entity's fields.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $default_format = ContestHelper::getDefaultFormat();

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Contest ID'))
      ->setDescription(t('The ID of the contest.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The contest UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The contest language code.'));

    $options = [
      'type'     => 'entity_reference_autocomplete',
      'weight'   => -10,
      'settings' => [
        'match_operator'    => 'CONTAINS',
        'size'              => '60',
        'autocomplete_type' => 'tags',
        'placeholder'       => '',
      ],
    ];
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The contest author.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
      ->setDefaultValueCallback('Drupal\contest\Entity\Contest::getCurrentUserId')
      ->setDisplayOptions('form', $options)
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The contest title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -100]);

    $view_opts = [
      'label'  => 'hidden',
      'type'   => $default_format,
      'weight' => -100,
    ];
    $form_opts = [
      'type'     => 'text_textarea_with_summary',
      'weight'   => -100,
      'settings' => ['rows' => 4],
    ];
    $fields['body'] = BaseFieldDefinition::create('text_with_summary')
      ->setLabel(t('Body'))
      ->setDescription(t('The contest body.'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', $view_opts)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', $form_opts)
      ->setDisplayConfigurable('form', TRUE);

    $fields['sponsor_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sponsor ID'))
      ->setDescription(t('The user ID of the contest sponsor.'))
      ->setRequired(FALSE)
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['type' => 'string', 'weight' => -90]);

    $fields['sponsor_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sponsor URL'))
      ->setDescription(t("The sponsor's URL."))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSetting('max_length', ContestStorage::STRING_MAX)
      ->setDisplayOptions('form', ['type' => 'string', 'weight' => -80]);

    $fields['start'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Start'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', ['type' => 'integer', 'weight' => -75]);

    $fields['end'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('End'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', ['type' => 'integer', 'weight' => -72]);

    $fields['period'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Frequency'))
      ->setDescription(t('How often can a user enter the contest.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', ContestStorage::getPeriodOptions())
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -71]);

    $fields['places'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Places'))
      ->setDescription(t('The number of winners.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_combine(range(1, 10), range(1, 10)))
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -70]);

    $fields['publish_winners'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Publish Winners'))
      ->setDescription(t('Publish the contest winners on the site.'))
      ->setSetting('allowed_values', [0 => t('No'), 1 => t('Yes')])
      ->setDefaultValue(0)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -60]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('When the contest was created, as a Unix timestamp.'));

    return $fields;
  }

  /**
   * Get the current user's ID.
   *
   * @return array
   *   A one element array with the current user's uid.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Add fields to the entity after load.
   *
   * @param Drupal\Core\Entity\EntityStorageInterface $storage
   *   An instance of the entity's storage class.
   * @param array $entities
   *   An array of contests.
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    foreach ($entities as $entity) {
      $entity->entries = $storage->getEntries($entity->id());
    }
  }

  /**
   * Delete fields connected to the contest.
   *
   * @param Drupal\Core\Entity\EntityStorageInterface $storage
   *   An instance of the entity's storage class.
   * @param array $entities
   *   An array of contests.
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $entity) {
      $storage->deleteEntries($entity);
    }
  }

  /**
   * Sorting function for Contest entities.
   *
   * @param object $a
   *   Constest.
   * @param object $b
   *   Contest.
   *
   * @return int
   *   An integer, (1, 0, -1) used to sort the contest entities.
   */
  public static function sort($a, $b) {
    return strcmp($a->label(), $b->label());
  }

}
