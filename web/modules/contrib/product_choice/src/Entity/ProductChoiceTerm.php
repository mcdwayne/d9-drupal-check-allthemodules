<?php

namespace Drupal\product_choice\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Product choice term entity.
 *
 * @ingroup product_choice
 *
 * @ContentEntityType(
 *   id = "product_choice_term",
 *   label = @Translation("Product choice term"),
 *   bundle_label = @Translation("Product choice term type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\product_choice\Form\ProductChoiceTermForm",
 *       "add" = "Drupal\product_choice\Form\ProductChoiceTermForm",
 *       "edit" = "Drupal\product_choice\Form\ProductChoiceTermForm",
 *       "delete" = "Drupal\product_choice\Form\ProductChoiceTermDeleteForm",
 *     },
 *     "access" = "Drupal\product_choice\ProductChoiceTermAccessControlHandler",
 *   },
 *   base_table = "product_choice_term",
 *   data_table = "product_choice_term_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer product choice lists",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "lid",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/products/product_choice_term/{product_choice_term}",
 *     "add-form" = "/admin/commerce/config/product_choice_lists/{product_choice_list}/terms/add",
 *     "edit-form" = "/admin/commerce/config/product_choice_lists/{product_choice_list}/terms/{product_choice_term}/edit",
 *     "delete-form" = "/admin/commerce/config/product_choice_lists/{product_choice_list}/terms/{product_choice_term}/delete",
 *     "usage-list" = "/admin/commerce/config/product_choice_lists/{product_choice_list}/terms",
 *   },
 *   bundle_entity_type = "product_choice_list",
 *   field_ui_base_route = "entity.product_choice_list.terms_list"
 * )
 */
class ProductChoiceTerm extends ContentEntityBase implements ProductChoiceTermInterface {

  /**
   * {@inheritdoc}
   */
  public function getList() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortened() {
    return $this->get('shortened')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedText() {
    return $this->get('formatted')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedFormat() {
    return $this->get('formatted')->format;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['lid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('List'))
      ->setDescription(t('The Product choice list.'))
      ->setSetting('target_type', 'product_choice_list')
      ->setReadOnly(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The default label of the Product choice term entity.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 60,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shortened'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Shortened'))
      ->setDescription(t('Shortened version of the label.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['formatted'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Formatted'))
      ->setDescription(t('Formatted version of the label.'))
      ->setSettings([
        'max_length' => 80,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['icon'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Icon'))
      ->setDescription(t('The product choice term icon'))
      ->setDisplayOptions('view', [
        'type' => 'image',
        'weight' => 1,
        'label' => 'hidden',
        'settings' => ['image_style' => 'thumbnail'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
    return $fields;
  }

}
