<?php

namespace Drupal\ingredient\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ingredient\IngredientInterface;

/**
 * Defines the Ingredient entity.
 *
 * @ContentEntityType(
 *   id = "ingredient",
 *   label = @Translation("Ingredient"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ingredient\IngredientListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ingredient\Form\IngredientForm",
 *       "default" = "Drupal\ingredient\Form\IngredientForm",
 *       "delete" = "Drupal\ingredient\Form\IngredientDeleteForm",
 *       "edit" = "Drupal\ingredient\Form\IngredientForm",
 *     },
 *     "access" = "Drupal\ingredient\IngredientAccessControlHandler",
 *     "views_data" = "Drupal\ingredient\IngredientViewsData",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "ingredient",
 *   data_table = "ingredient_field_data",
 *   admin_permission = "administer ingredient",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/ingredient/{ingredient}",
 *     "edit-form" = "/ingredient/{ingredient}/edit",
 *     "delete-form" = "/ingredient/{ingredient}/delete",
 *   },
 *   field_ui_base_route = "ingredient.ingredient_settings",
 * )
 */
class Ingredient extends ContentEntityBase implements IngredientInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Ingredient entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Ingredient entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Ingredient entity.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The ingredient language code.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 2,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the ingredient was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the ingredient was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $config = \Drupal::config('ingredient.settings');

    // Normalize the names of new ingredients to lowercase before saving.
    if ($this->isNew() && $config->get('ingredient_name_normalize')) {
      $name_field = $this->get('name');
      $values = $name_field->getValue();
      foreach ($values as $key => $value) {
        // Don't convert to lowercase if there is a &reg; (registered trademark
        // symbol).
        if (!strpos($value['value'], '®')) {
          $values[$key]['value'] = trim(strtolower($value['value']));
        }
      }
      $name_field->setValue($values, FALSE);
    }

    parent::preSave($storage);
  }

}
